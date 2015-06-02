<?php

namespace HF\POOL\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Types\Type;
use HF\POOL\Exception;
use HF\POOL\Options\ObjectLockOptions;
use HF\POOL\Provider\UserId\UserIdProviderInterface;

class POOLManager implements POOLManagerInterface
{

    /**
     * The DBAL Connection used for offline persistence
     *
     * @var $connection Connection
     */
    private $connection;

    /**
     * @var UserIdProviderInterface
     */
    private $userIdProvider;

    /**
     * @var ObjectLockOptions
     */
    private $options;

    /**
     * Constructor
     *
     * @param Connection              $connection
     * @param UserIdProviderInterface $userIdProvider
     * @param ObjectLockOptions       $options
     */
    public function __construct(
        Connection $connection,
        UserIdProviderInterface $userIdProvider,
        ObjectLockOptions $options
    ) {
        $this->connection     = $connection;
        $this->userIdProvider = $userIdProvider;
        $this->options        = $options;
    }

    /**
     * @inheritdoc
     */
    public function acquireLock($objectType, $objectKey, $ttl = null, $reason = null)
    {
        $this->connection->beginTransaction();

        if (!$userIdent = $this->userIdProvider->getId()) {
            return false;
        }

        try {
            $platform = $this->connection->getDatabasePlatform();
            $select   = 'SELECT * ' .
                'FROM ' . $platform->appendLockHint('recordlock', LockMode::PESSIMISTIC_WRITE) . ' ' .
                'WHERE object_type = :objectType AND object_key = :objectKey LIMIT 1 ' . $platform->getWriteLockSQL();

            $stmt = $this->connection->executeQuery(
                $select,
                [
                    'objectType' => $objectType,
                    'objectKey'  => $objectKey
                ],
                [
                    'objectType' => Type::STRING,
                    'objectKey'  => Type::STRING
                ]
            );

            $now = time();

            if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                if ($row['user_ident'] == $userIdent || ($row['lock_obtained'] + ($row['lock_ttl'] ?: $this->options->getTtl())) <= $now) {
                    $update = 'UPDATE `recordlock` SET lock_obtained = ?, lock_ttl = ?, reason = ? ' .
                        'WHERE object_type = ? AND object_key = ? AND user_ident = ?';

                    $rows = $this->connection->executeUpdate(
                        $update,
                        [
                            $now,
                            $ttl ? $ttl : $row['lock_ttl'],
                            $reason ? $reason : $row['reason'],
                            $objectType,
                            $objectKey,
                            $userIdent
                        ],
                        [Type::INTEGER, Type::INTEGER, Type::STRING, Type::STRING, Type::STRING, Type::STRING]
                    );

                    $result = true;
                } else {
                    $result = false;
                }
            } else {
                $insert = 'INSERT INTO `recordlock` (object_type, object_key, user_ident, lock_obtained, lock_ttl, reason) ' .
                    'VALUES (?, ?, ?, ?, ?, ?)';

                $rows = $this->connection->executeUpdate(
                    $insert,
                    [$objectType, $objectKey, $userIdent, $now, $ttl, $reason],
                    [Type::STRING, Type::STRING, Type::STRING, Type::INTEGER, Type::INTEGER, Type::STRING]
                );

                $result = ($rows == 1);
            }

            $this->connection->commit();

            return $result;
        } catch (DBALException $e) {
            $this->connection->rollback();
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function relinquishLock($objectType, $objectKey)
    {
        $sql = <<<EOT
DELETE FROM `recordlock`
WHERE (object_type = :objectType AND object_key = :objectKey)
AND ((
     lock_ttl IS NULL AND (lock_obtained + :ttl > :now)
    ) OR (
     lock_ttl IS NOT NULL AND (lock_obtained + lock_ttl) > :now
    ))
EOT;

        $values = [
            'now'        => time(),
            'ttl'        => $this->options->getTtl(),
            'objectType' => $objectType,
            'objectKey'  => $objectKey
        ];
        $types  = [
            'now'        => Type::INTEGER,
            'ttl'        => Type::INTEGER,
            'objectType' => Type::STRING,
            'objectKey'  => Type::STRING
        ];

        try {
            $deleted = $this->connection->executeUpdate($sql, $values, $types);
        } catch (DBALException $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        return (boolean) $deleted;
    }

    /**
     * @inheritdoc
     */
    public function relinquishExpiredLocks($ttl = null, $objectType = null, $userIdent = null)
    {
        $sql = <<<EOT
DELETE FROM `recordlock`
WHERE
    ((
     lock_ttl IS NULL AND (lock_obtained + :ttl < :now)
    ) OR (
     lock_ttl IS NOT NULL AND (lock_obtained + lock_ttl) < :now
    ))
EOT;

        $values = [
            'now' => time(),
            'ttl' => $ttl ?: $this->options->getTtl(),
        ];

        $types = [
            'now' => Type::INTEGER,
            'ttl' => Type::INTEGER,
        ];

        if ($objectType !== null) {
            $sql .= ' AND object_type = :objectType';
            $values['objectType'] = $objectType;
            $types['objectType']  = Type::STRING;
        }

        if ($userIdent !== null) {
            $sql .= ' AND user_ident = :userIdent';
            $values['userIdent'] = $userIdent;
            $types['userIdent']  = Type::STRING;
        }

        try {
            $deleted = $this->connection->executeUpdate($sql, $values, $types);
        } catch (DBALException $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        return (boolean) $deleted;
    }

    /**
     * @inheritdoc
     */
    public function getLockInfo($objectType, $objectKey)
    {
        $sql = <<<EOT
FROM `recordlock`
WHERE
    (object_type = :objectType AND object_key = :objectKey)
    AND
    ((
     lock_ttl IS NULL AND (lock_obtained + :ttl >= :now)
    ) OR (
     lock_ttl IS NOT NULL AND (lock_obtained + lock_ttl) >= :now
    ))
EOT;

        $values = [
            'now'        => time(),
            'ttl'        => $this->options->getTtl(),
            'objectType' => $objectType,
            'objectKey'  => $objectKey
        ];
        $types  = [
            'now'        => Type::INTEGER,
            'ttl'        => Type::INTEGER,
            'objectType' => Type::STRING,
            'objectKey'  => Type::STRING
        ];

        try {
            $select = 'SELECT `user_ident`, `lock_obtained`, `lock_ttl`, `reason` ' . $sql;
            $stmt   = $this->connection->executeQuery($select, $values, $types);
            $row    = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (DBALException $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        if ($row) {
            $valid = $row['lock_obtained'] - time() + ($row['lock_ttl'] ? $row['lock_ttl'] : $this->options->getTtl());

            return [
                'objectType' => $objectType,
                'objectKey'  => $objectKey,
                'userIdent'  => $row['user_ident'],
                'ttl'        => $valid,
                'reason'     => $row['reason'],
            ];
        } else {
            return false;
        }
    }
}
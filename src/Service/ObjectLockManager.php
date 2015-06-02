<?php

namespace HF\POOL\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Types\Type;
use HF\POOL\Exception;
use HF\POOL\Option\ObjectLockOptions;

class ObjectLockManager
{

    /**
     * @var $connection Connection
     */
    private $connection;

    /**
     * @var ObjectLockOptions
     */
    private $options;

    public function __construct(Connection $connection, ObjectLockOptions $options)
    {
        $this->connection = $connection;
        $this->options    = $options;
    }

    /**
     * Locks an object for a specific user identity. When a lock already exists it is renewed while preserving the
     * ttl/reason values unless overwritten with new ones
     *
     * @param string       $objectType type of object
     * @param string       $objectKey  a pk of the object
     * @param string       $userIdent  a user identity
     * @param null|integer $ttl        optional time in seconds for which the object should stay locked from the moment
     *                                 the object is locked
     * @param null         $reason     optional description of why the lock was acquired (e.g. editing, processing)
     * @return bool true on (re)locking, false when no lock was acquired (because it was already locked)
     * @throws Exception\RuntimeException
     */
    public function acquireLock($objectType, $objectKey, $userIdent, $ttl = null, $reason = null)
    {
        $this->connection->beginTransaction();

        try {
            $platform = $this->connection->getDatabasePlatform();
            $select   = 'SELECT * ' .
                'FROM ' . $platform->appendLockHint('recordlock', LockMode::PESSIMISTIC_WRITE) . ' ' .
                'WHERE object_type = ? AND object_key = ? LIMIT 1 ' . $platform->getWriteLockSQL();

            $stmt = $this->connection->executeQuery(
                $select,
                [$objectType, $objectKey],
                [Type::STRING, Type::STRING]
            );

            $now = time();

            if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                if ($row['user_ident'] == $userIdent) {
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
     * Relinguish a specific lock
     *
     * @param string $objectType the type of object
     * @param string $objectKey  a pk of the object
     * @return boolean indicating the released of a lock
     * @throws Exception\RuntimeException
     */
    public function relinquishLock($objectType, $objectKey)
    {
        try {
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

            $deleted = $this->connection->executeUpdate($sql, $values, $types);
        } catch (DBALException $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        return (boolean) $deleted;
    }

    /**
     * Deletes locks considered expired, filtered by object type and/or user ident.
     *
     * To consider locks expire we compare the time the lock was obtained with the ttl.
     *
     * The ttl used in the calculations come one of the following;
     *
     * - from the a lock_ttl field saved with the record
     *    when present it is compared to the given ttl given as argument. The longest takes precedence
     * - from the ttl argument given to this method
     * - the default from the options
     *
     * example : time is now 9:00, default ttl = 900
     *
     * record [obtained - ttl] - argument [ttl]
     *         9:00       null             null -> would be kept
     *         9:00       60               null -> would be kept
     *         9:00       null             0    -> would be deleted
     *         8:00       null             null -> would be deleted
     *         8:45       null             null -> would be deleted
     *         8:50       null             null -> would be kept
     *         8:50       60               null -> would be deleted
     *         8:50       null             null -> would be kept
     *         8:50       null             60   -> would be deleted
     *         8:50       null             660  -> would be kept
     *
     * @param null $ttl        optional age in seconds
     * @param null $objectType optional type of objects
     * @param null $userIdent  optional user ident
     * @return bool indicates locks were relinquished
     */
    public function relinquishLocks($ttl = null, $objectType = null, $userIdent = null)
    {
        $sql = <<<EOT
FROM `recordlock`
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
        $types  = [
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
            $deleted = $this->connection->executeUpdate("DELETE " . $sql, $values, $types);
        } catch (DBALException $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        return (boolean) $deleted;
    }

    /**
     * Gets the user ident indicating the owner of the lock.
     *
     * @param string $objectType the type of object
     * @param string $objectKey  a pk of the object
     * @return string the userIdent or false when not found
     * @throws Exception\RuntimeException
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
            return ['user_ident' => $row['user_ident'], 'ttl' => $valid, 'reason' => $row['reason']];
        } else {
            return false;
        }
    }
}
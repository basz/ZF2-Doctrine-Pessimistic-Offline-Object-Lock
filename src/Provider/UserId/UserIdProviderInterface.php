<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace HF\POOL\Provider\UserId;

interface UserIdProviderInterface
{
    /**
     * Return the current authenticated user identifier.
     *
     * @return string|int or false when no identifier is available
     */
    public function getId();
}

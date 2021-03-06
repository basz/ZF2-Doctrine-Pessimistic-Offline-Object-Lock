<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace HF\POOL\Provider\UserId;

use Zend\Stdlib\RequestInterface;

class Request implements UserIdProviderInterface
{
    /**
     * Use the composed request to fetch the identity from the query string
     * argument "user_id".
     *
     * @inheritdoc
     */
    public function getId()
    {
        return false;//$request->getQuery('user_id', false);
    }
}

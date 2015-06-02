<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace HF\POOL\Provider\UserId;

use Zend\Authentication\AuthenticationService as ZendAuthenticationService;

class ZfcUserAuthenticationService implements UserIdProviderInterface
{
    /**
     * @var ZendAuthenticationService
     */
    private $authenticationService;

    /**
     *  Set authentication service
     *
     * @param ZendAuthenticationService $service
     */
    public function __construct(ZendAuthenticationService $service)
    {
        $this->authenticationService = $service;
    }

    /**
     * Use Zend\Authentication\AuthenticationService to fetch the identity.
     *
     * @inheritdoc
     */
    public function getId()
    {
        if (!$this->authenticationService->hasIdentity()) {
            return false;
        }

        /** @var \ZfcUser\Entity\UserInterface $user */
        $user = $this->authenticationService->getIdentity();

        return $user->getId();
    }
}

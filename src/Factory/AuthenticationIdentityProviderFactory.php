<?php

namespace HF\POOL\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use HF\POOL\Identity\AuthenticationIdentityProvider;

/**
 * Factory to create the authentication identity provider
 *
 * @author  Michaël Gallego <mic.gallego@gmail.com>
 * @licence MIT
 */
class AuthenticationIdentityProviderFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     * @return AuthenticationIdentityProvider
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /* @var \Zend\Authentication\AuthenticationService $authenticationProvider */
        $authenticationProvider = $serviceLocator->get('hf_pool.authenticationService');

        return new AuthenticationIdentityProvider($authenticationProvider);
    }
}

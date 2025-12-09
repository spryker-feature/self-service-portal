<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Glue\SelfServicePortal;

use Spryker\Glue\Kernel\Backend\AbstractBundleDependencyProvider;
use Spryker\Glue\Kernel\Backend\Container;

/**
 * @method \SprykerFeature\Glue\SelfServicePortal\SelfServicePortalConfig getConfig()
 */
class SelfServicePortalDependencyProvider extends AbstractBundleDependencyProvider
{
    public const string CLIENT_SELF_SERVICE_PORTAL = 'CLIENT_SELF_SERVICE_PORTAL';

    public const string CLIENT_GLOSSARY_STORAGE = 'CLIENT_GLOSSARY_STORAGE';

    public const string CLIENT_STORE = 'CLIENT_STORE';

    public const string FACADE_SELF_SERVICE_PORTAL = 'FACADE_SELF_SERVICE_PORTAL';

    public const string CLIENT_CUSTOMER = 'CLIENT_CUSTOMER';

    public function provideBackendDependencies(Container $container): Container
    {
        $container = parent::provideBackendDependencies($container);
        $container = $this->addSelfServicePortalClient($container);
        $container = $this->addGlossaryStorageClient($container);
        $container = $this->addStoreClient($container);
        $container = $this->addSelfServicePortalFacade($container);
        $container = $this->addCustomerClient($container);

        return $container;
    }

    protected function addStoreClient(Container $container): Container
    {
        $container->set(static::CLIENT_STORE, function (Container $container) {
            return $container->getLocator()->store()->client();
        });

        return $container;
    }

    protected function addSelfServicePortalClient(Container $container): Container
    {
        $container->set(static::CLIENT_SELF_SERVICE_PORTAL, function (Container $container) {
            return $container->getLocator()->selfServicePortal()->client();
        });

        return $container;
    }

    protected function addGlossaryStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_GLOSSARY_STORAGE, function (Container $container) {
            return $container->getLocator()->glossaryStorage()->client();
        });

        return $container;
    }

    protected function addSelfServicePortalFacade(Container $container): Container
    {
        $container->set(static::FACADE_SELF_SERVICE_PORTAL, function (Container $container) {
            return $container->getLocator()->selfServicePortal()->facade();
        });

        return $container;
    }

    protected function addCustomerClient(Container $container): Container
    {
        $container->set(static::CLIENT_CUSTOMER, function (Container $container) {
            return $container->getLocator()->customer()->client();
        });

        return $container;
    }
}

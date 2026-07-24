<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\SelfServicePortal\Plugin\ShopApplication;

use Spryker\Yves\Kernel\AbstractPlugin;
use SprykerFeature\Yves\SelfServicePortal\Widget\SspAddressFormItemsByShipmentTypeWidget;
use SprykerShop\Yves\ShopApplicationExtension\Dependency\Plugin\WidgetCacheKeyGeneratorStrategyPluginInterface;

class AddressFormItemsByShipmentTypeWidgetCacheKeyGeneratorStrategyPlugin extends AbstractPlugin implements WidgetCacheKeyGeneratorStrategyPluginInterface
{
    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $arguments
     */
    public function generateCacheKey(array $arguments = []): ?string
    {
        return null;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getWidgetClassName(): string
    {
        return SspAddressFormItemsByShipmentTypeWidget::class;
    }
}

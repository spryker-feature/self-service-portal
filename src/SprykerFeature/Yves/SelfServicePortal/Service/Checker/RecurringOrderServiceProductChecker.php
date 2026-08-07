<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\SelfServicePortal\Service\Checker;

use Generated\Shared\Transfer\ProductViewTransfer;
use SprykerFeature\Yves\SelfServicePortal\SelfServicePortalConfig;

class RecurringOrderServiceProductChecker implements RecurringOrderServiceProductCheckerInterface
{
    public function __construct(protected SelfServicePortalConfig $selfServicePortalConfig)
    {
    }

    public function isRestricted(ProductViewTransfer $productViewTransfer): bool
    {
        $shipmentTypeKeys = $this->selfServicePortalConfig->getRecurringOrderServiceShipmentTypeKeys();

        if ($shipmentTypeKeys === []) {
            return false;
        }

        if (!$this->isServiceProduct($productViewTransfer)) {
            return false;
        }

        return !$this->hasShipmentTypeKey($productViewTransfer, $shipmentTypeKeys);
    }

    protected function isServiceProduct(ProductViewTransfer $productViewTransfer): bool
    {
        $serviceProductClassName = $this->selfServicePortalConfig->getServiceProductClassName();

        return in_array($serviceProductClassName, $productViewTransfer->getProductClassNames(), true);
    }

    /**
     * @param \Generated\Shared\Transfer\ProductViewTransfer $productViewTransfer
     * @param array<string> $shipmentTypeKeys
     *
     * @return bool
     */
    protected function hasShipmentTypeKey(ProductViewTransfer $productViewTransfer, array $shipmentTypeKeys): bool
    {
        foreach ($productViewTransfer->getShipmentTypes() as $shipmentTypeStorageTransfer) {
            if (in_array($shipmentTypeStorageTransfer->getKey(), $shipmentTypeKeys, true)) {
                return true;
            }
        }

        return false;
    }
}

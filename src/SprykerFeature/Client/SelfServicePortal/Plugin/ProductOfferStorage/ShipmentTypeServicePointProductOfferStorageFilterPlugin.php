<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\SelfServicePortal\Plugin\ProductOfferStorage;

use Generated\Shared\Transfer\ProductOfferStorageCollectionTransfer;
use Generated\Shared\Transfer\ProductOfferStorageCriteriaTransfer;
use Spryker\Client\Kernel\AbstractPlugin;
use Spryker\Client\ProductOfferStorageExtension\Dependency\Plugin\ProductOfferStorageFilterPluginInterface;

/**
 * @method \SprykerFeature\Client\SelfServicePortal\SelfServicePortalFactory getFactory()
 */
class ShipmentTypeServicePointProductOfferStorageFilterPlugin extends AbstractPlugin implements ProductOfferStorageFilterPluginInterface
{
    /**
     * {@inheritDoc}
     * - Filters product offers by shipment type UUIDs from criteria.
     * - Filters product offers by service point UUIDs from criteria.
     * - Returns offers that match all provided criteria dimensions.
     * - If criteria dimension is empty or null, does not filter by that dimension.
     * - Returns original collection if both criteria dimensions are empty or null.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ProductOfferStorageCollectionTransfer $productOfferStorageCollectionTransfer
     * @param \Generated\Shared\Transfer\ProductOfferStorageCriteriaTransfer $productOfferStorageCriteriaTransfer
     *
     * @return \Generated\Shared\Transfer\ProductOfferStorageCollectionTransfer
     */
    public function filter(
        ProductOfferStorageCollectionTransfer $productOfferStorageCollectionTransfer,
        ProductOfferStorageCriteriaTransfer $productOfferStorageCriteriaTransfer
    ): ProductOfferStorageCollectionTransfer {
        return $this->getFactory()
            ->createProductOfferStorageFilter()
            ->filter($productOfferStorageCollectionTransfer, $productOfferStorageCriteriaTransfer);
    }
}

<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\SelfServicePortal\ProductOffer\Filter;

use ArrayObject;
use Generated\Shared\Transfer\ProductOfferStorageCollectionTransfer;
use Generated\Shared\Transfer\ProductOfferStorageCriteriaTransfer;
use Generated\Shared\Transfer\ProductOfferStorageTransfer;

class ProductOfferStorageFilter implements ProductOfferStorageFilterInterface
{
    public function filter(
        ProductOfferStorageCollectionTransfer $productOfferStorageCollectionTransfer,
        ProductOfferStorageCriteriaTransfer $productOfferStorageCriteriaTransfer
    ): ProductOfferStorageCollectionTransfer {
        $shipmentTypeUuids = $productOfferStorageCriteriaTransfer->getShipmentTypeUuids();
        $servicePointUuids = $productOfferStorageCriteriaTransfer->getServicePointUuids();

        if ($this->shouldSkipFiltering($shipmentTypeUuids, $servicePointUuids)) {
            return $productOfferStorageCollectionTransfer;
        }

        $shipmentTypeUuidsMap = $this->createUuidMap($shipmentTypeUuids);
        $servicePointUuidsMap = $this->createUuidMap($servicePointUuids);

        $filteredProductOffers = [];
        foreach ($productOfferStorageCollectionTransfer->getProductOffers() as $productOfferStorageTransfer) {
            if ($this->isProductOfferMatching($productOfferStorageTransfer, $shipmentTypeUuidsMap, $servicePointUuidsMap)) {
                $filteredProductOffers[] = $productOfferStorageTransfer;
            }
        }

        return (new ProductOfferStorageCollectionTransfer())
            ->setProductOffers(new ArrayObject($filteredProductOffers));
    }

    /**
     * @param array<string> $shipmentTypeUuids
     * @param array<string> $servicePointUuids
     *
     * @return bool
     */
    protected function shouldSkipFiltering(array $shipmentTypeUuids, array $servicePointUuids): bool
    {
        return $shipmentTypeUuids === [] && $servicePointUuids === [];
    }

    /**
     * @param \Generated\Shared\Transfer\ProductOfferStorageTransfer $productOfferStorageTransfer
     * @param array<string, true> $shipmentTypeUuidsMap
     * @param array<string, true> $servicePointUuidsMap
     *
     * @return bool
     */
    protected function isProductOfferMatching(
        ProductOfferStorageTransfer $productOfferStorageTransfer,
        array $shipmentTypeUuidsMap,
        array $servicePointUuidsMap
    ): bool {
        $matchesShipmentTypes = $this->matchesShipmentTypeCriteria($productOfferStorageTransfer, $shipmentTypeUuidsMap);
        $matchesServicePoints = $this->matchesServicePointCriteria($productOfferStorageTransfer, $servicePointUuidsMap);

        return $matchesShipmentTypes && $matchesServicePoints;
    }

    /**
     * @param \Generated\Shared\Transfer\ProductOfferStorageTransfer $productOfferStorageTransfer
     * @param array<string, true> $shipmentTypeUuidsMap
     *
     * @return bool
     */
    protected function matchesShipmentTypeCriteria(
        ProductOfferStorageTransfer $productOfferStorageTransfer,
        array $shipmentTypeUuidsMap
    ): bool {
        if ($shipmentTypeUuidsMap === []) {
            return true;
        }

        foreach ($productOfferStorageTransfer->getShipmentTypes() as $shipmentTypeStorageTransfer) {
            if (isset($shipmentTypeUuidsMap[$shipmentTypeStorageTransfer->getUuid()])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \Generated\Shared\Transfer\ProductOfferStorageTransfer $productOfferStorageTransfer
     * @param array<string, true> $servicePointUuidsMap
     *
     * @return bool
     */
    protected function matchesServicePointCriteria(
        ProductOfferStorageTransfer $productOfferStorageTransfer,
        array $servicePointUuidsMap
    ): bool {
        if ($servicePointUuidsMap === []) {
            return true;
        }

        foreach ($productOfferStorageTransfer->getServices() as $serviceStorageTransfer) {
            $servicePointStorageTransfer = $serviceStorageTransfer->getServicePoint();

            if ($servicePointStorageTransfer && isset($servicePointUuidsMap[$servicePointStorageTransfer->getUuid()])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string> $uuids
     *
     * @return array<string, true>
     */
    protected function createUuidMap(array $uuids): array
    {
        return array_fill_keys($uuids, true);
    }
}

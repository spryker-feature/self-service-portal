<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\SelfServicePortal\Service\Reader;

use Generated\Shared\Transfer\ProductOfferStorageCriteriaTransfer;
use Generated\Shared\Transfer\ProductStorageCriteriaTransfer;
use Generated\Shared\Transfer\ProductViewTransfer;
use Spryker\Client\ProductOfferStorage\ProductOfferStorageClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;

class ProductReader implements ProductReaderInterface
{
    public function __construct(
        protected ProductStorageClientInterface $productStorageClient,
        protected ProductOfferStorageClientInterface $productOfferStorageClient
    ) {
    }

    public function findProductConcreteViewTransfer(
        int $idProductConcrete,
        string $locale,
        ProductOfferStorageCriteriaTransfer $productOfferStorageCriteriaTransfer
    ): ?ProductViewTransfer {
        $productViewTransfer = $this->productStorageClient->findProductConcreteViewTransfer(
            $idProductConcrete,
            $locale,
        );

        if (!$productViewTransfer) {
            return null;
        }

        $productStorageCriteriaTransfer = (new ProductStorageCriteriaTransfer())->fromArray(
            $productOfferStorageCriteriaTransfer->toArray(),
            true,
        );

        $shipmentTypeUuids = $productViewTransfer->getShipmentTypeUuids();
        $productViewTransfer->setShipmentTypeUuids($productStorageCriteriaTransfer->getShipmentTypeUuids());

        $productViewTransfer = $this->productOfferStorageClient
            ->expandProductViewTransfer($productViewTransfer, $productStorageCriteriaTransfer);

        $productViewTransfer->setShipmentTypeUuids($shipmentTypeUuids);

        return $productViewTransfer;
    }
}

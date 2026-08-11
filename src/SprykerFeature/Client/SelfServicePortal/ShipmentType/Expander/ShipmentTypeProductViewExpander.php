<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\SelfServicePortal\ShipmentType\Expander;

use Generated\Shared\Transfer\ProductViewTransfer;
use SprykerFeature\Client\SelfServicePortal\Service\Reader\ShipmentTypeStorageReaderInterface;

class ShipmentTypeProductViewExpander implements ShipmentTypeProductViewExpanderInterface
{
    /**
     * @var string
     */
    protected const PRODUCT_DATA_SHIPMENT_TYPE_UUIDS = 'shipment_type_uuids';

    public function __construct(protected ShipmentTypeStorageReaderInterface $shipmentTypeStorageReader)
    {
    }

    /**
     * @param array<string, mixed> $productData
     */
    public function expandProductViewWithShipmentTypes(
        ProductViewTransfer $productViewTransfer,
        array $productData,
        string $localeName
    ): ProductViewTransfer {
        if (!$productViewTransfer->getIdProductConcrete() || $productViewTransfer->getShipmentTypeUuids() === []) {
            return $productViewTransfer;
        }

        $shipmentTypeStorageCollectionTransfer = $this->shipmentTypeStorageReader->getShipmentTypeStorageCollection(
            array_values($productViewTransfer->getShipmentTypeUuids()),
        );

        return $productViewTransfer->setShipmentTypes(
            $shipmentTypeStorageCollectionTransfer->getShipmentTypeStorages(),
        );
    }
}

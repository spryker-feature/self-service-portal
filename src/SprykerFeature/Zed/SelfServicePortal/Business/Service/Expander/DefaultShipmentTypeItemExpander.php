<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\SelfServicePortal\Business\Service\Expander;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\ProductOfferShipmentTypeCollectionTransfer;
use Generated\Shared\Transfer\ProductOfferShipmentTypeConditionsTransfer;
use Generated\Shared\Transfer\ProductOfferShipmentTypeCriteriaTransfer;
use Generated\Shared\Transfer\ShipmentTypeTransfer;
use Spryker\Zed\ProductOfferShipmentType\Business\ProductOfferShipmentTypeFacadeInterface;
use SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalRepositoryInterface;

class DefaultShipmentTypeItemExpander implements DefaultShipmentTypeItemExpanderInterface
{
    public function __construct(
        protected SelfServicePortalRepositoryInterface $selfServicePortalRepository,
        protected ProductOfferShipmentTypeFacadeInterface $productOfferShipmentTypeFacade
    ) {
    }

    /**
     * @param list<\Generated\Shared\Transfer\ItemTransfer> $itemsWithoutShipmentType
     */
    public function expandItemsWithDefaultShipmentType(array $itemsWithoutShipmentType, ShipmentTypeTransfer $defaultShipmentTypeTransfer): void
    {
        $this->expandProductsWithDefaultShipmentType($itemsWithoutShipmentType, $defaultShipmentTypeTransfer);
        $this->expandProductOffersWithDefaultShipmentType($itemsWithoutShipmentType, $defaultShipmentTypeTransfer);
    }

    /**
     * @param list<\Generated\Shared\Transfer\ItemTransfer> $itemsWithoutShipmentType
     */
    protected function expandProductsWithDefaultShipmentType(array $itemsWithoutShipmentType, ShipmentTypeTransfer $defaultShipmentTypeTransfer): void
    {
        $productConcreteIds = [];
        foreach ($itemsWithoutShipmentType as $itemWithoutShipmentType) {
            if ($itemWithoutShipmentType->getProductOfferReference()) {
                continue;
            }

            if (!$itemWithoutShipmentType->getId()) {
                continue;
            }

            $productConcreteIds[] = $itemWithoutShipmentType->getId();
        }

        $productConcreteIdsWithDefaultShipmentType = $this->selfServicePortalRepository->getProductIdsWithShipmentType($productConcreteIds, $defaultShipmentTypeTransfer->getNameOrFail());

        foreach ($itemsWithoutShipmentType as $itemWithoutShipmentType) {
            if (!in_array($itemWithoutShipmentType->getId(), $productConcreteIdsWithDefaultShipmentType)) {
                continue;
            }

            $itemWithoutShipmentType->setShipmentType($defaultShipmentTypeTransfer);
            $this->setShipmentTypeUuid($itemWithoutShipmentType, $defaultShipmentTypeTransfer);
        }
    }

    /**
     * @param list<\Generated\Shared\Transfer\ItemTransfer> $itemsWithoutShipmentType
     */
    protected function expandProductOffersWithDefaultShipmentType(array $itemsWithoutShipmentType, ShipmentTypeTransfer $defaultShipmentTypeTransfer): void
    {
        $productOfferReferences = $this->extractProductOfferReferences($itemsWithoutShipmentType);
        if (!$productOfferReferences) {
            return;
        }

        $productOfferShipmentTypeCollection = $this->getProductOfferShipmentTypeCollection(
            $productOfferReferences,
            $defaultShipmentTypeTransfer,
        );

        $productOfferReferencesWithDefaultShipmentType = $this->extractProductOfferReferencesWithDefaultShipmentType(
            $productOfferShipmentTypeCollection,
        );

        $this->assignDefaultShipmentTypeToProductOfferItems(
            $itemsWithoutShipmentType,
            $productOfferReferencesWithDefaultShipmentType,
            $defaultShipmentTypeTransfer,
        );
    }

    /**
     * @param list<\Generated\Shared\Transfer\ItemTransfer> $itemsWithoutShipmentType
     *
     * @return list<string>
     */
    protected function extractProductOfferReferences(array $itemsWithoutShipmentType): array
    {
        $productOfferReferences = [];

        foreach ($itemsWithoutShipmentType as $itemWithoutShipmentType) {
            if (!$itemWithoutShipmentType->getProductOfferReference()) {
                continue;
            }

            $productOfferReferences[] = $itemWithoutShipmentType->getProductOfferReferenceOrFail();
        }

        return $productOfferReferences;
    }

    /**
     * @param list<string> $productOfferReferences
     */
    protected function getProductOfferShipmentTypeCollection(
        array $productOfferReferences,
        ShipmentTypeTransfer $defaultShipmentTypeTransfer
    ): ProductOfferShipmentTypeCollectionTransfer {
        return $this->productOfferShipmentTypeFacade->getProductOfferShipmentTypeCollection(
            (new ProductOfferShipmentTypeCriteriaTransfer())
                ->setProductOfferShipmentTypeConditions(
                    (new ProductOfferShipmentTypeConditionsTransfer())
                        ->setProductOfferReferences($productOfferReferences)
                        ->addShipmentTypeName($defaultShipmentTypeTransfer->getNameOrFail()),
                ),
        );
    }

    /**
     * @return list<string>
     */
    protected function extractProductOfferReferencesWithDefaultShipmentType(
        ProductOfferShipmentTypeCollectionTransfer $productOfferShipmentTypeCollection
    ): array {
        $productOfferReferencesWithDefaultShipmentType = [];

        foreach ($productOfferShipmentTypeCollection->getProductOfferShipmentTypes() as $productOfferShipmentTypeTransfer) {
            if ($productOfferShipmentTypeTransfer->getProductOffer() === null) {
                continue;
            }

            $productOfferReferencesWithDefaultShipmentType[] = $productOfferShipmentTypeTransfer->getProductOfferOrFail()
                ->getProductOfferReferenceOrFail();
        }

        return $productOfferReferencesWithDefaultShipmentType;
    }

    /**
     * @param list<\Generated\Shared\Transfer\ItemTransfer> $itemsWithoutShipmentType
     * @param list<string> $productOfferReferencesWithDefaultShipmentType
     */
    protected function assignDefaultShipmentTypeToProductOfferItems(
        array $itemsWithoutShipmentType,
        array $productOfferReferencesWithDefaultShipmentType,
        ShipmentTypeTransfer $defaultShipmentTypeTransfer
    ): void {
        foreach ($itemsWithoutShipmentType as $itemWithoutShipmentType) {
            if (!in_array($itemWithoutShipmentType->getProductOfferReference(), $productOfferReferencesWithDefaultShipmentType)) {
                continue;
            }

            $itemWithoutShipmentType->setShipmentType($defaultShipmentTypeTransfer);
            $this->setShipmentTypeUuid($itemWithoutShipmentType, $defaultShipmentTypeTransfer);
        }
    }

    protected function setShipmentTypeUuid(
        ItemTransfer $itemTransfer,
        ShipmentTypeTransfer $shipmentTypeTransfer
    ): ItemTransfer {
        if ($itemTransfer->getShipment() === null) {
            return $itemTransfer;
        }

        $itemTransfer->getShipmentOrFail()->setShipmentTypeUuid($shipmentTypeTransfer->getUuidOrFail());

        return $itemTransfer;
    }
}

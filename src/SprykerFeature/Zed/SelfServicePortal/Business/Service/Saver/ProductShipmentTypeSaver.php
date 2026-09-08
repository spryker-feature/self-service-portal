<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Business\Service\Saver;

use Generated\Shared\Transfer\EventEntityTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\ShipmentTypeConditionsTransfer;
use Generated\Shared\Transfer\ShipmentTypeCriteriaTransfer;
use Spryker\Zed\Event\Business\EventFacadeInterface;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use Spryker\Zed\Product\Dependency\ProductEvents;
use Spryker\Zed\ShipmentType\Business\ShipmentTypeFacadeInterface;
use SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalEntityManagerInterface;
use SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalRepositoryInterface;

class ProductShipmentTypeSaver implements ProductShipmentTypeSaverInterface
{
    use TransactionTrait;

    public function __construct(
        protected SelfServicePortalEntityManagerInterface $selfServicePortalEntityManager,
        protected SelfServicePortalRepositoryInterface $selfServicePortalRepository,
        protected EventFacadeInterface $eventFacade,
        protected ShipmentTypeFacadeInterface $shipmentTypeFacade,
    ) {
    }

    public function saveProductShipmentTypes(ProductConcreteTransfer $productConcreteTransfer): ProductConcreteTransfer
    {
        if (!$productConcreteTransfer->getIdProductConcrete()) {
            return $productConcreteTransfer;
        }

        return $this->getTransactionHandler()->handleTransaction(function () use ($productConcreteTransfer) {
            return $this->executeSaveProductShipmentTypesTransaction($productConcreteTransfer);
        });
    }

    protected function executeSaveProductShipmentTypesTransaction(
        ProductConcreteTransfer $productConcreteTransfer
    ): ProductConcreteTransfer {
        $idProductConcrete = $productConcreteTransfer->getIdProductConcreteOrFail();
        $existingShipmentTypeIds = $this->selfServicePortalRepository->getShipmentTypeIdsGroupedByIdProductConcrete([$idProductConcrete])[$idProductConcrete] ?? [];
        $this->resolveShipmentTypeIds($productConcreteTransfer->getShipmentTypes()->getArrayCopy());
        $newShipmentTypeIds = $this->extractShipmentTypeIds($productConcreteTransfer->getShipmentTypes()->getArrayCopy());

        $shipmentTypeIdsToCreate = array_diff($newShipmentTypeIds, $existingShipmentTypeIds);
        $shipmentTypeIdsToDelete = array_diff($existingShipmentTypeIds, $newShipmentTypeIds);

        if ($shipmentTypeIdsToDelete !== []) {
            $this->selfServicePortalEntityManager
                ->deleteProductShipmentTypes($productConcreteTransfer, $shipmentTypeIdsToDelete);
        }

        foreach ($shipmentTypeIdsToCreate as $idShipmentType) {
            $this->selfServicePortalEntityManager->createProductShipmentType($productConcreteTransfer, $idShipmentType);
        }

        if ($shipmentTypeIdsToCreate !== [] || $shipmentTypeIdsToDelete !== []) {
            $this->triggerProductUpdateEvent($idProductConcrete);
        }

        return $productConcreteTransfer;
    }

    /**
     * @param list<\Generated\Shared\Transfer\ShipmentTypeTransfer> $shipmentTypeTransfers
     */
    protected function resolveShipmentTypeIds(array $shipmentTypeTransfers): void
    {
        $uuidsToResolve = [];

        foreach ($shipmentTypeTransfers as $shipmentTypeTransfer) {
            if ($shipmentTypeTransfer->getIdShipmentType() === null && $shipmentTypeTransfer->getUuid() !== null) {
                $uuidsToResolve[$shipmentTypeTransfer->getUuid()] = true;
            }
        }

        if ($uuidsToResolve === []) {
            return;
        }

        $criteriaTransfer = (new ShipmentTypeCriteriaTransfer())
            ->setShipmentTypeConditions(
                (new ShipmentTypeConditionsTransfer())->setUuids(array_keys($uuidsToResolve)),
            );

        $collectionTransfer = $this->shipmentTypeFacade->getShipmentTypeCollection($criteriaTransfer);

        $shipmentTypeIdsIndexedByUuid = [];

        foreach ($collectionTransfer->getShipmentTypes() as $resolvedShipmentTypeTransfer) {
            $shipmentTypeIdsIndexedByUuid[$resolvedShipmentTypeTransfer->getUuidOrFail()] = $resolvedShipmentTypeTransfer->getIdShipmentTypeOrFail();
        }

        foreach ($shipmentTypeTransfers as $shipmentTypeTransfer) {
            if ($shipmentTypeTransfer->getIdShipmentType() !== null) {
                continue;
            }

            $uuid = $shipmentTypeTransfer->getUuid();

            if ($uuid !== null && isset($shipmentTypeIdsIndexedByUuid[$uuid])) {
                $shipmentTypeTransfer->setIdShipmentType($shipmentTypeIdsIndexedByUuid[$uuid]);
            }
        }
    }

    /**
     * @param list<\Generated\Shared\Transfer\ShipmentTypeTransfer> $shipmentTypeTransfers
     *
     * @return list<int>
     */
    protected function extractShipmentTypeIds(array $shipmentTypeTransfers): array
    {
        $shipmentTypeIds = [];

        foreach ($shipmentTypeTransfers as $shipmentTypeTransfer) {
            if ($shipmentTypeTransfer->getIdShipmentType() !== null) {
                $shipmentTypeIds[] = $shipmentTypeTransfer->getIdShipmentType();
            }
        }

        return $shipmentTypeIds;
    }

    protected function triggerProductUpdateEvent(int $idProductConcrete): void
    {
        $eventEntityTransfer = (new EventEntityTransfer())->setId($idProductConcrete);

        $this->eventFacade->triggerBulk(ProductEvents::PRODUCT_CONCRETE_PUBLISH, [$eventEntityTransfer]);
    }
}

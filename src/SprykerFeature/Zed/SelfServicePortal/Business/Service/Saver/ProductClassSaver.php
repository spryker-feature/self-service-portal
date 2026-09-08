<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Business\Service\Saver;

use ArrayObject;
use Generated\Shared\Transfer\EventEntityTransfer;
use Generated\Shared\Transfer\ProductClassConditionsTransfer;
use Generated\Shared\Transfer\ProductClassCriteriaTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Spryker\Zed\Event\Business\EventFacadeInterface;
use Spryker\Zed\Product\Dependency\ProductEvents;
use SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalEntityManagerInterface;
use SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalRepositoryInterface;

class ProductClassSaver implements ProductClassSaverInterface
{
    public function __construct(
        protected SelfServicePortalEntityManagerInterface $selfServicePortalEntityManager,
        protected SelfServicePortalRepositoryInterface $selfServicePortalRepository,
        protected EventFacadeInterface $eventFacade
    ) {
    }

    public function saveProductClassesForProductConcrete(ProductConcreteTransfer $productConcreteTransfer): ProductConcreteTransfer
    {
        if (!count($productConcreteTransfer->getProductClasses()) || !$productConcreteTransfer->getIdProductConcrete()) {
            return $productConcreteTransfer;
        }

        $idProductConcrete = $productConcreteTransfer->getIdProductConcreteOrFail();
        $this->resolveProductClassIds($productConcreteTransfer->getProductClasses());
        $productClassIds = $this->extractProductClassIds($productConcreteTransfer->getProductClasses());

        $productClassCriteriaTransfer = $this->createProductClassCriteriaTransfer($idProductConcrete, $productClassIds);
        $this->selfServicePortalEntityManager->saveProductClassesForProduct($productClassCriteriaTransfer);

        $idProductAbstract = $productConcreteTransfer->getFkProductAbstractOrFail();

        $this->eventFacade->trigger(
            ProductEvents::ENTITY_SPY_PRODUCT_ABSTRACT_UPDATE,
            (new EventEntityTransfer())->setId($idProductAbstract),
        );

        return $productConcreteTransfer;
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ProductClassTransfer> $productClassTransfers
     *
     * @return array<int>
     */
    protected function extractProductClassIds(ArrayObject $productClassTransfers): array
    {
        $productClassIds = [];

        foreach ($productClassTransfers as $productClassTransfer) {
            if ($productClassTransfer->getIdProductClass()) {
                $productClassIds[] = $productClassTransfer->getIdProductClassOrFail();
            }
        }

        return $productClassIds;
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ProductClassTransfer> $productClassTransfers
     */
    protected function resolveProductClassIds(ArrayObject $productClassTransfers): void
    {
        $keysToResolve = [];

        foreach ($productClassTransfers as $productClassTransfer) {
            if ($productClassTransfer->getIdProductClass() === null && $productClassTransfer->getKey() !== null) {
                $keysToResolve[] = $productClassTransfer->getKeyOrFail();
            }
        }

        $keysToResolve = array_values(array_unique($keysToResolve));

        if ($keysToResolve === []) {
            return;
        }

        $criteriaTransfer = (new ProductClassCriteriaTransfer())
            ->setProductClassConditions(
                (new ProductClassConditionsTransfer())->setKeys($keysToResolve),
            );

        $collectionTransfer = $this->selfServicePortalRepository->getProductClassCollection($criteriaTransfer);

        $productClassIdsIndexedByKey = [];

        foreach ($collectionTransfer->getProductClasses() as $resolvedProductClassTransfer) {
            $productClassIdsIndexedByKey[$resolvedProductClassTransfer->getKeyOrFail()] = $resolvedProductClassTransfer->getIdProductClassOrFail();
        }

        foreach ($productClassTransfers as $productClassTransfer) {
            if ($productClassTransfer->getIdProductClass() !== null) {
                continue;
            }

            $key = $productClassTransfer->getKey();

            if ($key !== null && isset($productClassIdsIndexedByKey[$key])) {
                $productClassTransfer->setIdProductClass($productClassIdsIndexedByKey[$key]);
            }
        }
    }

    /**
     * @param array<int> $productClassIds
     */
    protected function createProductClassCriteriaTransfer(int $idProductConcrete, array $productClassIds): ProductClassCriteriaTransfer
    {
        $productClassConditionsTransfer = (new ProductClassConditionsTransfer())
            ->setProductConcreteIds([$idProductConcrete])
            ->setProductClassIds($productClassIds);

        return (new ProductClassCriteriaTransfer())
            ->setProductClassConditions($productClassConditionsTransfer);
    }
}

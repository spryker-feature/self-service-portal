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
     * @param int $idProductConcrete
     * @param array<int> $productClassIds
     *
     * @return \Generated\Shared\Transfer\ProductClassCriteriaTransfer
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

<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Business\Service\Saver;

use ArrayObject;
use Generated\Shared\Transfer\ProductClassConditionsTransfer;
use Generated\Shared\Transfer\ProductClassCriteriaTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Spryker\Zed\ProductPageSearch\Business\ProductPageSearchFacadeInterface;
use Spryker\Zed\ProductStorage\Business\ProductStorageFacadeInterface;
use SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalEntityManagerInterface;
use SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalRepositoryInterface;

class ProductClassSaver implements ProductClassSaverInterface
{
    /**
     * @param \SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalEntityManagerInterface $selfServicePortalEntityManager
     * @param \SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalRepositoryInterface $selfServicePortalRepository
     * @param \Spryker\Zed\ProductPageSearch\Business\ProductPageSearchFacadeInterface $productPageSearchFacade
     * @param \Spryker\Zed\ProductStorage\Business\ProductStorageFacadeInterface $productStorageFacade
     */
    public function __construct(
        protected SelfServicePortalEntityManagerInterface $selfServicePortalEntityManager,
        protected SelfServicePortalRepositoryInterface $selfServicePortalRepository,
        protected ProductPageSearchFacadeInterface $productPageSearchFacade,
        protected ProductStorageFacadeInterface $productStorageFacade
    ) {
    }

    /**
     * @param \Generated\Shared\Transfer\ProductConcreteTransfer $productConcreteTransfer
     *
     * @return \Generated\Shared\Transfer\ProductConcreteTransfer
     */
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

        $this->productPageSearchFacade->refresh([$idProductAbstract]);
        $this->productStorageFacade->publishConcreteProducts([$idProductConcrete]);

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

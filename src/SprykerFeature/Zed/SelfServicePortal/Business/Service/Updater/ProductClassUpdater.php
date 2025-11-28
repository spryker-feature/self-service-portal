<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Business\Service\Updater;

use Generated\Shared\Transfer\ProductConcreteTransfer;
use SprykerFeature\Zed\SelfServicePortal\Business\Service\Saver\ProductClassSaverInterface;
use SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalEntityManagerInterface;

class ProductClassUpdater implements ProductClassUpdaterInterface
{
    public function __construct(
        protected SelfServicePortalEntityManagerInterface $selfServicePortalEntityManager,
        protected ProductClassSaverInterface $productClassSaver
    ) {
    }

    public function updateProductClassesForProductConcrete(ProductConcreteTransfer $productConcreteTransfer): ProductConcreteTransfer
    {
        $idProductConcrete = $productConcreteTransfer->getIdProductConcreteOrFail();

        $this->selfServicePortalEntityManager->deleteProductConcreteToProductClassRelations($idProductConcrete);

        return $this->productClassSaver->saveProductClassesForProductConcrete($productConcreteTransfer);
    }
}

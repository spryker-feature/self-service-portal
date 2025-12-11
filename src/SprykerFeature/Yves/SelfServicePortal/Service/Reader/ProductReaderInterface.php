<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\SelfServicePortal\Service\Reader;

use Generated\Shared\Transfer\ProductOfferStorageCriteriaTransfer;
use Generated\Shared\Transfer\ProductViewTransfer;

interface ProductReaderInterface
{
    public function findProductConcreteViewTransfer(
        int $idProductConcrete,
        string $locale,
        ProductOfferStorageCriteriaTransfer $productOfferStorageCriteriaTransfer
    ): ?ProductViewTransfer;
}

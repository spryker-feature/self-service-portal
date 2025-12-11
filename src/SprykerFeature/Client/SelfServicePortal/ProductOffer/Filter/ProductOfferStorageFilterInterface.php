<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\SelfServicePortal\ProductOffer\Filter;

use Generated\Shared\Transfer\ProductOfferStorageCollectionTransfer;
use Generated\Shared\Transfer\ProductOfferStorageCriteriaTransfer;

interface ProductOfferStorageFilterInterface
{
    public function filter(
        ProductOfferStorageCollectionTransfer $productOfferStorageCollectionTransfer,
        ProductOfferStorageCriteriaTransfer $productOfferStorageCriteriaTransfer
    ): ProductOfferStorageCollectionTransfer;
}

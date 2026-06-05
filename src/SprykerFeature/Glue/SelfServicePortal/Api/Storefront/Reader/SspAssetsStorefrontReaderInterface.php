<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\SelfServicePortal\Api\Storefront\Reader;

use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\SspAssetCollectionTransfer;

interface SspAssetsStorefrontReaderInterface
{
    public function getSspAssetsByReference(string $reference, CompanyUserTransfer $companyUserTransfer): SspAssetCollectionTransfer;

    public function getSspAssets(CompanyUserTransfer $companyUserTransfer, int $limit, int $offset): SspAssetCollectionTransfer;
}

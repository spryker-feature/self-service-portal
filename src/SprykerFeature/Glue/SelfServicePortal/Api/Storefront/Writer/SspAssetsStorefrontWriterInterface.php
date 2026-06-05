<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\SelfServicePortal\Api\Storefront\Writer;

use Generated\Shared\Transfer\SspAssetCollectionRequestTransfer;
use Generated\Shared\Transfer\SspAssetCollectionResponseTransfer;

interface SspAssetsStorefrontWriterInterface
{
    public function createSspAsset(SspAssetCollectionRequestTransfer $sspAssetCollectionRequestTransfer): SspAssetCollectionResponseTransfer;
}

<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\SelfServicePortal\Asset\Access;

use Generated\Shared\Transfer\CompanyUserTransfer;

interface SspAssetAccessResolverInterface
{
    public function findCompanyUserWithAssetAccess(string $assetReference): ?CompanyUserTransfer;
}

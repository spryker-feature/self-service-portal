<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\SelfServicePortal\Asset\Access;

use Generated\Shared\Transfer\CompanyUserTransfer;
use Spryker\Client\CompanyUser\CompanyUserClientInterface;
use SprykerFeature\Client\SelfServicePortal\Storage\Reader\SspAssetStorageReaderInterface;

class SspAssetAccessResolver implements SspAssetAccessResolverInterface
{
    public function __construct(
        protected CompanyUserClientInterface $companyUserClient,
        protected SspAssetStorageReaderInterface $sspAssetStorageReader
    ) {
    }

    public function findCompanyUserWithAssetAccess(string $assetReference): ?CompanyUserTransfer
    {
        if ($assetReference === '') {
            return null;
        }

        $companyUserTransfer = $this->companyUserClient->findCompanyUser();

        if ($companyUserTransfer === null) {
            return null;
        }

        $sspAssetStorageTransfer = $this->sspAssetStorageReader->findSspAssetStorageByReference($assetReference, $companyUserTransfer);

        return $sspAssetStorageTransfer !== null ? $companyUserTransfer : null;
    }
}

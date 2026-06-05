<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\SelfServicePortal\Api\Storefront\Reader;

use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\SspAssetCollectionTransfer;
use Generated\Shared\Transfer\SspAssetConditionsTransfer;
use Generated\Shared\Transfer\SspAssetCriteriaTransfer;
use SprykerFeature\Client\SelfServicePortal\SelfServicePortalClientInterface;

class SspAssetsStorefrontReader implements SspAssetsStorefrontReaderInterface
{
    public function __construct(protected SelfServicePortalClientInterface $selfServicePortalClient)
    {
    }

    public function getSspAssetsByReference(string $reference, CompanyUserTransfer $companyUserTransfer): SspAssetCollectionTransfer
    {
        $sspAssetCriteriaTransfer = (new SspAssetCriteriaTransfer())
            ->setCompanyUser($companyUserTransfer)
            ->setSspAssetConditions(
                (new SspAssetConditionsTransfer())->setReferences([$reference]),
            );

        return $this->selfServicePortalClient->getSspAssetCollection($sspAssetCriteriaTransfer);
    }

    public function getSspAssets(CompanyUserTransfer $companyUserTransfer, int $limit, int $offset): SspAssetCollectionTransfer
    {
        $sspAssetCriteriaTransfer = (new SspAssetCriteriaTransfer())
            ->setCompanyUser($companyUserTransfer)
            ->setPagination(
                (new PaginationTransfer())->setLimit($limit)->setOffset($offset),
            );

        return $this->selfServicePortalClient->getSspAssetCollection($sspAssetCriteriaTransfer);
    }
}

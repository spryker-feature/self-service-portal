<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\SelfServicePortal\Api\Storefront\Reader;

use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\SspInquiryCollectionTransfer;
use Generated\Shared\Transfer\SspInquiryConditionsTransfer;
use Generated\Shared\Transfer\SspInquiryCriteriaTransfer;
use Generated\Shared\Transfer\SspInquiryIncludeTransfer;
use Generated\Shared\Transfer\SspInquiryOwnerConditionGroupTransfer;
use SprykerFeature\Client\SelfServicePortal\SelfServicePortalClientInterface;

class SspInquiriesStorefrontReader implements SspInquiriesStorefrontReaderInterface
{
    public function __construct(protected SelfServicePortalClientInterface $selfServicePortalClient)
    {
    }

    public function getSspInquiryByReference(string $reference, CompanyUserTransfer $companyUserTransfer): SspInquiryCollectionTransfer
    {
        $sspInquiryCriteriaTransfer = (new SspInquiryCriteriaTransfer())
            ->setSspInquiryConditions(
                (new SspInquiryConditionsTransfer())
                    ->setReferences([$reference])
                    ->setSspInquiryOwnerConditionGroup(
                        (new SspInquiryOwnerConditionGroupTransfer())->setCompanyUser($companyUserTransfer),
                    ),
            )
            ->setInclude($this->buildSspInquiryIncludeTransfer());

        return $this->selfServicePortalClient->getSspInquiryCollection($sspInquiryCriteriaTransfer);
    }

    public function getSspInquiries(CompanyUserTransfer $companyUserTransfer, int $limit, int $offset): SspInquiryCollectionTransfer
    {
        $sspInquiryCriteriaTransfer = (new SspInquiryCriteriaTransfer())
            ->setSspInquiryConditions(
                (new SspInquiryConditionsTransfer())->setSspInquiryOwnerConditionGroup(
                    (new SspInquiryOwnerConditionGroupTransfer())->setCompanyUser($companyUserTransfer),
                ),
            )
            ->setPagination(
                (new PaginationTransfer())->setLimit($limit)->setOffset($offset),
            )
            ->setInclude($this->buildSspInquiryIncludeTransfer());

        return $this->selfServicePortalClient->getSspInquiryCollection($sspInquiryCriteriaTransfer);
    }

    protected function buildSspInquiryIncludeTransfer(): SspInquiryIncludeTransfer
    {
        return (new SspInquiryIncludeTransfer())
            ->setWithSspAsset(true)
            ->setWithOrder(true)
            ->setWithManualEvents(true);
    }
}

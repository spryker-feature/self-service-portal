<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\SelfServicePortal\Api\Storefront\Reader;

use Generated\Shared\Transfer\CompanyBusinessUnitTransfer;
use Generated\Shared\Transfer\CompanyTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\SspServiceCollectionTransfer;
use Generated\Shared\Transfer\SspServiceCriteriaTransfer;
use SprykerFeature\Client\SelfServicePortal\SelfServicePortalClientInterface;

class BookedServicesStorefrontReader implements BookedServicesStorefrontReaderInterface
{
    public function __construct(protected SelfServicePortalClientInterface $selfServicePortalClient)
    {
    }

    public function getBookedServices(
        CompanyUserTransfer $companyUserTransfer,
        CustomerTransfer $customerTransfer,
        int $limit,
        int $offset,
    ): SspServiceCollectionTransfer {
        $sspServiceCriteriaTransfer = (new SspServiceCriteriaTransfer())
            ->setCompanyUser(
                (new CompanyUserTransfer())
                    ->setIdCompanyUser($companyUserTransfer->getIdCompanyUser())
                    ->setCompany(
                        (new CompanyTransfer())->setIdCompany($companyUserTransfer->getFkCompany()),
                    )
                    ->setCompanyBusinessUnit(
                        (new CompanyBusinessUnitTransfer())->setIdCompanyBusinessUnit($companyUserTransfer->getFkCompanyBusinessUnit()),
                    )
                    ->setCustomer($customerTransfer),
            )
            ->setPagination(
                (new PaginationTransfer())->setLimit($limit)->setOffset($offset),
            );

        return $this->selfServicePortalClient->getSspServiceCollection($sspServiceCriteriaTransfer);
    }
}

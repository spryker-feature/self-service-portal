<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\SelfServicePortal\Api\Storefront\Reader;

use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\SspServiceCollectionTransfer;

interface BookedServicesStorefrontReaderInterface
{
    public function getBookedServices(
        CompanyUserTransfer $companyUserTransfer,
        CustomerTransfer $customerTransfer,
        int $limit,
        int $offset,
    ): SspServiceCollectionTransfer;
}

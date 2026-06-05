<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\SelfServicePortal\Api\Storefront\Reader;

use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\SspInquiryCollectionTransfer;

interface SspInquiriesStorefrontReaderInterface
{
    public function getSspInquiryByReference(string $reference, CompanyUserTransfer $companyUserTransfer): SspInquiryCollectionTransfer;

    public function getSspInquiries(CompanyUserTransfer $companyUserTransfer, int $limit, int $offset): SspInquiryCollectionTransfer;
}

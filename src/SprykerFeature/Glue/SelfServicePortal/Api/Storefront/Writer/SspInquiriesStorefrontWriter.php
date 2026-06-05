<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\SelfServicePortal\Api\Storefront\Writer;

use Generated\Shared\Transfer\SspInquiryCollectionRequestTransfer;
use Generated\Shared\Transfer\SspInquiryCollectionResponseTransfer;
use SprykerFeature\Client\SelfServicePortal\SelfServicePortalClientInterface;

class SspInquiriesStorefrontWriter implements SspInquiriesStorefrontWriterInterface
{
    public function __construct(protected SelfServicePortalClientInterface $selfServicePortalClient)
    {
    }

    public function createSspInquiry(SspInquiryCollectionRequestTransfer $sspInquiryCollectionRequestTransfer): SspInquiryCollectionResponseTransfer
    {
        return $this->selfServicePortalClient->createSspInquiryCollection($sspInquiryCollectionRequestTransfer);
    }
}

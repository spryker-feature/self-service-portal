<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Glue\SelfServicePortal\Processor\StorefrontApi\RestApi\Validator;

use Generated\Shared\Transfer\RestSspInquiriesAttributesTransfer;
use Generated\Shared\Transfer\SspInquiryCollectionResponseTransfer;

interface SspRequestValidatorInterface
{
    public function validateSspInquiryCreateRequest(
        RestSspInquiriesAttributesTransfer $restSspInquiriesAttributesTransfer
    ): SspInquiryCollectionResponseTransfer;
}

<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Glue\SelfServicePortal\Processor\StorefrontApi\RestApi\Validator;

use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\RestSspInquiriesAttributesTransfer;
use Generated\Shared\Transfer\SspInquiryCollectionResponseTransfer;
use SprykerFeature\Glue\SelfServicePortal\SelfServicePortalConfig;

class SspRequestValidator implements SspRequestValidatorInterface
{
    public function __construct(protected SelfServicePortalConfig $selfServicePortalConfig)
    {
    }

    public function validateSspInquiryCreateRequest(
        RestSspInquiriesAttributesTransfer $restSspInquiriesAttributesTransfer
    ): SspInquiryCollectionResponseTransfer {
        $sspInquiryCollectionResponseTransfer = new SspInquiryCollectionResponseTransfer();

        if (!$restSspInquiriesAttributesTransfer->getType()) {
            return $sspInquiryCollectionResponseTransfer
                ->addError((new ErrorTransfer())->setMessage(SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_TYPE_REQUIRED));
        }

        if (!$restSspInquiriesAttributesTransfer->getSubject()) {
            return $sspInquiryCollectionResponseTransfer
                ->addError((new ErrorTransfer())->setMessage(SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_SUBJECT_REQUIRED));
        }

        if (!$restSspInquiriesAttributesTransfer->getDescription()) {
            return $sspInquiryCollectionResponseTransfer
                ->addError((new ErrorTransfer())->setMessage(SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_DESCRIPTION_REQUIRED));
        }

        if ($restSspInquiriesAttributesTransfer->getType() === $this->selfServicePortalConfig->getSspAssetInquiryType() && !$restSspInquiriesAttributesTransfer->getSspAssetReference()) {
            return $sspInquiryCollectionResponseTransfer
                ->addError((new ErrorTransfer())->setMessage(SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_SSP_ASSET_REFERENCE_REQUIRED));
        }

        if ($restSspInquiriesAttributesTransfer->getType() !== $this->selfServicePortalConfig->getSspAssetInquiryType() && $restSspInquiriesAttributesTransfer->getSspAssetReference()) {
            return $sspInquiryCollectionResponseTransfer
                ->addError((new ErrorTransfer())->setMessage(SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_SSP_ASSET_REFERENCE_NOT_ALLOWED));
        }

        if ($restSspInquiriesAttributesTransfer->getType() === $this->selfServicePortalConfig->getOrderInquiryType() && !$restSspInquiriesAttributesTransfer->getOrderReference()) {
            return $sspInquiryCollectionResponseTransfer
                ->addError((new ErrorTransfer())->setMessage(SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_ORDER_REFERENCE_REQUIRED));
        }

        if ($restSspInquiriesAttributesTransfer->getType() !== $this->selfServicePortalConfig->getOrderInquiryType() && $restSspInquiriesAttributesTransfer->getOrderReference()) {
            return $sspInquiryCollectionResponseTransfer
                ->addError((new ErrorTransfer())->setMessage(SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_ORDER_REFERENCE_NOT_ALLOWED));
        }

        return $sspInquiryCollectionResponseTransfer;
    }
}

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

        $errorMessage = $this->findValidationErrorMessage($restSspInquiriesAttributesTransfer);

        if ($errorMessage !== null) {
            return $sspInquiryCollectionResponseTransfer
                ->addError((new ErrorTransfer())->setMessage($errorMessage));
        }

        return $sspInquiryCollectionResponseTransfer;
    }

    protected function findValidationErrorMessage(RestSspInquiriesAttributesTransfer $restSspInquiriesAttributesTransfer): ?string
    {
        return $this->findRequiredFieldErrorMessage($restSspInquiriesAttributesTransfer)
            ?? $this->findSspAssetReferenceErrorMessage($restSspInquiriesAttributesTransfer)
            ?? $this->findOrderReferenceErrorMessage($restSspInquiriesAttributesTransfer);
    }

    protected function findRequiredFieldErrorMessage(RestSspInquiriesAttributesTransfer $restSspInquiriesAttributesTransfer): ?string
    {
        if (!$restSspInquiriesAttributesTransfer->getType()) {
            return SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_TYPE_REQUIRED;
        }

        if (!$restSspInquiriesAttributesTransfer->getSubject()) {
            return SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_SUBJECT_REQUIRED;
        }

        if (!$restSspInquiriesAttributesTransfer->getDescription()) {
            return SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_DESCRIPTION_REQUIRED;
        }

        return null;
    }

    protected function findSspAssetReferenceErrorMessage(RestSspInquiriesAttributesTransfer $restSspInquiriesAttributesTransfer): ?string
    {
        $isSspAssetInquiryType = $restSspInquiriesAttributesTransfer->getType() === $this->selfServicePortalConfig->getSspAssetInquiryType();
        $hasSspAssetReference = (bool)$restSspInquiriesAttributesTransfer->getSspAssetReference();

        if ($isSspAssetInquiryType && !$hasSspAssetReference) {
            return SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_SSP_ASSET_REFERENCE_REQUIRED;
        }

        if (!$isSspAssetInquiryType && $hasSspAssetReference) {
            return SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_SSP_ASSET_REFERENCE_NOT_ALLOWED;
        }

        return null;
    }

    protected function findOrderReferenceErrorMessage(RestSspInquiriesAttributesTransfer $restSspInquiriesAttributesTransfer): ?string
    {
        $isOrderInquiryType = $restSspInquiriesAttributesTransfer->getType() === $this->selfServicePortalConfig->getOrderInquiryType();
        $hasOrderReference = (bool)$restSspInquiriesAttributesTransfer->getOrderReference();

        if ($isOrderInquiryType && !$hasOrderReference) {
            return SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_ORDER_REFERENCE_REQUIRED;
        }

        if (!$isOrderInquiryType && $hasOrderReference) {
            return SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_ORDER_REFERENCE_NOT_ALLOWED;
        }

        return null;
    }
}

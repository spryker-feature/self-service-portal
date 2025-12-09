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
    protected const string GLOSSARY_KEY_SSP_ASSET_REFERENCE_REQUIRED = 'self_service_portal.inquiry.validation.ssp_asset_reference_required';

    protected const string GLOSSARY_KEY_SSP_ASSET_REFERENCE_NOT_ALLOWED = 'self_service_portal.inquiry.validation.ssp_asset_reference_not_allowed';

    protected const string GLOSSARY_KEY_ORDER_REFERENCE_REQUIRED = 'self_service_portal.inquiry.validation.order_reference_required';

    protected const string GLOSSARY_KEY_ORDER_REFERENCE_NOT_ALLOWED = 'self_service_portal.inquiry.validation.order_reference_not_allowed';

    protected const string GLOSSARY_KEY_TYPE_REQUIRED = 'self_service_portal.inquiry.validation.type_required';

    protected const string GLOSSARY_KEY_SUBJECT_REQUIRED = 'self_service_portal.inquiry.validation.subject_required';

    protected const string GLOSSARY_KEY_DESCRIPTION_REQUIRED = 'self_service_portal.inquiry.validation.description_required';

    public function __construct(protected SelfServicePortalConfig $selfServicePortalConfig)
    {
    }

    public function validateSspInquiryCreateRequest(
        RestSspInquiriesAttributesTransfer $restSspInquiriesAttributesTransfer
    ): SspInquiryCollectionResponseTransfer {
        $sspInquiryCollectionResponseTransfer = new SspInquiryCollectionResponseTransfer();

        if (!$restSspInquiriesAttributesTransfer->getType()) {
            return $sspInquiryCollectionResponseTransfer
                ->addError((new ErrorTransfer())->setMessage(static::GLOSSARY_KEY_TYPE_REQUIRED));
        }

        if (!$restSspInquiriesAttributesTransfer->getSubject()) {
            return $sspInquiryCollectionResponseTransfer
                ->addError((new ErrorTransfer())->setMessage(static::GLOSSARY_KEY_SUBJECT_REQUIRED));
        }

        if (!$restSspInquiriesAttributesTransfer->getDescription()) {
            return $sspInquiryCollectionResponseTransfer
                ->addError((new ErrorTransfer())->setMessage(static::GLOSSARY_KEY_DESCRIPTION_REQUIRED));
        }

        if ($restSspInquiriesAttributesTransfer->getType() === $this->selfServicePortalConfig->getSspAssetInquiryType() && !$restSspInquiriesAttributesTransfer->getSspAssetReference()) {
            return $sspInquiryCollectionResponseTransfer
                ->addError((new ErrorTransfer())->setMessage(static::GLOSSARY_KEY_SSP_ASSET_REFERENCE_REQUIRED));
        }

        if ($restSspInquiriesAttributesTransfer->getType() !== $this->selfServicePortalConfig->getSspAssetInquiryType() && $restSspInquiriesAttributesTransfer->getSspAssetReference()) {
            return $sspInquiryCollectionResponseTransfer
                ->addError((new ErrorTransfer())->setMessage(static::GLOSSARY_KEY_SSP_ASSET_REFERENCE_NOT_ALLOWED));
        }

        if ($restSspInquiriesAttributesTransfer->getType() === $this->selfServicePortalConfig->getOrderInquiryType() && !$restSspInquiriesAttributesTransfer->getOrderReference()) {
            return $sspInquiryCollectionResponseTransfer
                ->addError((new ErrorTransfer())->setMessage(static::GLOSSARY_KEY_ORDER_REFERENCE_REQUIRED));
        }

        if ($restSspInquiriesAttributesTransfer->getType() !== $this->selfServicePortalConfig->getOrderInquiryType() && $restSspInquiriesAttributesTransfer->getOrderReference()) {
            return $sspInquiryCollectionResponseTransfer
                ->addError((new ErrorTransfer())->setMessage(static::GLOSSARY_KEY_ORDER_REFERENCE_NOT_ALLOWED));
        }

        return $sspInquiryCollectionResponseTransfer;
    }
}

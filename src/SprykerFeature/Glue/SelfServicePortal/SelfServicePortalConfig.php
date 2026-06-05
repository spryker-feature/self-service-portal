<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Glue\SelfServicePortal;

use Spryker\Glue\Kernel\AbstractBundleConfig;

/**
 * @method \SprykerFeature\Shared\SelfServicePortal\SelfServicePortalConfig getSharedConfig()
 */
class SelfServicePortalConfig extends AbstractBundleConfig
{
    /**
     * Specification
     * - Defines the collector resource name
     *
     * @api
     *
     * @var string
     */
    public const RESOURCE_SSP_ASSETS = 'ssp-assets';

    /**
     * Specification
     * - Defines the inquiries resource name
     *
     * @api
     *
     * @var string
     */
    public const RESOURCE_SSP_INQUIRIES = 'ssp-inquiries';

    /**
     * Specification
     * - Defines the services resource name
     *
     * @api
     *
     * @var string
     */
    public const RESOURCE_SSP_SERVICES = 'booked-services';

    /**
     * Specification
     * - Defines the glossary key for the asset not found error message.
     *
     * @api
     */
    public const string GLOSSARY_KEY_ASSET_NOT_FOUND = 'self_service_portal.asset.error.not-found';

    /**
     * Specification
     * - Defines the glossary key for the asset access denied error message.
     *
     * @api
     */
    public const string GLOSSARY_KEY_ASSET_ACCESS_DENIED = 'self_service_portal.asset.access.denied';

    /**
     * Specification
     * - Defines the glossary key for the asset invalid name validation error.
     *
     * @api
     */
    public const string GLOSSARY_KEY_ASSET_INVALID_NAME = 'self_service_portal.asset.validation.name.not_set';

    /**
     * Specification
     * - Defines the glossary key for the asset unknown error message.
     *
     * @api
     */
    public const string GLOSSARY_KEY_ASSET_UNKNOWN_ERROR = 'self_service_portal.asset.validation.unknown_error';

    /**
     * Specification
     * - Defines the glossary key for the inquiry not found error message.
     *
     * @api
     */
    public const string GLOSSARY_KEY_INQUIRY_NOT_FOUND = 'self_service_portal.inquiry.error.not-found';

    /**
     * Specification
     * - Defines the glossary key for the inquiry access denied error message.
     *
     * @api
     */
    public const string GLOSSARY_KEY_INQUIRY_ACCESS_DENIED = 'self_service_portal.inquiry.access.denied';

    /**
     * Specification
     * - Defines the glossary key for the inquiry subject not set validation error.
     *
     * @api
     */
    public const string GLOSSARY_KEY_INQUIRY_INVALID_SUBJECT = 'self_service_portal.inquiry.validation.subject.not_set';

    /**
     * Specification
     * - Defines the glossary key for the inquiry subject too long validation error.
     *
     * @api
     */
    public const string GLOSSARY_KEY_INQUIRY_INVALID_SUBJECT_TOO_LONG = 'self_service_portal.inquiry.validation.subject.too_long';

    /**
     * Specification
     * - Defines the glossary key for the inquiry description too long validation error.
     *
     * @api
     */
    public const string GLOSSARY_KEY_INQUIRY_INVALID_DESCRIPTION_TOO_LONG = 'self_service_portal.inquiry.validation.description.too_long';

    /**
     * Specification
     * - Defines the glossary key for the inquiry invalid type validation error.
     *
     * @api
     */
    public const string GLOSSARY_KEY_INQUIRY_INVALID_TYPE = 'self_service_portal.inquiry.validation.type.invalid';

    /**
     * Specification
     * - Defines the glossary key for the inquiry type not set validation error.
     *
     * @api
     */
    public const string GLOSSARY_KEY_INQUIRY_INVALID_TYPE_NOT_SET = 'self_service_portal.inquiry.validation.type.not_set';

    /**
     * Specification
     * - Defines the glossary key for the inquiry company user not set validation error.
     *
     * @api
     */
    public const string GLOSSARY_KEY_INQUIRY_COMPANY_USER_NOT_SET = 'self_service_portal.inquiry.validation.company_user.not_set';

    /**
     * Specification
     * - Defines the glossary key for the inquiry unknown error message.
     *
     * @api
     */
    public const string GLOSSARY_KEY_INQUIRY_UNKNOWN_ERROR = 'self_service_portal.inquiry.validation.unknown_error';

    /**
     * Specification
     * - Defines the glossary key for the inquiry type required validation error.
     *
     * @api
     */
    public const string GLOSSARY_KEY_INQUIRY_TYPE_REQUIRED = 'self_service_portal.inquiry.validation.type_required';

    /**
     * Specification
     * - Defines the glossary key for the inquiry subject required validation error.
     *
     * @api
     */
    public const string GLOSSARY_KEY_INQUIRY_SUBJECT_REQUIRED = 'self_service_portal.inquiry.validation.subject_required';

    /**
     * Specification
     * - Defines the glossary key for the inquiry description required validation error.
     *
     * @api
     */
    public const string GLOSSARY_KEY_INQUIRY_DESCRIPTION_REQUIRED = 'self_service_portal.inquiry.validation.description_required';

    /**
     * Specification
     * - Defines the glossary key for the inquiry SSP asset reference required validation error.
     *
     * @api
     */
    public const string GLOSSARY_KEY_INQUIRY_SSP_ASSET_REFERENCE_REQUIRED = 'self_service_portal.inquiry.validation.ssp_asset_reference_required';

    /**
     * Specification
     * - Defines the glossary key for the inquiry SSP asset reference not allowed validation error.
     *
     * @api
     */
    public const string GLOSSARY_KEY_INQUIRY_SSP_ASSET_REFERENCE_NOT_ALLOWED = 'self_service_portal.inquiry.validation.ssp_asset_reference_not_allowed';

    /**
     * Specification
     * - Defines the glossary key for the inquiry order reference required validation error.
     *
     * @api
     */
    public const string GLOSSARY_KEY_INQUIRY_ORDER_REFERENCE_REQUIRED = 'self_service_portal.inquiry.validation.order_reference_required';

    /**
     * Specification
     * - Defines the glossary key for the inquiry order reference not allowed validation error.
     *
     * @api
     */
    public const string GLOSSARY_KEY_INQUIRY_ORDER_REFERENCE_NOT_ALLOWED = 'self_service_portal.inquiry.validation.order_reference_not_allowed';

    /**
     * Specification:
     * - Defines the ssp asset inquiry type.
     *
     * @api
     *
     * @return string
     */
    public function getSspAssetInquiryType(): string
    {
        return $this->getSharedConfig()->getSspAssetInquirySource();
    }

    /**
     * Specification:
     * - Defines the order inquiry type.
     *
     * @api
     *
     * @return string
     */
    public function getOrderInquiryType(): string
    {
        return $this->getSharedConfig()->getOrderInquirySource();
    }
}

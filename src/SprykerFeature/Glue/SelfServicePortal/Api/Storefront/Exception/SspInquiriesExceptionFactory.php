<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\SelfServicePortal\Api\Storefront\Exception;

use Spryker\ApiPlatform\Exception\GlueApiException;
use Spryker\Client\GlossaryStorage\GlossaryStorageClientInterface;
use SprykerFeature\Glue\SelfServicePortal\SelfServicePortalConfig;
use Symfony\Component\HttpFoundation\Response;

class SspInquiriesExceptionFactory
{
    public function __construct(
        protected GlossaryStorageClientInterface $glossaryStorageClient,
    ) {
    }

    public function createInquiryNotFoundException(string $locale): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_NOT_FOUND,
            '',
            $this->translate(SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_NOT_FOUND, $locale),
        );
    }

    public function createInquiryAccessDeniedException(string $locale): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_FORBIDDEN,
            '',
            $this->translate(SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_ACCESS_DENIED, $locale),
        );
    }

    public function createInvalidInquirySubjectException(string $locale): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            '',
            $this->translate(SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_INVALID_SUBJECT, $locale),
        );
    }

    public function createInvalidInquirySubjectTooLongException(string $locale): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            '',
            $this->translate(SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_INVALID_SUBJECT_TOO_LONG, $locale),
        );
    }

    public function createInvalidInquiryDescriptionTooLongException(string $locale): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            '',
            $this->translate(SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_INVALID_DESCRIPTION_TOO_LONG, $locale),
        );
    }

    public function createInvalidInquiryTypeException(string $locale): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            '',
            $this->translate(SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_INVALID_TYPE, $locale),
        );
    }

    public function createInvalidInquiryTypeNotSetException(string $locale): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            '',
            $this->translate(SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_INVALID_TYPE_NOT_SET, $locale),
        );
    }

    public function createInquiryCompanyUserNotSetException(string $locale): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            '',
            $this->translate(SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_COMPANY_USER_NOT_SET, $locale),
        );
    }

    public function createInquiryUnknownErrorException(string $locale): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_BAD_REQUEST,
            '',
            $this->translate(SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_UNKNOWN_ERROR, $locale),
        );
    }

    public function createInquiryTypeRequiredException(string $locale): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_BAD_REQUEST,
            '',
            $this->translate(SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_TYPE_REQUIRED, $locale),
        );
    }

    public function createInquirySubjectRequiredException(string $locale): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_BAD_REQUEST,
            '',
            $this->translate(SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_SUBJECT_REQUIRED, $locale),
        );
    }

    public function createInquiryDescriptionRequiredException(string $locale): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_BAD_REQUEST,
            '',
            $this->translate(SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_DESCRIPTION_REQUIRED, $locale),
        );
    }

    public function createSspAssetReferenceRequiredException(string $locale): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_BAD_REQUEST,
            '',
            $this->translate(SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_SSP_ASSET_REFERENCE_REQUIRED, $locale),
        );
    }

    public function createSspAssetReferenceNotAllowedException(string $locale): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_BAD_REQUEST,
            '',
            $this->translate(SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_SSP_ASSET_REFERENCE_NOT_ALLOWED, $locale),
        );
    }

    public function createOrderReferenceRequiredException(string $locale): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_BAD_REQUEST,
            '',
            $this->translate(SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_ORDER_REFERENCE_REQUIRED, $locale),
        );
    }

    public function createOrderReferenceNotAllowedException(string $locale): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_BAD_REQUEST,
            '',
            $this->translate(SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_ORDER_REFERENCE_NOT_ALLOWED, $locale),
        );
    }

    public function createExceptionFromErrorMessage(string $message, string $locale): GlueApiException
    {
        return match ($message) {
            SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_NOT_FOUND => $this->createInquiryNotFoundException($locale),
            SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_ACCESS_DENIED => $this->createInquiryAccessDeniedException($locale),
            SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_INVALID_SUBJECT => $this->createInvalidInquirySubjectException($locale),
            SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_INVALID_SUBJECT_TOO_LONG => $this->createInvalidInquirySubjectTooLongException($locale),
            SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_INVALID_DESCRIPTION_TOO_LONG => $this->createInvalidInquiryDescriptionTooLongException($locale),
            SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_INVALID_TYPE => $this->createInvalidInquiryTypeException($locale),
            SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_INVALID_TYPE_NOT_SET => $this->createInvalidInquiryTypeNotSetException($locale),
            SelfServicePortalConfig::GLOSSARY_KEY_INQUIRY_COMPANY_USER_NOT_SET => $this->createInquiryCompanyUserNotSetException($locale),
            default => $this->createInquiryUnknownErrorException($locale),
        };
    }

    protected function translate(string $glossaryKey, string $locale): string
    {
        $translated = $this->glossaryStorageClient->translate($glossaryKey, $locale);

        return $translated !== '' ? $translated : $glossaryKey;
    }
}

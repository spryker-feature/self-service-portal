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

class SspAssetsExceptionFactory
{
    public function __construct(
        protected GlossaryStorageClientInterface $glossaryStorageClient,
    ) {
    }

    public function createAssetNotFoundException(string $locale): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_NOT_FOUND,
            '',
            $this->translate(SelfServicePortalConfig::GLOSSARY_KEY_ASSET_NOT_FOUND, $locale),
        );
    }

    public function createAssetAccessDeniedException(string $locale): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_FORBIDDEN,
            '',
            $this->translate(SelfServicePortalConfig::GLOSSARY_KEY_ASSET_ACCESS_DENIED, $locale),
        );
    }

    public function createAssetInvalidNameException(string $locale): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            '',
            $this->translate(SelfServicePortalConfig::GLOSSARY_KEY_ASSET_INVALID_NAME, $locale),
        );
    }

    public function createAssetUnknownErrorException(string $locale): GlueApiException
    {
        return new GlueApiException(
            Response::HTTP_BAD_REQUEST,
            '',
            $this->translate(SelfServicePortalConfig::GLOSSARY_KEY_ASSET_UNKNOWN_ERROR, $locale),
        );
    }

    public function createExceptionFromErrorMessage(string $message, string $locale): GlueApiException
    {
        return match ($message) {
            SelfServicePortalConfig::GLOSSARY_KEY_ASSET_NOT_FOUND => $this->createAssetNotFoundException($locale),
            SelfServicePortalConfig::GLOSSARY_KEY_ASSET_ACCESS_DENIED => $this->createAssetAccessDeniedException($locale),
            SelfServicePortalConfig::GLOSSARY_KEY_ASSET_INVALID_NAME => $this->createAssetInvalidNameException($locale),
            default => $this->createAssetUnknownErrorException($locale),
        };
    }

    protected function translate(string $glossaryKey, string $locale): string
    {
        $translated = $this->glossaryStorageClient->translate($glossaryKey, $locale);

        return $translated !== '' ? $translated : $glossaryKey;
    }
}

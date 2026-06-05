<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\SelfServicePortal\Api\Storefront\Provider;

use Generated\Api\Storefront\SspAssetsStorefrontResource;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\SspAssetTransfer;
use Spryker\ApiPlatform\State\Provider\AbstractStorefrontProvider;
use Spryker\Service\Serializer\SerializerServiceInterface;
use SprykerFeature\Glue\SelfServicePortal\Api\Storefront\Exception\SspAssetsExceptionFactory;
use SprykerFeature\Glue\SelfServicePortal\Api\Storefront\Reader\SspAssetsStorefrontReaderInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SspAssetsStorefrontProvider extends AbstractStorefrontProvider
{
    protected const string KEY_REFERENCE = 'reference';

    public function __construct(
        protected SspAssetsStorefrontReaderInterface $reader,
        protected SspAssetsExceptionFactory $exceptionFactory,
        protected SerializerServiceInterface $serializer,
    ) {
    }

    /**
     * @return array<\Generated\Api\Storefront\SspAssetsStorefrontResource>
     */
    protected function provideCollection(): array
    {
        $locale = $this->getLocale()->getLocaleNameOrFail();
        $companyUserTransfer = $this->resolveCompanyUser($locale);
        $limit = $this->getPaginationLimit();
        $offset = $this->getPaginationOffset();

        $sspAssetCollectionTransfer = $this->reader->getSspAssets($companyUserTransfer, $limit, $offset);

        $resources = [];

        foreach ($sspAssetCollectionTransfer->getSspAssets() as $sspAssetTransfer) {
            $resources[] = $this->mapTransferToResource($sspAssetTransfer);
        }

        $pagination = $sspAssetCollectionTransfer->getPagination();

        if ($pagination !== null && count($resources) > 0) {
            $resources[0]->pagination = $this->calculatePagination($offset, $limit, $pagination->getNbResults() ?? 0);
        }

        return $resources;
    }

    /**
     * @throws \Spryker\ApiPlatform\Exception\ApiPlatformContextException
     *
     * @return \Generated\Api\Storefront\SspAssetsStorefrontResource|null
     */
    protected function provideItem(): ?object
    {
        $locale = $this->getLocale()->getLocaleNameOrFail();
        $companyUserTransfer = $this->resolveCompanyUser($locale);
        $reference = (string)$this->getUriVariable(static::KEY_REFERENCE);

        $sspAssetCollectionTransfer = $this->reader->getSspAssetsByReference($reference, $companyUserTransfer);

        if ($sspAssetCollectionTransfer->getSspAssets()->count() === 0) {
            throw $this->exceptionFactory->createAssetNotFoundException($locale);
        }

        return $this->mapTransferToResource(
            $sspAssetCollectionTransfer->getSspAssets()->getIterator()->current(),
        );
    }

    protected function mapTransferToResource(SspAssetTransfer $sspAssetTransfer): SspAssetsStorefrontResource
    {
        return $this->serializer->denormalize(
            $sspAssetTransfer->toArray(true, true),
            SspAssetsStorefrontResource::class,
        );
    }

    protected function resolveCompanyUser(string $locale): CompanyUserTransfer
    {
        if (!$this->hasCustomer()) {
            throw new AccessDeniedException();
        }

        $companyUserTransfer = $this->getCustomer()->getCompanyUserTransfer();

        if ($companyUserTransfer === null) {
            throw $this->exceptionFactory->createAssetAccessDeniedException($locale);
        }

        return $companyUserTransfer;
    }
}

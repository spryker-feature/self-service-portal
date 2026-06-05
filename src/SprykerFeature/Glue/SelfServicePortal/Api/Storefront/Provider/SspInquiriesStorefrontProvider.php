<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\SelfServicePortal\Api\Storefront\Provider;

use Generated\Api\Storefront\SspInquiriesStorefrontResource;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\SspInquiryTransfer;
use Spryker\ApiPlatform\State\Provider\AbstractStorefrontProvider;
use Spryker\Service\Serializer\SerializerServiceInterface;
use SprykerFeature\Glue\SelfServicePortal\Api\Storefront\Exception\SspInquiriesExceptionFactory;
use SprykerFeature\Glue\SelfServicePortal\Api\Storefront\Reader\SspInquiriesStorefrontReaderInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SspInquiriesStorefrontProvider extends AbstractStorefrontProvider
{
    protected const string KEY_REFERENCE = 'reference';

    public function __construct(
        protected SspInquiriesStorefrontReaderInterface $reader,
        protected SspInquiriesExceptionFactory $exceptionFactory,
        protected SerializerServiceInterface $serializer,
    ) {
    }

    /**
     * @return array<\Generated\Api\Storefront\SspInquiriesStorefrontResource>
     */
    protected function provideCollection(): array
    {
        $locale = $this->getLocale()->getLocaleNameOrFail();
        $companyUserTransfer = $this->resolveCompanyUser($locale);
        $limit = $this->getPaginationLimit();
        $offset = $this->getPaginationOffset();

        $sspInquiryCollectionTransfer = $this->reader->getSspInquiries($companyUserTransfer, $limit, $offset);

        $resources = [];

        foreach ($sspInquiryCollectionTransfer->getSspInquiries() as $sspInquiryTransfer) {
            $resources[] = $this->mapTransferToResource($sspInquiryTransfer);
        }

        $pagination = $sspInquiryCollectionTransfer->getPagination();

        if ($pagination !== null && count($resources) > 0) {
            $resources[0]->pagination = $this->calculatePagination($offset, $limit, $pagination->getNbResults() ?? 0);
        }

        return $resources;
    }

    /**
     * @throws \Spryker\ApiPlatform\Exception\ApiPlatformContextException
     *
     * @return \Generated\Api\Storefront\SspInquiriesStorefrontResource|null
     */
    protected function provideItem(): ?object
    {
        $locale = $this->getLocale()->getLocaleNameOrFail();
        $companyUserTransfer = $this->resolveCompanyUser($locale);
        $reference = (string)$this->getUriVariable(static::KEY_REFERENCE);

        $sspInquiryCollectionTransfer = $this->reader->getSspInquiryByReference($reference, $companyUserTransfer);

        if ($sspInquiryCollectionTransfer->getSspInquiries()->count() === 0) {
            throw $this->exceptionFactory->createInquiryNotFoundException($locale);
        }

        return $this->mapTransferToResource(
            $sspInquiryCollectionTransfer->getSspInquiries()->getIterator()->current(),
        );
    }

    protected function mapTransferToResource(SspInquiryTransfer $sspInquiryTransfer): SspInquiriesStorefrontResource
    {
        $data = $sspInquiryTransfer->toArray(true, true);
        $data['sspAssetReference'] = $sspInquiryTransfer->getSspAsset()?->getReference();
        $data['orderReference'] = $sspInquiryTransfer->getOrder()?->getOrderReference();

        return $this->serializer->denormalize($data, SspInquiriesStorefrontResource::class);
    }

    protected function resolveCompanyUser(string $locale): CompanyUserTransfer
    {
        if (!$this->hasCustomer()) {
            throw new AccessDeniedException();
        }

        $companyUserTransfer = $this->getCustomer()->getCompanyUserTransfer();

        if ($companyUserTransfer === null) {
            throw $this->exceptionFactory->createInquiryCompanyUserNotSetException($locale);
        }

        return $companyUserTransfer;
    }
}

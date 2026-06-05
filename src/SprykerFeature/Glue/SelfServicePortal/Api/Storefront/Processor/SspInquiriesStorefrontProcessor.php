<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\SelfServicePortal\Api\Storefront\Processor;

use Generated\Api\Storefront\SspInquiriesStorefrontResource;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\SspAssetTransfer;
use Generated\Shared\Transfer\SspInquiryCollectionRequestTransfer;
use Generated\Shared\Transfer\SspInquiryTransfer;
use Spryker\ApiPlatform\State\Processor\AbstractStorefrontProcessor;
use Spryker\Service\Serializer\SerializerServiceInterface;
use SprykerFeature\Glue\SelfServicePortal\Api\Storefront\Exception\SspInquiriesExceptionFactory;
use SprykerFeature\Glue\SelfServicePortal\Api\Storefront\Writer\SspInquiriesStorefrontWriterInterface;
use SprykerFeature\Glue\SelfServicePortal\SelfServicePortalConfig;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SspInquiriesStorefrontProcessor extends AbstractStorefrontProcessor
{
    public function __construct(
        protected SspInquiriesStorefrontWriterInterface $writer,
        protected SspInquiriesExceptionFactory $exceptionFactory,
        protected SelfServicePortalConfig $selfServicePortalConfig,
        protected SerializerServiceInterface $serializer,
    ) {
    }

    protected function processPost(mixed $data): SspInquiriesStorefrontResource
    {
        if (!$this->hasCustomer()) {
            throw new AccessDeniedException();
        }

        $locale = $this->getLocale()->getLocaleNameOrFail();
        $companyUserTransfer = $this->getCustomer()->getCompanyUserTransfer();

        if ($companyUserTransfer === null) {
            throw $this->exceptionFactory->createInquiryCompanyUserNotSetException($locale);
        }

        $this->validateInquiryCreateRequest($data, $locale);

        $sspInquiryCollectionRequestTransfer = $this->buildSspInquiryCollectionRequestTransfer($data, $companyUserTransfer);

        $sspInquiryCollectionResponseTransfer = $this->writer->createSspInquiry($sspInquiryCollectionRequestTransfer);

        $firstError = $sspInquiryCollectionResponseTransfer->getErrors()->getIterator()->current();

        if ($firstError !== null) {
            throw $this->exceptionFactory->createExceptionFromErrorMessage((string)$firstError->getMessage(), $locale);
        }

        $createdInquiry = $sspInquiryCollectionResponseTransfer->getSspInquiries()->getIterator()->current();

        if ($createdInquiry !== null) {
            $responseData = $createdInquiry->toArray(true, true);
            $responseData['sspAssetReference'] = $createdInquiry->getSspAsset()?->getReference();
            $responseData['orderReference'] = $createdInquiry->getOrder()?->getOrderReference();

            return $this->serializer->denormalize($responseData, SspInquiriesStorefrontResource::class);
        }

        return $data;
    }

    protected function validateInquiryCreateRequest(SspInquiriesStorefrontResource $data, string $locale): void
    {
        if (!$data->type) {
            throw $this->exceptionFactory->createInquiryTypeRequiredException($locale);
        }

        if (!$data->subject) {
            throw $this->exceptionFactory->createInquirySubjectRequiredException($locale);
        }

        if (!$data->description) {
            throw $this->exceptionFactory->createInquiryDescriptionRequiredException($locale);
        }

        if ($data->type === $this->selfServicePortalConfig->getSspAssetInquiryType() && !$data->sspAssetReference) {
            throw $this->exceptionFactory->createSspAssetReferenceRequiredException($locale);
        }

        if ($data->type !== $this->selfServicePortalConfig->getSspAssetInquiryType() && $data->sspAssetReference) {
            throw $this->exceptionFactory->createSspAssetReferenceNotAllowedException($locale);
        }

        if ($data->type === $this->selfServicePortalConfig->getOrderInquiryType() && !$data->orderReference) {
            throw $this->exceptionFactory->createOrderReferenceRequiredException($locale);
        }

        if ($data->type !== $this->selfServicePortalConfig->getOrderInquiryType() && $data->orderReference) {
            throw $this->exceptionFactory->createOrderReferenceNotAllowedException($locale);
        }
    }

    protected function buildSspInquiryCollectionRequestTransfer(
        SspInquiriesStorefrontResource $data,
        CompanyUserTransfer $companyUserTransfer,
    ): SspInquiryCollectionRequestTransfer {
        $customerTransfer = $this->getCustomer();

        $sspInquiryTransfer = (new SspInquiryTransfer())
            ->setType($data->type)
            ->setSubject($data->subject)
            ->setDescription($data->description)
            ->setSspAsset((new SspAssetTransfer())->setReference($data->sspAssetReference))
            ->setCompanyUser($companyUserTransfer)
            ->setOrder(
                (new OrderTransfer())
                    ->setOrderReference($data->orderReference)
                    ->setCustomerReference($customerTransfer->getCustomerReference()),
            );

        return (new SspInquiryCollectionRequestTransfer())
            ->addSspInquiry($sspInquiryTransfer)
            ->setCompanyUser($companyUserTransfer);
    }
}

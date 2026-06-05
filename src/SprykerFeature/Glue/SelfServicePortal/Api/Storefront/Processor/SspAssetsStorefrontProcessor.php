<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\SelfServicePortal\Api\Storefront\Processor;

use Generated\Api\Storefront\SspAssetsStorefrontResource;
use Generated\Shared\Transfer\CompanyBusinessUnitTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\SspAssetBusinessUnitAssignmentTransfer;
use Generated\Shared\Transfer\SspAssetCollectionRequestTransfer;
use Generated\Shared\Transfer\SspAssetTransfer;
use Spryker\ApiPlatform\State\Processor\AbstractStorefrontProcessor;
use Spryker\Service\Serializer\SerializerServiceInterface;
use SprykerFeature\Glue\SelfServicePortal\Api\Storefront\Exception\SspAssetsExceptionFactory;
use SprykerFeature\Glue\SelfServicePortal\Api\Storefront\Writer\SspAssetsStorefrontWriterInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SspAssetsStorefrontProcessor extends AbstractStorefrontProcessor
{
    public function __construct(
        protected SspAssetsStorefrontWriterInterface $writer,
        protected SspAssetsExceptionFactory $exceptionFactory,
        protected SerializerServiceInterface $serializer,
    ) {
    }

    protected function processPost(mixed $data): SspAssetsStorefrontResource
    {
        if (!$this->hasCustomer()) {
            throw new AccessDeniedException();
        }

        $locale = $this->getLocale()->getLocaleNameOrFail();
        $companyUserTransfer = $this->getCustomer()->getCompanyUserTransfer();

        if ($companyUserTransfer === null) {
            throw $this->exceptionFactory->createAssetAccessDeniedException($locale);
        }

        $sspAssetCollectionRequestTransfer = $this->buildSspAssetCollectionRequestTransfer($data, $companyUserTransfer);

        $sspAssetCollectionResponseTransfer = $this->writer->createSspAsset($sspAssetCollectionRequestTransfer);

        $firstError = $sspAssetCollectionResponseTransfer->getErrors()->getIterator()->current();

        if ($firstError !== null) {
            throw $this->exceptionFactory->createExceptionFromErrorMessage((string)$firstError->getMessage(), $locale);
        }

        $createdAsset = $sspAssetCollectionResponseTransfer->getSspAssets()->getIterator()->current();

        if ($createdAsset !== null) {
            return $this->serializer->denormalize(
                $createdAsset->toArray(true, true),
                SspAssetsStorefrontResource::class,
            );
        }

        return $data;
    }

    protected function buildSspAssetCollectionRequestTransfer(
        SspAssetsStorefrontResource $data,
        CompanyUserTransfer $companyUserTransfer,
    ): SspAssetCollectionRequestTransfer {
        $companyBusinessUnitTransfer = (new CompanyBusinessUnitTransfer())
            ->setIdCompanyBusinessUnit($companyUserTransfer->getFkCompanyBusinessUnit());

        $sspAssetTransfer = (new SspAssetTransfer())
            ->setName($data->name)
            ->setSerialNumber($data->serialNumber)
            ->setNote($data->note)
            ->setExternalImageUrl($data->externalImageUrl)
            ->setCompanyBusinessUnit($companyBusinessUnitTransfer);

        $sspAssetTransfer->addBusinessUnitAssignment(
            (new SspAssetBusinessUnitAssignmentTransfer())->setCompanyBusinessUnit($companyBusinessUnitTransfer),
        );

        return (new SspAssetCollectionRequestTransfer())
            ->addSspAsset($sspAssetTransfer)
            ->setCompanyUser(
                (new CompanyUserTransfer())
                    ->setIdCompanyUser($companyUserTransfer->getIdCompanyUser())
                    ->setFkCompany($companyUserTransfer->getFkCompany())
                    ->setFkCompanyBusinessUnit($companyUserTransfer->getFkCompanyBusinessUnit()),
            );
    }
}

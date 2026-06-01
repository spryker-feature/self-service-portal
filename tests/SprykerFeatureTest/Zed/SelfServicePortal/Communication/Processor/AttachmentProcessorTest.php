<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeatureTest\Zed\SelfServicePortal\Communication\Processor;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\FileAttachmentCollectionRequestTransfer;
use Generated\Shared\Transfer\FileAttachmentCollectionResponseTransfer;
use Generated\Shared\Transfer\FileAttachmentTransfer;
use Generated\Shared\Transfer\FileTransfer;
use SprykerFeature\Zed\SelfServicePortal\Business\SelfServicePortalFacadeInterface;
use SprykerFeature\Zed\SelfServicePortal\Communication\CompanyFile\Form\AttachFileForm;
use SprykerFeature\Zed\SelfServicePortal\Communication\CompanyFile\FormDataNormalizer;
use SprykerFeature\Zed\SelfServicePortal\Communication\CompanyFile\Mapper\FileAttachmentMapper;
use SprykerFeature\Zed\SelfServicePortal\Communication\CompanyFile\Processor\AttachmentProcessor;
use SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group SelfServicePortal
 * @group Communication
 * @group Processor
 * @group AttachmentProcessorTest
 */
class AttachmentProcessorTest extends Unit
{
    protected const ID_FILE = 1;

    protected const ID_BUSINESS_UNIT = 10;

    protected const ID_BUSINESS_UNIT_FROM_COMPANY = 20;

    protected const ID_COMPANY = 5;

    protected const ID_COMPANY_USER = 30;

    protected const ID_MODEL = 40;

    protected const ID_ASSET = 50;

    public function testProcessAllScopesFormSavesBuIdsDirectly(): void
    {
        $savedRequest = null;
        $processor = $this->createProcessor(function (FileAttachmentCollectionRequestTransfer $req) use (&$savedRequest): void {
            $savedRequest = $req;
        });

        $processor->processAllScopesForm(
            [AttachFileForm::FIELD_BUSINESS_UNIT_IDS_TO_BE_ATTACHED => (string)static::ID_BUSINESS_UNIT],
            static::ID_FILE,
            $this->createFileAttachmentTransfer(),
        );

        $buCollection = $savedRequest->getFileAttachmentsToAdd()[0]->getBusinessUnitCollectionOrFail();
        $this->assertCount(1, $buCollection->getCompanyBusinessUnits());
        $this->assertSame(static::ID_BUSINESS_UNIT, $buCollection->getCompanyBusinessUnits()[0]->getIdCompanyBusinessUnitOrFail());
    }

    public function testProcessAllScopesFormExpandsCompanyIdsToBuIds(): void
    {
        $savedRequest = null;
        $processor = $this->createProcessor(
            function (FileAttachmentCollectionRequestTransfer $req) use (&$savedRequest): void {
                $savedRequest = $req;
            },
            [static::ID_COMPANY => [static::ID_BUSINESS_UNIT_FROM_COMPANY]],
        );

        $processor->processAllScopesForm(
            [AttachFileForm::FIELD_COMPANY_IDS_TO_BE_ATTACHED => (string)static::ID_COMPANY],
            static::ID_FILE,
            $this->createFileAttachmentTransfer(),
        );

        $buCollection = $savedRequest->getFileAttachmentsToAdd()[0]->getBusinessUnitCollectionOrFail();
        $this->assertCount(1, $buCollection->getCompanyBusinessUnits());
        $this->assertSame(static::ID_BUSINESS_UNIT_FROM_COMPANY, $buCollection->getCompanyBusinessUnits()[0]->getIdCompanyBusinessUnitOrFail());
    }

    public function testProcessAllScopesFormMergesBuAndCompanyIdsDeduped(): void
    {
        $savedRequest = null;
        $processor = $this->createProcessor(
            function (FileAttachmentCollectionRequestTransfer $req) use (&$savedRequest): void {
                $savedRequest = $req;
            },
            [static::ID_COMPANY => [static::ID_BUSINESS_UNIT]],
        );

        $processor->processAllScopesForm(
            [
                AttachFileForm::FIELD_BUSINESS_UNIT_IDS_TO_BE_ATTACHED => (string)static::ID_BUSINESS_UNIT,
                AttachFileForm::FIELD_COMPANY_IDS_TO_BE_ATTACHED => (string)static::ID_COMPANY,
            ],
            static::ID_FILE,
            $this->createFileAttachmentTransfer(),
        );

        $buCollection = $savedRequest->getFileAttachmentsToAdd()[0]->getBusinessUnitCollectionOrFail();
        $this->assertCount(1, $buCollection->getCompanyBusinessUnits(), 'Duplicate BU ID must be deduplicated');
    }

    public function testProcessAllScopesFormHandlesAllEntityTypes(): void
    {
        $savedRequest = null;
        $processor = $this->createProcessor(function (FileAttachmentCollectionRequestTransfer $req) use (&$savedRequest): void {
            $savedRequest = $req;
        });

        $processor->processAllScopesForm(
            [
                AttachFileForm::FIELD_ASSET_IDS_TO_BE_ATTACHED => (string)static::ID_ASSET,
                AttachFileForm::FIELD_BUSINESS_UNIT_IDS_TO_BE_ATTACHED => (string)static::ID_BUSINESS_UNIT,
                AttachFileForm::FIELD_COMPANY_USER_IDS_TO_BE_ATTACHED => (string)static::ID_COMPANY_USER,
                AttachFileForm::FIELD_MODEL_IDS_TO_BE_ATTACHED => (string)static::ID_MODEL,
            ],
            static::ID_FILE,
            $this->createFileAttachmentTransfer(),
        );

        $toAdd = $savedRequest->getFileAttachmentsToAdd()[0];
        $this->assertCount(1, $toAdd->getSspAssetCollectionOrFail()->getSspAssets());
        $this->assertCount(1, $toAdd->getBusinessUnitCollectionOrFail()->getCompanyBusinessUnits());
        $this->assertCount(1, $toAdd->getCompanyUserCollectionOrFail()->getCompanyUsers());
        $this->assertCount(1, $toAdd->getSspModelCollectionOrFail()->getSspModels());
    }

    public function testProcessAllScopesFormWithEmptyDataReturnsRedirect(): void
    {
        $savedRequest = null;
        $processor = $this->createProcessor(function (FileAttachmentCollectionRequestTransfer $req) use (&$savedRequest): void {
            $savedRequest = $req;
        });

        $result = $processor->processAllScopesForm([], static::ID_FILE, $this->createFileAttachmentTransfer());

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $toAdd = $savedRequest->getFileAttachmentsToAdd()[0];
        $this->assertNull($toAdd->getBusinessUnitCollection(), 'No BU collection must be set for empty form data');
        $this->assertNull($toAdd->getSspAssetCollection(), 'No asset collection must be set for empty form data');
        $this->assertNull($toAdd->getCompanyUserCollection(), 'No CU collection must be set for empty form data');
        $this->assertNull($toAdd->getSspModelCollection(), 'No model collection must be set for empty form data');
    }

    /**
     * @param callable $onCreateAttachment
     * @param array<int, array<int>> $companyToBuMap
     *
     * @return \SprykerFeature\Zed\SelfServicePortal\Communication\CompanyFile\Processor\AttachmentProcessor
     */
    protected function createProcessor(callable $onCreateAttachment, array $companyToBuMap = []): AttachmentProcessor
    {
        $facade = $this->createMock(SelfServicePortalFacadeInterface::class);
        $facade->method('createFileAttachmentCollection')
            ->willReturnCallback(function (FileAttachmentCollectionRequestTransfer $req) use ($onCreateAttachment) {
                $onCreateAttachment($req);

                return new FileAttachmentCollectionResponseTransfer();
            });

        $repository = $this->createMock(SelfServicePortalRepositoryInterface::class);
        $repository->method('getBusinessUnitIdsForCompanies')
            ->willReturnCallback(static function (array $companyIds) use ($companyToBuMap): array {
                return $companyToBuMap[$companyIds[0]] ?? [];
            });

        return new AttachmentProcessor(
            $facade,
            $repository,
            new FormDataNormalizer(),
            new FileAttachmentMapper(),
        );
    }

    protected function createFileAttachmentTransfer(): FileAttachmentTransfer
    {
        return (new FileAttachmentTransfer())
            ->setFile((new FileTransfer())->setIdFile(static::ID_FILE));
    }
}

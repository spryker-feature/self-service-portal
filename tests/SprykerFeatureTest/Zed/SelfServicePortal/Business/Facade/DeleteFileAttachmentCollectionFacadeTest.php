<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeatureTest\Zed\SelfServicePortal\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CompanyBusinessUnitCollectionTransfer;
use Generated\Shared\Transfer\CompanyBusinessUnitTransfer;
use Generated\Shared\Transfer\CompanyUserCollectionTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\FileAttachmentCollectionRequestTransfer;
use Generated\Shared\Transfer\FileAttachmentCollectionResponseTransfer;
use Generated\Shared\Transfer\FileAttachmentTransfer;
use Generated\Shared\Transfer\FileTransfer;
use Generated\Shared\Transfer\SspAssetBusinessUnitAssignmentTransfer;
use Generated\Shared\Transfer\SspAssetCollectionTransfer;
use Generated\Shared\Transfer\SspAssetTransfer;
use Orm\Zed\FileManager\Persistence\SpyFileQuery;
use Orm\Zed\SelfServicePortal\Persistence\SpyCompanyBusinessUnitFileQuery;
use Orm\Zed\SelfServicePortal\Persistence\SpyCompanyUserFileQuery;
use Orm\Zed\SelfServicePortal\Persistence\SpySspAssetFileQuery;
use Spryker\Service\Flysystem\Plugin\FileSystem\FileSystemWriterPlugin;
use Spryker\Service\FlysystemLocalFileSystem\Plugin\Flysystem\LocalFilesystemBuilderPlugin;
use Spryker\Shared\FileSystem\FileSystemConstants;
use SprykerFeatureTest\Zed\SelfServicePortal\SelfServicePortalBusinessTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group SelfServicePortal
 * @group Business
 * @group Facade
 * @group DeleteFileAttachmentCollectionFacadeTest
 */
class DeleteFileAttachmentCollectionFacadeTest extends Unit
{
    protected const string PLUGIN_COLLECTION_FILESYSTEM_BUILDER = 'filesystem builder plugin collection';

    protected const string PLUGIN_WRITER = 'PLUGIN_WRITER';

    /**
     * @var \SprykerFeatureTest\Zed\SelfServicePortal\SelfServicePortalBusinessTester
     */
    protected SelfServicePortalBusinessTester $tester;

    protected function _before(): void
    {
        $this->tester->setDependency(static::PLUGIN_WRITER, new FileSystemWriterPlugin());
        $this->tester->setDependency(static::PLUGIN_COLLECTION_FILESYSTEM_BUILDER, [
            new LocalFilesystemBuilderPlugin(),
        ]);

        $localFilesystemBuilderConfiguration = [
            'sprykerAdapterClass' => LocalFilesystemBuilderPlugin::class,
            'root' => '/data/data/tmp/ssp-files',
            'path' => '/',
        ];

        $this->tester->setConfig(FileSystemConstants::FILESYSTEM_SERVICE, [
            's3-import' => $localFilesystemBuilderConfiguration,
            'files' => $localFilesystemBuilderConfiguration,
        ]);
    }

    public function testDeleteAllFileAttachmentsForFileIsSuccessful(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $businessUnitTransfer = $this->tester->haveCompanyBusinessUnit([
            CompanyBusinessUnitTransfer::FK_COMPANY => $companyTransfer->getIdCompany(),
            CompanyBusinessUnitTransfer::COMPANY => $companyTransfer,
        ]);
        $companyUserTransfer = $this->tester->createCompanyUser($companyTransfer);
        $companyUserTransfer->setCompanyBusinessUnit($businessUnitTransfer);

        $fileTransfer = $this->tester->haveFile();

        $this->tester->haveCompanyBusinessUnitFileAttachment([
            'idFile' => $fileTransfer->getIdFileOrFail(),
            'idCompanyBusinessUnit' => $businessUnitTransfer->getIdCompanyBusinessUnitOrFail(),
        ]);

        $this->tester->haveCompanyUserFileAttachment([
            'idFile' => $fileTransfer->getIdFileOrFail(),
            'idCompanyUser' => $companyUserTransfer->getIdCompanyUserOrFail(),
        ]);

        $sspAssetTransfer = $this->tester->haveAsset([
            SspAssetTransfer::BUSINESS_UNIT_ASSIGNMENTS => [
                [SspAssetBusinessUnitAssignmentTransfer::COMPANY_BUSINESS_UNIT => $businessUnitTransfer],
            ],
        ]);

        $this->tester->haveSspAssetFileAttachment([
            'idFile' => $fileTransfer->getIdFileOrFail(),
            'idSspAsset' => $sspAssetTransfer->getIdSspAssetOrFail(),
        ]);

        $fileAttachmentCollectionRequestTransfer = (new FileAttachmentCollectionRequestTransfer())
            ->addFileIdToDeleteAttachments($fileTransfer->getIdFileOrFail());

        $file2Transfer = $this->tester->haveFile();

        $this->tester->haveCompanyUserFileAttachment([
            'idFile' => $file2Transfer->getIdFileOrFail(),
            'idCompanyUser' => $companyUserTransfer->getIdCompanyUserOrFail(),
        ]);

        // Act
        $fileAttachmentCollectionResponseTransfer = $this->tester->getFacade()
            ->deleteFileAttachmentCollection($fileAttachmentCollectionRequestTransfer);

        // Assert
        $this->assertInstanceOf(FileAttachmentCollectionResponseTransfer::class, $fileAttachmentCollectionResponseTransfer);

        $this->assertCount(1, SpyCompanyUserFileQuery::create()->findByFkFile($file2Transfer->getIdFileOrFail()));
        $this->assertCount(0, SpyCompanyUserFileQuery::create()->findByFkFile($fileTransfer->getIdFileOrFail()));
        $this->assertCount(0, SpyCompanyBusinessUnitFileQuery::create()->findByFkFile($fileTransfer->getIdFileOrFail()));
        $this->assertCount(0, SpySspAssetFileQuery::create()->findByFkFile($fileTransfer->getIdFileOrFail()));
        $this->assertCount(1, SpyFileQuery::create()->findByIdFile($fileTransfer->getIdFileOrFail()));
    }

    public function testDeleteSpecificCompanyFileAttachmentIsSuccessful(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $businessUnitTransfer = $this->tester->haveCompanyBusinessUnit([
            CompanyBusinessUnitTransfer::FK_COMPANY => $companyTransfer->getIdCompany(),
            CompanyBusinessUnitTransfer::COMPANY => $companyTransfer,
        ]);

        $fileTransfer1 = $this->tester->haveFile();
        $fileTransfer2 = $this->tester->haveFile();

        $this->tester->haveCompanyBusinessUnitFileAttachment([
            'idFile' => $fileTransfer1->getIdFileOrFail(),
            'idCompanyBusinessUnit' => $businessUnitTransfer->getIdCompanyBusinessUnitOrFail(),
        ]);

        $this->tester->haveCompanyBusinessUnitFileAttachment([
            'idFile' => $fileTransfer2->getIdFileOrFail(),
            'idCompanyBusinessUnit' => $businessUnitTransfer->getIdCompanyBusinessUnitOrFail(),
        ]);

        $fileAttachmentTransfer = (new FileAttachmentTransfer())
            ->setFile((new FileTransfer())->setIdFile($fileTransfer1->getIdFileOrFail()))
            ->setBusinessUnitCollection(
                (new CompanyBusinessUnitCollectionTransfer())
                    ->addCompanyBusinessUnit(
                        (new CompanyBusinessUnitTransfer())
                            ->setIdCompanyBusinessUnit($businessUnitTransfer->getIdCompanyBusinessUnitOrFail()),
                    ),
            );

        $fileAttachmentCollectionRequestTransfer = (new FileAttachmentCollectionRequestTransfer())
            ->addFileAttachmentToDelete($fileAttachmentTransfer);

        // Act
        $fileAttachmentCollectionResponseTransfer = $this->tester->getFacade()
            ->deleteFileAttachmentCollection($fileAttachmentCollectionRequestTransfer);

        // Assert
        $this->assertInstanceOf(FileAttachmentCollectionResponseTransfer::class, $fileAttachmentCollectionResponseTransfer);

        $this->assertCount(0, SpyCompanyBusinessUnitFileQuery::create()->findByFkFile($fileTransfer1->getIdFileOrFail()));
        $this->assertCount(1, SpyCompanyBusinessUnitFileQuery::create()->findByFkFile($fileTransfer2->getIdFileOrFail()));
        $this->assertCount(1, SpyFileQuery::create()->findByIdFile($fileTransfer1->getIdFileOrFail()));
        $this->assertCount(1, SpyFileQuery::create()->findByIdFile($fileTransfer2->getIdFileOrFail()));
    }

    public function testDeleteSpecificCompanyUserFileAttachmentIsSuccessful(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $companyUserTransfer = $this->tester->createCompanyUser($companyTransfer);

        $fileTransfer = $this->tester->haveFile();

        $this->tester->haveCompanyUserFileAttachment([
            'idFile' => $fileTransfer->getIdFileOrFail(),
            'idCompanyUser' => $companyUserTransfer->getIdCompanyUserOrFail(),
        ]);

        $fileAttachmentTransfer = (new FileAttachmentTransfer())
            ->setFile((new FileTransfer())->setIdFile($fileTransfer->getIdFileOrFail()))
            ->setCompanyUserCollection(
                (new CompanyUserCollectionTransfer())
                    ->addCompanyUser(
                        (new CompanyUserTransfer())
                            ->setIdCompanyUser($companyUserTransfer->getIdCompanyUserOrFail()),
                    ),
            );

        $fileAttachmentCollectionRequestTransfer = (new FileAttachmentCollectionRequestTransfer())
            ->addFileAttachmentToDelete($fileAttachmentTransfer);

        // Act
        $fileAttachmentCollectionResponseTransfer = $this->tester->getFacade()
            ->deleteFileAttachmentCollection($fileAttachmentCollectionRequestTransfer);

        // Assert
        $this->assertInstanceOf(FileAttachmentCollectionResponseTransfer::class, $fileAttachmentCollectionResponseTransfer);

        $this->assertCount(0, SpyCompanyUserFileQuery::create()->findByFkFile($fileTransfer->getIdFileOrFail()));
        $this->assertCount(1, SpyFileQuery::create()->findByIdFile($fileTransfer->getIdFileOrFail()));
    }

    public function testDeleteSpecificSspAssetFileAttachmentIsSuccessful(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $businessUnitTransfer = $this->tester->haveCompanyBusinessUnit([
            CompanyBusinessUnitTransfer::FK_COMPANY => $companyTransfer->getIdCompany(),
            CompanyBusinessUnitTransfer::COMPANY => $companyTransfer,
        ]);

        $sspAssetTransfer = $this->tester->haveAsset([
            SspAssetTransfer::BUSINESS_UNIT_ASSIGNMENTS => [
                [SspAssetBusinessUnitAssignmentTransfer::COMPANY_BUSINESS_UNIT => $businessUnitTransfer],
            ],
        ]);

        $fileTransfer = $this->tester->haveFile();

        $this->tester->haveSspAssetFileAttachment([
            'idFile' => $fileTransfer->getIdFileOrFail(),
            'idSspAsset' => $sspAssetTransfer->getIdSspAssetOrFail(),
        ]);

        $fileAttachmentTransfer = (new FileAttachmentTransfer())
            ->setFile((new FileTransfer())->setIdFile($fileTransfer->getIdFileOrFail()))
            ->setSspAssetCollection(
                (new SspAssetCollectionTransfer())
                    ->addSspAsset(
                        (new SspAssetTransfer())
                            ->setIdSspAsset($sspAssetTransfer->getIdSspAssetOrFail()),
                    ),
            );

        $fileAttachmentCollectionRequestTransfer = (new FileAttachmentCollectionRequestTransfer())
            ->addFileAttachmentToDelete($fileAttachmentTransfer);

        // Act
        $fileAttachmentCollectionResponseTransfer = $this->tester->getFacade()
            ->deleteFileAttachmentCollection($fileAttachmentCollectionRequestTransfer);

        // Assert
        $this->assertInstanceOf(FileAttachmentCollectionResponseTransfer::class, $fileAttachmentCollectionResponseTransfer);

        $this->assertCount(0, SpySspAssetFileQuery::create()->findByFkFile($fileTransfer->getIdFileOrFail()));
        $this->assertCount(1, SpyFileQuery::create()->findByIdFile($fileTransfer->getIdFileOrFail()));
    }

    public function testDeleteMultipleFileAttachmentsIsSuccessful(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $businessUnitTransfer = $this->tester->haveCompanyBusinessUnit([
            CompanyBusinessUnitTransfer::FK_COMPANY => $companyTransfer->getIdCompany(),
            CompanyBusinessUnitTransfer::COMPANY => $companyTransfer,
        ]);
        $companyUserTransfer = $this->tester->createCompanyUser($companyTransfer);
        $companyUserTransfer->setCompanyBusinessUnit($businessUnitTransfer);

        $fileTransfer1 = $this->tester->haveFile();
        $fileTransfer2 = $this->tester->haveFile();

        // Create attachments
        $this->tester->haveCompanyBusinessUnitFileAttachment([
            'idFile' => $fileTransfer1->getIdFileOrFail(),
            'idCompanyBusinessUnit' => $businessUnitTransfer->getIdCompanyBusinessUnitOrFail(),
        ]);

        $this->tester->haveCompanyUserFileAttachment([
            'idFile' => $fileTransfer2->getIdFileOrFail(),
            'idCompanyUser' => $companyUserTransfer->getIdCompanyUserOrFail(),
        ]);

        $fileAttachmentTransfer1 = (new FileAttachmentTransfer())
            ->setFile((new FileTransfer())->setIdFile($fileTransfer1->getIdFileOrFail()))
            ->setBusinessUnitCollection(
                (new CompanyBusinessUnitCollectionTransfer())
                    ->addCompanyBusinessUnit(
                        (new CompanyBusinessUnitTransfer())
                            ->setIdCompanyBusinessUnit($businessUnitTransfer->getIdCompanyBusinessUnitOrFail()),
                    ),
            );

        $fileAttachmentTransfer2 = (new FileAttachmentTransfer())
            ->setFile((new FileTransfer())->setIdFile($fileTransfer2->getIdFileOrFail()))
            ->setCompanyUserCollection(
                (new CompanyUserCollectionTransfer())
                    ->addCompanyUser(
                        (new CompanyUserTransfer())
                            ->setIdCompanyUser($companyUserTransfer->getIdCompanyUserOrFail()),
                    ),
            );

        $fileAttachmentCollectionRequestTransfer = (new FileAttachmentCollectionRequestTransfer())
            ->addFileAttachmentToDelete($fileAttachmentTransfer1)
            ->addFileAttachmentToDelete($fileAttachmentTransfer2);

        // Act
        $fileAttachmentCollectionResponseTransfer = $this->tester->getFacade()
            ->deleteFileAttachmentCollection($fileAttachmentCollectionRequestTransfer);

        // Assert
        $this->assertInstanceOf(FileAttachmentCollectionResponseTransfer::class, $fileAttachmentCollectionResponseTransfer);

        $this->assertCount(0, SpyCompanyBusinessUnitFileQuery::create()->findByFkFile($fileTransfer1->getIdFileOrFail()));
        $this->assertCount(0, SpyCompanyUserFileQuery::create()->findByFkFile($fileTransfer2->getIdFileOrFail()));
        $this->assertCount(1, SpyFileQuery::create()->findByIdFile($fileTransfer1->getIdFileOrFail()));
        $this->assertCount(1, SpyFileQuery::create()->findByIdFile($fileTransfer2->getIdFileOrFail()));
    }
}

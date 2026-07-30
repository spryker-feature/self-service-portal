<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeatureTest\Zed\SelfServicePortal\Communication\Plugin\DataImport;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\DataImporterConfigurationTransfer;
use Generated\Shared\Transfer\DataImporterReaderConfigurationTransfer;
use Orm\Zed\CompanyUser\Persistence\SpyCompanyUser;
use Orm\Zed\FileManager\Persistence\SpyFile;
use Spryker\Zed\DataImport\Business\Exception\DataImportException;
use SprykerFeature\Zed\SelfServicePortal\Business\SelfServicePortalBusinessFactory;
use SprykerFeature\Zed\SelfServicePortal\Communication\Plugin\DataImport\SspFileAttachmentDataImportPlugin;
use SprykerFeature\Zed\SelfServicePortal\SelfServicePortalConfig;
use SprykerFeatureTest\Zed\SelfServicePortal\SelfServicePortalCommunicationTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group SelfServicePortal
 * @group Communication
 * @group Plugin
 * @group DataImport
 * @group SspFileAttachmentDataImportPluginTest
 */
class SspFileAttachmentDataImportPluginTest extends Unit
{
    protected const int EXPECTED_IMPORT_COUNT = 4;

    protected const string IMPORT_FILE_PATH = 'import/file_attachment.csv';

    protected const string IMPORT_FILE_PATH_INVALID_REFERENCE = 'import/file_attachment_invalid_reference.csv';

    protected const string IMPORT_FILE_PATH_INVALID_ENTITY_TYPE = 'import/file_attachment_invalid_entity_type.csv';

    protected const string FILE_REFERENCE = 'FILE-ATTACH-1';

    protected const string BUSINESS_UNIT_KEY = 'file-attach-import-bu';

    protected const string COMPANY_USER_KEY = 'file-attach-import-cu';

    protected const string ASSET_REFERENCE = 'FILE-ATTACH-ASSET--1';

    protected const string MODEL_REFERENCE = 'FILE-ATTACH-MODEL--1';

    protected SelfServicePortalCommunicationTester $tester;

    protected ?SpyFile $fileEntity = null;

    protected SpyCompanyUser $companyUserEntity;

    protected int $idCompanyBusinessUnit;

    protected int $idSspAsset;

    protected int $idSspModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tester->ensureFileAndFileAttachmentTablesAreEmpty();
        $this->removeImportEntities();

        $this->fileEntity = $this->tester->haveFileEntity([
            'file_reference' => static::FILE_REFERENCE,
            'file_name' => 'Attached File.pdf',
        ]);

        $companyTransfer = $this->tester->haveCompany();

        $companyBusinessUnitTransfer = $this->tester->haveCompanyBusinessUnit([
            'key' => static::BUSINESS_UNIT_KEY,
            'fkCompany' => $companyTransfer->getIdCompany(),
        ]);
        $this->idCompanyBusinessUnit = $companyBusinessUnitTransfer->getIdCompanyBusinessUnit();

        $customerTransfer = $this->tester->haveCustomer();
        $this->companyUserEntity = $this->tester->haveCompanyUserEntity(
            $companyTransfer->getIdCompany(),
            $customerTransfer->getIdCustomer(),
            static::COMPANY_USER_KEY,
        );

        $sspAssetTransfer = $this->tester->haveAsset([
            'reference' => static::ASSET_REFERENCE,
            'name' => 'File Attachment Test Asset',
            'status' => 'active',
        ]);
        $this->idSspAsset = $sspAssetTransfer->getIdSspAsset();

        $sspModelTransfer = $this->tester->haveSspModel([
            'reference' => static::MODEL_REFERENCE,
            'name' => 'File Attachment Test Model',
        ]);
        $this->idSspModel = $sspModelTransfer->getIdSspModel();
    }

    protected function tearDown(): void
    {
        if ($this->fileEntity !== null) {
            $this->tester->deleteFileWithAttachmentsByIdFile($this->fileEntity->getIdFile());
        }

        $this->removeImportEntities();

        parent::tearDown();
    }

    protected function removeImportEntities(): void
    {
        $this->tester->ensureFileAttachmentImportEntitiesDoNotExist(
            static::COMPANY_USER_KEY,
            static::BUSINESS_UNIT_KEY,
            static::ASSET_REFERENCE,
            static::MODEL_REFERENCE,
        );
    }

    public function testImportImportsFileAttachmentsForAllEntityTypes(): void
    {
        // Arrange
        $sspFileAttachmentDataImportPlugin = $this->createSspFileAttachmentDataImportPlugin();

        // Act
        $dataImporterReportTransfer = $sspFileAttachmentDataImportPlugin->import($this->createDataImporterConfigurationTransfer(static::IMPORT_FILE_PATH));

        // Assert
        $this->assertTrue($dataImporterReportTransfer->getIsSuccess());
        $this->assertSame(static::EXPECTED_IMPORT_COUNT, $dataImporterReportTransfer->getImportedDataSetCount());

        $idFile = $this->fileEntity->getIdFile();
        $this->assertTrue($this->tester->isCompanyBusinessUnitFileAttachmentExists($this->idCompanyBusinessUnit, $idFile));
        $this->assertTrue($this->tester->isCompanyUserFileAttachmentExists($this->companyUserEntity->getIdCompanyUser(), $idFile));
        $this->assertTrue($this->tester->isSspAssetFileAttachmentExists($this->idSspAsset, $idFile));
        $this->assertTrue($this->tester->isSspModelFileAttachmentExists($this->idSspModel, $idFile));
    }

    public function testImportIsIdempotentOnRerun(): void
    {
        // Arrange
        $sspFileAttachmentDataImportPlugin = $this->createSspFileAttachmentDataImportPlugin();
        $dataImporterConfigurationTransfer = $this->createDataImporterConfigurationTransfer(static::IMPORT_FILE_PATH);
        $sspFileAttachmentDataImportPlugin->import($dataImporterConfigurationTransfer);

        // Act
        $dataImporterReportTransfer = $sspFileAttachmentDataImportPlugin->import($dataImporterConfigurationTransfer);

        // Assert
        $this->assertTrue($dataImporterReportTransfer->getIsSuccess());
        $this->assertSame(static::EXPECTED_IMPORT_COUNT, $this->tester->getFileAttachmentTotalCount(), 'Expected no additional attachment rows after re-import');
    }

    public function testImportWithUnknownFileReferenceThrowsException(): void
    {
        // Arrange
        $sspFileAttachmentDataImportPlugin = new SspFileAttachmentDataImportPlugin();
        $dataImporterConfigurationTransfer = $this->createDataImporterConfigurationTransfer(static::IMPORT_FILE_PATH_INVALID_REFERENCE)
            ->setThrowException(true);

        // Act
        try {
            $sspFileAttachmentDataImportPlugin->import($dataImporterConfigurationTransfer);
            $this->fail(sprintf('Expected "%s" to be thrown', DataImportException::class));
        } catch (DataImportException) {
        }

        // Assert
        $this->assertSame(0, $this->tester->getFileAttachmentTotalCount(), 'Expected no attachment rows to be persisted for the failed import');
    }

    public function testImportWithUnsupportedEntityTypeThrowsException(): void
    {
        // Arrange
        $sspFileAttachmentDataImportPlugin = new SspFileAttachmentDataImportPlugin();
        $dataImporterConfigurationTransfer = $this->createDataImporterConfigurationTransfer(static::IMPORT_FILE_PATH_INVALID_ENTITY_TYPE)
            ->setThrowException(true);

        // Act
        try {
            $sspFileAttachmentDataImportPlugin->import($dataImporterConfigurationTransfer);
            $this->fail(sprintf('Expected "%s" to be thrown', DataImportException::class));
        } catch (DataImportException) {
        }

        // Assert
        $this->assertSame(0, $this->tester->getFileAttachmentTotalCount(), 'Expected no attachment rows to be persisted for the failed import');
    }

    public function testGetImportType(): void
    {
        // Arrange
        $sspFileAttachmentDataImportPlugin = new SspFileAttachmentDataImportPlugin();

        // Act
        $importType = $sspFileAttachmentDataImportPlugin->getImportType();

        // Assert
        $this->assertSame(SelfServicePortalConfig::IMPORT_TYPE_FILE_ATTACHMENT, $importType);
    }

    protected function createDataImporterConfigurationTransfer(string $importFilePath): DataImporterConfigurationTransfer
    {
        return (new DataImporterConfigurationTransfer())
            ->setImportType(SelfServicePortalConfig::IMPORT_TYPE_FILE_ATTACHMENT)
            ->setReaderConfiguration(
                (new DataImporterReaderConfigurationTransfer())->setFileName(codecept_data_dir() . $importFilePath),
            );
    }

    protected function createSspFileAttachmentDataImportPlugin(): SspFileAttachmentDataImportPlugin
    {
        $sspFileAttachmentDataImportPlugin = new SspFileAttachmentDataImportPlugin();

        $moduleNameConstant = '\Pyz\Zed\SelfServicePortal\SelfServicePortalConfig::MODULE_NAME';

        if (!defined($moduleNameConstant)) {
            return $sspFileAttachmentDataImportPlugin;
        }

        $configMock = $this->createPartialMock(SelfServicePortalConfig::class, ['getFileAttachmentDataImporterConfiguration']);
        $configMock->method('getFileAttachmentDataImporterConfiguration')
            ->willReturn(
                (new SelfServicePortalConfig())
                    ->getFileAttachmentDataImporterConfiguration()
                    ->setModuleName(
                        constant($moduleNameConstant),
                    ),
            );

        $sspFileAttachmentDataImportPlugin->setBusinessFactory(
            (new SelfServicePortalBusinessFactory())
                ->setConfig($configMock),
        );

        return $sspFileAttachmentDataImportPlugin;
    }
}

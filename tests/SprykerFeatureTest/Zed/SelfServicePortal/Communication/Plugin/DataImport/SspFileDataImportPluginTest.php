<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeatureTest\Zed\SelfServicePortal\Communication\Plugin\DataImport;

use Codeception\Test\Unit;
use FilesystemIterator;
use Generated\Shared\Transfer\DataImporterConfigurationTransfer;
use Generated\Shared\Transfer\DataImporterReaderConfigurationTransfer;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Spryker\Service\Flysystem\Plugin\FileSystem\FileSystemReaderPlugin;
use Spryker\Service\Flysystem\Plugin\FileSystem\FileSystemStreamPlugin;
use Spryker\Service\Flysystem\Plugin\FileSystem\FileSystemWriterPlugin;
use Spryker\Service\FlysystemLocalFileSystem\Plugin\Flysystem\LocalFilesystemBuilderPlugin;
use Spryker\Shared\FileSystem\FileSystemConstants;
use Spryker\Zed\DataImport\Business\Exception\DataImportException;
use SprykerFeature\Zed\SelfServicePortal\Business\SelfServicePortalBusinessFactory;
use SprykerFeature\Zed\SelfServicePortal\Communication\Plugin\DataImport\SspFileDataImportPlugin;
use SprykerFeature\Zed\SelfServicePortal\SelfServicePortalConfig;
use SprykerFeatureTest\Zed\SelfServicePortal\SelfServicePortalCommunicationTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group SelfServicePortal
 * @group Communication
 * @group Plugin
 * @group DataImport
 * @group SspFileDataImportPluginTest
 */
class SspFileDataImportPluginTest extends Unit
{
    protected const int EXPECTED_IMPORT_COUNT = 2;

    protected const string IMPORT_FILE_PATH = 'import/file.csv';

    protected const string IMPORT_FILE_PATH_INVALID = 'import/file_invalid_path.csv';

    protected const string IMPORT_FILE_PATH_MISSING_MIME_TYPE = 'import/file_missing_mime_type.csv';

    protected const string PLUGIN_COLLECTION_FILESYSTEM_BUILDER = 'filesystem builder plugin collection';

    protected const string PLUGIN_WRITER = 'PLUGIN_WRITER';

    protected const string PLUGIN_STREAM = 'PLUGIN_STREAM';

    protected const string PLUGIN_READER = 'PLUGIN_READER';

    protected const string FILE_REFERENCE_1 = 'FILE-IMPORT-1';

    protected const string FILE_REFERENCE_2 = 'FILE-IMPORT-2';

    protected const string FILE_REFERENCE_INVALID_PATH = 'FILE-IMPORT-3';

    protected const string FILE_REFERENCE_MISSING_MIME_TYPE = 'FILE-IMPORT-4';

    protected SelfServicePortalCommunicationTester $tester;

    protected string $fileSystemRootDirectory = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->tester->ensureFileAndFileAttachmentTablesAreEmpty();

        $this->tester->setDependency(static::PLUGIN_WRITER, new FileSystemWriterPlugin());
        $this->tester->setDependency(static::PLUGIN_STREAM, new FileSystemStreamPlugin());
        $this->tester->setDependency(static::PLUGIN_READER, new FileSystemReaderPlugin());
        $this->tester->setDependency(static::PLUGIN_COLLECTION_FILESYSTEM_BUILDER, [
            new LocalFilesystemBuilderPlugin(),
        ]);

        $this->fileSystemRootDirectory = sprintf('%s%s%s', sys_get_temp_dir(), DIRECTORY_SEPARATOR, uniqid('ssp-files-test-', true));

        $localFilesystemBuilderConfiguration = [
            'sprykerAdapterClass' => LocalFilesystemBuilderPlugin::class,
            'root' => $this->fileSystemRootDirectory,
            'path' => '/',
        ];

        $this->tester->setConfig(FileSystemConstants::FILESYSTEM_SERVICE, [
            'files' => $localFilesystemBuilderConfiguration,
            'ssp-files' => $localFilesystemBuilderConfiguration,
            'import-files' => [
                'sprykerAdapterClass' => LocalFilesystemBuilderPlugin::class,
                'root' => codecept_data_dir('import'),
                'path' => '/',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        $this->removeFileSystemRootDirectory();

        parent::tearDown();
    }

    protected function removeFileSystemRootDirectory(): void
    {
        if ($this->fileSystemRootDirectory === '' || !is_dir($this->fileSystemRootDirectory)) {
            return;
        }

        $fileInfos = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->fileSystemRootDirectory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @var \SplFileInfo $fileInfo */
        foreach ($fileInfos as $fileInfo) {
            $fileInfo->isDir() ? rmdir($fileInfo->getPathname()) : unlink($fileInfo->getPathname());
        }

        rmdir($this->fileSystemRootDirectory);
    }

    public function testImportImportsFiles(): void
    {
        // Arrange
        $sspFileDataImportPlugin = $this->createSspFileDataImportPlugin();

        // Act
        $dataImporterReportTransfer = $sspFileDataImportPlugin->import($this->createDataImporterConfigurationTransfer(static::IMPORT_FILE_PATH));

        // Assert
        $this->assertTrue($dataImporterReportTransfer->getIsSuccess());
        $this->assertSame(static::EXPECTED_IMPORT_COUNT, $dataImporterReportTransfer->getImportedDataSetCount());

        $fileEntity1 = $this->tester->findFileEntityByFileReference(static::FILE_REFERENCE_1);
        $fileEntity2 = $this->tester->findFileEntityByFileReference(static::FILE_REFERENCE_2);

        $this->assertNotNull($fileEntity1, 'Expected imported file to exist');
        $this->assertNotNull($fileEntity2, 'Expected imported file to exist');
        $this->assertSame(1, $this->tester->getFileInfoCountByIdFile($fileEntity1->getIdFile()));
        $this->assertSame(1, $this->tester->getFileInfoCountByIdFile($fileEntity2->getIdFile()));
    }

    public function testImportIsIdempotentOnRerun(): void
    {
        // Arrange
        $sspFileDataImportPlugin = $this->createSspFileDataImportPlugin();
        $dataImporterConfigurationTransfer = $this->createDataImporterConfigurationTransfer(static::IMPORT_FILE_PATH);
        $sspFileDataImportPlugin->import($dataImporterConfigurationTransfer);

        // Act
        $dataImporterReportTransfer = $sspFileDataImportPlugin->import($dataImporterConfigurationTransfer);

        // Assert
        $this->assertTrue($dataImporterReportTransfer->getIsSuccess());

        $fileEntity = $this->tester->findFileEntityByFileReference(static::FILE_REFERENCE_1);
        $this->assertNotNull($fileEntity);
        $this->assertSame(1, $this->tester->getFileInfoCountByIdFile($fileEntity->getIdFile()), 'Expected no additional file info versions after re-import');
    }

    public function testImportWithInvalidPathThrowsException(): void
    {
        // Arrange
        $sspFileDataImportPlugin = $this->createSspFileDataImportPlugin();
        $dataImporterConfigurationTransfer = $this->createDataImporterConfigurationTransfer(static::IMPORT_FILE_PATH_INVALID)
            ->setThrowException(true);

        // Act
        try {
            $sspFileDataImportPlugin->import($dataImporterConfigurationTransfer);
            $this->fail(sprintf('Expected "%s" to be thrown', DataImportException::class));
        } catch (DataImportException) {
        }

        // Assert
        $this->assertNull(
            $this->tester->findFileEntityByFileReference(static::FILE_REFERENCE_INVALID_PATH),
            'Expected no file entity to be persisted for the failed import',
        );
    }

    public function testImportWithMissingMimeTypeThrowsException(): void
    {
        // Arrange
        $sspFileDataImportPlugin = $this->createSspFileDataImportPlugin();
        $dataImporterConfigurationTransfer = $this->createDataImporterConfigurationTransfer(static::IMPORT_FILE_PATH_MISSING_MIME_TYPE)
            ->setThrowException(true);

        // Act
        try {
            $sspFileDataImportPlugin->import($dataImporterConfigurationTransfer);
            $this->fail(sprintf('Expected "%s" to be thrown', DataImportException::class));
        } catch (DataImportException) {
        }

        // Assert
        $this->assertNull(
            $this->tester->findFileEntityByFileReference(static::FILE_REFERENCE_MISSING_MIME_TYPE),
            'Expected no file entity to be persisted for the failed import',
        );
    }

    public function testGetImportType(): void
    {
        // Arrange
        $sspFileDataImportPlugin = new SspFileDataImportPlugin();

        // Act
        $importType = $sspFileDataImportPlugin->getImportType();

        // Assert
        $this->assertSame(SelfServicePortalConfig::IMPORT_TYPE_FILE, $importType);
    }

    protected function createDataImporterConfigurationTransfer(string $importFilePath): DataImporterConfigurationTransfer
    {
        return (new DataImporterConfigurationTransfer())
            ->setImportType(SelfServicePortalConfig::IMPORT_TYPE_FILE)
            ->setThrowException(true)
            ->setReaderConfiguration(
                (new DataImporterReaderConfigurationTransfer())->setFileName(codecept_data_dir() . $importFilePath),
            );
    }

    protected function createSspFileDataImportPlugin(): SspFileDataImportPlugin
    {
        $sspFileDataImportPlugin = new SspFileDataImportPlugin();

        $moduleNameConstant = '\Pyz\Zed\SelfServicePortal\SelfServicePortalConfig::MODULE_NAME';

        if (!defined($moduleNameConstant)) {
            return $sspFileDataImportPlugin;
        }

        $configMock = $this->createPartialMock(SelfServicePortalConfig::class, ['getFileDataImporterConfiguration']);
        $configMock->method('getFileDataImporterConfiguration')
            ->willReturn(
                (new SelfServicePortalConfig())
                    ->getFileDataImporterConfiguration()
                    ->setModuleName(
                        constant($moduleNameConstant),
                    ),
            );

        $sspFileDataImportPlugin->setBusinessFactory(
            (new SelfServicePortalBusinessFactory())
                ->setConfig($configMock),
        );

        return $sspFileDataImportPlugin;
    }
}

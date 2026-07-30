<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Business\CompanyFile\DataImport\Step;

use finfo;
use Generated\Shared\Transfer\FileSystemQueryTransfer;
use Orm\Zed\FileManager\Persistence\SpyFileQuery;
use Spryker\Service\FileSystem\FileSystemServiceInterface;
use Spryker\Service\FileSystemExtension\Dependency\Exception\FileSystemReadException;
use Spryker\Zed\DataImport\Business\Exception\InvalidDataException;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\DataImportStepInterface;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;
use SprykerFeature\Zed\SelfServicePortal\Business\CompanyFile\DataImport\DataSet\FileDataSetInterface;
use SprykerFeature\Zed\SelfServicePortal\SelfServicePortalConfig;

class FileContentValidationStep implements DataImportStepInterface
{
    /**
     * @var list<string>
     */
    protected const REQUIRED_COLUMNS = [
        FileDataSetInterface::COLUMN_FILE_REFERENCE,
        FileDataSetInterface::COLUMN_FILE_NAME,
        FileDataSetInterface::COLUMN_PATH,
        FileDataSetInterface::COLUMN_MIME_TYPE,
        FileDataSetInterface::COLUMN_EXTENSION,
    ];

    public function __construct(
        protected SelfServicePortalConfig $config,
        protected FileSystemServiceInterface $fileSystemService
    ) {
    }

    public function execute(DataSetInterface $dataSet): void
    {
        $this->validateRequiredColumns($dataSet);
        $this->validateFileExtension($dataSet);
        $this->validateDeclaredMimeType($dataSet);

        $fileEntity = SpyFileQuery::create()
            ->clear()
            ->findOneByFileReference($dataSet[FileDataSetInterface::COLUMN_FILE_REFERENCE]);

        if ($fileEntity !== null) {
            $dataSet[FileDataSetInterface::ID_FILE] = $fileEntity->getIdFile();

            return;
        }

        $this->validateFileSize($dataSet[FileDataSetInterface::COLUMN_PATH]);

        $content = $this->readContentFromFileSystem($dataSet[FileDataSetInterface::COLUMN_PATH]);

        $this->validateContentMimeType($content, $dataSet[FileDataSetInterface::COLUMN_PATH]);

        $dataSet[FileDataSetInterface::CONTENT] = $content;
    }

    protected function validateRequiredColumns(DataSetInterface $dataSet): void
    {
        foreach (static::REQUIRED_COLUMNS as $column) {
            if (empty($dataSet[$column])) {
                throw new InvalidDataException(sprintf('"%s" is required', $column));
            }
        }
    }

    protected function validateFileExtension(DataSetInterface $dataSet): void
    {
        $allowedFileExtensions = $this->config->getCompanyFileAllowedFileExtensions();
        $extension = sprintf('.%s', ltrim(strtolower($dataSet[FileDataSetInterface::COLUMN_EXTENSION]), '.'));

        if (!in_array($extension, $allowedFileExtensions, true)) {
            throw new InvalidDataException(sprintf(
                'File extension "%s" is not allowed. Allowed extensions: %s',
                $dataSet[FileDataSetInterface::COLUMN_EXTENSION],
                implode(', ', $allowedFileExtensions),
            ));
        }
    }

    protected function validateDeclaredMimeType(DataSetInterface $dataSet): void
    {
        $allowedMimeTypes = $this->config->getCompanyFileAllowedMimeTypes();
        $mimeType = strtolower($dataSet[FileDataSetInterface::COLUMN_MIME_TYPE]);

        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            throw new InvalidDataException(sprintf(
                'MIME type "%s" is not allowed. Allowed MIME types: %s',
                $dataSet[FileDataSetInterface::COLUMN_MIME_TYPE],
                implode(', ', $allowedMimeTypes),
            ));
        }
    }

    protected function validateContentMimeType(string $content, string $path): void
    {
        $allowedMimeTypes = $this->config->getCompanyFileAllowedMimeTypes();
        $detectedMimeType = (string)(new finfo(FILEINFO_MIME_TYPE))->buffer($content);

        if (!in_array($detectedMimeType, $allowedMimeTypes, true)) {
            throw new InvalidDataException(sprintf(
                'File "%s" content is detected as "%s" which is not allowed. Allowed MIME types: %s',
                $path,
                $detectedMimeType,
                implode(', ', $allowedMimeTypes),
            ));
        }
    }

    protected function validateFileSize(string $path): void
    {
        $fileSystemName = $this->config->getFileImportFileSystemName();

        $fileSystemQueryTransfer = (new FileSystemQueryTransfer())
            ->setFileSystemName($fileSystemName)
            ->setPath($path);

        try {
            $sizeInBytes = $this->fileSystemService->getSize($fileSystemQueryTransfer);
        } catch (FileSystemReadException $fileSystemReadException) {
            throw new InvalidDataException(
                sprintf('File "%s" could not be read from file system "%s"', $path, $fileSystemName),
                0,
                $fileSystemReadException,
            );
        }

        $maxFileSize = $this->config->getCompanyFileMaxFileSize();

        if ($sizeInBytes > $this->convertToBytes($maxFileSize)) {
            throw new InvalidDataException(
                sprintf('File "%s" exceeds the maximum allowed size of %s', $path, $maxFileSize),
            );
        }
    }

    protected function convertToBytes(string $size): int
    {
        $unit = strtolower(substr(trim($size), -1));
        $bytes = (int)$size;

        return match ($unit) {
            'g' => $bytes * 1024 * 1024 * 1024,
            'm' => $bytes * 1024 * 1024,
            'k' => $bytes * 1024,
            default => $bytes,
        };
    }

    /**
     * @throws \Spryker\Zed\DataImport\Business\Exception\InvalidDataException
     */
    protected function readContentFromFileSystem(string $path): string
    {
        $fileSystemName = $this->config->getFileImportFileSystemName();

        $fileSystemQueryTransfer = (new FileSystemQueryTransfer())
            ->setFileSystemName($fileSystemName)
            ->setPath($path);

        try {
            return $this->fileSystemService->read($fileSystemQueryTransfer);
        } catch (FileSystemReadException $fileSystemReadException) {
            throw new InvalidDataException(
                sprintf('File "%s" could not be read from file system "%s"', $path, $fileSystemName),
                0,
                $fileSystemReadException,
            );
        }
    }
}

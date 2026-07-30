<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Business\CompanyFile\DataImport\Step;

use Generated\Shared\Transfer\FileInfoTransfer;
use Generated\Shared\Transfer\FileManagerDataTransfer;
use Generated\Shared\Transfer\FileTransfer;
use Generated\Shared\Transfer\SequenceNumberSettingsTransfer;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\DataImportStepInterface;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;
use Spryker\Zed\FileManager\Business\FileManagerFacadeInterface;
use Spryker\Zed\SequenceNumber\Business\SequenceNumberFacadeInterface;
use SprykerFeature\Zed\SelfServicePortal\Business\CompanyFile\DataImport\DataSet\FileDataSetInterface;
use SprykerFeature\Zed\SelfServicePortal\SelfServicePortalConfig;

class FileWriterStep implements DataImportStepInterface
{
    public function __construct(
        protected SelfServicePortalConfig $config,
        protected FileManagerFacadeInterface $fileManagerFacade,
        protected SequenceNumberFacadeInterface $sequenceNumberFacade
    ) {
    }

    public function execute(DataSetInterface $dataSet): void
    {
        if (isset($dataSet[FileDataSetInterface::ID_FILE])) {
            return;
        }

        $fileManagerDataTransfer = $this->fileManagerFacade->saveFile(
            $this->createFileManagerDataTransfer($dataSet),
        );

        // Is needed to generate a unique sequence number for the file placed into spy_sequence_number DB table.
        $this->sequenceNumberFacade->generate(
            (new SequenceNumberSettingsTransfer())
                ->setName($this->config->getCompanyFileSequenceNumberName())
                ->setPrefix($this->config->getCompanyFileSequenceNumberPrefix()),
        );

        $dataSet[FileDataSetInterface::ID_FILE] = $fileManagerDataTransfer->getFileOrFail()->getIdFileOrFail();
    }

    protected function createFileManagerDataTransfer(DataSetInterface $dataSet): FileManagerDataTransfer
    {
        $content = $dataSet[FileDataSetInterface::CONTENT];

        return (new FileManagerDataTransfer())
            ->setFile(
                (new FileTransfer())
                    ->setFileName($dataSet[FileDataSetInterface::COLUMN_FILE_NAME])
                    ->setFileReference($dataSet[FileDataSetInterface::COLUMN_FILE_REFERENCE]),
            )
            ->setFileInfo(
                (new FileInfoTransfer())
                    ->setType($dataSet[FileDataSetInterface::COLUMN_MIME_TYPE])
                    ->setExtension($dataSet[FileDataSetInterface::COLUMN_EXTENSION])
                    ->setSize(strlen($content))
                    ->setStorageName($this->config->getCompanyFileUploadStorageName()),
            )
            ->setContent($content);
    }
}

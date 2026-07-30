<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Business\CompanyFile\DataImport\Step;

use Orm\Zed\FileManager\Persistence\Map\SpyFileTableMap;
use Orm\Zed\FileManager\Persistence\SpyFileQuery;
use Spryker\Zed\DataImport\Business\Exception\InvalidDataException;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\DataImportStepInterface;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;
use SprykerFeature\Zed\SelfServicePortal\Business\CompanyFile\DataImport\DataSet\FileAttachmentDataSetInterface;

class FileReferenceToIdFileStep implements DataImportStepInterface
{
    /**
     * @var array<string, int>
     */
    protected array $idFileCache = [];

    public function execute(DataSetInterface $dataSet): void
    {
        $fileReference = $dataSet[FileAttachmentDataSetInterface::COLUMN_FILE_REFERENCE] ?? '';

        $dataSet[FileAttachmentDataSetInterface::ID_FILE] = $this->getIdFileByFileReference($fileReference);
    }

    /**
     * @throws \Spryker\Zed\DataImport\Business\Exception\InvalidDataException
     */
    protected function getIdFileByFileReference(string $fileReference): int
    {
        if (isset($this->idFileCache[$fileReference])) {
            return $this->idFileCache[$fileReference];
        }

        /** @var int|null $idFile */
        $idFile = SpyFileQuery::create()
            ->select(SpyFileTableMap::COL_ID_FILE)
            ->findOneByFileReference($fileReference);

        if (!$idFile) {
            throw new InvalidDataException(sprintf('File with reference "%s" does not exist', $fileReference));
        }

        $this->idFileCache[$fileReference] = $idFile;

        return $idFile;
    }
}

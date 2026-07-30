<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Business\CompanyFile\DataImport\Step;

use Orm\Zed\SelfServicePortal\Persistence\SpyCompanyBusinessUnitFile;
use Orm\Zed\SelfServicePortal\Persistence\SpyCompanyBusinessUnitFileQuery;
use Orm\Zed\SelfServicePortal\Persistence\SpyCompanyUserFile;
use Orm\Zed\SelfServicePortal\Persistence\SpyCompanyUserFileQuery;
use Orm\Zed\SelfServicePortal\Persistence\SpySspAssetFile;
use Orm\Zed\SelfServicePortal\Persistence\SpySspAssetFileQuery;
use Orm\Zed\SelfServicePortal\Persistence\SpySspModelToFile;
use Orm\Zed\SelfServicePortal\Persistence\SpySspModelToFileQuery;
use Spryker\Zed\DataImport\Business\Exception\InvalidDataException;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\DataImportStepInterface;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;
use SprykerFeature\Zed\SelfServicePortal\Business\CompanyFile\DataImport\DataSet\FileAttachmentDataSetInterface;
use SprykerFeature\Zed\SelfServicePortal\SelfServicePortalConfig;

class FileAttachmentWriterStep implements DataImportStepInterface
{
    public function execute(DataSetInterface $dataSet): void
    {
        $fileAttachmentEntity = $this->findOrCreateFileAttachmentEntity(
            $dataSet[FileAttachmentDataSetInterface::COLUMN_ENTITY_TYPE],
            $dataSet[FileAttachmentDataSetInterface::ID_ENTITY],
            $dataSet[FileAttachmentDataSetInterface::ID_FILE],
        );

        if ($fileAttachmentEntity->isNew()) {
            $fileAttachmentEntity->save();
        }
    }

    /**
     * @throws \Spryker\Zed\DataImport\Business\Exception\InvalidDataException
     */
    protected function findOrCreateFileAttachmentEntity(
        string $entityType,
        int $idEntity,
        int $idFile
    ): SpyCompanyBusinessUnitFile|SpyCompanyUserFile|SpySspAssetFile|SpySspModelToFile {
        return match ($entityType) {
            SelfServicePortalConfig::ENTITY_TYPE_COMPANY_BUSINESS_UNIT => SpyCompanyBusinessUnitFileQuery::create()
                ->clear()
                ->filterByFkCompanyBusinessUnit($idEntity)
                ->filterByFkFile($idFile)
                ->findOneOrCreate(),
            SelfServicePortalConfig::ENTITY_TYPE_COMPANY_USER => SpyCompanyUserFileQuery::create()
                ->clear()
                ->filterByFkCompanyUser($idEntity)
                ->filterByFkFile($idFile)
                ->findOneOrCreate(),
            SelfServicePortalConfig::ENTITY_TYPE_SSP_ASSET => SpySspAssetFileQuery::create()
                ->clear()
                ->filterByFkSspAsset($idEntity)
                ->filterByFkFile($idFile)
                ->findOneOrCreate(),
            SelfServicePortalConfig::ENTITY_TYPE_SSP_MODEL => SpySspModelToFileQuery::create()
                ->clear()
                ->filterByFkSspModel($idEntity)
                ->filterByFkFile($idFile)
                ->findOneOrCreate(),
            default => throw new InvalidDataException(sprintf('Unsupported entity type "%s"', $entityType)),
        };
    }
}

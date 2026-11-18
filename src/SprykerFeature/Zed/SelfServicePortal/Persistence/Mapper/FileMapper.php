<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Persistence\Mapper;

use Generated\Shared\Transfer\CompanyBusinessUnitCollectionTransfer;
use Generated\Shared\Transfer\CompanyBusinessUnitTransfer;
use Generated\Shared\Transfer\CompanyCollectionTransfer;
use Generated\Shared\Transfer\CompanyTransfer;
use Generated\Shared\Transfer\CompanyUserCollectionTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\FileAttachmentCollectionTransfer;
use Generated\Shared\Transfer\FileAttachmentTransfer;
use Generated\Shared\Transfer\FileInfoTransfer;
use Generated\Shared\Transfer\FileTransfer;
use Generated\Shared\Transfer\SspAssetCollectionTransfer;
use Generated\Shared\Transfer\SspAssetTransfer;
use Generated\Shared\Transfer\SspModelCollectionTransfer;
use Generated\Shared\Transfer\SspModelTransfer;
use Orm\Zed\FileManager\Persistence\SpyFile;
use Propel\Runtime\Collection\Collection;
use SprykerFeature\Zed\SelfServicePortal\Persistence\QueryBuilder\FileAttachmentQueryBuilder;

class FileMapper
{
    /**
     * @param \Propel\Runtime\Collection\Collection<\Orm\Zed\FileManager\Persistence\SpyFile> $fileEntities
     */
    public function mapFileEntityCollectionToFileAttachmentCollectionTransfer(
        Collection $fileEntities,
        FileAttachmentCollectionTransfer $fileCollectionTransfer
    ): FileAttachmentCollectionTransfer {
        /** @var \Orm\Zed\FileManager\Persistence\SpyFile $fileEntity */
        foreach ($fileEntities as $fileEntity) {
            $fileCollectionTransfer->addFileAttachment($this->mapFileEntityToFileAttachmentTransfer($fileEntity));
        }

        return $fileCollectionTransfer;
    }

    protected function mapFileEntityToFileAttachmentTransfer(SpyFile $fileEntity): FileAttachmentTransfer
    {
        $fileTransfer = $this->mapFileEntityToFileTransfer($fileEntity);
        $fileTransfer = $this->addFileInfoTransfers($fileEntity, $fileTransfer);

        $fileAttachmentTransfer = (new FileAttachmentTransfer())->setFile($fileTransfer);

        $fileAttachmentTransfer = $this->initEmptyCollections($fileAttachmentTransfer);

        $this->addSspAssets($fileEntity, $fileAttachmentTransfer);
        $this->addCompanyUsers($fileEntity, $fileAttachmentTransfer);
        $this->addBusinessUnits($fileEntity, $fileAttachmentTransfer);
        $this->addCompanies($fileEntity, $fileAttachmentTransfer);
        $this->addSspModels($fileEntity, $fileAttachmentTransfer);

        return $fileAttachmentTransfer;
    }

    protected function addSspAssets(SpyFile $fileEntity, FileAttachmentTransfer $fileAttachmentTransfer): void
    {
        if (!$fileEntity->hasVirtualColumn(FileAttachmentQueryBuilder::SSP_ASSET_IDS_COLUMN)) {
            return;
        }

        foreach ($this->extractRelationIds($fileEntity->getVirtualColumn(FileAttachmentQueryBuilder::SSP_ASSET_IDS_COLUMN)) as $sspAssetId) {
            $fileAttachmentTransfer->getSspAssetCollectionOrFail()->addSspAsset(
                (new SspAssetTransfer())->setIdSspAsset((int)$sspAssetId),
            );
        }
    }

    protected function addCompanyUsers(SpyFile $fileEntity, FileAttachmentTransfer $fileAttachmentTransfer): void
    {
        if (!$fileEntity->hasVirtualColumn(FileAttachmentQueryBuilder::COMPANY_USER_IDS_COLUMN)) {
            return;
        }

        foreach ($this->extractRelationIds($fileEntity->getVirtualColumn(FileAttachmentQueryBuilder::COMPANY_USER_IDS_COLUMN)) as $companyUserId) {
            $fileAttachmentTransfer->getCompanyUserCollectionOrFail()->addCompanyUser(
                (new CompanyUserTransfer())->setIdCompanyUser((int)$companyUserId),
            );
        }
    }

    protected function addBusinessUnits(SpyFile $fileEntity, FileAttachmentTransfer $fileAttachmentTransfer): void
    {
        if (!$fileEntity->hasVirtualColumn(FileAttachmentQueryBuilder::BUSINESS_UNIT_IDS_COLUMN)) {
            return;
        }

        foreach ($this->extractRelationIds($fileEntity->getVirtualColumn(FileAttachmentQueryBuilder::BUSINESS_UNIT_IDS_COLUMN)) as $businessUnitId) {
            $fileAttachmentTransfer->getBusinessUnitCollectionOrFail()->addCompanyBusinessUnit(
                (new CompanyBusinessUnitTransfer())->setIdCompanyBusinessUnit((int)$businessUnitId),
            );
        }
    }

    protected function addCompanies(SpyFile $fileEntity, FileAttachmentTransfer $fileAttachmentTransfer): void
    {
        if (!$fileEntity->hasVirtualColumn(FileAttachmentQueryBuilder::COMPANY_IDS_COLUMN)) {
            return;
        }

        foreach ($this->extractRelationIds($fileEntity->getVirtualColumn(FileAttachmentQueryBuilder::COMPANY_IDS_COLUMN)) as $companyId) {
            $fileAttachmentTransfer->getCompanyCollectionOrFail()->addCompany(
                (new CompanyTransfer())->setIdCompany((int)$companyId),
            );
        }
    }

    protected function addSspModels(SpyFile $fileEntity, FileAttachmentTransfer $fileAttachmentTransfer): void
    {
        if (!$fileEntity->hasVirtualColumn(FileAttachmentQueryBuilder::SSP_MODEL_IDS_COLUMN)) {
            return;
        }

        foreach ($this->extractRelationIds($fileEntity->getVirtualColumn(FileAttachmentQueryBuilder::SSP_MODEL_IDS_COLUMN)) as $modelId) {
            $fileAttachmentTransfer->getSspModelCollectionOrFail()->addSspModel(
                (new SspModelTransfer())->setIdSspModel((int)$modelId),
            );
        }
    }

    protected function mapFileEntityToFileTransfer(SpyFile $fileEntity): FileTransfer
    {
        return (new FileTransfer())
            ->fromArray($fileEntity->toArray(), true);
    }

    protected function addFileInfoTransfers(SpyFile $file, FileTransfer $fileTransfer): FileTransfer
    {
        foreach ($file->getSpyFileInfos() as $fileInfo) {
            $fileTransfer->addFileInfo(
                (new FileInfoTransfer())->fromArray(
                    $fileInfo->toArray(),
                    true,
                ),
            );
        }

        return $fileTransfer;
    }

    /**
     * @return array<int|string>
     */
    protected function extractRelationIds(?string $relationIdsString): array
    {
        if (!$relationIdsString) {
            return [];
        }

        return array_unique(explode(',', $relationIdsString));
    }

    protected function initEmptyCollections(FileAttachmentTransfer $fileAttachmentTransfer): FileAttachmentTransfer
    {
        return $fileAttachmentTransfer
            ->setSspAssetCollection(new SspAssetCollectionTransfer())
            ->setCompanyUserCollection(new CompanyUserCollectionTransfer())
            ->setBusinessUnitCollection(new CompanyBusinessUnitCollectionTransfer())
            ->setCompanyCollection(new CompanyCollectionTransfer())
            ->setSspModelCollection(new SspModelCollectionTransfer());
    }
}

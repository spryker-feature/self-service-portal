<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Business\CompanyFile\DataImport\Step;

use Orm\Zed\CompanyBusinessUnit\Persistence\Map\SpyCompanyBusinessUnitTableMap;
use Orm\Zed\CompanyBusinessUnit\Persistence\SpyCompanyBusinessUnitQuery;
use Orm\Zed\CompanyUser\Persistence\Map\SpyCompanyUserTableMap;
use Orm\Zed\CompanyUser\Persistence\SpyCompanyUserQuery;
use Orm\Zed\SelfServicePortal\Persistence\Map\SpySspAssetTableMap;
use Orm\Zed\SelfServicePortal\Persistence\Map\SpySspModelTableMap;
use Orm\Zed\SelfServicePortal\Persistence\SpySspAssetQuery;
use Orm\Zed\SelfServicePortal\Persistence\SpySspModelQuery;
use Spryker\Zed\DataImport\Business\Exception\InvalidDataException;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\DataImportStepInterface;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;
use SprykerFeature\Zed\SelfServicePortal\Business\CompanyFile\DataImport\DataSet\FileAttachmentDataSetInterface;
use SprykerFeature\Zed\SelfServicePortal\SelfServicePortalConfig;

class FileAttachmentEntityKeyToIdEntityStep implements DataImportStepInterface
{
    /**
     * @var array<string, array<string, int>>
     */
    protected array $idEntityCachesByEntityType = [];

    public function execute(DataSetInterface $dataSet): void
    {
        $entityType = $dataSet[FileAttachmentDataSetInterface::COLUMN_ENTITY_TYPE] ?? '';
        $entityKey = $dataSet[FileAttachmentDataSetInterface::COLUMN_ENTITY_KEY] ?? '';

        $dataSet[FileAttachmentDataSetInterface::ID_ENTITY] = $this->getIdEntity($entityType, $entityKey);
    }

    /**
     * @throws \Spryker\Zed\DataImport\Business\Exception\InvalidDataException
     */
    protected function getIdEntity(string $entityType, string $entityKey): int
    {
        if (isset($this->idEntityCachesByEntityType[$entityType][$entityKey])) {
            return $this->idEntityCachesByEntityType[$entityType][$entityKey];
        }

        $idEntity = $this->findIdEntity($entityType, $entityKey);

        if (!$idEntity) {
            throw new InvalidDataException(sprintf('Entity of type "%s" with key "%s" does not exist', $entityType, $entityKey));
        }

        $this->idEntityCachesByEntityType[$entityType][$entityKey] = $idEntity;

        return $idEntity;
    }

    protected function findIdEntity(string $entityType, string $entityKey): ?int
    {
        return match ($entityType) {
            SelfServicePortalConfig::ENTITY_TYPE_COMPANY_BUSINESS_UNIT => SpyCompanyBusinessUnitQuery::create()
                ->select(SpyCompanyBusinessUnitTableMap::COL_ID_COMPANY_BUSINESS_UNIT)
                ->findOneByKey($entityKey),
            SelfServicePortalConfig::ENTITY_TYPE_COMPANY_USER => SpyCompanyUserQuery::create()
                ->select(SpyCompanyUserTableMap::COL_ID_COMPANY_USER)
                ->findOneByKey($entityKey),
            SelfServicePortalConfig::ENTITY_TYPE_SSP_ASSET => SpySspAssetQuery::create()
                ->select(SpySspAssetTableMap::COL_ID_SSP_ASSET)
                ->findOneByReference($entityKey),
            SelfServicePortalConfig::ENTITY_TYPE_SSP_MODEL => SpySspModelQuery::create()
                ->select(SpySspModelTableMap::COL_ID_SSP_MODEL)
                ->findOneByReference($entityKey),
            default => throw new InvalidDataException(sprintf('Unsupported entity type "%s"', $entityType)),
        };
    }
}

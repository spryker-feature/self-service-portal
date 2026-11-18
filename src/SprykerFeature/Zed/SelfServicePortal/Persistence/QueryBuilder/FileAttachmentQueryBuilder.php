<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Persistence\QueryBuilder;

use ArrayObject;
use Generated\Shared\Transfer\FileAttachmentConditionsTransfer;
use Generated\Shared\Transfer\FileAttachmentCriteriaTransfer;
use Orm\Zed\FileManager\Persistence\Map\SpyFileInfoTableMap;
use Orm\Zed\FileManager\Persistence\SpyFileQuery;
use Orm\Zed\SelfServicePortal\Persistence\Map\SpyCompanyBusinessUnitFileTableMap;
use Orm\Zed\SelfServicePortal\Persistence\Map\SpyCompanyUserFileTableMap;
use Orm\Zed\SelfServicePortal\Persistence\Map\SpySspAssetFileTableMap;
use Orm\Zed\SelfServicePortal\Persistence\Map\SpySspModelToFileTableMap;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion;
use Propel\Runtime\ActiveQuery\ModelCriteria;

class FileAttachmentQueryBuilder
{
    /**
     * @var string
     */
    public const BUSINESS_UNIT_IDS_COLUMN = 'businessUnitIds';

    /**
     * @var string
     */
    public const COMPANY_IDS_COLUMN = 'companyIds';

    /**
     * @var string
     */
    public const COMPANY_USER_IDS_COLUMN = 'companyUserIds';

    /**
     * @var string
     */
    public const SSP_ASSET_IDS_COLUMN = 'sspAssetIds';

    /**
     * @var string
     */
    public const SSP_MODEL_IDS_COLUMN = 'sspModelIds';

    public function __construct(protected FileAttachmentRelationCriteriaBuilderInterface $fileAttachmentRelationCriteriaBuilder)
    {
    }

    public function applyCriteria(SpyFileQuery $fileQuery, FileAttachmentCriteriaTransfer $fileAttachmentCriteriaTransfer): SpyFileQuery
    {
        $fileQuery = $this->applyFileIdFilter($fileQuery, $fileAttachmentCriteriaTransfer);
        $fileQuery = $this->applyFileAttachmentUuidFilter($fileQuery, $fileAttachmentCriteriaTransfer);
        $fileQuery = $this->addRelationsToAQuery($fileQuery, $fileAttachmentCriteriaTransfer);
        $fileQuery = $this->applyFileAttachmentSearch($fileQuery, $fileAttachmentCriteriaTransfer);
        $fileQuery = $this->applyFileAttachmentTypeFilter($fileQuery, $fileAttachmentCriteriaTransfer);
        $fileQuery = $this->applyFileAttachmentDateRangeFilter($fileQuery, $fileAttachmentCriteriaTransfer);

        /** @var \ArrayObject<array-key, \Generated\Shared\Transfer\SortTransfer> $sortTransfers */
        $sortTransfers = $fileAttachmentCriteriaTransfer->getSortCollection();
        $this->applyFileAttachmentSorting($fileQuery, $sortTransfers);

        return $fileQuery;
    }

    protected function addRelationsToAQuery(SpyFileQuery $fileQuery, FileAttachmentCriteriaTransfer $fileAttachmentCriteriaTransfer): SpyFileQuery
    {
        if (!$this->hasAnyRelationRequested($fileAttachmentCriteriaTransfer)) {
            return $fileQuery;
        }

        $fileAttachmentConditions = $fileAttachmentCriteriaTransfer->getFileAttachmentConditions() ?? new FileAttachmentConditionsTransfer();

        $fileAttachmentFilterCriteria = null;
        if ($fileAttachmentCriteriaTransfer->getWithBusinessUnitRelation()) {
            $fileAttachmentFilterCriteria = $this->addSubCriteria(
                $this->addBusinessUnitRelation($fileQuery, $fileAttachmentConditions),
                $fileAttachmentFilterCriteria,
            );
        }

        if ($fileAttachmentCriteriaTransfer->getWithCompanyUserRelation()) {
            $fileAttachmentFilterCriteria = $this->addSubCriteria(
                $this->addCompanyUserRelation($fileQuery, $fileAttachmentConditions),
                $fileAttachmentFilterCriteria,
            );
        }

        if ($fileAttachmentCriteriaTransfer->getWithSspAssetRelation()) {
            $fileAttachmentFilterCriteria = $this->addSubCriteria(
                $this->addSspAssetRelation($fileQuery, $fileAttachmentCriteriaTransfer),
                $fileAttachmentFilterCriteria,
            );
        }

        if ($fileAttachmentCriteriaTransfer->getWithSspModelRelation()) {
            $fileAttachmentFilterCriteria = $this->addSubCriteria(
                $this->addSspModelRelation($fileQuery, $fileAttachmentConditions),
                $fileAttachmentFilterCriteria,
            );
        }

        if (!$fileAttachmentFilterCriteria) {
            return $fileQuery;
        }

        $fileQuery->addAnd($fileAttachmentFilterCriteria);

        return $fileQuery;
    }

    protected function hasAnyRelationRequested(FileAttachmentCriteriaTransfer $fileAttachmentCriteriaTransfer): bool
    {
        return $fileAttachmentCriteriaTransfer->getWithBusinessUnitRelation()
            || $fileAttachmentCriteriaTransfer->getWithCompanyUserRelation()
            || $fileAttachmentCriteriaTransfer->getWithSspAssetRelation()
            || $fileAttachmentCriteriaTransfer->getWithSspModelRelation();
    }

    protected function addBusinessUnitRelation(SpyFileQuery $fileQuery, FileAttachmentConditionsTransfer $fileAttachmentConditions): ?AbstractCriterion
    {
        $fileQuery
            ->withColumn('GROUP_CONCAT(' . SpyCompanyBusinessUnitFileTableMap::COL_FK_COMPANY_BUSINESS_UNIT . ')', static::BUSINESS_UNIT_IDS_COLUMN)
            ->joinSpyCompanyBusinessUnitFile(null, Criteria::LEFT_JOIN);

        return $this->fileAttachmentRelationCriteriaBuilder->getBusinessUnitFileCriteria($fileQuery, $fileAttachmentConditions);
    }

    protected function addCompanyUserRelation(SpyFileQuery $fileQuery, FileAttachmentConditionsTransfer $fileAttachmentConditions): ?AbstractCriterion
    {
        $fileQuery
            ->withColumn('GROUP_CONCAT(' . SpyCompanyUserFileTableMap::COL_FK_COMPANY_USER . ')', static::COMPANY_USER_IDS_COLUMN)
            ->joinSpyCompanyUserFile(null, Criteria::LEFT_JOIN);

        return $this->fileAttachmentRelationCriteriaBuilder->getCompanyUserFileCriteria($fileQuery, $fileAttachmentConditions);
    }

    protected function addSspAssetRelation(SpyFileQuery $fileQuery, FileAttachmentCriteriaTransfer $fileAttachmentCriteriaTransfer): ?AbstractCriterion
    {
        $fileQuery
            ->withColumn('GROUP_CONCAT(' . SpySspAssetFileTableMap::COL_FK_SSP_ASSET . ')', static::SSP_ASSET_IDS_COLUMN)
            ->useSpySspAssetFileQuery(null, Criteria::LEFT_JOIN);

        return $this->fileAttachmentRelationCriteriaBuilder->getSspAssetFileCriteria($fileQuery, $fileAttachmentCriteriaTransfer);
    }

    protected function addSspModelRelation(SpyFileQuery $fileQuery, FileAttachmentConditionsTransfer $fileAttachmentConditions): ?AbstractCriterion
    {
        $fileQuery
            ->withColumn('GROUP_CONCAT(' . SpySspModelToFileTableMap::COL_FK_SSP_MODEL . ')', static::SSP_MODEL_IDS_COLUMN)
            ->joinSpySspModelToFile(null, Criteria::LEFT_JOIN);

        return $this->fileAttachmentRelationCriteriaBuilder->getSspModelToFileCriteria($fileQuery, $fileAttachmentConditions);
    }

    protected function applyFileAttachmentSearch(
        SpyFileQuery $query,
        FileAttachmentCriteriaTransfer $fileAttachmentCriteriaTransfer
    ): SpyFileQuery {
        if (!$fileAttachmentCriteriaTransfer->getFileAttachmentSearchConditions()) {
            return $query;
        }

        $searchString = $fileAttachmentCriteriaTransfer->getFileAttachmentSearchConditions()->getSearchString();
        if ($searchString) {
            $query->filterByFileName_Like(sprintf('%%%s%%', $searchString))
                ->_or()
                ->filterByFileReference_Like(sprintf('%%%s%%', $searchString));
        }

        return $query;
    }

    protected function applyFileAttachmentTypeFilter(
        SpyFileQuery $query,
        FileAttachmentCriteriaTransfer $fileAttachmentCriteriaTransfer
    ): SpyFileQuery {
        $fileTypes = $fileAttachmentCriteriaTransfer->getFileAttachmentConditionsOrFail()->getFileTypes();

        if (!$fileTypes) {
            return $query;
        }

        return $query
            ->useSpyFileInfoQuery()
            ->filterByExtension_In($fileTypes)
            ->endUse();
    }

    protected function applyFileAttachmentDateRangeFilter(
        SpyFileQuery $query,
        FileAttachmentCriteriaTransfer $fileAttachmentCriteriaTransfer
    ): SpyFileQuery {
        $rangeCreatedAt = $fileAttachmentCriteriaTransfer->getFileAttachmentConditionsOrFail()->getRangeCreatedAt();
        if (!$rangeCreatedAt) {
            return $query;
        }

        if ($rangeCreatedAt->getFrom()) {
            $query->useSpyFileInfoQuery()
                ->filterByCreatedAt($rangeCreatedAt->getFrom(), Criteria::GREATER_EQUAL)
                ->endUse();
        }

        if ($rangeCreatedAt->getTo()) {
            $query->useSpyFileInfoQuery()
                ->filterByCreatedAt($rangeCreatedAt->getTo(), Criteria::LESS_EQUAL)
                ->endUse();
        }

        return $query;
    }

    protected function applyFileAttachmentUuidFilter(
        SpyFileQuery $query,
        FileAttachmentCriteriaTransfer $fileAttachmentCriteriaTransfer
    ): SpyFileQuery {
        $uuids = $fileAttachmentCriteriaTransfer->getFileAttachmentConditionsOrFail()->getUuids();
        if ($uuids !== []) {
            $query
                ->filterByUuid_In($uuids);
        }

        return $query;
    }

    protected function applyFileIdFilter(
        SpyFileQuery $query,
        FileAttachmentCriteriaTransfer $fileAttachmentCriteriaTransfer
    ): SpyFileQuery {
        $fileIds = $fileAttachmentCriteriaTransfer->getFileAttachmentConditionsOrFail()->getIdFiles();
        if ($fileIds !== []) {
            $query
                ->filterByIdFile_In($fileIds);
        }

        return $query;
    }

    /**
     * @param \ArrayObject<array-key, \Generated\Shared\Transfer\SortTransfer> $sortTransfers
     */
    protected function applyFileAttachmentSorting(ModelCriteria $query, ArrayObject $sortTransfers): ModelCriteria
    {
        $fileAttachmentSortFieldMapping = $this->getFileAttachmentSortFieldMapping();
        foreach ($sortTransfers as $sortTransfer) {
            $query
                ->groupBy($fileAttachmentSortFieldMapping[$sortTransfer->getFieldOrFail()] ?? $sortTransfer->getFieldOrFail())
                ->orderBy(
                    $fileAttachmentSortFieldMapping[$sortTransfer->getFieldOrFail()] ?? $sortTransfer->getFieldOrFail(),
                    $sortTransfer->getIsAscending() ? Criteria::ASC : Criteria::DESC,
                );
        }

        return $query;
    }

    /**
     * @return array<string, string>
     */
    protected function getFileAttachmentSortFieldMapping(): array
    {
        return [
            'fileType' => SpyFileInfoTableMap::COL_EXTENSION,
            'size' => SpyFileInfoTableMap::COL_SIZE,
            'createdAt' => SpyFileInfoTableMap::COL_CREATED_AT,
        ];
    }

    protected function addSubCriteria(?AbstractCriterion $subCriteria, ?AbstractCriterion $criteria): ?AbstractCriterion
    {
        if (!$subCriteria) {
            return $criteria;
        }

        if ($criteria) {
            $criteria->addOr($subCriteria);

            return $criteria;
        }

        return $subCriteria;
    }
}

<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\SelfServicePortal\Persistence\QueryBuilder;

use Generated\Shared\Transfer\FileAttachmentConditionsTransfer;
use Generated\Shared\Transfer\FileAttachmentCriteriaTransfer;
use Orm\Zed\CompanyBusinessUnit\Persistence\Map\SpyCompanyBusinessUnitTableMap;
use Orm\Zed\FileManager\Persistence\SpyFileQuery;
use Orm\Zed\SelfServicePortal\Persistence\Map\SpyCompanyUserFileTableMap;
use Orm\Zed\SelfServicePortal\Persistence\Map\SpySspAssetTableMap;
use Orm\Zed\SelfServicePortal\Persistence\Map\SpySspModelToFileTableMap;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion;

class FileAttachmentRelationCriteriaBuilder implements FileAttachmentRelationCriteriaBuilderInterface
{
    public function getBusinessUnitFileCriteria(
        SpyFileQuery $fileQuery,
        FileAttachmentConditionsTransfer $fileAttachmentConditionsTransfer
    ): ?AbstractCriterion {
        $businessUnitQuery = $fileQuery
            ->useSpyCompanyBusinessUnitFileQuery(null, Criteria::LEFT_JOIN)
            ->useCompanyBusinessUnitQuery(null, Criteria::LEFT_JOIN);

        $businessUnitQuery->endUse()->endUse();

        $criteria = null;
        if ($fileAttachmentConditionsTransfer->getCompanyIds()) {
            $criteria = $businessUnitQuery->getNewCriterion(
                SpyCompanyBusinessUnitTableMap::COL_FK_COMPANY,
                $fileAttachmentConditionsTransfer->getCompanyIds(),
                Criteria::IN,
            );
        }

        if ($fileAttachmentConditionsTransfer->getBusinessUnitIds()) {
            $businessUnitIdFileCriteria = $businessUnitQuery->getNewCriterion(
                SpyCompanyBusinessUnitTableMap::COL_ID_COMPANY_BUSINESS_UNIT,
                $fileAttachmentConditionsTransfer->getBusinessUnitIds(),
                Criteria::IN,
            );

            if ($criteria) {
                $criteria->addAnd($businessUnitIdFileCriteria);

                return $criteria;
            }

            return $businessUnitIdFileCriteria;
        }

        if ($fileAttachmentConditionsTransfer->getBusinessUnitUuids()) {
            $businessUnitUuidCriteria = $businessUnitQuery->getNewCriterion(
                SpyCompanyBusinessUnitTableMap::COL_UUID,
                $fileAttachmentConditionsTransfer->getBusinessUnitUuids(),
                Criteria::IN,
            );

            if ($criteria) {
                $criteria->addAnd($businessUnitUuidCriteria);

                return $criteria;
            }
        }

        return $criteria;
    }

    public function getCompanyUserFileCriteria(
        SpyFileQuery $fileQuery,
        FileAttachmentConditionsTransfer $fileAttachmentConditionsTransfer
    ): ?AbstractCriterion {
        $companyUserFileQuery = $fileQuery
            ->useSpyCompanyUserFileQuery(null, Criteria::LEFT_JOIN);

        $companyUserQuery = $companyUserFileQuery->useCompanyUserQuery('company_user', Criteria::LEFT_JOIN);

        $companyUserFileQuery->endUse();

        $criteria = null;
        if ($fileAttachmentConditionsTransfer->getCompanyUserIds()) {
            $criteria = $companyUserFileQuery->getNewCriterion(
                SpyCompanyUserFileTableMap::COL_FK_COMPANY_USER,
                $fileAttachmentConditionsTransfer->getCompanyUserIds(),
                Criteria::IN,
            );
        }

        if ($fileAttachmentConditionsTransfer->getCompanyIds()) {
            $companyIdCriteria = $companyUserQuery->getNewCriterion(
                'company_user.fk_company',
                $fileAttachmentConditionsTransfer->getCompanyIds(),
                Criteria::IN,
            );

            if ($criteria) {
                $companyIdCriteria->addAnd($criteria);
            }

            if (!$criteria) {
                $criteria = $companyIdCriteria;
            }
        }

        if ($fileAttachmentConditionsTransfer->getBusinessUnitIds()) {
            $businessUnitIdCriteria = $companyUserQuery->getNewCriterion(
                'company_user.fk_company_business_unit',
                $fileAttachmentConditionsTransfer->getBusinessUnitIds(),
                Criteria::IN,
            );

            if ($criteria) {
                $criteria->addAnd($businessUnitIdCriteria);

                return $criteria;
            }

            return $businessUnitIdCriteria;
        }

        return $criteria;
    }

    public function getSspAssetFileCriteria(SpyFileQuery $fileQuery, FileAttachmentCriteriaTransfer $criteriaTransfer): ?AbstractCriterion
    {
        $fileAttachmentCriteriaTransfer = $criteriaTransfer->getFileAttachmentConditionsOrFail();

        $sspAssetQuery = $fileQuery
            ->useSpySspAssetFileQuery(null, Criteria::LEFT_JOIN)
            ->useSspAssetQuery(null, Criteria::LEFT_JOIN);

        $companyBusinessUnitQuery = $sspAssetQuery
            ->useSpySspAssetToCompanyBusinessUnitQuery(null, Criteria::LEFT_JOIN)
            ->joinSpyCompanyBusinessUnit('ssp_asset_business_unit', Criteria::LEFT_JOIN);

        $companyBusinessUnitQuery
            ->endUse()
            ->endUse()
            ->endUse();

        $criteria = null;
        if ($fileAttachmentCriteriaTransfer->getAssetReferences()) {
            $criteria = $sspAssetQuery->getNewCriterion(
                SpySspAssetTableMap::COL_REFERENCE,
                $fileAttachmentCriteriaTransfer->getAssetReferences(),
                Criteria::IN,
            );
        }

        if ($fileAttachmentCriteriaTransfer->getSspAssetCompanyIds()) {
            $assetCompanyIdCriterion = $fileQuery->getNewCriterion(
                'ssp_asset_business_unit.fk_company',
                $fileAttachmentCriteriaTransfer->getSspAssetCompanyIds(),
                Criteria::IN,
            );

            if ($criteria) {
                $criteria->addAnd($assetCompanyIdCriterion);
            } else {
                $criteria = $assetCompanyIdCriterion;
            }
        }

        if ($fileAttachmentCriteriaTransfer->getSspAssetBusinessUnitIds()) {
            $assetBusinessUnitIdCriterion = $fileQuery->getNewCriterion(
                'ssp_asset_business_unit.id_company_business_unit',
                $fileAttachmentCriteriaTransfer->getSspAssetBusinessUnitIds(),
                Criteria::IN,
            );

            if ($criteria) {
                $criteria->addAnd($assetBusinessUnitIdCriterion);
            } else {
                $criteria = $assetBusinessUnitIdCriterion;
            }
        }

        return $criteria;
    }

    public function getSspModelToFileCriteria(
        SpyFileQuery $fileQuery,
        FileAttachmentConditionsTransfer $fileAttachmentConditionsTransfer
    ): ?AbstractCriterion {
        $sspModelToFileQuery = $fileQuery
            ->useSpySspModelToFileQuery(null, Criteria::LEFT_JOIN);

        if (!$fileAttachmentConditionsTransfer->getSspModelIds()) {
            return null;
        }

        return $sspModelToFileQuery->getNewCriterion(
            SpySspModelToFileTableMap::COL_FK_SSP_MODEL,
            $fileAttachmentConditionsTransfer->getSspModelIds(),
            Criteria::IN,
        );
    }
}

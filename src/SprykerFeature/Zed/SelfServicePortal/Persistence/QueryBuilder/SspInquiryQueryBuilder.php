<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Persistence\QueryBuilder;

use Generated\Shared\Transfer\SspAssetTransfer;
use Generated\Shared\Transfer\SspInquiryConditionsTransfer;
use Orm\Zed\SelfServicePortal\Persistence\Map\SpySspInquirySspAssetTableMap;
use Orm\Zed\SelfServicePortal\Persistence\SpySspInquiryQuery;
use Propel\Runtime\ActiveQuery\ModelCriteria;

class SspInquiryQueryBuilder
{
    public function applyConditions(SpySspInquiryQuery $sspInquiryQuery, SspInquiryConditionsTransfer $sspInquiryConditions): SpySspInquiryQuery
    {
        $this->applyIdentityFilters($sspInquiryQuery, $sspInquiryConditions);
        $this->applyOwnerFilter($sspInquiryQuery, $sspInquiryConditions);
        $this->applyDateFilters($sspInquiryQuery, $sspInquiryConditions);
        $this->applySspAssetFilters($sspInquiryQuery, $sspInquiryConditions);

        if ($sspInquiryConditions->getSearchString()) {
            $searchText = '%' . $sspInquiryConditions->getSearchStringOrFail() . '%';
            $sspInquiryQuery
                ->filterBySubject_Like($searchText)
                    ->_or()
                ->filterByReference_Like($searchText);
        }

        return $sspInquiryQuery;
    }

    public function applyOwnerFilter(SpySspInquiryQuery $sspInquiryQuery, SspInquiryConditionsTransfer $sspInquiryConditions): SpySspInquiryQuery
    {
        $sspInquiryOwnerConditionGroup = $sspInquiryConditions->getSspInquiryOwnerConditionGroup();

        if ($sspInquiryOwnerConditionGroup) {
            $hasOwnerCondition = false;
            $companyUserQuery = $sspInquiryQuery->useSpyCompanyUserQuery();

            if ($sspInquiryOwnerConditionGroup->getCompanyUser()?->getIdCompanyUser()) {
                $hasOwnerCondition = true;
                $companyUserQuery->filterByIdCompanyUser($sspInquiryOwnerConditionGroup->getCompanyUser()->getIdCompanyUser());
            }

            if ($sspInquiryOwnerConditionGroup->getIdCompany()) {
                if ($hasOwnerCondition) {
                    $companyUserQuery->_or();
                }

                $hasOwnerCondition = true;
                $companyUserQuery->filterByFkCompany($sspInquiryOwnerConditionGroup->getIdCompany());
            }

            if ($sspInquiryOwnerConditionGroup->getIdCompanyBusinessUnit()) {
                if ($hasOwnerCondition) {
                    $companyUserQuery->_or();
                }

                $companyUserQuery->filterByFkCompanyBusinessUnit($sspInquiryOwnerConditionGroup->getIdCompanyBusinessUnitOrFail());
            }

            $companyUserQuery->endUse();
        }

        return $sspInquiryQuery;
    }

    protected function applyIdentityFilters(SpySspInquiryQuery $sspInquiryQuery, SspInquiryConditionsTransfer $sspInquiryConditions): void
    {
        if ($sspInquiryConditions->getSspInquiryIds() !== []) {
            $sspInquiryQuery->filterByIdSspInquiry_In($sspInquiryConditions->getSspInquiryIds());
        }

        if ($sspInquiryConditions->getReferences() !== []) {
            $sspInquiryQuery->filterByReference_In($sspInquiryConditions->getReferences());
        }

        if ($sspInquiryConditions->getType() !== null) {
            $sspInquiryQuery->filterByType($sspInquiryConditions->getType());
        }

        if ($sspInquiryConditions->getStatus() !== null) {
            $sspInquiryQuery
                ->useStateMachineItemStateQuery()
                    ->filterByName($sspInquiryConditions->getStatus())
                ->endUse();
        }
    }

    protected function applyDateFilters(SpySspInquiryQuery $sspInquiryQuery, SspInquiryConditionsTransfer $sspInquiryConditions): void
    {
        if ($sspInquiryConditions->getCreatedDateFrom() !== null) {
            $sspInquiryQuery->filterByCreatedAt($sspInquiryConditions->getCreatedDateFrom(), ModelCriteria::GREATER_EQUAL);
        }

        if ($sspInquiryConditions->getCreatedDateTo() !== null) {
            $sspInquiryQuery->filterByCreatedAt($sspInquiryConditions->getCreatedDateTo(), ModelCriteria::LESS_EQUAL);
        }
    }

    protected function applySspAssetFilters(SpySspInquiryQuery $sspInquiryQuery, SspInquiryConditionsTransfer $sspInquiryConditions): void
    {
        if ($sspInquiryConditions->getSspAssetIds() !== []) {
            $sspInquiryQuery
                ->joinSpySspInquirySspAsset()
                ->withColumn(SpySspInquirySspAssetTableMap::COL_FK_SSP_ASSET, SspAssetTransfer::ID_SSP_ASSET)
                ->useSpySspInquirySspAssetExistsQuery()
                    ->filterByFkSspAsset_In($sspInquiryConditions->getSspAssetIds())
                ->endUse();
        }

        if ($sspInquiryConditions->getSspAssetReferences() !== []) {
            $sspInquiryQuery
                ->useSpySspInquirySspAssetExistsQuery()
                    ->useSpySspAssetQuery()
                        ->filterByReference_In($sspInquiryConditions->getSspAssetReferences())
                    ->endUse()
                ->endUse();
        }
    }
}

<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Persistence\QueryBuilder;

use Generated\Shared\Transfer\SspServiceConditionsTransfer;
use Generated\Shared\Transfer\SspServiceCriteriaTransfer;
use Generated\Shared\Transfer\SspServicesSearchConditionTransfer;
use Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery;
use SprykerFeature\Zed\SelfServicePortal\SelfServicePortalConfig;

class SspServiceQueryBuilder
{
    public function __construct(protected SelfServicePortalConfig $selfServicePortalConfig)
    {
    }

    public function applyFilters(SpySalesOrderItemQuery $query, SspServiceCriteriaTransfer $sspServiceCriteriaTransfer): SpySalesOrderItemQuery
    {
        if (!$sspServiceCriteriaTransfer->getServiceConditions()) {
            return $query;
        }

        $serviceConditionsTransfer = $sspServiceCriteriaTransfer->getServiceConditionsOrFail();

        $this->applyProductClassFilter($query, $serviceConditionsTransfer);
        $this->applyServicesSearchConditionFilter($query, $serviceConditionsTransfer->getServicesSearchCondition());
        $this->applyOwnerFilters($query, $serviceConditionsTransfer);

        if ($serviceConditionsTransfer->getSspAssetReferences() !== []) {
            $query->useSalesOrderItemSspAssetQuery()
                ->filterByReference_In($serviceConditionsTransfer->getSspAssetReferences())
                ->endUse();
        }

        return $query;
    }

    protected function applyProductClassFilter(SpySalesOrderItemQuery $query, SspServiceConditionsTransfer $serviceConditionsTransfer): void
    {
        $productClassNameToFilter = $serviceConditionsTransfer->getProductClass() ?: $this->selfServicePortalConfig->getServiceProductClassName();

        if ($productClassNameToFilter) {
            $query->useSpySalesOrderItemProductClassQuery()
                ->useSpySalesProductClassQuery()
                    ->filterByName($productClassNameToFilter)
                ->endUse()
            ->endUse();
        }
    }

    protected function applyServicesSearchConditionFilter(
        SpySalesOrderItemQuery $query,
        ?SspServicesSearchConditionTransfer $servicesSearchCondition
    ): void {
        if (!$servicesSearchCondition) {
            return;
        }

        if ($servicesSearchCondition->getProductName()) {
            $query->filterByName_Like(sprintf('%%%s%%', $servicesSearchCondition->getProductName()));
        }

        if ($servicesSearchCondition->getSku()) {
            $query->filterBySku_Like(sprintf('%%%s%%', $servicesSearchCondition->getSku()));
        }

        if ($servicesSearchCondition->getOrderReference()) {
            $query->useOrderQuery()
                    ->filterByOrderReference_Like(sprintf('%%%s%%', $servicesSearchCondition->getOrderReference()))
                ->endUse();
        }
    }

    protected function applyOwnerFilters(SpySalesOrderItemQuery $query, SspServiceConditionsTransfer $serviceConditionsTransfer): void
    {
        if ($serviceConditionsTransfer->getCompanyBusinessUnitUuid()) {
            $query->useOrderQuery()
                ->filterByCompanyBusinessUnitUuid($serviceConditionsTransfer->getCompanyBusinessUnitUuid())
                ->endUse();
        }

        if ($serviceConditionsTransfer->getCompanyUuid()) {
            $query->useOrderQuery()
                ->filterByCompanyUuid($serviceConditionsTransfer->getCompanyUuid())
                ->endUse();
        }

        if ($serviceConditionsTransfer->getCustomerReference()) {
            $query->useOrderQuery()
                ->filterByCustomerReference($serviceConditionsTransfer->getCustomerReference())
                ->endUse();
        }
    }
}

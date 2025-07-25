<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Persistence;

use ArrayObject;
use Generated\Shared\Transfer\FileAttachmentCollectionTransfer;
use Generated\Shared\Transfer\FileAttachmentCriteriaTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\ProductClassCollectionTransfer;
use Generated\Shared\Transfer\ProductClassCriteriaTransfer;
use Generated\Shared\Transfer\SspAssetCollectionTransfer;
use Generated\Shared\Transfer\SspAssetCriteriaTransfer;
use Generated\Shared\Transfer\SspAssetTransfer;
use Generated\Shared\Transfer\SspInquiryCollectionTransfer;
use Generated\Shared\Transfer\SspInquiryConditionsTransfer;
use Generated\Shared\Transfer\SspInquiryCriteriaTransfer;
use Generated\Shared\Transfer\SspInquiryTransfer;
use Generated\Shared\Transfer\SspServiceCollectionTransfer;
use Generated\Shared\Transfer\SspServiceCriteriaTransfer;
use Orm\Zed\FileManager\Persistence\Map\SpyFileTableMap;
use Orm\Zed\Oms\Persistence\Map\SpyOmsOrderItemStateTableMap;
use Orm\Zed\Sales\Persistence\Map\SpySalesOrderItemMetadataTableMap;
use Orm\Zed\Sales\Persistence\Map\SpySalesOrderItemTableMap;
use Orm\Zed\Sales\Persistence\Map\SpySalesOrderTableMap;
use Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery;
use Orm\Zed\SelfServicePortal\Persistence\Map\SpyProductShipmentTypeTableMap;
use Orm\Zed\SelfServicePortal\Persistence\Map\SpySspAssetTableMap;
use Orm\Zed\SelfServicePortal\Persistence\Map\SpySspInquirySspAssetTableMap;
use Orm\Zed\SelfServicePortal\Persistence\Map\SpySspInquiryTableMap;
use Orm\Zed\SelfServicePortal\Persistence\SpySspAssetQuery;
use Orm\Zed\SelfServicePortal\Persistence\SpySspInquiryQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Collection\Collection;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;

/**
 * @method \SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalPersistenceFactory getFactory()
 */
class SelfServicePortalRepository extends AbstractRepository implements SelfServicePortalRepositoryInterface
{
    /**
     * @var string
     */
    protected const FIELD_ORDER_REFERENCE = 'order_reference';

    /**
     * @var string
     */
    protected const FIELD_SCHEDULED_AT = 'scheduled_at';

    /**
     * @var string
     */
    protected const FIELD_CREATED_AT = 'created_at';

    /**
     * @var string
     */
    protected const FIELD_ID_SALES_ORDER = 'id_sales_order';

    /**
     * @var string
     */
    protected const FIELD_STATE_NAME = 'state_name';

    /**
     * @var string
     */
    protected const FIELD_PRODUCT_NAME = 'product_name';

    /**
     * @var string
     */
    protected const FIELD_ID_SALES_ORDER_ITEM = 'id_sales_order_item';

    /**
     * @var string
     */
    protected const SORT_DIRECTION_ASC = 'ASC';

    /**
     * @var array<string, string>
     */
    protected const SERVICE_SORT_FIELD_MAPPING = [
        'order_reference' => SpySalesOrderTableMap::COL_ORDER_REFERENCE,
        'scheduled_at' => SpySalesOrderItemMetadataTableMap::COL_SCHEDULED_AT,
        'product_name' => SpySalesOrderItemTableMap::COL_NAME,
        'created_at' => SpySalesOrderItemTableMap::COL_CREATED_AT,
        'state' => SpyOmsOrderItemStateTableMap::COL_NAME,
    ];

    /**
     * @param list<int> $productConcreteIds
     *
     * @return array<int, list<int>>
     */
    public function getShipmentTypeIdsGroupedByIdProductConcrete(array $productConcreteIds): array
    {
        $productShipmentTypeEntities = $this->getFactory()
            ->createProductShipmentTypeQuery()
            ->filterByFkProduct_In($productConcreteIds)
            ->find();

        $groupedShipmentTypeIds = [];
        foreach ($productShipmentTypeEntities as $productShipmentTypeEntity) {
            $groupedShipmentTypeIds[$productShipmentTypeEntity->getFkProduct()][] = $productShipmentTypeEntity->getFkShipmentType();
        }

        /** @var array<int, list<int>> $groupedShipmentTypeIds */
        return $groupedShipmentTypeIds;
    }

    /**
     * @param list<int> $productConcreteIds
     * @param string $shipmentTypeName
     *
     * @return array<int, list<int>>
     */
    public function getProductIdsWithShipmentType(array $productConcreteIds, string $shipmentTypeName): array
    {
        $productShipmentTypeEntities = $this->getFactory()
            ->createProductShipmentTypeQuery()
            ->useSpyShipmentTypeQuery()
                ->filterByName($shipmentTypeName)
            ->endUse()
            ->filterByFkProduct_In($productConcreteIds)
            ->select(SpyProductShipmentTypeTableMap::COL_FK_PRODUCT)
            ->find();

        /** @var array<int, list<int>> $productConcreteIdsWithShipmentType */
        $productConcreteIdsWithShipmentType = $productShipmentTypeEntities->getData();

        return $productConcreteIdsWithShipmentType;
    }

    /**
     * @param \Generated\Shared\Transfer\SspServiceCriteriaTransfer $sspServiceCriteriaTransfer
     *
     * @return \Generated\Shared\Transfer\SspServiceCollectionTransfer
     */
    public function getSspServiceCollection(SspServiceCriteriaTransfer $sspServiceCriteriaTransfer): SspServiceCollectionTransfer
    {
        $sspServiceCollectionTransfer = new SspServiceCollectionTransfer();

        $query = $this->getFactory()->getSalesOrderItemPropelQuery();

        $query = $this->joinServiceOrderData($query);

        $query->joinSalesOrderItemSspAsset(null, Criteria::LEFT_JOIN);

        $query = $this->applySspServiceFilters($query, $sspServiceCriteriaTransfer);
        $query = $this->applySspServiceSorting($query, $sspServiceCriteriaTransfer);

        $query = $query->groupByIdSalesOrderItem();

        $sspServiceEntities = $this->getSspServicePaginatedCollection($query, $sspServiceCriteriaTransfer);

        $sspServiceMapper = $this->getFactory()->createSspServiceMapper();
        $sspServiceTransfers = $sspServiceMapper->mapSalesOrderItemEntitiesToSspServiceTransfers($sspServiceEntities->getData());

        foreach ($sspServiceTransfers as $sspServiceTransfer) {
            $sspServiceCollectionTransfer->addService($sspServiceTransfer);
        }

        $sspServiceCollectionTransfer->setPagination($sspServiceCriteriaTransfer->getPagination());

        return $sspServiceCollectionTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\ProductClassCriteriaTransfer $productClassCriteriaTransfer
     *
     * @return \Generated\Shared\Transfer\ProductClassCollectionTransfer
     */
    public function getProductClassCollection(ProductClassCriteriaTransfer $productClassCriteriaTransfer): ProductClassCollectionTransfer
    {
        $productClassCollectionTransfer = new ProductClassCollectionTransfer();

        if (!$productClassCriteriaTransfer->getProductClassConditions()) {
            $productClassEntities = $this->getFactory()
                ->createProductClassQuery()
                ->find()->getData();

            return $this->getFactory()
                ->createProductClassMapper()
                ->mapProductClassEntitiesToProductClassCollectionTransfer($productClassEntities, $productClassCollectionTransfer);
        }

        $productClassConditions = $productClassCriteriaTransfer->getProductClassConditions();
        $skus = $productClassConditions->getSkus();
        $productAbstractIds = $productClassConditions->getProductAbstractIds();
        $productConcreteIds = $productClassConditions->getProductConcreteIds();

        if (!$skus && !$productAbstractIds && !$productConcreteIds) {
            return $productClassCollectionTransfer;
        }

        $productToProductClassQuery = $this->getFactory()
            ->createProductToProductClassQuery()
            ->joinWithProductClass()
            ->useProductQuery();

        if ($skus) {
            $productToProductClassQuery->filterBySku_In($skus);
        }

        if ($productAbstractIds) {
            $productToProductClassQuery->filterByFkProductAbstract_In($productAbstractIds);
        }

        if ($productConcreteIds) {
            $productToProductClassQuery->filterByIdProduct_In($productConcreteIds);
        }

        $productToProductClassEntities = $productToProductClassQuery
            ->endUse()
            ->find();

        if ($productToProductClassEntities->isEmpty()) {
            return $productClassCollectionTransfer;
        }

        return $this->getFactory()
            ->createProductClassMapper()
            ->mapProductToProductClassEntitiesToProductClassCollectionTransfer(
                $productToProductClassEntities->getArrayCopy(),
                $productClassCollectionTransfer,
            );
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery $query
     *
     * @return \Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery
     */
    protected function joinServiceOrderData(SpySalesOrderItemQuery $query): SpySalesOrderItemQuery
    {
        $query
            ->useMetadataQuery(null, Criteria::LEFT_JOIN)
                ->withColumn(SpySalesOrderItemMetadataTableMap::COL_SCHEDULED_AT, static::FIELD_SCHEDULED_AT)
            ->endUse()
            ->useOrderQuery()
                ->withColumn(SpySalesOrderTableMap::COL_ORDER_REFERENCE, static::FIELD_ORDER_REFERENCE)
                ->withColumn(SpySalesOrderTableMap::COL_ID_SALES_ORDER, static::FIELD_ID_SALES_ORDER)
                ->withColumn(SpySalesOrderTableMap::COL_FIRST_NAME, 'first_name')
                ->withColumn(SpySalesOrderTableMap::COL_LAST_NAME, 'last_name')
                ->addJoin(
                    SpySalesOrderTableMap::COL_COMPANY_UUID,
                    'spy_company.uuid',
                    Criteria::LEFT_JOIN,
                )
                ->withColumn('spy_company.name', 'company_name')
            ->endUse();

        $query
            ->useStateQuery()
                ->withColumn(SpyOmsOrderItemStateTableMap::COL_NAME, static::FIELD_STATE_NAME)
            ->endUse()
            ->withColumn(SpySalesOrderItemTableMap::COL_NAME, static::FIELD_PRODUCT_NAME)
            ->withColumn(SpySalesOrderItemTableMap::COL_ID_SALES_ORDER_ITEM, static::FIELD_ID_SALES_ORDER_ITEM)
            ->withColumn(SpySalesOrderItemTableMap::COL_CREATED_AT, static::FIELD_CREATED_AT);

        return $query;
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery $query
     * @param \Generated\Shared\Transfer\SspServiceCriteriaTransfer $sspServiceCriteriaTransfer
     *
     * @return \Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery
     */
    protected function applySspServiceFilters(SpySalesOrderItemQuery $query, SspServiceCriteriaTransfer $sspServiceCriteriaTransfer): SpySalesOrderItemQuery
    {
        if (!$sspServiceCriteriaTransfer->getServiceConditions()) {
            return $query;
        }

        $serviceConditionsTransfer = $sspServiceCriteriaTransfer->getServiceConditionsOrFail();

        $productClassNameToFilter = $serviceConditionsTransfer->getProductClass() ?: $this->getFactory()->getConfig()->getServiceProductClassName();

        if ($productClassNameToFilter) {
            $query->useSpySalesOrderItemProductClassQuery()
                ->useSpySalesProductClassQuery()
                    ->filterByName($productClassNameToFilter)
                ->endUse()
            ->endUse();
        }

        if ($serviceConditionsTransfer->getServicesSearchCondition()) {
            $servicesSearchCondition = $serviceConditionsTransfer->getServicesSearchCondition();

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

        if ($serviceConditionsTransfer->getSspAssetReferences() !== []) {
            $query->useSalesOrderItemSspAssetQuery()
                ->filterByReference_In($serviceConditionsTransfer->getSspAssetReferences())
                ->endUse();
        }

        return $query;
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery $query
     * @param \Generated\Shared\Transfer\SspServiceCriteriaTransfer $sspServiceCriteriaTransfer
     *
     * @return \Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery
     */
    protected function applySspServiceSorting(SpySalesOrderItemQuery $query, SspServiceCriteriaTransfer $sspServiceCriteriaTransfer): SpySalesOrderItemQuery
    {
        if (count($sspServiceCriteriaTransfer->getSortCollection()) === 0) {
            return $query->orderBy(static::FIELD_SCHEDULED_AT, Criteria::DESC)
                ->orderBy(SpySalesOrderItemTableMap::COL_ID_SALES_ORDER_ITEM, Criteria::ASC);
        }

        $serviceSortFieldMapping = static::SERVICE_SORT_FIELD_MAPPING;
        foreach ($sspServiceCriteriaTransfer->getSortCollection() as $sortTransfer) {
            if (isset($serviceSortFieldMapping[$sortTransfer->getField()])) {
                $query->orderBy($serviceSortFieldMapping[$sortTransfer->getField()], $sortTransfer->getDirection() ?: Criteria::DESC);
            }
        }

        $query->orderBy(SpySalesOrderItemTableMap::COL_ID_SALES_ORDER_ITEM, Criteria::ASC);

        return $query;
    }

    /**
     * @param array<int> $salesOrderItemIds
     *
     * @return array<\Generated\Shared\Transfer\ItemTransfer>
     */
    public function getSalesOrderItemsByIds(array $salesOrderItemIds): array
    {
        if (!$salesOrderItemIds) {
            return [];
        }

        /** @var \Propel\Runtime\Collection\ObjectCollection<\Orm\Zed\Sales\Persistence\SpySalesOrderItem> $salesOrderItemEntities */
        $salesOrderItemEntities = $this->getFactory()
            ->getSalesOrderItemPropelQuery()
            ->filterByIdSalesOrderItem_In($salesOrderItemIds)
            ->find();

        return $this->getFactory()
            ->createSalesOrderItemMapper()
            ->mapSalesOrderItemEntitiesToItemTransfers($salesOrderItemEntities);
    }

    /**
     * @param \Generated\Shared\Transfer\FileAttachmentCriteriaTransfer $fileAttachmentCriteriaTransfer
     *
     * @return \Generated\Shared\Transfer\FileAttachmentCollectionTransfer
     */
    public function getFileAttachmentCollection(
        FileAttachmentCriteriaTransfer $fileAttachmentCriteriaTransfer
    ): FileAttachmentCollectionTransfer {
        $query = $this->getFactory()
            ->getFilePropelQuery()
            ->leftJoinSpyFileInfo()
            ->groupBy(SpyFileTableMap::COL_ID_FILE);

        $query = $this->getFactory()->createFileAttachmentQueryBuilder()->applyCriteria($query, $fileAttachmentCriteriaTransfer);

        $fileAttachmentCollectionTransfer = (new FileAttachmentCollectionTransfer())
            ->setPagination($fileAttachmentCriteriaTransfer->getPagination());

        return $this->getFactory()
            ->createFileMapper()
            ->mapFileEntityCollectionToFileAttachmentCollectionTransfer(
                $this->getPaginatedFileAttachmentCollection($query, $fileAttachmentCriteriaTransfer->getPagination()),
                $fileAttachmentCollectionTransfer,
            );
    }

    /**
     * @param \Generated\Shared\Transfer\SspInquiryCriteriaTransfer $sspInquiryCriteriaTransfer
     *
     * @return \Generated\Shared\Transfer\SspInquiryCollectionTransfer
     */
    public function getSspInquiryCollection(
        SspInquiryCriteriaTransfer $sspInquiryCriteriaTransfer
    ): SspInquiryCollectionTransfer {
         $sspInquiryCollectionTransfer = new SspInquiryCollectionTransfer();

         $sspInquiryQuery = $this->getFactory()->createSspInquiryQuery()
            ->joinWithStateMachineItemState();

         $sspInquiryQuery = $this->applyInquiryFilters($sspInquiryQuery, $sspInquiryCriteriaTransfer);
         $sspInquiryEntities = $this->getPaginatedInquiryCollection($sspInquiryQuery, $sspInquiryCriteriaTransfer->getPagination());
         $sspInquiryMapper = $this->getFactory()->createSspInquiryMapper();

        foreach ($sspInquiryEntities as $sspInquiryEntity) {
             $sspInquiryCollectionTransfer->addSspInquiry(
                 $sspInquiryMapper->mapSspInquiryEntityToSspInquiryTransfer($sspInquiryEntity, new SspInquiryTransfer()),
             );
        }

         $sspInquiryCollectionTransfer->setPagination($sspInquiryCriteriaTransfer->getPagination());

        return $sspInquiryCollectionTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\SspInquiryCriteriaTransfer $sspInquiryCriteriaTransfer
     *
     * @return \Generated\Shared\Transfer\SspInquiryCollectionTransfer
     */
    public function getSspInquiryFileCollection(SspInquiryCriteriaTransfer $sspInquiryCriteriaTransfer): SspInquiryCollectionTransfer
    {
        $sspInquiryFileQuery = $this->getFactory()->createSspInquiryFileQuery();

        $sspInquiryFileQuery->filterByFkSspInquiry_In($sspInquiryCriteriaTransfer->getSspInquiryConditionsOrFail()->getSspInquiryIds());

        $sspInquiryCollectionTransfer = new SspInquiryCollectionTransfer();

        /**
         * @var \Propel\Runtime\Collection\ObjectCollection<\Orm\Zed\SelfServicePortal\Persistence\SpySspInquiryFile> $sspInquiryFileEntities
         */
        $sspInquiryFileEntities = $sspInquiryFileQuery->find();

        if ($sspInquiryFileEntities->count() === 0) {
            return $sspInquiryCollectionTransfer;
        }

        return $this->getFactory()
            ->createSspInquiryMapper()
            ->mapSspInquiryFileEntitiesToSspInquiryCollectionTransfer(
                $sspInquiryFileEntities,
                $sspInquiryCollectionTransfer,
            );
    }

    /**
     * @param \Generated\Shared\Transfer\SspInquiryCriteriaTransfer $sspInquiryCriteriaTransfer
     *
     * @return \Generated\Shared\Transfer\SspInquiryCollectionTransfer
     */
    public function getSspInquiryOrderCollection(SspInquiryCriteriaTransfer $sspInquiryCriteriaTransfer): SspInquiryCollectionTransfer
    {
         $sspInquiryOrderQuery = $this->getFactory()->createSspInquiryOrderQuery();

         $sspInquiryOrderQuery->filterByFkSspInquiry_In($sspInquiryCriteriaTransfer->getSspInquiryConditionsOrFail()->getSspInquiryIds());

         $sspInquiryCollectionTransfer = new SspInquiryCollectionTransfer();

        foreach ($sspInquiryOrderQuery->find() as $sspInquiryOrderEntity) {
             $sspInquiryCollectionTransfer->addSspInquiry(
                 (new SspInquiryTransfer())
                    ->setIdSspInquiry($sspInquiryOrderEntity->getFkSspInquiry())
                    ->setOrder((new OrderTransfer())->setIdSalesOrder($sspInquiryOrderEntity->getFkSalesOrder())),
             );
        }

        return $sspInquiryCollectionTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\SspInquiryCriteriaTransfer $sspInquiryCriteriaTransfer
     *
     * @return \Generated\Shared\Transfer\SspInquiryCollectionTransfer
     */
    public function getSspInquirySspAssetCollection(SspInquiryCriteriaTransfer $sspInquiryCriteriaTransfer): SspInquiryCollectionTransfer
    {
        $sspInquirySspAssetQuery = $this->getFactory()->createSspInquirySspAssetQuery();

        $sspInquirySspAssetQuery->filterByFkSspInquiry_In($sspInquiryCriteriaTransfer->getSspInquiryConditionsOrFail()->getSspInquiryIds());

        $inquiryCollectionTransfer = new SspInquiryCollectionTransfer();

        foreach ($sspInquirySspAssetQuery->find() as $sspInquirySspAssetEntity) {
            $inquiryCollectionTransfer->addSspInquiry(
                (new SspInquiryTransfer())
                    ->setIdSspInquiry($sspInquirySspAssetEntity->getFkSspInquiry())
                    ->setSspAsset((new SspAssetTransfer())->setIdSspAsset($sspInquirySspAssetEntity->getFkSspAsset())),
            );
        }

        return $inquiryCollectionTransfer;
    }

    /**
     * @param array<int> $stateIds
     *
     * @return array<\Generated\Shared\Transfer\StateMachineItemTransfer>
     */
    public function getStateMachineItemsByStateIds(array $stateIds): array
    {
        /** @var \Orm\Zed\SelfServicePortal\Persistence\SpySspInquiryQuery<\Orm\Zed\SelfServicePortal\Persistence\SpySspInquiry> $sspInquiryQuery */
         $sspInquiryQuery = $this->getFactory()
            ->createSspInquiryQuery()
            ->joinWithStateMachineItemState()
            ->useStateMachineItemStateQuery()
            ->joinWithProcess()
            ->endUse();

        /** @var \Propel\Runtime\Collection\ObjectCollection<\Orm\Zed\SelfServicePortal\Persistence\SpySspInquiry> $sspInquiryEntities */
         $sspInquiryEntities = $sspInquiryQuery
            ->filterByFkStateMachineItemState_In($stateIds)
            ->find();

        return $this->getFactory()->createSspInquiryMapper()->mapSspInquiryEntityCollectionToStateMachineItemTransfers(
            $sspInquiryEntities,
        );
    }

    /**
     * @param \Orm\Zed\SelfServicePortal\Persistence\SpySspInquiryQuery $sspInquiryQuery
     * @param \Generated\Shared\Transfer\SspInquiryCriteriaTransfer $sspInquiryCriteriaTransfer
     *
     * @return \Orm\Zed\SelfServicePortal\Persistence\SpySspInquiryQuery
     */
    protected function applyInquiryFilters(SpySspInquiryQuery $sspInquiryQuery, SspInquiryCriteriaTransfer $sspInquiryCriteriaTransfer): SpySspInquiryQuery
    {
         $sspInquiryQuery = $this->applySspInquirySorting($sspInquiryQuery, $sspInquiryCriteriaTransfer->getSortCollection());

         $sspInquiryConditions = $sspInquiryCriteriaTransfer->getSspInquiryConditions();

        if (!$sspInquiryConditions) {
            return $sspInquiryQuery;
        }

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

        $this->applySspInquiryOwnerFilter($sspInquiryQuery, $sspInquiryConditions);

        if ($sspInquiryConditions->getCreatedDateFrom() !== null) {
             $sspInquiryQuery->filterByCreatedAt($sspInquiryConditions->getCreatedDateFrom(), ModelCriteria::GREATER_EQUAL);
        }

        if ($sspInquiryConditions->getCreatedDateTo() !== null) {
             $sspInquiryQuery->filterByCreatedAt($sspInquiryConditions->getCreatedDateTo(), ModelCriteria::LESS_EQUAL);
        }

        if ($sspInquiryConditions->getIdStore() !== null) {
             $sspInquiryQuery->filterByFkStore($sspInquiryConditions->getIdStore());
        }

        if ($sspInquiryConditions->getStoreName() !== null) {
            $sspInquiryQuery
                ->useSpyStoreQuery()
                    ->filterByName($sspInquiryConditions->getStoreName())
                ->endUse();
        }

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

        return $sspInquiryQuery;
    }

    /**
     * @param \Orm\Zed\SelfServicePortal\Persistence\SpySspInquiryQuery $sspInquiryQuery
     * @param \Generated\Shared\Transfer\SspInquiryConditionsTransfer $sspInquiryConditions
     *
     * @return \Orm\Zed\SelfServicePortal\Persistence\SpySspInquiryQuery
     */
    public function applySspInquiryOwnerFilter(SpySspInquiryQuery $sspInquiryQuery, SspInquiryConditionsTransfer $sspInquiryConditions): SpySspInquiryQuery
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

    /**
     * @param \Orm\Zed\SelfServicePortal\Persistence\SpySspInquiryQuery $sspInquiryQuery
     * @param \ArrayObject<int, \Generated\Shared\Transfer\SortTransfer> $sortCollection
     *
     * @return \Orm\Zed\SelfServicePortal\Persistence\SpySspInquiryQuery
     */
    protected function applySspInquirySorting(SpySspInquiryQuery $sspInquiryQuery, ArrayObject $sortCollection): SpySspInquiryQuery
    {
        foreach ($sortCollection as $sort) {
            $field = $sort->getFieldOrFail();
            if ($field === SspInquiryTransfer::CREATED_DATE) {
                $field = SpySspInquiryTableMap::COL_CREATED_AT;
            }
            $sspInquiryQuery->orderBy($field, $sort->getIsAscending() ? Criteria::ASC : Criteria::DESC);
        }

        return $sspInquiryQuery;
    }

    /**
     * @param \Propel\Runtime\ActiveQuery\ModelCriteria $query
     * @param \Generated\Shared\Transfer\PaginationTransfer|null $paginationTransfer
     *
     * @return \Propel\Runtime\Collection\Collection
     */
    protected function getPaginatedInquiryCollection(ModelCriteria $query, ?PaginationTransfer $paginationTransfer = null): Collection
    {
        if ($paginationTransfer === null) {
            return $query->find();
        }

        $page = $paginationTransfer
            ->requirePage()
            ->getPageOrFail();

        $maxPerPage = $paginationTransfer
            ->requireMaxPerPage()
            ->getMaxPerPageOrFail();

        $paginationModel = $query->paginate($page, $maxPerPage);

        $paginationTransfer->setNbResults($paginationModel->getNbResults());
        $paginationTransfer->setFirstIndex($paginationModel->getFirstIndex());
        $paginationTransfer->setLastIndex($paginationModel->getLastIndex());
        $paginationTransfer->setFirstPage($paginationModel->getFirstPage());
        $paginationTransfer->setLastPage($paginationModel->getLastPage());
        $paginationTransfer->setNextPage($paginationModel->getNextPage());
        $paginationTransfer->setPreviousPage($paginationModel->getPreviousPage());

        return $paginationModel->getResults();
    }

    /**
     * @param \Propel\Runtime\ActiveQuery\ModelCriteria $query
     * @param \Generated\Shared\Transfer\PaginationTransfer|null $paginationTransfer
     *
     * @return \Propel\Runtime\Collection\Collection
     */
    protected function getPaginatedFileAttachmentCollection(ModelCriteria $query, ?PaginationTransfer $paginationTransfer = null): Collection
    {
        if ($paginationTransfer === null) {
            return $query->find();
        }

        $page = $paginationTransfer
            ->requirePage()
            ->getPageOrFail();

        $maxPerPage = $paginationTransfer
            ->requireMaxPerPage()
            ->getMaxPerPageOrFail();

        $paginationModel = $query->paginate($page, $maxPerPage);

        $paginationTransfer->setNbResults($paginationModel->getNbResults());
        $paginationTransfer->setFirstIndex($paginationModel->getFirstIndex());
        $paginationTransfer->setLastIndex($paginationModel->getLastIndex());
        $paginationTransfer->setFirstPage($paginationModel->getFirstPage());
        $paginationTransfer->setLastPage($paginationModel->getLastPage());
        $paginationTransfer->setNextPage($paginationModel->getNextPage());
        $paginationTransfer->setPreviousPage($paginationModel->getPreviousPage());

        return $paginationModel->getResults();
    }

    /**
     * @param \Generated\Shared\Transfer\SspAssetCriteriaTransfer $sspAssetCriteriaTransfer
     *
     * @return \Generated\Shared\Transfer\SspAssetCollectionTransfer
     */
    public function getSspAssetCollection(SspAssetCriteriaTransfer $sspAssetCriteriaTransfer): SspAssetCollectionTransfer
    {
        $sspAssetCollectionTransfer = new SspAssetCollectionTransfer();

        $sspAssetQuery = $this->getFactory()->createSspAssetQuery();

        $sspAssetQuery = $this->applyAssetConditions($sspAssetQuery, $sspAssetCriteriaTransfer);
        $sspAssetQuery = $this->applyAssetSorting($sspAssetQuery, $sspAssetCriteriaTransfer);

        if ($sspAssetCriteriaTransfer->getInclude()?->getWithOwnerCompanyBusinessUnit()) {
            $sspAssetQuery->joinWithSpyCompanyBusinessUnit(Criteria::LEFT_JOIN);
        }

        $sspAssetEntities = $this->getAssetPaginatedCollection($sspAssetQuery, $sspAssetCriteriaTransfer->getPagination());
        $sspAssetIds = [];
        foreach ($sspAssetEntities as $sspAssetEntity) {
            $sspAssetTransfer = $this->getFactory()
                ->createAssetMapper()
                ->mapSpySspAssetEntityToSspAssetTransfer($sspAssetEntity, new SspAssetTransfer());

            if ($sspAssetCriteriaTransfer->getInclude()) {
                $sspAssetTransfer = $this->getFactory()
                    ->createAssetMapper()
                    ->mapSpySspAssetEntityToSspAssetTransferIncludes(
                        $sspAssetEntity,
                        $sspAssetTransfer,
                        $sspAssetCriteriaTransfer->getIncludeOrFail(),
                    );
            }

            $sspAssetCollectionTransfer->addSspAsset($sspAssetTransfer);
            $sspAssetIds[] = $sspAssetTransfer->getIdSspAsset();
        }

        $sspAssetCollectionTransfer->setPagination($sspAssetCriteriaTransfer->getPagination());

        if (!$sspAssetCriteriaTransfer->getInclude()?->getWithAssignedBusinessUnits()) {
            return $sspAssetCollectionTransfer;
        }

        /** @var \Orm\Zed\SelfServicePortal\Persistence\SpySspAssetToCompanyBusinessUnitQuery $sspAssetToCompanyBusinessUnitQuery */
        $sspAssetToCompanyBusinessUnitQuery = $this->getFactory()->createSspAssetToCompanyBusinessUnitQuery()
            ->filterByFkSspAsset_In($sspAssetIds)
            ->joinWithSpyCompanyBusinessUnit()
            ->useSpyCompanyBusinessUnitQuery()
                ->joinWithCompany()
            ->endUse();

        if ($sspAssetCriteriaTransfer->getSspAssetConditions()) {
            if ($sspAssetCriteriaTransfer->getSspAssetConditionsOrFail()->getAssignedBusinessUnitId()) {
                $sspAssetToCompanyBusinessUnitQuery->filterByFkCompanyBusinessUnit(
                    $sspAssetCriteriaTransfer->getSspAssetConditionsOrFail()->getAssignedBusinessUnitId(),
                );
            }

            if ($sspAssetCriteriaTransfer->getSspAssetConditionsOrFail()->getAssignedBusinessUnitCompanyId()) {
                $sspAssetToCompanyBusinessUnitQuery
                    ->useSpyCompanyBusinessUnitQuery()
                        ->filterByFkCompany($sspAssetCriteriaTransfer->getSspAssetConditionsOrFail()->getAssignedBusinessUnitCompanyId())
                    ->endUse();
            }
        }

        /** @var \Propel\Runtime\Collection\ObjectCollection<\Orm\Zed\SelfServicePortal\Persistence\SpySspAssetToCompanyBusinessUnit> $sspAssetToCompanyBusinessUnitEntities */
        $sspAssetToCompanyBusinessUnitEntities = $sspAssetToCompanyBusinessUnitQuery->find();

        $sspAssetCollectionTransfer = $this->getFactory()
            ->createSspAssetBusinessUnitAssignmentMapper()
            ->mapSspAssetToCompanyBusinessUnitEntitiesToSspAssetCollection(
                $sspAssetToCompanyBusinessUnitEntities,
                $sspAssetCollectionTransfer,
            );

        return $sspAssetCollectionTransfer;
    }

    /**
     * @param \Orm\Zed\SelfServicePortal\Persistence\SpySspAssetQuery $sspAssetQuery
     * @param \Generated\Shared\Transfer\SspAssetCriteriaTransfer $sspAssetCriteriaTransfer
     *
     * @return \Orm\Zed\SelfServicePortal\Persistence\SpySspAssetQuery
     */
    protected function applyAssetConditions(
        SpySspAssetQuery $sspAssetQuery,
        SspAssetCriteriaTransfer $sspAssetCriteriaTransfer
    ): SpySspAssetQuery {
        $sspAssetConditionsTransfer = $sspAssetCriteriaTransfer->getSspAssetConditions();

        if (!$sspAssetConditionsTransfer) {
            return $sspAssetQuery;
        }

        if ($sspAssetConditionsTransfer->getSspAssetIds()) {
            $sspAssetQuery->filterByIdSspAsset_In($sspAssetConditionsTransfer->getSspAssetIds());
        }

        if ($sspAssetConditionsTransfer->getReferences()) {
            $sspAssetQuery->filterByReference_In($sspAssetConditionsTransfer->getReferences());
        }

        if ($sspAssetConditionsTransfer->getStatus()) {
            $sspAssetQuery->filterByStatus($sspAssetConditionsTransfer->getStatus());
        }

        if ($sspAssetConditionsTransfer->getAssignedBusinessUnitId()) {
            $sspAssetQuery
                ->useSpySspAssetToCompanyBusinessUnitExistsQuery()
                    ->filterByFkCompanyBusinessUnit($sspAssetConditionsTransfer->getAssignedBusinessUnitId())
                ->endUse();
        }

        if ($sspAssetConditionsTransfer->getAssignedBusinessUnitCompanyId()) {
            $sspAssetQuery
                ->useSpySspAssetToCompanyBusinessUnitExistsQuery()
                    ->useSpyCompanyBusinessUnitQuery()
                        ->filterByFkCompany($sspAssetConditionsTransfer->getAssignedBusinessUnitCompanyId())
                    ->endUse()
                ->endUse();
        }

        if ($sspAssetConditionsTransfer->getStatuses() !== []) {
            $sspAssetQuery->filterByStatus_In($sspAssetConditionsTransfer->getStatuses());
        }

        if ($sspAssetConditionsTransfer->getSearchText()) {
            $searchText = '%' . $sspAssetConditionsTransfer->getSearchTextOrFail() . '%';
            $sspAssetQuery->filterByName_Like($searchText)
                ->_or()
                ->filterByReference_Like($searchText)
                ->_or()
                ->filterBySerialNumber_Like($searchText);
        }

        if ($sspAssetConditionsTransfer->getImageFileIds() !== []) {
            $sspAssetQuery->filterByFkImageFile_In($sspAssetConditionsTransfer->getImageFileIds());
        }

        return $sspAssetQuery;
    }

    /**
     * @param \Orm\Zed\SelfServicePortal\Persistence\SpySspAssetQuery $sspAssetQuery
     * @param \Generated\Shared\Transfer\SspAssetCriteriaTransfer $sspAssetCriteriaTransfer
     *
     * @return \Orm\Zed\SelfServicePortal\Persistence\SpySspAssetQuery
     */
    protected function applyAssetSorting(
        SpySspAssetQuery $sspAssetQuery,
        SspAssetCriteriaTransfer $sspAssetCriteriaTransfer
    ): SpySspAssetQuery {
        $sortCollection = $sspAssetCriteriaTransfer->getSortCollection();

        if (!$sortCollection->count()) {
            return $sspAssetQuery;
        }

        foreach ($sortCollection as $sort) {
            $field = $sort->getFieldOrFail();
            if ($field === SspAssetTransfer::CREATED_DATE) {
                $field = SpySspAssetTableMap::COL_CREATED_AT;
            }
            $direction = $sort->getIsAscending() ? Criteria::ASC : Criteria::DESC;
            $sspAssetQuery->orderBy($field, $direction);
        }

        return $sspAssetQuery;
    }

    /**
     * @param \Propel\Runtime\ActiveQuery\ModelCriteria $query
     * @param \Generated\Shared\Transfer\PaginationTransfer|null $paginationTransfer
     *
     * @return \Propel\Runtime\Collection\Collection
     */
    protected function getAssetPaginatedCollection(ModelCriteria $query, ?PaginationTransfer $paginationTransfer = null): Collection
    {
        if ($paginationTransfer === null) {
            return $query->find();
        }

        $page = $paginationTransfer
            ->requirePage()
            ->getPageOrFail();

        $maxPerPage = $paginationTransfer
            ->requireMaxPerPage()
            ->getMaxPerPageOrFail();

        $paginationModel = $query->paginate($page, $maxPerPage);

        $paginationTransfer->setNbResults($paginationModel->getNbResults());
        $paginationTransfer->setFirstIndex($paginationModel->getFirstIndex());
        $paginationTransfer->setLastIndex($paginationModel->getLastIndex());
        $paginationTransfer->setFirstPage($paginationModel->getFirstPage());
        $paginationTransfer->setLastPage($paginationModel->getLastPage());
        $paginationTransfer->setNextPage($paginationModel->getNextPage());
        $paginationTransfer->setPreviousPage($paginationModel->getPreviousPage());

        return $paginationModel->getResults();
    }

    /**
     * @param array<int> $salesOrderItemIds
     *
     * @return array<\Generated\Shared\Transfer\SspAssetTransfer>
     */
    public function getSspAssetsBySalesOrderItemIds(array $salesOrderItemIds): array
    {
        if (!$salesOrderItemIds) {
            return [];
        }

        $salesOrderItemSspAssetQuery = $this->getFactory()
            ->getSalesOrderItemSspAssetQuery()
            ->filterByFkSalesOrderItem_In($salesOrderItemIds);

        $salesOrderItemSspAssetEntities = $salesOrderItemSspAssetQuery->find();
        $sspAssetTransfers = [];

        foreach ($salesOrderItemSspAssetEntities as $salesOrderItemSspAssetEntity) {
            $sspAssetTransfer = new SspAssetTransfer();
            $sspAssetTransfer->fromArray($salesOrderItemSspAssetEntity->toArray(), true);
            $sspAssetTransfer->offsetSet('idSalesOrderItem', $salesOrderItemSspAssetEntity->getFkSalesOrderItem());

            $sspAssetTransfers[] = $sspAssetTransfer;
        }

        return $sspAssetTransfers;
    }

    /**
     * @deprecated Use getSspAssetsBySalesOrderItemIds() and business layer indexation instead.
     *
     * @param array<int> $salesOrderItemIds
     *
     * @return array<int, \Generated\Shared\Transfer\SspAssetTransfer>
     */
    public function getSspAssetsIndexedByIdSalesOrderItem(array $salesOrderItemIds): array
    {
        if (!$salesOrderItemIds) {
            return [];
        }

        $salesOrderItemSspAssetQuery = $this->getFactory()
            ->getSalesOrderItemSspAssetQuery()
            ->filterByFkSalesOrderItem_In($salesOrderItemIds);

        $salesOrderItemSspAssetEntities = $salesOrderItemSspAssetQuery->find();
        $sspAssetsIndexedByIdSalesOrderItem = [];

        foreach ($salesOrderItemSspAssetEntities as $salesOrderItemSspAssetEntity) {
            $sspAssetTransfer = new SspAssetTransfer();
            $sspAssetTransfer->fromArray($salesOrderItemSspAssetEntity->toArray(), true);

            $sspAssetsIndexedByIdSalesOrderItem[(int)$salesOrderItemSspAssetEntity->getFkSalesOrderItem()] = $sspAssetTransfer;
        }

        return $sspAssetsIndexedByIdSalesOrderItem;
    }

    /**
     * @param array<int> $salesOrderItemIds
     *
     * @return void
     */
    public function deleteSalesOrderItemProductClassesBySalesOrderItemIds(array $salesOrderItemIds): void
    {
        if (!$salesOrderItemIds) {
            return;
        }

        $this->getFactory()
            ->createSalesOrderItemProductClassQuery()
            ->filterByFkSalesOrderItem_In($salesOrderItemIds)
            ->delete();
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery $query
     * @param \Generated\Shared\Transfer\SspServiceCriteriaTransfer $sspServiceCriteriaTransfer
     *
     * @return \Propel\Runtime\Collection\Collection<\Orm\Zed\Sales\Persistence\SpySalesOrderItem>
     */
    protected function getSspServicePaginatedCollection(
        SpySalesOrderItemQuery $query,
        SspServiceCriteriaTransfer $sspServiceCriteriaTransfer
    ): Collection {
        if (!$sspServiceCriteriaTransfer->getPagination()) {
            return $query->find();
        }

        $paginationTransfer = $sspServiceCriteriaTransfer->getPaginationOrFail();
        $page = $paginationTransfer
            ->requirePage()
            ->getPageOrFail();

        $maxPerPage = $paginationTransfer
            ->requireMaxPerPage()
            ->getMaxPerPageOrFail();

        $paginationModel = $query->paginate($page, $maxPerPage);

        $paginationTransfer->setNbResults($paginationModel->getNbResults());
        $paginationTransfer->setFirstIndex($paginationModel->getFirstIndex());
        $paginationTransfer->setLastIndex($paginationModel->getLastIndex());
        $paginationTransfer->setFirstPage($paginationModel->getFirstPage());
        $paginationTransfer->setLastPage($paginationModel->getLastPage());
        $paginationTransfer->setNextPage($paginationModel->getNextPage());
        $paginationTransfer->setPreviousPage($paginationModel->getPreviousPage());

        return $paginationModel->getResults();
    }
}

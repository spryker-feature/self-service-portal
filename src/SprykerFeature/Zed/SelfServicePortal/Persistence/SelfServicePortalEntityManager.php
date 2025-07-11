<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Persistence;

use Generated\Shared\Transfer\CompanyBusinessUnitTransfer;
use Generated\Shared\Transfer\CompanyTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\FileAttachmentTransfer;
use Generated\Shared\Transfer\FileCollectionTransfer;
use Generated\Shared\Transfer\ProductClassTransfer;
use Generated\Shared\Transfer\SalesOrderItemSspAssetTransfer;
use Generated\Shared\Transfer\SspAssetTransfer;
use Generated\Shared\Transfer\SspInquiryTransfer;
use Orm\Zed\SelfServicePortal\Persistence\SpyCompanyBusinessUnitFileQuery;
use Orm\Zed\SelfServicePortal\Persistence\SpyCompanyFileQuery;
use Orm\Zed\SelfServicePortal\Persistence\SpyCompanyUserFileQuery;
use Orm\Zed\SelfServicePortal\Persistence\SpySalesOrderItemSspAsset;
use Orm\Zed\SelfServicePortal\Persistence\SpySspAsset;
use Orm\Zed\SelfServicePortal\Persistence\SpySspAssetFileQuery;
use Orm\Zed\SelfServicePortal\Persistence\SpySspAssetToCompanyBusinessUnitQuery;
use Orm\Zed\SelfServicePortal\Persistence\SpySspInquiry;
use Orm\Zed\SelfServicePortal\Persistence\SpySspInquiryFile;
use Orm\Zed\SelfServicePortal\Persistence\SpySspInquirySalesOrder;
use Orm\Zed\SelfServicePortal\Persistence\SpySspInquirySspAsset;
use Propel\Runtime\Exception\InvalidArgumentException;
use Spryker\Zed\Kernel\Persistence\AbstractEntityManager;

/**
 * @method \SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalPersistenceFactory getFactory()
 */
class SelfServicePortalEntityManager extends AbstractEntityManager implements SelfServicePortalEntityManagerInterface
{
    /**
     * @param int $idProductConcrete
     * @param int $idShipmentType
     *
     * @return void
     */
    public function createProductShipmentType(int $idProductConcrete, int $idShipmentType): void
    {
        $productShipmentTypeEntity = $this->getFactory()
            ->createProductShipmentTypeQuery()
            ->filterByFkProduct($idProductConcrete)
            ->filterByFkShipmentType($idShipmentType)
            ->findOneOrCreate();

        $productShipmentTypeEntity->save();
    }

    /**
     * @param int $idProductConcrete
     * @param list<int> $shipmentTypeIds
     *
     * @return void
     */
    public function deleteProductShipmentTypesByIdProductConcreteAndShipmentTypeIds(
        int $idProductConcrete,
        array $shipmentTypeIds
    ): void {
        $this->getFactory()
            ->createProductShipmentTypeQuery()
            ->filterByFkProduct($idProductConcrete)
            ->filterByFkShipmentType_In($shipmentTypeIds)
            ->delete();
    }

    /**
     * @param int $idProduct
     * @param array<int> $productClassIds
     *
     * @return void
     */
    public function saveProductClassesForProduct(int $idProduct, array $productClassIds): void
    {
        $this->deleteProductClassesByProductId($idProduct);

        foreach ($productClassIds as $idProductClass) {
            $productToProductClassEntity = $this->getFactory()->createProductToProductClassQuery()
                ->filterByFkProduct($idProduct)
                ->filterByFkProductClass($idProductClass)
                ->findOneOrCreate();

            $productToProductClassEntity->save();
        }
    }

    /**
     * @param int $idProduct
     *
     * @return void
     */
    public function deleteProductClassesByProductId(int $idProduct): void
    {
        $this->getFactory()
            ->createProductToProductClassQuery()
            ->filterByFkProduct($idProduct)
            ->delete();
    }

    /**
     * @param int $idSalesOrderItem
     * @param \Generated\Shared\Transfer\ProductClassTransfer $productClassTransfer
     *
     * @return void
     */
    public function saveSalesOrderItemProductClass(int $idSalesOrderItem, ProductClassTransfer $productClassTransfer): void
    {
        $salesProductClassEntity = $this->getFactory()
            ->createSalesProductClassQuery()
            ->filterByName($productClassTransfer->getName())
            ->findOneOrCreate();

        if ($salesProductClassEntity->isNew()) {
            $salesProductClassEntity->save();
        }

        $salesOrderItemProductClassEntity = $this->getFactory()
            ->createSalesOrderItemProductClassQuery()
            ->filterByFkSalesOrderItem($idSalesOrderItem)
            ->filterByFkSalesProductClass($salesProductClassEntity->getIdSalesProductClass())
            ->findOneOrCreate();

        if ($salesOrderItemProductClassEntity->isNew()) {
            $salesOrderItemProductClassEntity->save();
        }
    }

    /**
     * @param \Generated\Shared\Transfer\FileAttachmentTransfer $fileAttachmentTransfer
     *
     * @return void
     */
    public function deleteFileAttachmentCollection(
        FileAttachmentTransfer $fileAttachmentTransfer
    ): void {
        $idFile = $fileAttachmentTransfer->getFileOrFail()->getIdFileOrFail();

        $companyIds = array_map(
            static fn (CompanyTransfer $companyTransfer): int => $companyTransfer->getIdCompanyOrFail(),
            $fileAttachmentTransfer->getCompanyCollection()?->getCompanies()?->getArrayCopy() ?? [],
        );

        $this->getFactory()->createCompanyFileQuery()
            ->filterByFkCompany_In($companyIds)
            ->filterByFkFile($idFile)
            ->delete();

        $businessUnitIds = array_map(
            static fn (CompanyBusinessUnitTransfer $businessUnitTransfer): int => $businessUnitTransfer->getIdCompanyBusinessUnitOrFail(),
            $fileAttachmentTransfer->getBusinessUnitCollection()?->getCompanyBusinessUnits()?->getArrayCopy() ?? [],
        );

        $this->getFactory()->createCompanyBusinessUnitFileQuery()
            ->filterByFkCompanyBusinessUnit_In($businessUnitIds)
            ->filterByFkFile($idFile)
            ->delete();

        $companyUserIds = array_map(
            static fn (CompanyUserTransfer $companyUserTransfer): int => $companyUserTransfer->getIdCompanyUserOrFail(),
            $fileAttachmentTransfer->getCompanyUserCollection()?->getCompanyUsers()?->getArrayCopy() ?? [],
        );

        $this->getFactory()->createCompanyUserFileQuery()
            ->filterByFkCompanyUser_In($companyUserIds)
            ->filterByFkFile($idFile)
            ->delete();

        $sspAssetIds = array_map(
            static fn (SspAssetTransfer $sspAssetTransfer): int => $sspAssetTransfer->getIdSspAssetOrFail(),
            $fileAttachmentTransfer->getSspAssetCollection()?->getSspAssets()?->getArrayCopy() ?? [],
        );

        $this->getFactory()->createSspAssetFileQuery()
            ->filterByFkSspAsset_In($sspAssetIds)
            ->filterByFkFile($idFile)
            ->delete();
    }

    /**
     * @param list<\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyFileQuery|\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyUserFileQuery|\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyBusinessUnitFileQuery|\Orm\Zed\SelfServicePortal\Persistence\SpySspAssetFileQuery> $fileAttachmentQueryList
     * @param list<int> $fileIds
     * @param bool $applyOnlyToModifiedQueries
     *
     * @return list<\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyFileQuery|\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyUserFileQuery|\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyBusinessUnitFileQuery|\Orm\Zed\SelfServicePortal\Persistence\SpySspAssetFileQuery>
     */
    protected function applyFileAttachmentByFileIdsCondition(
        array $fileAttachmentQueryList,
        array $fileIds,
        bool $applyOnlyToModifiedQueries
    ): array {
        foreach ($fileAttachmentQueryList as $fileAttachmentQuery) {
            if ($applyOnlyToModifiedQueries && !$fileAttachmentQuery->hasWhereClause()) {
                continue;
            }

            $fileAttachmentQuery->filterByFkFile_In($fileIds);
        }

        return $fileAttachmentQueryList;
    }

    /**
     * @param list<\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyFileQuery|\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyUserFileQuery|\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyBusinessUnitFileQuery> $fileAttachmentQueryList
     * @param list<int> $companyIds
     *
     * @return list<\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyFileQuery|\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyUserFileQuery|\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyBusinessUnitFileQuery>
     */
    protected function applyFileAttachmentByCompanyIdsCondition(
        array $fileAttachmentQueryList,
        array $companyIds
    ): array {
        foreach ($fileAttachmentQueryList as $fileAttachmentQuery) {
            if ($fileAttachmentQuery instanceof SpyCompanyFileQuery) {
                $fileAttachmentQuery->filterByFkCompany_In($companyIds);
            }
        }

        return $fileAttachmentQueryList;
    }

    /**
     * @param list<\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyFileQuery|\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyUserFileQuery|\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyBusinessUnitFileQuery> $fileAttachmentQueryList
     * @param list<int> $companyUserIds
     *
     * @return list<\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyFileQuery|\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyUserFileQuery|\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyBusinessUnitFileQuery>
     */
    protected function applyFileAttachmentByCompanyUserIdsCondition(
        array $fileAttachmentQueryList,
        array $companyUserIds
    ): array {
        foreach ($fileAttachmentQueryList as $fileAttachmentQuery) {
            if ($fileAttachmentQuery instanceof SpyCompanyUserFileQuery) {
                $fileAttachmentQuery->filterByFkCompanyUser_In($companyUserIds);
            }
        }

        return $fileAttachmentQueryList;
    }

    /**
     * @param list<\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyFileQuery|\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyUserFileQuery|\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyBusinessUnitFileQuery> $fileAttachmentQueryList
     * @param list<int> $companyBusinessUnitIds
     *
     * @return list<\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyFileQuery|\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyUserFileQuery|\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyBusinessUnitFileQuery>
     */
    protected function applyFileAttachmentByCompanyBusinessUnitIdsCondition(
        array $fileAttachmentQueryList,
        array $companyBusinessUnitIds
    ): array {
        foreach ($fileAttachmentQueryList as $fileAttachmentQuery) {
            if ($fileAttachmentQuery instanceof SpyCompanyBusinessUnitFileQuery) {
                $fileAttachmentQuery->filterByFkCompanyBusinessUnit_In($companyBusinessUnitIds);
            }
        }

        return $fileAttachmentQueryList;
    }

    /**
     * @param list<\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyFileQuery|\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyUserFileQuery|\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyBusinessUnitFileQuery|\Orm\Zed\SelfServicePortal\Persistence\SpySspAssetFileQuery> $fileAttachmentQueryList
     * @param list<int> $assetIds
     *
     * @return list<\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyFileQuery|\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyUserFileQuery|\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyBusinessUnitFileQuery|\Orm\Zed\SelfServicePortal\Persistence\SpySspAssetFileQuery>
     */
    protected function applyFileAttachmentByAssetIdsCondition(
        array $fileAttachmentQueryList,
        array $assetIds
    ): array {
        foreach ($fileAttachmentQueryList as $fileAttachmentQuery) {
            if ($fileAttachmentQuery instanceof SpySspAssetFileQuery) {
                $fileAttachmentQuery->filterByFkSspAsset_In($assetIds);
            }
        }

        return $fileAttachmentQueryList;
    }

    /**
     * @param list<\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyFileQuery|\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyUserFileQuery|\Orm\Zed\SelfServicePortal\Persistence\SpyCompanyBusinessUnitFileQuery|\Orm\Zed\SelfServicePortal\Persistence\SpySspAssetFileQuery> $fileAttachmentQueryList
     *
     * @return void
     */
    protected function deleteFileAttachments(array $fileAttachmentQueryList): void
    {
        foreach ($fileAttachmentQueryList as $fileAttachmentQuery) {
            if (!$fileAttachmentQuery->hasWhereClause()) {
                continue;
            }

            $fileAttachmentQuery->delete();
        }
    }

    /**
     * @param \Generated\Shared\Transfer\FileAttachmentTransfer $fileAttachmentTransfer
     *
     * @return \Generated\Shared\Transfer\FileAttachmentTransfer
     */
    public function saveFileAttachment(FileAttachmentTransfer $fileAttachmentTransfer): FileAttachmentTransfer
    {
        $fileAttachmentSaver = $this->getFactory()->createFileAttachmentSaver();

        $fileAttachmentSaver->saveBusinessUnitFileAttachment($fileAttachmentTransfer);
        $fileAttachmentSaver->saveCompanyFileAttachments($fileAttachmentTransfer);
        $fileAttachmentSaver->saveCompanyUserAttachment($fileAttachmentTransfer);
        $fileAttachmentSaver->saveSspAssetFileAttachment($fileAttachmentTransfer);

        return $fileAttachmentTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\SspInquiryTransfer $sspInquiryTransfer
     *
     * @return \Generated\Shared\Transfer\SspInquiryTransfer
     */
    public function createSspInquiry(SspInquiryTransfer $sspInquiryTransfer): SspInquiryTransfer
    {
        return $this->saveSspInquiry($sspInquiryTransfer, new SpySspInquiry());
    }

    /**
     * @param \Generated\Shared\Transfer\SspInquiryTransfer $sspInquiryTransfer
     *
     * @return \Generated\Shared\Transfer\SspInquiryTransfer|null
     */
    public function updateSspInquiry(SspInquiryTransfer $sspInquiryTransfer): ?SspInquiryTransfer
    {
        $sspInquiryQuery = $this->getFactory()->createSspInquiryQuery();

        $sspInquiryEntity = $sspInquiryQuery->filterByIdSspInquiry($sspInquiryTransfer->getIdSspInquiry())->findOne();

        if (!$sspInquiryEntity) {
            return null;
        }

        return $this->saveSspInquiry($sspInquiryTransfer, $sspInquiryEntity);
    }

    /**
     * @param \Generated\Shared\Transfer\SspInquiryTransfer $sspInquiryTransfer
     * @param \Orm\Zed\SelfServicePortal\Persistence\SpySspInquiry $sspInquiryEntity
     *
     * @return \Generated\Shared\Transfer\SspInquiryTransfer
     */
    protected function saveSspInquiry(SspInquiryTransfer $sspInquiryTransfer, SpySspInquiry $sspInquiryEntity): SspInquiryTransfer
    {
        $sspInquiryEntity = $this->getFactory()->createSspInquiryMapper()->mapSspInquiryTransferToSspInquiryEntity($sspInquiryTransfer, $sspInquiryEntity);

        if ($sspInquiryTransfer->getStatus()) {
            $stateMachineItemState = $this->getFactory()->getStateMachineItemStatePropelQuery()->findOneByName($sspInquiryTransfer->getStatus());
            if ($stateMachineItemState) {
                $sspInquiryEntity->setFkStateMachineItemState($stateMachineItemState->getIdStateMachineItemState());
            }
        }

        if ($sspInquiryEntity->isNew() || $sspInquiryEntity->isModified()) {
            $sspInquiryEntity->save();
        }

        $sspInquiryTransfer = $this->getFactory()->createSspInquiryMapper()->mapSspInquiryEntityToSspInquiryTransfer($sspInquiryEntity, $sspInquiryTransfer);

        return $sspInquiryTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\SspInquiryTransfer $sspInquiryTransfer
     *
     * @return \Generated\Shared\Transfer\SspInquiryTransfer
     */
    public function createSspInquiryFiles(SspInquiryTransfer $sspInquiryTransfer): SspInquiryTransfer
    {
        foreach ($sspInquiryTransfer->getFiles() as $fileTransfer) {
            $sspInquiryFileEntity = (new SpySspInquiryFile())
                ->setFkFile($fileTransfer->getIdFileOrFail())
                ->setFkSspInquiry($sspInquiryTransfer->getIdSspInquiryOrFail());

            $sspInquiryFileEntity->save();
        }

        return $sspInquiryTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\SspInquiryTransfer $sspInquiryTransfer
     *
     * @return \Generated\Shared\Transfer\SspInquiryTransfer
     */
    public function createSspInquirySalesOrder(SspInquiryTransfer $sspInquiryTransfer): SspInquiryTransfer
    {
        $sspInquirySalesOrderEntity = (new SpySspInquirySalesOrder())
            ->setFkSspInquiry($sspInquiryTransfer->getIdSspInquiryOrFail())
            ->setFkSalesOrder($sspInquiryTransfer->getOrderOrFail()->getIdSalesOrderOrFail());

        $sspInquirySalesOrderEntity->save();

        return $sspInquiryTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\SspInquiryTransfer $sspInquiryTransfer
     *
     * @return \Generated\Shared\Transfer\SspInquiryTransfer
     */
    public function createSspInquirySspAsset(SspInquiryTransfer $sspInquiryTransfer): SspInquiryTransfer
    {
        $sspInquirySspAssetEntity = (new SpySspInquirySspAsset())
            ->setFkSspInquiry($sspInquiryTransfer->getIdSspInquiryOrFail())
            ->setFkSspAsset($sspInquiryTransfer->getSspAssetOrFail()->getIdSspAssetOrFail());

        $sspInquirySspAssetEntity->save();

        return $sspInquiryTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\FileCollectionTransfer $fileCollectionTransfer
     *
     * @return void
     */
    public function deleteSspInquiryFileRelation(FileCollectionTransfer $fileCollectionTransfer): void
    {
        $fileIds = [];

        foreach ($fileCollectionTransfer->getFiles() as $fileTransfer) {
            $fileIds[] = $fileTransfer->getIdFileOrFail();
        }

        if (!$fileIds) {
            return;
        }

        $this->getFactory()->createSspInquiryFileQuery()->filterByFkFile_In($fileIds)->delete();
    }

    /**
     * @param \Generated\Shared\Transfer\SspAssetTransfer $sspAssetTransfer
     *
     * @return \Generated\Shared\Transfer\SspAssetTransfer
     */
    public function createSspAsset(SspAssetTransfer $sspAssetTransfer): SspAssetTransfer
    {
        $spySspAssetEntity = $this->getFactory()
            ->createAssetMapper()
            ->mapSspAssetTransferToSpySspAssetEntity($sspAssetTransfer, new SpySspAsset());

        $spySspAssetEntity->save();
        $sspAssetTransfer->setIdSspAsset($spySspAssetEntity->getIdSspAsset());

        return $this->getFactory()
            ->createAssetMapper()
            ->mapSpySspAssetEntityToSspAssetTransfer($spySspAssetEntity, $sspAssetTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\SspAssetTransfer $sspAssetTransfer
     *
     * @throws \Propel\Runtime\Exception\InvalidArgumentException
     *
     * @return \Generated\Shared\Transfer\SspAssetTransfer
     */
    public function updateSspAsset(SspAssetTransfer $sspAssetTransfer): SspAssetTransfer
    {
        $spySspAssetEntity = $this->getFactory()
            ->createSspAssetQuery()
            ->findOneByIdSspAsset($sspAssetTransfer->getIdSspAssetOrFail());

        if (!$spySspAssetEntity) {
            throw new InvalidArgumentException('Ssp Asset not found');
        }

        $spySspAssetEntity = $this->getFactory()
            ->createAssetMapper()
            ->mapSspAssetTransferToSpySspAssetEntity($sspAssetTransfer, $spySspAssetEntity);

        if ($spySspAssetEntity->isModified()) {
            $spySspAssetEntity->save();
        }

        return $this->getFactory()
            ->createAssetMapper()
            ->mapSpySspAssetEntityToSspAssetTransfer($spySspAssetEntity, $sspAssetTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\SalesOrderItemSspAssetTransfer $salesOrderItemSspAssetTransfer
     *
     * @return void
     */
    public function createSalesOrderItemSspAsset(SalesOrderItemSspAssetTransfer $salesOrderItemSspAssetTransfer): void
    {
        $salesOrderItemSspAssetEntity = new SpySalesOrderItemSspAsset();
        $salesOrderItemSspAssetEntity->fromArray($salesOrderItemSspAssetTransfer->toArray());
        $salesOrderItemSspAssetEntity->setFkSalesOrderItem($salesOrderItemSspAssetTransfer->getSalesOrderItemOrFail()->getIdSalesOrderItemOrFail());
        $salesOrderItemSspAssetEntity->save();
    }

    /**
     * @param int $idSspAsset
     * @param array<int> $businessUnitIds
     *
     * @return void
     */
    public function deleteAssetToCompanyBusinessUnitRelations(int $idSspAsset, array $businessUnitIds): void
    {
        SpySspAssetToCompanyBusinessUnitQuery::create()
            ->filterByFkSspAsset($idSspAsset)
            ->filterByFkCompanyBusinessUnit_In($businessUnitIds)
            ->delete();
    }

    /**
     * @param int $idSspAsset
     * @param array<int> $businessUnitIds
     *
     * @return void
     */
    public function createAssetToCompanyBusinessUnitRelation(int $idSspAsset, array $businessUnitIds): void
    {
        foreach ($businessUnitIds as $idCompanyBusinessUnit) {
            $assetCompanyBusinessUnitRelationEntity = $this->getFactory()
                ->createSspAssetToCompanyBusinessUnitQuery()
                ->filterByFkSspAsset($idSspAsset)
                ->filterByFkCompanyBusinessUnit($idCompanyBusinessUnit)
                ->findOneOrCreate();

            if ($assetCompanyBusinessUnitRelationEntity->isNew()) {
                $assetCompanyBusinessUnitRelationEntity->save();
            }
        }
    }

    /**
     * @param int $idProductConcrete
     *
     * @return void
     */
    public function deleteProductConcreteToProductClassRelations(int $idProductConcrete): void
    {
        $this->getFactory()
            ->createProductToProductClassQuery()
            ->filterByFkProduct($idProductConcrete)
            ->delete();
    }

    /**
     * @param int $idProductConcrete
     * @param array<int> $productClassIds
     *
     * @return void
     */
    public function saveProductConcreteProductClassRelations(int $idProductConcrete, array $productClassIds): void
    {
        foreach ($productClassIds as $idProductClass) {
            $productConcreteProductClassEntity = $this->getFactory()
                ->createProductToProductClassQuery()
                ->filterByFkProduct($idProductConcrete)
                ->filterByFkProductClass($idProductClass)
                ->findOneOrCreate();

            if ($productConcreteProductClassEntity->isNew()) {
                $productConcreteProductClassEntity->save();
            }
        }
    }
}

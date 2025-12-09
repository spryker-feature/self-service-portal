<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Business\Service\Expander;

use ArrayObject;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\ProductClassConditionsTransfer;
use Generated\Shared\Transfer\ProductClassCriteriaTransfer;
use SprykerFeature\Zed\SelfServicePortal\Business\Asset\Extractor\SalesOrderItemIdExtractorInterface;
use SprykerFeature\Zed\SelfServicePortal\Business\Service\Grouper\ProductClassGrouperInterface;
use SprykerFeature\Zed\SelfServicePortal\Business\Service\Indexer\ProductClassIndexerInterface;
use SprykerFeature\Zed\SelfServicePortal\Business\Service\Utility\SkuExtractorInterface;
use SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalRepositoryInterface;

class OrderItemProductClassExpander implements OrderItemProductClassExpanderInterface
{
    /**
     * @var array<string>
     */
    protected static array $productClassExpanderIsExecuted = [];

    public function __construct(
        protected ProductClassGrouperInterface $productClassGrouper,
        protected SelfServicePortalRepositoryInterface $selfServicePortalRepository,
        protected ProductClassIndexerInterface $productClassIndexer,
        protected SkuExtractorInterface $skuExtractor,
        protected SalesOrderItemIdExtractorInterface $salesOrderItemIdExtractor
    ) {
    }

    public function expandOrderItemsWithProductClasses(OrderTransfer $orderTransfer): OrderTransfer
    {
        if (!$orderTransfer->getOrderReference()) {
            return $orderTransfer;
        }

        if (in_array($orderTransfer->getOrderReferenceOrFail(), static::$productClassExpanderIsExecuted)) {
            return $orderTransfer;
        }

        static::$productClassExpanderIsExecuted[] = $orderTransfer->getOrderReferenceOrFail();

        $itemTransfers = $orderTransfer->getItems()->getArrayCopy();

        if (!$itemTransfers) {
            return $orderTransfer;
        }

        $skus = $this->skuExtractor->extractSkusFromItemTransfers($itemTransfers);

        if (!$skus) {
            return $orderTransfer;
        }

        $productClassCollectionTransfer = $this->selfServicePortalRepository->getProductClassCollection(
            (new ProductClassCriteriaTransfer())
            ->setProductClassConditions(
                (new ProductClassConditionsTransfer())->setSkus($skus),
            ),
        );

        $productClassesBySkus = $this->productClassIndexer->getProductClassesIndexedBySku($productClassCollectionTransfer->getProductClasses()->getArrayCopy());

        if (!$productClassesBySkus) {
            return $orderTransfer;
        }

        $salesOrderItemIdToSkuMap = $this->createSalesOrderItemIdToSkuMap($itemTransfers);

        $productClassesBySalesOrderItemId = $this->productClassGrouper->groupProductClassesBySalesOrderItemIds(
            $productClassesBySkus,
            $salesOrderItemIdToSkuMap,
        );

        if (!$productClassesBySalesOrderItemId) {
            return $orderTransfer;
        }

        return $this->expandOrderItemTransfersWithProductClasses($orderTransfer, $productClassesBySalesOrderItemId);
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     *
     * @return array<int, string>
     */
    protected function createSalesOrderItemIdToSkuMap(array $itemTransfers): array
    {
        $salesOrderItemIdToSkuMap = [];

        foreach ($itemTransfers as $itemTransfer) {
            $salesOrderItemIdToSkuMap[(int)$itemTransfer->getIdSalesOrderItem()] = (string)$itemTransfer->getSku();
        }

        return $salesOrderItemIdToSkuMap;
    }

    /**
     * @param \Generated\Shared\Transfer\OrderTransfer $orderTransfer
     * @param array<int, array<\Generated\Shared\Transfer\ProductClassTransfer>> $productClassesBySalesOrderItemId
     *
     * @return \Generated\Shared\Transfer\OrderTransfer
     */
    protected function expandOrderItemTransfersWithProductClasses(
        OrderTransfer $orderTransfer,
        array $productClassesBySalesOrderItemId
    ): OrderTransfer {
        foreach ($orderTransfer->getItems() as $itemTransfer) {
            $idSalesOrderItem = $itemTransfer->getIdSalesOrderItem();

            if (!isset($productClassesBySalesOrderItemId[$idSalesOrderItem])) {
                continue;
            }

            $itemTransfer->setProductClasses(new ArrayObject($productClassesBySalesOrderItemId[$idSalesOrderItem]));
        }

        return $orderTransfer;
    }
}

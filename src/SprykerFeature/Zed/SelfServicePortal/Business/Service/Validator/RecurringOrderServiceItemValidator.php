<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\SelfServicePortal\Business\Service\Validator;

use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\ProductClassConditionsTransfer;
use Generated\Shared\Transfer\ProductClassCriteriaTransfer;
use SprykerFeature\Zed\SelfServicePortal\Business\Service\Indexer\ProductClassIndexerInterface;
use SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalRepositoryInterface;
use SprykerFeature\Zed\SelfServicePortal\SelfServicePortalConfig;

class RecurringOrderServiceItemValidator implements RecurringOrderServiceItemValidatorInterface
{
    protected const string GLOSSARY_KEY_SERVICE_DELIVERY_REQUIRED = 'self_service_portal.recurring_order.add_product.error.service_delivery_required';

    protected const string PARAMETER_SKU = '%sku%';

    public function __construct(
        protected SelfServicePortalConfig $selfServicePortalConfig,
        protected SelfServicePortalRepositoryInterface $selfServicePortalRepository,
        protected ProductClassIndexerInterface $productClassIndexer
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     */
    public function validate(array $itemTransfers, string $sku): ?ErrorTransfer
    {
        $shipmentTypeKeys = $this->selfServicePortalConfig->getRecurringOrderServiceShipmentTypeKeys();

        if ($shipmentTypeKeys === []) {
            return null;
        }

        $serviceProductSkus = $this->getServiceProductSkus($itemTransfers);

        if ($serviceProductSkus === []) {
            return null;
        }

        foreach ($itemTransfers as $itemTransfer) {
            if (!in_array($itemTransfer->getSku(), $serviceProductSkus, true)) {
                continue;
            }

            if (!$this->hasShipmentTypeKey($itemTransfer, $shipmentTypeKeys)) {
                return $this->createError($sku);
            }
        }

        return null;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     *
     * @return array<string>
     */
    protected function getServiceProductSkus(array $itemTransfers): array
    {
        $skus = $this->extractSkus($itemTransfers);

        if ($skus === []) {
            return [];
        }

        $productClassCollectionTransfer = $this->selfServicePortalRepository->getProductClassCollection(
            (new ProductClassCriteriaTransfer())->setProductClassConditions(
                (new ProductClassConditionsTransfer())->setSkus($skus),
            ),
        );

        $productClassTransfersBySku = $this->productClassIndexer->getProductClassesIndexedBySku(
            $productClassCollectionTransfer->getProductClasses()->getArrayCopy(),
        );

        return $this->filterServiceProductSkus($productClassTransfersBySku);
    }

    /**
     * @param array<string, array<\Generated\Shared\Transfer\ProductClassTransfer>> $productClassTransfersBySku
     *
     * @return array<string>
     */
    protected function filterServiceProductSkus(array $productClassTransfersBySku): array
    {
        $serviceProductClassName = $this->selfServicePortalConfig->getServiceProductClassName();
        $serviceProductSkus = [];

        foreach ($productClassTransfersBySku as $sku => $productClassTransfers) {
            if ($this->hasServiceProductClass($productClassTransfers, $serviceProductClassName)) {
                $serviceProductSkus[] = (string)$sku;
            }
        }

        return $serviceProductSkus;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ProductClassTransfer> $productClassTransfers
     */
    protected function hasServiceProductClass(array $productClassTransfers, string $serviceProductClassName): bool
    {
        foreach ($productClassTransfers as $productClassTransfer) {
            if ($productClassTransfer->getName() === $serviceProductClassName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     *
     * @return array<string>
     */
    protected function extractSkus(array $itemTransfers): array
    {
        $skus = [];

        foreach ($itemTransfers as $itemTransfer) {
            $sku = $itemTransfer->getSku();

            if ($sku !== null) {
                $skus[] = $sku;
            }
        }

        return array_values(array_unique($skus));
    }

    /**
     * @param array<string> $shipmentTypeKeys
     */
    protected function hasShipmentTypeKey(ItemTransfer $itemTransfer, array $shipmentTypeKeys): bool
    {
        $shipmentTypeKey = $itemTransfer->getShipment()?->getMethod()?->getShipmentType()?->getKey();

        return in_array($shipmentTypeKey, $shipmentTypeKeys, true);
    }

    protected function createError(string $sku): ErrorTransfer
    {
        return (new ErrorTransfer())
            ->setMessage(static::GLOSSARY_KEY_SERVICE_DELIVERY_REQUIRED)
            ->setParameters([static::PARAMETER_SKU => $sku]);
    }
}

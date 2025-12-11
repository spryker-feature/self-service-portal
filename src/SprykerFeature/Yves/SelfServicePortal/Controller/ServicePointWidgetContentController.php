<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\SelfServicePortal\Controller;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\ProductOfferStorageCriteriaTransfer;
use Generated\Shared\Transfer\ProductViewTransfer;
use Generated\Shared\Transfer\ServicePointStorageTransfer;
use Generated\Shared\Transfer\ShipmentTypeStorageTransfer;
use SprykerShop\Yves\ShopApplication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @method \SprykerFeature\Yves\SelfServicePortal\SelfServicePortalConfig getConfig()
 * @method \SprykerFeature\Yves\SelfServicePortal\SelfServicePortalFactory getFactory()
 */
class ServicePointWidgetContentController extends AbstractController
{
    protected const string REQUEST_PARAM_ID_PRODUCT_CONCRETE = 'id-product-concrete';

    protected const string REQUEST_PARAM_QUANTITY = 'quantity';

    protected const string REQUEST_PARAM_SERVICE_TYPE_UUID = 'service-type-uuid';

    protected const string REQUEST_PARAM_SHIPMENT_TYPE_UUID = 'shipment-type-uuid';

    protected const string REQUEST_PARAM_SERVICE_POINT_UUID = 'service-point-uuid';

    protected const string REQUEST_PARAM_SERVICE_TYPE_KEY = 'service-type-key';

    protected const string RESPONSE_KEY_CONTENT = 'content';

    protected const string VIEW_DATA_KEY_SHIPMENT_TYPE_UUID = 'shipmentTypeUuid';

    protected const string VIEW_DATA_KEY_SERVICE_POINT = 'servicePoint';

    protected const string VIEW_IS_SERVICE_POINT_REQUIRED = 'isServicePointRequired';

    protected const string VIEW_DATA_KEY_ITEMS = 'items';

    protected const string VIEW_DATA_KEY_PRODUCT = 'product';

    protected const string VIEW_DATA_KEY_QUANTITY = 'quantity';

    protected const string VIEW_DATA_KEY_PRODUCT_OFFER_STORAGE_CRITERIA = 'productOfferStorageCriteria';

    protected const string VIEW_DATA_KEY_SERVICE_TYPE_KEY = 'serviceTypeKey';

    protected const string VIEW_DATA_KEY_SERVICE_TYPE_UUID = 'serviceTypeUuid';

    public function indexAction(Request $request): JsonResponse
    {
        $servicePointWidgetContentViewData = $this->getServicePointWidgetContentViewData($request);

        return $this->jsonResponse([
            static::RESPONSE_KEY_CONTENT => $this->renderServicePointWidgetContent($servicePointWidgetContentViewData),
        ]);
    }

    /**
     * @param array<string, mixed> $servicePointWidgetContentViewData
     *
     * @return string
     */
    protected function renderServicePointWidgetContent(array $servicePointWidgetContentViewData): string
    {
        $response = $this->renderView(
            $this->getFactory()->getConfig()->getServicePointWidgetContentTemplatePath(),
            $servicePointWidgetContentViewData,
        );

        return $response->getContent() ?: '';
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return array<string, mixed>
     */
    protected function getServicePointWidgetContentViewData(Request $request): array
    {
        $idProductConcrete = (int)$request->query->get(static::REQUEST_PARAM_ID_PRODUCT_CONCRETE);
        $quantity = (int)$request->query->get(static::REQUEST_PARAM_QUANTITY, 1);
        $shipmentTypeUuid = (string)$request->query->get(static::REQUEST_PARAM_SHIPMENT_TYPE_UUID);
        $serviceTypeUuid = (string)$request->query->get(static::REQUEST_PARAM_SERVICE_TYPE_UUID);
        $serviceTypeKey = (string)$request->query->get(static::REQUEST_PARAM_SERVICE_TYPE_KEY);
        $servicePointUuid = (string)$request->query->get(static::REQUEST_PARAM_SERVICE_POINT_UUID);

        $storeName = $this->getFactory()->getStoreClient()->getCurrentStore()->getNameOrFail();

        $shipmentTypeStorageTransfer = $this->findShipmentTypeStorage($shipmentTypeUuid, $storeName);
        $productOfferStorageCriteriaTransfer = $this->createProductOfferStorageCriteria(
            $shipmentTypeUuid,
            $servicePointUuid,
        );
        $servicePointTransfer = $this->findServicePointStorage($servicePointUuid, $storeName);
        $productViewTransfer = $this->findProductView($idProductConcrete, $productOfferStorageCriteriaTransfer);
        $isServicePointRequired = $this->isServicePointRequired($shipmentTypeStorageTransfer->getKeyOrFail());
        $itemTransfers = $this->createItemTransfers($productViewTransfer->getSkuOrFail(), $quantity);

        return [
            static::VIEW_DATA_KEY_SERVICE_TYPE_UUID => $serviceTypeUuid,
            static::VIEW_DATA_KEY_SERVICE_TYPE_KEY => $serviceTypeKey,
            static::VIEW_DATA_KEY_SHIPMENT_TYPE_UUID => $shipmentTypeUuid,
            static::VIEW_DATA_KEY_SERVICE_POINT => $servicePointTransfer,
            static::VIEW_DATA_KEY_ITEMS => $itemTransfers,
            static::VIEW_DATA_KEY_PRODUCT => $productViewTransfer,
            static::VIEW_DATA_KEY_QUANTITY => $quantity,
            static::VIEW_IS_SERVICE_POINT_REQUIRED => $isServicePointRequired,
        ];
    }

    protected function findShipmentTypeStorage(string $shipmentTypeUuid, string $storeName): ShipmentTypeStorageTransfer
    {
        $shipmentTypeStorageCollectionTransfer = $this->getFactory()
            ->createShipmentTypeReader()
            ->getShipmentTypeStorageCollection([$shipmentTypeUuid], $storeName);

        $shipmentTypeStorageTransfer = $shipmentTypeStorageCollectionTransfer
            ->getShipmentTypeStorages()
            ->getIterator()
            ->current();

        if (!$shipmentTypeStorageTransfer) {
            throw new NotFoundHttpException('Shipment type not found.');
        }

        return $shipmentTypeStorageTransfer;
    }

    protected function createProductOfferStorageCriteria(string $shipmentTypeUuid, string $servicePointUuid): ProductOfferStorageCriteriaTransfer
    {
        $productOfferStorageCriteriaTransfer = (new ProductOfferStorageCriteriaTransfer());
        if ($shipmentTypeUuid) {
            $productOfferStorageCriteriaTransfer->addShipmentTypeUuid($shipmentTypeUuid);
        }

        if ($servicePointUuid) {
            $productOfferStorageCriteriaTransfer->addServicePointUuid($servicePointUuid);
        }

        return $productOfferStorageCriteriaTransfer;
    }

    protected function findServicePointStorage(string $servicePointUuid, string $storeName): ?ServicePointStorageTransfer
    {
        if (!$servicePointUuid) {
            return null;
        }

        $servicePointCollectionTransfer = $this->getFactory()
            ->createServicePointReader()
            ->getServicePointStorageCollection([$servicePointUuid], $storeName);

        $servicePointTransfer = $servicePointCollectionTransfer
            ->getServicePointStorages()
            ->getIterator()
            ->current();

        if (!$servicePointTransfer) {
            throw new NotFoundHttpException('Service point not found.');
        }

        return $servicePointTransfer;
    }

    protected function findProductView(
        int $idProductConcrete,
        ProductOfferStorageCriteriaTransfer $productOfferStorageCriteriaTransfer
    ): ProductViewTransfer {
        $productViewTransfer = $this->getFactory()
            ->createProductReader()
            ->findProductConcreteViewTransfer(
                $idProductConcrete,
                $this->getLocale(),
                $productOfferStorageCriteriaTransfer,
            );

        if (!$productViewTransfer) {
            throw new NotFoundHttpException('Product not found.');
        }

        return $productViewTransfer;
    }

    protected function isServicePointRequired(string $shipmentTypeKey): bool
    {
        return in_array(
            $shipmentTypeKey,
            $this->getFactory()->getConfig()->getShipmentTypeKeysRequiringServicePoint(),
        );
    }

    /**
     * @param string $sku
     * @param int $quantity
     *
     * @return array<\Generated\Shared\Transfer\ItemTransfer>
     */
    protected function createItemTransfers(string $sku, int $quantity): array
    {
        return [
            (new ItemTransfer())
                ->setSkuOrFail($sku)
                ->setQuantity($quantity)
                ->setIsMerchantCheckSkipped(true),
        ];
    }
}

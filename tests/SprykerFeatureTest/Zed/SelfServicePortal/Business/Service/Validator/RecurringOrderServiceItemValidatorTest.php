<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\SelfServicePortal\Business\Service\Validator;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\ProductClassCollectionTransfer;
use Generated\Shared\Transfer\ProductClassTransfer;
use Generated\Shared\Transfer\ShipmentMethodTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use Generated\Shared\Transfer\ShipmentTypeTransfer;
use SprykerFeature\Zed\SelfServicePortal\Business\Service\Indexer\ProductClassIndexer;
use SprykerFeature\Zed\SelfServicePortal\Business\Service\Validator\RecurringOrderServiceItemValidator;
use SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalRepositoryInterface;
use SprykerFeature\Zed\SelfServicePortal\SelfServicePortalConfig;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group SelfServicePortal
 * @group Business
 * @group Service
 * @group Validator
 * @group RecurringOrderServiceItemValidatorTest
 */
class RecurringOrderServiceItemValidatorTest extends Unit
{
    protected const string SERVICE_PRODUCT_CLASS_NAME = 'Service';

    protected const string SHIPMENT_TYPE_DELIVERY = 'delivery';

    protected const string SHIPMENT_TYPE_ON_SITE_SERVICE = 'on-site-service';

    protected const string SKU = 'service-001-1';

    protected const string SKU_REGULAR = '041_25904691';

    protected const string GLOSSARY_KEY = 'self_service_portal.recurring_order.add_product.error.service_delivery_required';

    public function testServiceItemDeliveredToAnAddressIsAccepted(): void
    {
        // Arrange
        $itemTransfers = [$this->createItem(true, static::SHIPMENT_TYPE_DELIVERY)];

        // Act
        $errorTransfer = $this->createValidator()->validate($itemTransfers, static::SKU);

        // Assert
        $this->assertNull($errorTransfer);
    }

    public function testServiceItemFulfilledOnSiteIsRejectedWithTheModuleOwnGlossaryKey(): void
    {
        // Arrange
        $itemTransfers = [$this->createItem(true, static::SHIPMENT_TYPE_ON_SITE_SERVICE)];

        // Act
        $errorTransfer = $this->createValidator()->validate($itemTransfers, static::SKU);

        // Assert
        $this->assertNotNull($errorTransfer);
        $this->assertSame(static::GLOSSARY_KEY, $errorTransfer->getMessage());
        $this->assertSame(['%sku%' => static::SKU], $errorTransfer->getParameters());
    }

    public function testServiceItemWithoutAShipmentTypeIsRejected(): void
    {
        // Arrange
        $itemTransfers = [$this->createItem(true, null)];

        // Act
        $errorTransfer = $this->createValidator()->validate($itemTransfers, static::SKU);

        // Assert
        $this->assertNotNull($errorTransfer);
    }

    public function testItemWithoutServiceClassIsAccepted(): void
    {
        // Arrange
        $itemTransfers = [$this->createItem(false, static::SHIPMENT_TYPE_ON_SITE_SERVICE)];

        // Act
        $errorTransfer = $this->createValidator()->validate($itemTransfers, static::SKU);

        // Assert
        $this->assertNull($errorTransfer);
    }

    public function testOneUnsupportedServiceItemRejectsTheWholeAddition(): void
    {
        // Arrange
        $itemTransfers = [
            $this->createItem(true, static::SHIPMENT_TYPE_DELIVERY),
            $this->createItem(true, static::SHIPMENT_TYPE_ON_SITE_SERVICE),
        ];

        // Act
        $errorTransfer = $this->createValidator()->validate($itemTransfers, static::SKU);

        // Assert
        $this->assertNotNull($errorTransfer);
    }

    public function testEmptyShipmentTypeKeysDisableTheRestriction(): void
    {
        // Arrange
        $itemTransfers = [$this->createItem(true, static::SHIPMENT_TYPE_ON_SITE_SERVICE)];

        // Act
        $errorTransfer = $this->createValidator([])->validate($itemTransfers, static::SKU);

        // Assert
        $this->assertNull($errorTransfer);
    }

    /**
     * @param array<string>|null $shipmentTypeKeys
     */
    protected function createValidator(?array $shipmentTypeKeys = null): RecurringOrderServiceItemValidator
    {
        $selfServicePortalConfigMock = $this->createMock(SelfServicePortalConfig::class);
        $selfServicePortalConfigMock
            ->method('getRecurringOrderServiceShipmentTypeKeys')
            ->willReturn($shipmentTypeKeys ?? [static::SHIPMENT_TYPE_DELIVERY]);
        $selfServicePortalConfigMock
            ->method('getServiceProductClassName')
            ->willReturn(static::SERVICE_PRODUCT_CLASS_NAME);

        return new RecurringOrderServiceItemValidator(
            $selfServicePortalConfigMock,
            $this->createRepositoryMock(),
            new ProductClassIndexer(),
        );
    }

    protected function createRepositoryMock(): SelfServicePortalRepositoryInterface
    {
        $productClassCollectionTransfer = (new ProductClassCollectionTransfer())
            ->addProductClass(
                (new ProductClassTransfer())
                    ->setSku(static::SKU)
                    ->setName(static::SERVICE_PRODUCT_CLASS_NAME),
            );

        $selfServicePortalRepositoryMock = $this->createMock(SelfServicePortalRepositoryInterface::class);
        $selfServicePortalRepositoryMock
            ->method('getProductClassCollection')
            ->willReturn($productClassCollectionTransfer);

        return $selfServicePortalRepositoryMock;
    }

    protected function createItem(bool $isServiceProduct, ?string $shipmentTypeKey): ItemTransfer
    {
        $shipmentMethodTransfer = new ShipmentMethodTransfer();

        if ($shipmentTypeKey !== null) {
            $shipmentMethodTransfer->setShipmentType((new ShipmentTypeTransfer())->setKey($shipmentTypeKey));
        }

        return (new ItemTransfer())
            ->setSku($isServiceProduct ? static::SKU : static::SKU_REGULAR)
            ->setShipment((new ShipmentTransfer())->setMethod($shipmentMethodTransfer));
    }
}

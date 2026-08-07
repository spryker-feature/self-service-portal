<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeatureTest\Yves\SelfServicePortal\Service\Checker;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductViewTransfer;
use Generated\Shared\Transfer\ShipmentTypeStorageTransfer;
use SprykerFeature\Yves\SelfServicePortal\SelfServicePortalConfig;
use SprykerFeature\Yves\SelfServicePortal\Service\Checker\RecurringOrderServiceProductChecker;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group SelfServicePortal
 * @group Service
 * @group Checker
 * @group RecurringOrderServiceProductCheckerTest
 */
class RecurringOrderServiceProductCheckerTest extends Unit
{
    protected const string SERVICE_PRODUCT_CLASS_NAME = 'Service';

    protected const string SCHEDULED_PRODUCT_CLASS_NAME = 'Scheduled';

    protected const string SHIPMENT_TYPE_DELIVERY = 'delivery';

    protected const string SHIPMENT_TYPE_IN_CENTER_SERVICE = 'in-center-service';

    protected const string SHIPMENT_TYPE_ON_SITE_SERVICE = 'on-site-service';

    public function testProductWithoutServiceClassIsNotRestricted(): void
    {
        // Arrange
        $productViewTransfer = $this->createProductView([], [static::SHIPMENT_TYPE_IN_CENTER_SERVICE]);

        // Act
        $isRestricted = $this->createChecker()->isRestricted($productViewTransfer);

        // Assert
        $this->assertFalse($isRestricted);
    }

    public function testServiceProductSupportingDeliveryIsNotRestricted(): void
    {
        // Arrange
        $productViewTransfer = $this->createProductView(
            [static::SERVICE_PRODUCT_CLASS_NAME, static::SCHEDULED_PRODUCT_CLASS_NAME],
            [static::SHIPMENT_TYPE_IN_CENTER_SERVICE, static::SHIPMENT_TYPE_DELIVERY],
        );

        // Act
        $isRestricted = $this->createChecker()->isRestricted($productViewTransfer);

        // Assert
        $this->assertFalse($isRestricted);
    }

    public function testServiceProductFulfilledInCenterOnlyIsRestricted(): void
    {
        // Arrange
        $productViewTransfer = $this->createProductView(
            [static::SERVICE_PRODUCT_CLASS_NAME],
            [static::SHIPMENT_TYPE_IN_CENTER_SERVICE],
        );

        // Act
        $isRestricted = $this->createChecker()->isRestricted($productViewTransfer);

        // Assert
        $this->assertTrue($isRestricted);
    }

    public function testServiceProductFulfilledOnSiteIsRestricted(): void
    {
        // Arrange
        $productViewTransfer = $this->createProductView(
            [static::SERVICE_PRODUCT_CLASS_NAME],
            [static::SHIPMENT_TYPE_ON_SITE_SERVICE, static::SHIPMENT_TYPE_IN_CENTER_SERVICE],
        );

        // Act
        $isRestricted = $this->createChecker()->isRestricted($productViewTransfer);

        // Assert
        $this->assertTrue($isRestricted);
    }

    public function testServiceProductWithoutPublishedShipmentTypesIsRestricted(): void
    {
        // Arrange
        $productViewTransfer = $this->createProductView([static::SERVICE_PRODUCT_CLASS_NAME], []);

        // Act
        $isRestricted = $this->createChecker()->isRestricted($productViewTransfer);

        // Assert
        $this->assertTrue($isRestricted);
    }

    public function testEmptyShipmentTypeKeysDisableTheRestriction(): void
    {
        // Arrange
        $productViewTransfer = $this->createProductView(
            [static::SERVICE_PRODUCT_CLASS_NAME],
            [static::SHIPMENT_TYPE_IN_CENTER_SERVICE],
        );

        // Act
        $isRestricted = $this->createChecker([])->isRestricted($productViewTransfer);

        // Assert
        $this->assertFalse($isRestricted);
    }

    /**
     * @param array<string>|null $shipmentTypeKeys
     *
     * @return \SprykerFeature\Yves\SelfServicePortal\Service\Checker\RecurringOrderServiceProductChecker
     */
    protected function createChecker(?array $shipmentTypeKeys = null): RecurringOrderServiceProductChecker
    {
        $selfServicePortalConfigMock = $this->createMock(SelfServicePortalConfig::class);
        $selfServicePortalConfigMock
            ->method('getRecurringOrderServiceShipmentTypeKeys')
            ->willReturn($shipmentTypeKeys ?? [static::SHIPMENT_TYPE_DELIVERY]);
        $selfServicePortalConfigMock
            ->method('getServiceProductClassName')
            ->willReturn(static::SERVICE_PRODUCT_CLASS_NAME);

        return new RecurringOrderServiceProductChecker($selfServicePortalConfigMock);
    }

    /**
     * @param array<string> $productClassNames
     * @param array<string> $shipmentTypeKeys
     *
     * @return \Generated\Shared\Transfer\ProductViewTransfer
     */
    protected function createProductView(array $productClassNames, array $shipmentTypeKeys): ProductViewTransfer
    {
        $productViewTransfer = (new ProductViewTransfer())->setProductClassNames($productClassNames);

        foreach ($shipmentTypeKeys as $shipmentTypeKey) {
            $productViewTransfer->addShipmentType((new ShipmentTypeStorageTransfer())->setKey($shipmentTypeKey));
        }

        return $productViewTransfer;
    }
}

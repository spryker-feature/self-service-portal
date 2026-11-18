<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\SelfServicePortal\Business\Service\Expander;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\ProductOfferShipmentTypeCollectionTransfer;
use Generated\Shared\Transfer\ProductOfferShipmentTypeTransfer;
use Generated\Shared\Transfer\ProductOfferTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use Generated\Shared\Transfer\ShipmentTypeTransfer;
use Spryker\Zed\ProductOfferShipmentType\Business\ProductOfferShipmentTypeFacadeInterface;
use SprykerFeature\Zed\SelfServicePortal\Business\Service\Expander\DefaultShipmentTypeItemExpander;
use SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalRepositoryInterface;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group SelfServicePortal
 * @group Business
 * @group Service
 * @group Expander
 * @group DefaultShipmentTypeItemExpanderTest
 */
class DefaultShipmentTypeItemExpanderTest extends Unit
{
    protected const string SHIPMENT_TYPE_NAME = 'delivery';

    protected const string SHIPMENT_TYPE_UUID = 'uuid-delivery';

    protected const string PRODUCT_OFFER_REFERENCE = 'offer-1';

    public function testExpandsProductItemWhenProductHasDefaultShipmentType(): void
    {
        // Arrange
        $itemTransfer = (new ItemTransfer())->setId(10)->setShipment(new ShipmentTransfer());
        $repositoryMock = $this->createMock(SelfServicePortalRepositoryInterface::class);
        $repositoryMock->expects($this->once())
            ->method('getProductIdsWithShipmentType')
            ->with([10], static::SHIPMENT_TYPE_NAME)
            ->willReturn([10]);

        // Act
        $this->createExpander($repositoryMock)->expandItemsWithDefaultShipmentType([$itemTransfer], $this->createShipmentTypeTransfer());

        // Assert
        $this->assertSame(static::SHIPMENT_TYPE_NAME, $itemTransfer->getShipmentTypeOrFail()->getNameOrFail());
        $this->assertSame(static::SHIPMENT_TYPE_UUID, $itemTransfer->getShipmentOrFail()->getShipmentTypeUuid());
    }

    public function testDoesNotExpandProductItemWhenProductHasNoDefaultShipmentType(): void
    {
        // Arrange
        $itemTransfer = (new ItemTransfer())->setId(10);
        $repositoryMock = $this->createMock(SelfServicePortalRepositoryInterface::class);
        $repositoryMock->method('getProductIdsWithShipmentType')->willReturn([]);

        // Act
        $this->createExpander($repositoryMock)->expandItemsWithDefaultShipmentType([$itemTransfer], $this->createShipmentTypeTransfer());

        // Assert
        $this->assertNull($itemTransfer->getShipmentType());
    }

    public function testExpandsProductOfferItemWhenOfferHasDefaultShipmentType(): void
    {
        // Arrange
        $itemTransfer = (new ItemTransfer())->setId(10)->setProductOfferReference(static::PRODUCT_OFFER_REFERENCE);
        $repositoryMock = $this->createMock(SelfServicePortalRepositoryInterface::class);
        $repositoryMock->method('getProductIdsWithShipmentType')->willReturn([]);
        $facadeMock = $this->createMock(ProductOfferShipmentTypeFacadeInterface::class);
        $facadeMock->expects($this->once())
            ->method('getProductOfferShipmentTypeCollection')
            ->willReturn(
                (new ProductOfferShipmentTypeCollectionTransfer())->addProductOfferShipmentType(
                    (new ProductOfferShipmentTypeTransfer())
                        ->setProductOffer((new ProductOfferTransfer())->setProductOfferReference(static::PRODUCT_OFFER_REFERENCE)),
                ),
            );

        // Act
        $this->createExpander($repositoryMock, $facadeMock)->expandItemsWithDefaultShipmentType([$itemTransfer], $this->createShipmentTypeTransfer());

        // Assert
        $this->assertSame(static::SHIPMENT_TYPE_NAME, $itemTransfer->getShipmentTypeOrFail()->getNameOrFail());
    }

    public function testDoesNotCallFacadeWhenNoItemHasProductOfferReference(): void
    {
        // Arrange
        $itemTransfer = (new ItemTransfer())->setId(10);
        $repositoryMock = $this->createMock(SelfServicePortalRepositoryInterface::class);
        $repositoryMock->method('getProductIdsWithShipmentType')->willReturn([]);
        $facadeMock = $this->createMock(ProductOfferShipmentTypeFacadeInterface::class);
        $facadeMock->expects($this->never())->method('getProductOfferShipmentTypeCollection');

        // Act
        $this->createExpander($repositoryMock, $facadeMock)->expandItemsWithDefaultShipmentType([$itemTransfer], $this->createShipmentTypeTransfer());

        // Assert
        $this->assertNull($itemTransfer->getShipmentType());
    }

    protected function createExpander(
        SelfServicePortalRepositoryInterface $repository,
        ?ProductOfferShipmentTypeFacadeInterface $facade = null
    ): DefaultShipmentTypeItemExpander {
        return new DefaultShipmentTypeItemExpander(
            $repository,
            $facade ?? $this->createMock(ProductOfferShipmentTypeFacadeInterface::class),
        );
    }

    protected function createShipmentTypeTransfer(): ShipmentTypeTransfer
    {
        return (new ShipmentTypeTransfer())
            ->setName(static::SHIPMENT_TYPE_NAME)
            ->setUuid(static::SHIPMENT_TYPE_UUID);
    }
}

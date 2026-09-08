<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeatureTest\Zed\SelfServicePortal\Business\Service\Saver;

use Closure;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\ShipmentTypeCollectionTransfer;
use Generated\Shared\Transfer\ShipmentTypeCriteriaTransfer;
use Generated\Shared\Transfer\ShipmentTypeTransfer;
use Spryker\Zed\Event\Business\EventFacadeInterface;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionHandlerInterface;
use Spryker\Zed\ShipmentType\Business\ShipmentTypeFacadeInterface;
use SprykerFeature\Zed\SelfServicePortal\Business\Service\Saver\ProductShipmentTypeSaver;
use SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalEntityManagerInterface;
use SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalRepositoryInterface;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group SelfServicePortal
 * @group Business
 * @group Service
 * @group Saver
 * @group ProductShipmentTypeSaverWithUuidTest
 */
class ProductShipmentTypeSaverWithUuidTest extends Unit
{
    protected const int ID_PRODUCT_CONCRETE = 1;

    protected const string UUID_SHIPMENT_TYPE_A = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';

    protected const string UUID_SHIPMENT_TYPE_B = 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb';

    protected const int ID_SHIPMENT_TYPE_A = 100;

    protected const int ID_SHIPMENT_TYPE_B = 200;

    public function testSaveProductShipmentTypesResolvesIdFromUuid(): void
    {
        // Arrange
        $shipmentTypeTransfer = (new ShipmentTypeTransfer())
            ->setUuid(static::UUID_SHIPMENT_TYPE_A);

        $productConcreteTransfer = (new ProductConcreteTransfer())
            ->setIdProductConcrete(static::ID_PRODUCT_CONCRETE)
            ->addShipmentType($shipmentTypeTransfer);

        $resolvedShipmentTypeTransfer = (new ShipmentTypeTransfer())
            ->setUuid(static::UUID_SHIPMENT_TYPE_A)
            ->setIdShipmentType(static::ID_SHIPMENT_TYPE_A);

        $shipmentTypeCollectionTransfer = (new ShipmentTypeCollectionTransfer())
            ->addShipmentType($resolvedShipmentTypeTransfer);

        $selfServicePortalRepositoryMock = $this->createRepositoryMock();
        $selfServicePortalRepositoryMock->method('getShipmentTypeIdsGroupedByIdProductConcrete')
            ->willReturn([]);

        $shipmentTypeFacadeMock = $this->createShipmentTypeFacadeMock();
        $shipmentTypeFacadeMock->expects($this->once())
            ->method('getShipmentTypeCollection')
            ->with($this->callback(function (ShipmentTypeCriteriaTransfer $shipmentTypeCriteriaTransfer): bool {
                $uuids = $shipmentTypeCriteriaTransfer->getShipmentTypeConditions()->getUuids();

                return count($uuids) === 1 && $uuids[0] === static::UUID_SHIPMENT_TYPE_A;
            }))
            ->willReturn($shipmentTypeCollectionTransfer);

        $selfServicePortalEntityManagerMock = $this->createEntityManagerMock();
        $selfServicePortalEntityManagerMock->expects($this->once())
            ->method('createProductShipmentType')
            ->with(
                $this->callback(function (ProductConcreteTransfer $productConcreteTransfer): bool {
                    return $productConcreteTransfer->getIdProductConcrete() === static::ID_PRODUCT_CONCRETE;
                }),
                static::ID_SHIPMENT_TYPE_A,
            );

        $productShipmentTypeSaver = $this->createProductShipmentTypeSaverWithBypassedTransaction(
            $selfServicePortalEntityManagerMock,
            $selfServicePortalRepositoryMock,
            $shipmentTypeFacadeMock,
        );

        // Act
        $resultTransfer = $productShipmentTypeSaver->saveProductShipmentTypes($productConcreteTransfer);

        // Assert
        $this->assertSame(static::ID_PRODUCT_CONCRETE, $resultTransfer->getIdProductConcrete());
    }

    public function testSaveProductShipmentTypesWithIdDoesNotCallShipmentTypeFacade(): void
    {
        // Arrange
        $shipmentTypeTransfer = (new ShipmentTypeTransfer())
            ->setIdShipmentType(static::ID_SHIPMENT_TYPE_A);

        $productConcreteTransfer = (new ProductConcreteTransfer())
            ->setIdProductConcrete(static::ID_PRODUCT_CONCRETE)
            ->addShipmentType($shipmentTypeTransfer);

        $selfServicePortalRepositoryMock = $this->createRepositoryMock();
        $selfServicePortalRepositoryMock->method('getShipmentTypeIdsGroupedByIdProductConcrete')
            ->willReturn([]);

        $shipmentTypeFacadeMock = $this->createShipmentTypeFacadeMock();
        $shipmentTypeFacadeMock->expects($this->never())
            ->method('getShipmentTypeCollection');

        $selfServicePortalEntityManagerMock = $this->createEntityManagerMock();
        $selfServicePortalEntityManagerMock->expects($this->once())
            ->method('createProductShipmentType')
            ->with(
                $this->anything(),
                static::ID_SHIPMENT_TYPE_A,
            );

        $productShipmentTypeSaver = $this->createProductShipmentTypeSaverWithBypassedTransaction(
            $selfServicePortalEntityManagerMock,
            $selfServicePortalRepositoryMock,
            $shipmentTypeFacadeMock,
        );

        // Act
        $resultTransfer = $productShipmentTypeSaver->saveProductShipmentTypes($productConcreteTransfer);

        // Assert
        $this->assertSame(static::ID_PRODUCT_CONCRETE, $resultTransfer->getIdProductConcrete());
    }

    public function testSaveProductShipmentTypesMixedUuidAndIdResolvesOnlyMissing(): void
    {
        // Arrange
        $shipmentTypeWithId = (new ShipmentTypeTransfer())
            ->setIdShipmentType(static::ID_SHIPMENT_TYPE_A);

        $shipmentTypeWithUuid = (new ShipmentTypeTransfer())
            ->setUuid(static::UUID_SHIPMENT_TYPE_B);

        $productConcreteTransfer = (new ProductConcreteTransfer())
            ->setIdProductConcrete(static::ID_PRODUCT_CONCRETE)
            ->addShipmentType($shipmentTypeWithId)
            ->addShipmentType($shipmentTypeWithUuid);

        $resolvedShipmentTypeTransfer = (new ShipmentTypeTransfer())
            ->setUuid(static::UUID_SHIPMENT_TYPE_B)
            ->setIdShipmentType(static::ID_SHIPMENT_TYPE_B);

        $shipmentTypeCollectionTransfer = (new ShipmentTypeCollectionTransfer())
            ->addShipmentType($resolvedShipmentTypeTransfer);

        $selfServicePortalRepositoryMock = $this->createRepositoryMock();
        $selfServicePortalRepositoryMock->method('getShipmentTypeIdsGroupedByIdProductConcrete')
            ->willReturn([]);

        $shipmentTypeFacadeMock = $this->createShipmentTypeFacadeMock();
        $shipmentTypeFacadeMock->expects($this->once())
            ->method('getShipmentTypeCollection')
            ->with($this->callback(function (ShipmentTypeCriteriaTransfer $shipmentTypeCriteriaTransfer): bool {
                $uuids = $shipmentTypeCriteriaTransfer->getShipmentTypeConditions()->getUuids();

                return count($uuids) === 1 && $uuids[0] === static::UUID_SHIPMENT_TYPE_B;
            }))
            ->willReturn($shipmentTypeCollectionTransfer);

        $createdShipmentTypeIds = [];
        $selfServicePortalEntityManagerMock = $this->createEntityManagerMock();
        $selfServicePortalEntityManagerMock->expects($this->exactly(2))
            ->method('createProductShipmentType')
            ->willReturnCallback(function (ProductConcreteTransfer $productConcreteTransfer, int $idShipmentType) use (&$createdShipmentTypeIds): void {
                $createdShipmentTypeIds[] = $idShipmentType;
            });

        $productShipmentTypeSaver = $this->createProductShipmentTypeSaverWithBypassedTransaction(
            $selfServicePortalEntityManagerMock,
            $selfServicePortalRepositoryMock,
            $shipmentTypeFacadeMock,
        );

        // Act
        $productShipmentTypeSaver->saveProductShipmentTypes($productConcreteTransfer);

        // Assert
        sort($createdShipmentTypeIds);
        $this->assertSame([static::ID_SHIPMENT_TYPE_A, static::ID_SHIPMENT_TYPE_B], $createdShipmentTypeIds);
    }

    public function testExtractShipmentTypeIdsFiltersNullIds(): void
    {
        // Arrange
        $shipmentTypeWithId = (new ShipmentTypeTransfer())
            ->setIdShipmentType(static::ID_SHIPMENT_TYPE_A);

        $shipmentTypeWithUuid = (new ShipmentTypeTransfer())
            ->setUuid(static::UUID_SHIPMENT_TYPE_B);

        $productConcreteTransfer = (new ProductConcreteTransfer())
            ->setIdProductConcrete(static::ID_PRODUCT_CONCRETE)
            ->addShipmentType($shipmentTypeWithId)
            ->addShipmentType($shipmentTypeWithUuid);

        $shipmentTypeCollectionTransfer = new ShipmentTypeCollectionTransfer();

        $selfServicePortalRepositoryMock = $this->createRepositoryMock();
        $selfServicePortalRepositoryMock->method('getShipmentTypeIdsGroupedByIdProductConcrete')
            ->willReturn([]);

        $shipmentTypeFacadeMock = $this->createShipmentTypeFacadeMock();
        $shipmentTypeFacadeMock->method('getShipmentTypeCollection')
            ->willReturn($shipmentTypeCollectionTransfer);

        $createdShipmentTypeIds = [];
        $selfServicePortalEntityManagerMock = $this->createEntityManagerMock();
        $selfServicePortalEntityManagerMock->expects($this->once())
            ->method('createProductShipmentType')
            ->willReturnCallback(function (ProductConcreteTransfer $productConcreteTransfer, int $idShipmentType) use (&$createdShipmentTypeIds): void {
                $createdShipmentTypeIds[] = $idShipmentType;
            });

        $productShipmentTypeSaver = $this->createProductShipmentTypeSaverWithBypassedTransaction(
            $selfServicePortalEntityManagerMock,
            $selfServicePortalRepositoryMock,
            $shipmentTypeFacadeMock,
        );

        // Act
        $productShipmentTypeSaver->saveProductShipmentTypes($productConcreteTransfer);

        // Assert
        $this->assertSame([static::ID_SHIPMENT_TYPE_A], $createdShipmentTypeIds);
    }

    public function testSaveProductShipmentTypesWithNoProductConcreteIdReturnsEarly(): void
    {
        // Arrange
        $productConcreteTransfer = new ProductConcreteTransfer();

        $selfServicePortalEntityManagerMock = $this->createEntityManagerMock();
        $selfServicePortalEntityManagerMock->expects($this->never())
            ->method('createProductShipmentType');

        $productShipmentTypeSaver = $this->createProductShipmentTypeSaverWithBypassedTransaction(
            $selfServicePortalEntityManagerMock,
            $this->createRepositoryMock(),
            $this->createShipmentTypeFacadeMock(),
        );

        // Act
        $resultTransfer = $productShipmentTypeSaver->saveProductShipmentTypes($productConcreteTransfer);

        // Assert
        $this->assertNull($resultTransfer->getIdProductConcrete());
    }

    protected function createProductShipmentTypeSaverWithBypassedTransaction(
        SelfServicePortalEntityManagerInterface $selfServicePortalEntityManagerMock,
        SelfServicePortalRepositoryInterface $selfServicePortalRepositoryMock,
        ShipmentTypeFacadeInterface $shipmentTypeFacadeMock,
    ): ProductShipmentTypeSaver {
        $transactionHandlerMock = $this->createMock(TransactionHandlerInterface::class);
        $transactionHandlerMock->method('handleTransaction')
            ->willReturnCallback(function (Closure $callback) {
                return $callback();
            });

        $productShipmentTypeSaver = $this->getMockBuilder(ProductShipmentTypeSaver::class)
            ->setConstructorArgs([
                $selfServicePortalEntityManagerMock,
                $selfServicePortalRepositoryMock,
                $this->createEventFacadeMock(),
                $shipmentTypeFacadeMock,
            ])
            ->onlyMethods(['getTransactionHandler'])
            ->getMock();

        $productShipmentTypeSaver->method('getTransactionHandler')
            ->willReturn($transactionHandlerMock);

        return $productShipmentTypeSaver;
    }

    protected function createRepositoryMock(): SelfServicePortalRepositoryInterface
    {
        return $this->createMock(SelfServicePortalRepositoryInterface::class);
    }

    protected function createEntityManagerMock(): SelfServicePortalEntityManagerInterface
    {
        return $this->createMock(SelfServicePortalEntityManagerInterface::class);
    }

    protected function createEventFacadeMock(): EventFacadeInterface
    {
        return $this->createMock(EventFacadeInterface::class);
    }

    protected function createShipmentTypeFacadeMock(): ShipmentTypeFacadeInterface
    {
        return $this->createMock(ShipmentTypeFacadeInterface::class);
    }
}

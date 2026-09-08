<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeatureTest\Zed\SelfServicePortal\Business\Service\Saver;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductClassCollectionTransfer;
use Generated\Shared\Transfer\ProductClassCriteriaTransfer;
use Generated\Shared\Transfer\ProductClassTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Spryker\Zed\Event\Business\EventFacadeInterface;
use SprykerFeature\Zed\SelfServicePortal\Business\Service\Saver\ProductClassSaver;
use SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalEntityManagerInterface;
use SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalRepositoryInterface;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group SelfServicePortal
 * @group Business
 * @group Service
 * @group Saver
 * @group ProductClassSaverWithKeyTest
 */
class ProductClassSaverWithKeyTest extends Unit
{
    protected const int ID_PRODUCT_CONCRETE = 1;

    protected const int FK_PRODUCT_ABSTRACT = 10;

    protected const string KEY_PRODUCT_CLASS_A = 'service';

    protected const string KEY_PRODUCT_CLASS_B = 'scheduled';

    protected const string KEY_PRODUCT_CLASS_NUMERIC = '123';

    protected const int ID_PRODUCT_CLASS_A = 100;

    protected const int ID_PRODUCT_CLASS_B = 200;

    protected const int ID_PRODUCT_CLASS_NUMERIC = 300;

    public function testSaveProductClassesResolvesIdFromKey(): void
    {
        // Arrange
        $productClassTransferA = (new ProductClassTransfer())
            ->setKey(static::KEY_PRODUCT_CLASS_A);

        $productConcreteTransfer = (new ProductConcreteTransfer())
            ->setIdProductConcrete(static::ID_PRODUCT_CONCRETE)
            ->setFkProductAbstract(static::FK_PRODUCT_ABSTRACT)
            ->addProductClass($productClassTransferA);

        $resolvedProductClassTransfer = (new ProductClassTransfer())
            ->setKey(static::KEY_PRODUCT_CLASS_A)
            ->setIdProductClass(static::ID_PRODUCT_CLASS_A);

        $productClassCollectionTransfer = (new ProductClassCollectionTransfer())
            ->addProductClass($resolvedProductClassTransfer);

        $selfServicePortalRepositoryMock = $this->createRepositoryMock();
        $selfServicePortalRepositoryMock->expects($this->once())
            ->method('getProductClassCollection')
            ->with($this->callback(function (ProductClassCriteriaTransfer $productClassCriteriaTransfer): bool {
                $keys = $productClassCriteriaTransfer->getProductClassConditions()->getKeys();

                return count($keys) === 1 && $keys[0] === static::KEY_PRODUCT_CLASS_A;
            }))
            ->willReturn($productClassCollectionTransfer);

        $selfServicePortalEntityManagerMock = $this->createEntityManagerMock();
        $selfServicePortalEntityManagerMock->expects($this->once())
            ->method('saveProductClassesForProduct')
            ->with($this->callback(function (ProductClassCriteriaTransfer $productClassCriteriaTransfer): bool {
                $conditions = $productClassCriteriaTransfer->getProductClassConditions();
                $productClassIds = $conditions->getProductClassIds();

                return in_array(static::ID_PRODUCT_CLASS_A, $productClassIds)
                    && $conditions->getProductConcreteIds() === [static::ID_PRODUCT_CONCRETE];
            }));

        $productClassSaver = new ProductClassSaver(
            $selfServicePortalEntityManagerMock,
            $selfServicePortalRepositoryMock,
            $this->createEventFacadeMock(),
        );

        // Act
        $resultTransfer = $productClassSaver->saveProductClassesForProductConcrete($productConcreteTransfer);

        // Assert
        $this->assertSame(static::ID_PRODUCT_CONCRETE, $resultTransfer->getIdProductConcrete());
        $this->assertSame(
            static::ID_PRODUCT_CLASS_A,
            $resultTransfer->getProductClasses()->offsetGet(0)->getIdProductClass(),
        );
    }

    public function testSaveProductClassesWithIdDoesNotCallRepository(): void
    {
        // Arrange
        $productClassTransfer = (new ProductClassTransfer())
            ->setIdProductClass(static::ID_PRODUCT_CLASS_A);

        $productConcreteTransfer = (new ProductConcreteTransfer())
            ->setIdProductConcrete(static::ID_PRODUCT_CONCRETE)
            ->setFkProductAbstract(static::FK_PRODUCT_ABSTRACT)
            ->addProductClass($productClassTransfer);

        $selfServicePortalRepositoryMock = $this->createRepositoryMock();
        $selfServicePortalRepositoryMock->expects($this->never())
            ->method('getProductClassCollection');

        $selfServicePortalEntityManagerMock = $this->createEntityManagerMock();
        $selfServicePortalEntityManagerMock->expects($this->once())
            ->method('saveProductClassesForProduct')
            ->with($this->callback(function (ProductClassCriteriaTransfer $productClassCriteriaTransfer): bool {
                return $productClassCriteriaTransfer->getProductClassConditions()->getProductClassIds() === [static::ID_PRODUCT_CLASS_A];
            }));

        $productClassSaver = new ProductClassSaver(
            $selfServicePortalEntityManagerMock,
            $selfServicePortalRepositoryMock,
            $this->createEventFacadeMock(),
        );

        // Act
        $resultTransfer = $productClassSaver->saveProductClassesForProductConcrete($productConcreteTransfer);

        // Assert
        $this->assertSame(static::ID_PRODUCT_CLASS_A, $resultTransfer->getProductClasses()->offsetGet(0)->getIdProductClass());
    }

    public function testSaveProductClassesMixedKeyAndIdResolvesOnlyMissing(): void
    {
        // Arrange
        $productClassTransferWithId = (new ProductClassTransfer())
            ->setIdProductClass(static::ID_PRODUCT_CLASS_A);

        $productClassTransferWithKey = (new ProductClassTransfer())
            ->setKey(static::KEY_PRODUCT_CLASS_B);

        $productConcreteTransfer = (new ProductConcreteTransfer())
            ->setIdProductConcrete(static::ID_PRODUCT_CONCRETE)
            ->setFkProductAbstract(static::FK_PRODUCT_ABSTRACT)
            ->addProductClass($productClassTransferWithId)
            ->addProductClass($productClassTransferWithKey);

        $resolvedProductClassTransfer = (new ProductClassTransfer())
            ->setKey(static::KEY_PRODUCT_CLASS_B)
            ->setIdProductClass(static::ID_PRODUCT_CLASS_B);

        $productClassCollectionTransfer = (new ProductClassCollectionTransfer())
            ->addProductClass($resolvedProductClassTransfer);

        $selfServicePortalRepositoryMock = $this->createRepositoryMock();
        $selfServicePortalRepositoryMock->expects($this->once())
            ->method('getProductClassCollection')
            ->with($this->callback(function (ProductClassCriteriaTransfer $productClassCriteriaTransfer): bool {
                $keys = $productClassCriteriaTransfer->getProductClassConditions()->getKeys();

                return count($keys) === 1 && $keys[0] === static::KEY_PRODUCT_CLASS_B;
            }))
            ->willReturn($productClassCollectionTransfer);

        $selfServicePortalEntityManagerMock = $this->createEntityManagerMock();
        $selfServicePortalEntityManagerMock->expects($this->once())
            ->method('saveProductClassesForProduct')
            ->with($this->callback(function (ProductClassCriteriaTransfer $productClassCriteriaTransfer): bool {
                $productClassIds = $productClassCriteriaTransfer->getProductClassConditions()->getProductClassIds();
                sort($productClassIds);

                return $productClassIds === [static::ID_PRODUCT_CLASS_A, static::ID_PRODUCT_CLASS_B];
            }));

        $productClassSaver = new ProductClassSaver(
            $selfServicePortalEntityManagerMock,
            $selfServicePortalRepositoryMock,
            $this->createEventFacadeMock(),
        );

        // Act
        $resultTransfer = $productClassSaver->saveProductClassesForProductConcrete($productConcreteTransfer);

        // Assert
        $this->assertCount(2, $resultTransfer->getProductClasses());
    }

    public function testSaveProductClassesWithUnresolvableKeySkipsEntry(): void
    {
        // Arrange
        $productClassTransfer = (new ProductClassTransfer())
            ->setKey(static::KEY_PRODUCT_CLASS_A);

        $productConcreteTransfer = (new ProductConcreteTransfer())
            ->setIdProductConcrete(static::ID_PRODUCT_CONCRETE)
            ->setFkProductAbstract(static::FK_PRODUCT_ABSTRACT)
            ->addProductClass($productClassTransfer);

        $productClassCollectionTransfer = new ProductClassCollectionTransfer();

        $selfServicePortalRepositoryMock = $this->createRepositoryMock();
        $selfServicePortalRepositoryMock->expects($this->once())
            ->method('getProductClassCollection')
            ->willReturn($productClassCollectionTransfer);

        $selfServicePortalEntityManagerMock = $this->createEntityManagerMock();
        $selfServicePortalEntityManagerMock->expects($this->once())
            ->method('saveProductClassesForProduct')
            ->with($this->callback(function (ProductClassCriteriaTransfer $productClassCriteriaTransfer): bool {
                return $productClassCriteriaTransfer->getProductClassConditions()->getProductClassIds() === [];
            }));

        $productClassSaver = new ProductClassSaver(
            $selfServicePortalEntityManagerMock,
            $selfServicePortalRepositoryMock,
            $this->createEventFacadeMock(),
        );

        // Act
        $resultTransfer = $productClassSaver->saveProductClassesForProductConcrete($productConcreteTransfer);

        // Assert
        $this->assertNull($resultTransfer->getProductClasses()->offsetGet(0)->getIdProductClass());
    }

    public function testSaveProductClassesPassesNumericKeyAsString(): void
    {
        // Arrange
        $productClassTransfer = (new ProductClassTransfer())
            ->setKey(static::KEY_PRODUCT_CLASS_NUMERIC);

        $productConcreteTransfer = (new ProductConcreteTransfer())
            ->setIdProductConcrete(static::ID_PRODUCT_CONCRETE)
            ->setFkProductAbstract(static::FK_PRODUCT_ABSTRACT)
            ->addProductClass($productClassTransfer);

        $resolvedProductClassTransfer = (new ProductClassTransfer())
            ->setKey(static::KEY_PRODUCT_CLASS_NUMERIC)
            ->setIdProductClass(static::ID_PRODUCT_CLASS_NUMERIC);

        $productClassCollectionTransfer = (new ProductClassCollectionTransfer())
            ->addProductClass($resolvedProductClassTransfer);

        $selfServicePortalRepositoryMock = $this->createRepositoryMock();
        $selfServicePortalRepositoryMock->expects($this->once())
            ->method('getProductClassCollection')
            ->with($this->callback(function (ProductClassCriteriaTransfer $productClassCriteriaTransfer): bool {
                $keys = $productClassCriteriaTransfer->getProductClassConditions()->getKeys();

                return $keys === [static::KEY_PRODUCT_CLASS_NUMERIC];
            }))
            ->willReturn($productClassCollectionTransfer);

        $productClassSaver = new ProductClassSaver(
            $this->createEntityManagerMock(),
            $selfServicePortalRepositoryMock,
            $this->createEventFacadeMock(),
        );

        // Act
        $resultTransfer = $productClassSaver->saveProductClassesForProductConcrete($productConcreteTransfer);

        // Assert
        $this->assertSame(
            static::ID_PRODUCT_CLASS_NUMERIC,
            $resultTransfer->getProductClasses()->offsetGet(0)->getIdProductClass(),
        );
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
}

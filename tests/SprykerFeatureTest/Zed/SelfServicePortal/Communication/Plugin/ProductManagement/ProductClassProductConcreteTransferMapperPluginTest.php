<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeatureTest\Zed\SelfServicePortal\Communication\Plugin\ProductManagement;

use ArrayObject;
use Codeception\Test\Unit;
use SprykerFeature\Zed\SelfServicePortal\Communication\Plugin\ProductManagement\ProductClassProductConcreteTransferMapperPlugin;
use SprykerFeature\Zed\SelfServicePortal\Communication\Service\Form\ProductClassForm;
use SprykerFeatureTest\Zed\SelfServicePortal\SelfServicePortalCommunicationTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group SelfServicePortal
 * @group Communication
 * @group Plugin
 * @group ProductManagement
 * @group ProductClassProductConcreteTransferMapperPluginTest
 * Add your own group annotations below this line
 */
class ProductClassProductConcreteTransferMapperPluginTest extends Unit
{
    protected SelfServicePortalCommunicationTester $tester;

    public function testGivenAProductClassInTheFormDataWhenMapThenTheProductClassIsSetOnTheProductConcrete(): void
    {
        // Arrange
        $productConcreteTransfer = $this->tester->haveProduct();
        $productClassTransfer = $this->tester->haveProductClass();
        $formData = [
            ProductClassForm::FIELD_PRODUCT_CLASSES => new ArrayObject([$productClassTransfer]),
        ];

        // Act
        $productConcreteTransfer = (new ProductClassProductConcreteTransferMapperPlugin())
            ->map($productConcreteTransfer, $formData);

        // Assert
        $this->assertCount(1, $productConcreteTransfer->getProductClasses());
        $this->assertSame(
            $productClassTransfer->getIdProductClassOrFail(),
            $productConcreteTransfer->getProductClasses()[0]->getIdProductClassOrFail(),
        );
    }

    public function testGivenMultipleProductClassesInTheFormDataWhenMapThenAllProductClassesAreSetOnTheProductConcrete(): void
    {
        // Arrange
        $productConcreteTransfer = $this->tester->haveProduct();
        $firstProductClassTransfer = $this->tester->haveProductClass();
        $secondProductClassTransfer = $this->tester->haveProductClass();
        $formData = [
            ProductClassForm::FIELD_PRODUCT_CLASSES => new ArrayObject([
                $firstProductClassTransfer,
                $secondProductClassTransfer,
            ]),
        ];

        // Act
        $productConcreteTransfer = (new ProductClassProductConcreteTransferMapperPlugin())
            ->map($productConcreteTransfer, $formData);

        // Assert
        $this->assertCount(2, $productConcreteTransfer->getProductClasses());
        $this->assertSame(
            $firstProductClassTransfer->getIdProductClassOrFail(),
            $productConcreteTransfer->getProductClasses()[0]->getIdProductClassOrFail(),
        );
        $this->assertSame(
            $secondProductClassTransfer->getIdProductClassOrFail(),
            $productConcreteTransfer->getProductClasses()[1]->getIdProductClassOrFail(),
        );
    }

    public function testGivenNoProductClassesInTheFormDataWhenMapThenTheExistingProductClassesAreKept(): void
    {
        // Arrange
        $productClassTransfer = $this->tester->haveProductClass();
        $productConcreteTransfer = $this->tester->haveProduct()->addProductClass($productClassTransfer);

        // Act
        $productConcreteTransfer = (new ProductClassProductConcreteTransferMapperPlugin())
            ->map($productConcreteTransfer, []);

        // Assert
        $this->assertCount(1, $productConcreteTransfer->getProductClasses());
        $this->assertSame(
            $productClassTransfer->getIdProductClassOrFail(),
            $productConcreteTransfer->getProductClasses()[0]->getIdProductClassOrFail(),
        );
    }

    public function testGivenProductClassesInTheFormDataWhenMapThenTheExistingProductClassesAreReplaced(): void
    {
        // Arrange
        $existingProductClassTransfer = $this->tester->haveProductClass();
        $newProductClassTransfer = $this->tester->haveProductClass();
        $productConcreteTransfer = $this->tester->haveProduct()->addProductClass($existingProductClassTransfer);
        $formData = [
            ProductClassForm::FIELD_PRODUCT_CLASSES => new ArrayObject([$newProductClassTransfer]),
        ];

        // Act
        $productConcreteTransfer = (new ProductClassProductConcreteTransferMapperPlugin())
            ->map($productConcreteTransfer, $formData);

        // Assert
        $this->assertCount(1, $productConcreteTransfer->getProductClasses());
        $this->assertSame(
            $newProductClassTransfer->getIdProductClassOrFail(),
            $productConcreteTransfer->getProductClasses()[0]->getIdProductClassOrFail(),
        );
    }
}

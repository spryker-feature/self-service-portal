<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeatureTest\Zed\SelfServicePortal\Business\Plugin\Product;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductClassTransfer;
use Generated\Shared\Transfer\ProductConcreteCollectionRequestTransfer;
use Generated\Shared\Transfer\ProductConcreteCollectionResponseTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use SprykerFeature\Zed\SelfServicePortal\Communication\Plugin\Product\ProductClassExistsProductConcreteCollectionCreateValidatorPlugin;
use SprykerFeatureTest\Zed\SelfServicePortal\SelfServicePortalBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group SelfServicePortal
 * @group Business
 * @group Plugin
 * @group Product
 * @group ProductClassExistsProductConcreteCollectionCreateValidatorPluginTest
 *
 * Add your own group annotations below this line
 */
class ProductClassExistsProductConcreteCollectionCreateValidatorPluginTest extends Unit
{
    protected const string UNKNOWN_KEY = 'non-existent-product-class-key';

    protected const string PRODUCT_SKU = 'concrete-sku';

    protected SelfServicePortalBusinessTester $tester;

    public function testValidateReturnsErrorWhenProductClassKeyDoesNotExist(): void
    {
        // Arrange
        $productConcreteCollectionRequestTransfer = (new ProductConcreteCollectionRequestTransfer())
            ->addProduct(
                (new ProductConcreteTransfer())
                    ->setSku(static::PRODUCT_SKU)
                    ->addProductClass((new ProductClassTransfer())->setKey(static::UNKNOWN_KEY)),
            );

        // Act
        $productConcreteCollectionResponseTransfer = (new ProductClassExistsProductConcreteCollectionCreateValidatorPlugin())->validate(
            $productConcreteCollectionRequestTransfer,
            new ProductConcreteCollectionResponseTransfer(),
        );

        // Assert
        $this->assertCount(1, $productConcreteCollectionResponseTransfer->getErrors());
        $this->assertSame(static::PRODUCT_SKU, $productConcreteCollectionResponseTransfer->getErrors()->offsetGet(0)->getEntityIdentifier());
    }

    public function testValidatePassesWhenNoProductClassKeysProvided(): void
    {
        // Arrange
        $productConcreteCollectionRequestTransfer = (new ProductConcreteCollectionRequestTransfer())
            ->addProduct((new ProductConcreteTransfer())->setSku(static::PRODUCT_SKU));

        // Act
        $productConcreteCollectionResponseTransfer = (new ProductClassExistsProductConcreteCollectionCreateValidatorPlugin())->validate(
            $productConcreteCollectionRequestTransfer,
            new ProductConcreteCollectionResponseTransfer(),
        );

        // Assert
        $this->assertCount(0, $productConcreteCollectionResponseTransfer->getErrors());
    }
}

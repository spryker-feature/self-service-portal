<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\SelfServicePortal\Communication\Plugin\Product;

use Generated\Shared\Transfer\ProductConcreteCollectionRequestTransfer;
use Generated\Shared\Transfer\ProductConcreteCollectionResponseTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\ProductExtension\Dependency\Plugin\ProductConcreteCollectionUpdateValidatorPluginInterface;

/**
 * @method \SprykerFeature\Zed\SelfServicePortal\Business\SelfServicePortalBusinessFactory getBusinessFactory()
 */
class ProductClassExistsProductConcreteCollectionUpdateValidatorPlugin extends AbstractPlugin implements ProductConcreteCollectionUpdateValidatorPluginInterface
{
    /**
     * {@inheritDoc}
     * - Validates the product class keys referenced in `ProductConcreteTransfer.productClasses`.
     * - Resolves all referenced keys of the collection with a single query.
     * - Adds an error per concrete product referencing a product class key that does not exist.
     * - Returns the response unchanged when no concrete product references a product class.
     *
     * @api
     */
    public function validate(
        ProductConcreteCollectionRequestTransfer $productConcreteCollectionRequestTransfer,
        ProductConcreteCollectionResponseTransfer $productConcreteCollectionResponseTransfer
    ): ProductConcreteCollectionResponseTransfer {
        return $this->getBusinessFactory()
            ->createProductClassValidator()
            ->validateProductConcreteCollection($productConcreteCollectionRequestTransfer, $productConcreteCollectionResponseTransfer);
    }
}

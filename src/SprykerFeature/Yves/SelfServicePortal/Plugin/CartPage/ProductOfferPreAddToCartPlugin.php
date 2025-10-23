<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\SelfServicePortal\Plugin\CartPage;

use Generated\Shared\Transfer\ItemTransfer;
use Spryker\Yves\Kernel\AbstractPlugin;
use SprykerShop\Yves\CartPageExtension\Dependency\Plugin\PreAddToCartPluginInterface;

/**
 * @method \SprykerFeature\Yves\SelfServicePortal\SelfServicePortalFactory getFactory()
 */
class ProductOfferPreAddToCartPlugin extends AbstractPlugin implements PreAddToCartPluginInterface
{
    protected const PARAM_PRODUCT_OFFER_REFERENCE = 'product_offer_reference';

    /**
     * {@inheritDoc}
     * - Sets product offer reference to item transfer.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     * @param array<string, mixed> $params
     *
     * @return \Generated\Shared\Transfer\ItemTransfer
     */
    public function preAddToCart(ItemTransfer $itemTransfer, array $params): ItemTransfer
    {
         if (!isset($params[static::PARAM_PRODUCT_OFFER_REFERENCE])) {
            return $itemTransfer;
        }

        $productOfferReference = $params[static::PARAM_PRODUCT_OFFER_REFERENCE] ?: null;

        if (!$productOfferReference) {
            return $itemTransfer;
        }

        $productOfferStorageTransfer = $this->getFactory()->getProductOfferStorageClient()->findProductOfferStorageByReference($productOfferReference);

        if (!$productOfferStorageTransfer) {
            return $itemTransfer;
        }

        return $itemTransfer->setProductOfferReference($productOfferReference);
    }
}

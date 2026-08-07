<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\SelfServicePortal\Plugin\OrderExperienceManagement;

use Generated\Shared\Transfer\ProductViewTransfer;
use Spryker\Yves\Kernel\AbstractPlugin;
use SprykerFeature\Yves\OrderExperienceManagement\Dependency\Plugin\AddedProductConcreteRestrictionPluginInterface;

/**
 * @method \SprykerFeature\Yves\SelfServicePortal\SelfServicePortalFactory getFactory()
 */
class ServiceProductAddedProductConcreteRestrictionPlugin extends AbstractPlugin implements AddedProductConcreteRestrictionPluginInterface
{
    /**
     * {@inheritDoc}
     * - Restricts a service product whose shipment types do not include one a recurring order can serve.
     * - A service fulfilled on site or in a service center needs an appointment, which a recurring order places
     *   unattended and therefore cannot book.
     *
     * @api
     */
    public function isRestricted(ProductViewTransfer $productViewTransfer): bool
    {
        return $this->getFactory()->createRecurringOrderServiceProductChecker()->isRestricted($productViewTransfer);
    }
}

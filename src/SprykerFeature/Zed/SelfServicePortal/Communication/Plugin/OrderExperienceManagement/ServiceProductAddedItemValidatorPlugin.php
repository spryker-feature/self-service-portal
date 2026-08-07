<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\SelfServicePortal\Communication\Plugin\OrderExperienceManagement;

use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\AddedItemValidatorPluginInterface;

/**
 * @method \SprykerFeature\Zed\SelfServicePortal\Business\SelfServicePortalBusinessFactory getBusinessFactory()
 * @method \SprykerFeature\Zed\SelfServicePortal\SelfServicePortalConfig getConfig()
 */
class ServiceProductAddedItemValidatorPlugin extends AbstractPlugin implements AddedItemValidatorPluginInterface
{
    /**
     * {@inheritDoc}
     * - Rejects a service item whose resolved shipment method is not one a recurring order can serve.
     * - Closes the request-level gap left by the storefront restriction, which only hides the product.
     *
     * @api
     *
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     */
    public function validate(
        RecurringScheduleItemAdditionTransfer $recurringScheduleItemAdditionTransfer,
        array $itemTransfers,
    ): ?ErrorTransfer {
        return $this->getBusinessFactory()
            ->createRecurringOrderServiceItemValidator()
            ->validate($itemTransfers, $recurringScheduleItemAdditionTransfer->getSkuOrFail());
    }
}

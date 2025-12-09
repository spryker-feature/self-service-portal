<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Business\Service\Expander;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use SprykerFeature\Zed\SelfServicePortal\SelfServicePortalConfig;

class OrderItemReschedulableExpander implements OrderItemReschedulableExpanderInterface
{
    public function __construct(
        protected SelfServicePortalConfig $config,
        protected OrderItemProductClassExpanderInterface $orderItemProductClassExpander
    ) {
    }

    public function expandOrderItemsWithReschedulableFlag(OrderTransfer $orderTransfer): OrderTransfer
    {
        $this->orderItemProductClassExpander->expandOrderItemsWithProductClasses($orderTransfer);

        foreach ($orderTransfer->getItems() as $itemTransfer) {
            $this->expandOrderItemWithReschedulableFlag($itemTransfer);
        }

        return $orderTransfer;
    }

    protected function expandOrderItemWithReschedulableFlag(ItemTransfer $itemTransfer): ItemTransfer
    {
        if (in_array($itemTransfer->getState()?->getName(), $this->config->getServiceNotReschedulableStates(), true)) {
            $itemTransfer->setIsReschedulable(false);

            return $itemTransfer;
        }

        $serviceProductClassName = $this->config->getServiceProductClassName();
        foreach ($itemTransfer->getProductClasses() as $productClass) {
            if ($productClass->getName() === $serviceProductClassName) {
                return $itemTransfer->setIsReschedulable(true);
            }
        }

        return $itemTransfer->setIsReschedulable(false);
    }
}

<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\SelfServicePortal\Service\Expander;

use Generated\Shared\Transfer\ItemTransfer;

interface ShipmentTypeExpanderInterface
{
    /**
     * @param array<string, mixed> $params
     */
    public function expandItemTransferWithShipmentType(ItemTransfer $itemTransfer, array $params): ItemTransfer;
}

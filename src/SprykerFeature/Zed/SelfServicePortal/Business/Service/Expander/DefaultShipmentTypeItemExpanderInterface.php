<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\SelfServicePortal\Business\Service\Expander;

use Generated\Shared\Transfer\ShipmentTypeTransfer;

interface DefaultShipmentTypeItemExpanderInterface
{
    /**
     * @param list<\Generated\Shared\Transfer\ItemTransfer> $itemsWithoutShipmentType
     */
    public function expandItemsWithDefaultShipmentType(array $itemsWithoutShipmentType, ShipmentTypeTransfer $defaultShipmentTypeTransfer): void;
}

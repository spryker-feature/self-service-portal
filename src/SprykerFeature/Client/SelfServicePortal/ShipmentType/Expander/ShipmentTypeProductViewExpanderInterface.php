<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\SelfServicePortal\ShipmentType\Expander;

use Generated\Shared\Transfer\ProductViewTransfer;

interface ShipmentTypeProductViewExpanderInterface
{
    /**
     * @param array<string, mixed> $productData
     */
    public function expandProductViewWithShipmentTypes(
        ProductViewTransfer $productViewTransfer,
        array $productData,
        string $localeName
    ): ProductViewTransfer;
}

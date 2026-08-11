<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\SelfServicePortal\Asset\Expander;

use Generated\Shared\Transfer\ItemTransfer;

interface SspAssetExpanderInterface
{
    /**
     * @param array<string, mixed> $params
     */
    public function expandItemTransferWithSspAsset(ItemTransfer $itemTransfer, array $params): ItemTransfer;
}

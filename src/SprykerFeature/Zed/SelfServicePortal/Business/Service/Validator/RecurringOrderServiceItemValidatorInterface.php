<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\SelfServicePortal\Business\Service\Validator;

use Generated\Shared\Transfer\ErrorTransfer;

interface RecurringOrderServiceItemValidatorInterface
{
    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     */
    public function validate(array $itemTransfers, string $sku): ?ErrorTransfer;
}

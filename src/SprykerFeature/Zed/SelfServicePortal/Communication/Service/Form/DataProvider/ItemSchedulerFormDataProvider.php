<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Communication\Service\Form\DataProvider;

use Generated\Shared\Transfer\ItemMetadataTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Spryker\Service\UtilDateTime\UtilDateTimeServiceInterface;

class ItemSchedulerFormDataProvider
{
    public const string OPTION_CURRENT_TIMEZONE = 'current_timezone';

    public function __construct(protected UtilDateTimeServiceInterface $utilDateTimeService)
    {
    }

    public function getData(ItemTransfer $itemTransfer): ItemTransfer
    {
        if (!$itemTransfer->getMetadata()) {
            $itemTransfer->setMetadata(new ItemMetadataTransfer());
        }

        return $itemTransfer;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return [
            static::OPTION_CURRENT_TIMEZONE => $this->utilDateTimeService->getTimezone(),
        ];
    }
}

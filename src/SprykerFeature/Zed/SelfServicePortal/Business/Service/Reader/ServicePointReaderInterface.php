<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Business\Service\Reader;

interface ServicePointReaderInterface
{
    /**
     * @param array<string> $servicePointUuids
     *
     * @return array<string, \Generated\Shared\Transfer\ServicePointTransfer>
     */
    public function getServicePointsIndexedByUuids(array $servicePointUuids, string $storeName): array;
}

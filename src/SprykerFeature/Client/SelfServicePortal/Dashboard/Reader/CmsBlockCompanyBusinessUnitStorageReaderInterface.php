<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\SelfServicePortal\Dashboard\Reader;

use Generated\Shared\Transfer\CmsBlockRequestTransfer;

interface CmsBlockCompanyBusinessUnitStorageReaderInterface
{
    /**
     * @return array<\Generated\Shared\Transfer\CmsBlockTransfer>
     */
    public function getCmsBlocks(CmsBlockRequestTransfer $cmsBlockRequestTransfer): array;
}

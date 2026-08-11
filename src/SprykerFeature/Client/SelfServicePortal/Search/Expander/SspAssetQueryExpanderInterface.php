<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\SelfServicePortal\Search\Expander;

use Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface;

interface SspAssetQueryExpanderInterface
{
    /**
     * @param array<string, mixed> $requestParameters
     */
    public function expandQuery(
        QueryInterface $searchQuery,
        array $requestParameters = []
    ): QueryInterface;
}

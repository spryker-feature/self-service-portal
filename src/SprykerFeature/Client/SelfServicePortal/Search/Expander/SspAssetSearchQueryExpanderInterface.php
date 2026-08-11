<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Client\SelfServicePortal\Search\Expander;

use Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface;
use SprykerFeature\Client\SelfServicePortal\Builder\PaginationConfigBuilderInterface;
use SprykerFeature\Client\SelfServicePortal\Builder\SortConfigBuilderInterface;

interface SspAssetSearchQueryExpanderInterface
{
    /**
     * @param array<string, mixed> $requestParameters
     */
    public function expandQuery(
        QueryInterface $searchQuery,
        array $requestParameters,
        PaginationConfigBuilderInterface $paginationConfigBuilder,
        SortConfigBuilderInterface $sortConfigBuilder
    ): QueryInterface;
}

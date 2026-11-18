<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\SelfServicePortal;

use Generated\Shared\Transfer\PaginationConfigTransfer;
use Generated\Shared\Transfer\SortConfigTransfer;
use Spryker\Client\Kernel\AbstractBundleConfig;
use SprykerFeature\Shared\SelfServicePortal\SelfServicePortalConfig as SharedSelfServicePortalConfig;

/**
 * @method \SprykerFeature\Shared\SelfServicePortal\SelfServicePortalConfig getSharedConfig()
 */
class SelfServicePortalConfig extends AbstractBundleConfig
{
    /**
     * @uses \Spryker\Shared\SearchElasticsearch\SearchElasticsearchConstants::FULL_TEXT_BOOSTED_BOOSTING_VALUE
     *
     * @var string
     */
    protected const FULL_TEXT_BOOSTED_BOOSTING_VALUE = 'SEARCH_ELASTICSEARCH:FULL_TEXT_BOOSTED_BOOSTING_VALUE';

    protected const int COMPATIBLE_PRODUCT_SEARCH_ITEMS_PER_PAGE = 12;

    /**
     * Specification:
     * - Returns the boosting value for asset's full text boosted fields in Elasticsearch queries.
     * - Used to increase the relevance score of matches in boosted full text fields.
     * - Applied to Wildcard queries for enhanced pattern matching performance.
     * - Applied to MultiMatch queries to prioritize boosted field matches.
     *
     * @api
     */
    public function getElasticsearchFullTextBoostedBoostingValue(): int
    {
        return $this->get(static::FULL_TEXT_BOOSTED_BOOSTING_VALUE, 3);
    }

    /**
     * @var string
     */
    protected const SORT_NAME = 'name';

    /**
     * @var string
     */
    protected const SORT_PARAMETER_NAME_ASC = 'name_asc';

    /**
     * @var string
     */
    protected const SORT_PARAMETER_NAME_DESC = 'name_desc';

    /**
     * Specification:
     * - Returns pagination configuration for SSP asset search.
     *
     * @api
     */
    public function getSspAssetSearchPaginationConfigTransfer(): PaginationConfigTransfer
    {
        return $this->getSharedConfig()->getSspAssetSearchPaginationConfigTransfer();
    }

    /**
     * Specification:
     * - Returns sort configuration for ascending name sorting.
     *
     * @api
     */
    public function getAscendingNameSortConfigTransfer(): SortConfigTransfer
    {
        return (new SortConfigTransfer())
            ->setName(static::SORT_NAME)
            ->setParameterName(static::SORT_PARAMETER_NAME_ASC)
            ->setFieldName('string-sort')
            ->setIsDescending(false);
    }

    /**
     * Specification:
     * - Returns sort configuration for descending name sorting.
     *
     * @api
     */
    public function getDescendingNameSortConfigTransfer(): SortConfigTransfer
    {
        return (new SortConfigTransfer())
            ->setName(static::SORT_NAME)
            ->setParameterName(static::SORT_PARAMETER_NAME_DESC)
            ->setFieldName('string-sort')
            ->setIsDescending(true);
    }

    /**
     * Specification:
     * - Returns default sort configuration for SSP asset search.
     *
     * @api
     */
    public function getDefaultSortConfigTransfer(): SortConfigTransfer
    {
        return $this->getAscendingNameSortConfigTransfer();
    }

    /**
     * Specification:
     * - Returns a list of shipment type keys that are applicable for product offer service availability.
     * - Products with offers associated to these shipment types will be considered for availability checks.
     *
     * @api
     *
     * @return list<string>
     */
    public function getProductOfferServiceAvailabilityShipmentTypeKeys(): array
    {
        return [];
    }

    /**
     * Specification:
     * - Returns the service product class name.
     *
     * @api
     */
    public function getServiceProductClassName(): string
    {
        return $this->getSharedConfig()->getServiceProductClassName();
    }

    /**
     * Specification:
     * - Returns the name of the product class of spare parts.
     * - The compatible product reader uses it to find the spare parts that are compatible with an asset.
     *
     * @api
     */
    public function getSparePartProductClassName(): string
    {
        return $this->getSharedConfig()->getSparePartProductClassName();
    }

    /**
     * Specification:
     * - Returns the maximum number of booked services that the asset details reader returns for an asset.
     *
     * @api
     */
    public function getAssetDetailsServiceCount(): int
    {
        return $this->getSharedConfig()->getAssetDetailsServiceCount();
    }

    /**
     * Specification:
     * - Returns the number of items per page that the compatible product reader requests from the catalog search.
     * - The value must be one of the valid items-per-page options of the catalog search, otherwise the catalog search ignores it.
     *
     * @api
     */
    public function getCompatibleProductSearchItemsPerPage(): int
    {
        return static::COMPATIBLE_PRODUCT_SEARCH_ITEMS_PER_PAGE;
    }

    /**
     * Specification:
     * - Returns the asset statuses that allow the view action.
     * - The asset details reader returns only assets with these statuses, the same as the asset details page.
     *
     * @api
     *
     * @return list<string>
     */
    public function getAssetDetailsViewStatuses(): array
    {
        $statuses = [];

        foreach ($this->getSharedConfig()->getSspStatusAllowedActionsMapping() as $status => $allowedActions) {
            if (in_array(SharedSelfServicePortalConfig::ACTION_VIEW, $allowedActions, true)) {
                $statuses[] = $status;
            }
        }

        return $statuses;
    }

    /**
     * Specification:
     * - Returns the display names of the asset statuses, keyed by the status.
     *
     * @api
     *
     * @return array<string, string>
     */
    public function getAssetDetailsStatusNames(): array
    {
        return $this->getSharedConfig()->getAssetStatuses();
    }
}

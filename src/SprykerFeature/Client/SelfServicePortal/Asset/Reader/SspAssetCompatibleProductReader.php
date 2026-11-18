<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\SelfServicePortal\Asset\Reader;

use Generated\Shared\Transfer\PaginationSearchResultTransfer;
use Spryker\Client\Catalog\CatalogClientInterface;
use Spryker\Shared\Log\LoggerTrait;
use SprykerFeature\Client\SelfServicePortal\Asset\Access\SspAssetAccessResolverInterface;
use SprykerFeature\Client\SelfServicePortal\SelfServicePortalConfig;

class SspAssetCompatibleProductReader implements SspAssetCompatibleProductReaderInterface
{
    use LoggerTrait;

    public const string ARGUMENT_ASSET_REFERENCE = 'assetReference';

    public const string ARGUMENT_PRODUCT_KIND = 'productKind';

    public const string ARGUMENT_QUERY = 'query';

    public const string ARGUMENT_PAGE = 'page';

    public const string ARGUMENT_LIMIT = 'limit';

    public const string PRODUCT_KIND_SPARE_PARTS = 'spare_parts';

    public const string PRODUCT_KIND_SERVICES = 'services';

    public const array SUPPORTED_PRODUCT_KINDS = [
        self::PRODUCT_KIND_SPARE_PARTS,
        self::PRODUCT_KIND_SERVICES,
    ];

    public const int DEFAULT_LIMIT = 5;

    public const int LIMIT_MAX = 12;

    /**
     * @uses \SprykerFeature\Client\SelfServicePortal\Plugin\Catalog\ProductClassFacetConfigTransferBuilderPlugin::PARAMETER_NAME
     */
    protected const string REQUEST_PARAMETER_PRODUCT_CLASS = 'product-class-names';

    /**
     * @uses \SprykerFeature\Client\SelfServicePortal\Search\Expander\SspAssetQueryExpander::PARAMETER_SSP_ASSET_REFERENCE
     */
    protected const string REQUEST_PARAMETER_SSP_ASSET_REFERENCE = 'ssp-asset-reference';

    /**
     * @uses \Spryker\Client\Catalog\Plugin\Config\CatalogSearchConfigBuilder::PARAMETER_NAME_PAGE
     */
    protected const string REQUEST_PARAMETER_PAGE = 'page';

    /**
     * @uses \Spryker\Client\Catalog\Plugin\Config\CatalogSearchConfigBuilder::PARAMETER_NAME_ITEMS_PER_PAGE
     */
    protected const string REQUEST_PARAMETER_ITEMS_PER_PAGE = 'ipp';

    protected const int SEARCH_RESULT_WINDOW_MAX = 10000;

    protected const string SEARCH_RESULT_KEY_PRODUCTS = 'products';

    protected const string SEARCH_RESULT_KEY_PAGINATION = 'pagination';

    protected const string SEARCH_PRODUCT_KEY_ID_PRODUCT_ABSTRACT = 'id_product_abstract';

    protected const string SEARCH_PRODUCT_KEY_SKU = 'abstract_sku';

    protected const string SEARCH_PRODUCT_KEY_NAME = 'abstract_name';

    protected const string SEARCH_PRODUCT_KEY_PRICE = 'price';

    protected const string SEARCH_PRODUCT_KEY_URL = 'url';

    protected const string KEY_ASSET_REFERENCE = 'assetReference';

    protected const string KEY_PRODUCT_KIND = 'productKind';

    protected const string KEY_IS_ASSET_AVAILABLE = 'isAssetAvailable';

    protected const string KEY_PRODUCTS = 'products';

    protected const string KEY_TOTAL_RESULTS = 'totalResults';

    protected const string KEY_PAGE = 'page';

    protected const string KEY_HAS_MORE_RESULTS = 'hasMoreResults';

    protected const string KEY_SUGGESTED_NEXT_ACTION = 'suggestedNextAction';

    protected const string PRODUCT_KEY_ID_PRODUCT_ABSTRACT = 'idProductAbstract';

    protected const string PRODUCT_KEY_SKU = 'sku';

    protected const string PRODUCT_KEY_NAME = 'name';

    protected const string PRODUCT_KEY_PRICE = 'price';

    protected const string PRODUCT_KEY_URL = 'url';

    protected const string NEXT_ACTION_MISSING_REFERENCE = 'Pass the reference of the asset as `assetReference`. When the customer names the asset by its name or serial number, find the reference with `ssp_asset_search`; otherwise ask the customer to open or name the asset.';

    protected const string NEXT_ACTION_UNSUPPORTED_KIND = 'Pass `productKind` as one of: %s.';

    protected const string NEXT_ACTION_NOT_AVAILABLE = 'No asset with this reference is available to the customer. If the value is a name or a serial number, find the asset with `ssp_asset_search`. Otherwise show no products, tell the customer that the asset is not available to them, and offer to find their own assets by name or serial number.';

    protected const string NEXT_ACTION_NO_COMPATIBLE_PRODUCTS = 'The shop has no %s that are compatible with this asset. Display no products: tell the customer so, and offer the other kind of compatible products or a document question instead.';

    protected const string NEXT_ACTION_PAGE_OUT_OF_RANGE = 'The search returns only the first %d products, so page %d has no products. Do not ask for a higher `page`: search with a more specific `query` instead.';

    protected const string NEXT_ACTION_NO_MATCH = 'No compatible %1$s match this query. Search again without `query` to list all compatible %1$s, and never show products of a plain catalog search as compatible.';

    protected const string LOG_MESSAGE_MISSING_PAGINATION = 'The catalog search result has no pagination. The total of compatible products falls back to the count of found products.';

    public function __construct(
        protected SspAssetAccessResolverInterface $sspAssetAccessResolver,
        protected CatalogClientInterface $catalogClient,
        protected SelfServicePortalConfig $selfServicePortalConfig
    ) {
    }

    /**
     * @param array<int|string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public function searchCompatibleProducts(array $arguments): array
    {
        $arguments = $this->unwrapArguments($arguments);
        $assetReference = $this->extractString($arguments, static::ARGUMENT_ASSET_REFERENCE);
        $productKind = $this->extractString($arguments, static::ARGUMENT_PRODUCT_KIND);

        if ($assetReference === '') {
            return $this->createEmptyResult($assetReference, $productKind, static::NEXT_ACTION_MISSING_REFERENCE);
        }

        if (!in_array($productKind, static::SUPPORTED_PRODUCT_KINDS, true)) {
            return $this->createEmptyResult(
                $assetReference,
                $productKind,
                sprintf(static::NEXT_ACTION_UNSUPPORTED_KIND, implode(', ', static::SUPPORTED_PRODUCT_KINDS)),
            );
        }

        if ($this->sspAssetAccessResolver->findCompanyUserWithAssetAccess($assetReference) === null) {
            return $this->createEmptyResult($assetReference, $productKind, static::NEXT_ACTION_NOT_AVAILABLE) + [static::KEY_IS_ASSET_AVAILABLE => false];
        }

        $page = $this->extractPositiveInteger($arguments, static::ARGUMENT_PAGE) ?? 1;
        $limit = min($this->extractPositiveInteger($arguments, static::ARGUMENT_LIMIT) ?? static::DEFAULT_LIMIT, static::LIMIT_MAX);
        $itemsPerPage = $this->selfServicePortalConfig->getCompatibleProductSearchItemsPerPage();
        $resultWindow = intdiv(static::SEARCH_RESULT_WINDOW_MAX, $itemsPerPage) * $itemsPerPage;
        $lastPage = intdiv($resultWindow, $limit);

        if ($page > $lastPage) {
            return $this->createEmptyResult(
                $assetReference,
                $productKind,
                sprintf(static::NEXT_ACTION_PAGE_OUT_OF_RANGE, $lastPage * $limit, $page),
            ) + [static::KEY_IS_ASSET_AVAILABLE => true, static::KEY_PAGE => $page, static::KEY_HAS_MORE_RESULTS => false];
        }

        $searchParameters = [
            static::REQUEST_PARAMETER_SSP_ASSET_REFERENCE => $assetReference,
            static::REQUEST_PARAMETER_PRODUCT_CLASS => [$this->resolveProductClassName($productKind)],
            static::REQUEST_PARAMETER_ITEMS_PER_PAGE => $itemsPerPage,
        ];
        $searchResult = $this->searchProducts(
            $this->extractString($arguments, static::ARGUMENT_QUERY),
            $searchParameters,
            $itemsPerPage,
            ($page - 1) * $limit,
            $limit,
        );
        $totalResults = $searchResult[static::KEY_TOTAL_RESULTS];

        $result = [
            static::KEY_ASSET_REFERENCE => $assetReference,
            static::KEY_PRODUCT_KIND => $productKind,
            static::KEY_IS_ASSET_AVAILABLE => true,
            static::KEY_TOTAL_RESULTS => $totalResults,
            static::KEY_PAGE => $page,
            static::KEY_HAS_MORE_RESULTS => $page < $lastPage && $page * $limit < $totalResults,
            static::KEY_PRODUCTS => $searchResult[static::KEY_PRODUCTS],
        ];

        if ($totalResults !== 0) {
            return $result;
        }

        $productKindLabel = str_replace('_', ' ', $productKind);
        $result[static::KEY_SUGGESTED_NEXT_ACTION] = $this->extractString($arguments, static::ARGUMENT_QUERY) === ''
            ? sprintf(static::NEXT_ACTION_NO_COMPATIBLE_PRODUCTS, $productKindLabel)
            : sprintf(static::NEXT_ACTION_NO_MATCH, $productKindLabel);

        return $result;
    }

    protected function resolveProductClassName(string $productKind): string
    {
        if ($productKind === static::PRODUCT_KIND_SERVICES) {
            return $this->selfServicePortalConfig->getServiceProductClassName();
        }

        return $this->selfServicePortalConfig->getSparePartProductClassName();
    }

    /**
     * @param array<string, mixed> $searchParameters
     *
     * @return array<string, mixed>
     */
    protected function searchProducts(string $query, array $searchParameters, int $itemsPerPage, int $firstResult, int $limit): array
    {
        $searchPage = intdiv($firstResult, $itemsPerPage) + 1;
        $searchResults = $this->catalogClient->catalogSearch($query, $searchParameters + [static::REQUEST_PARAMETER_PAGE => $searchPage]);
        $searchProducts = array_slice($this->extractSearchProducts($searchResults), $firstResult % $itemsPerPage);
        $totalResults = $this->extractTotalResults($searchResults, $firstResult + count($searchProducts));
        $lastResult = min($firstResult + $limit, $totalResults);
        $searchProductCount = count($searchProducts);

        while ($searchProductCount < $lastResult - $firstResult && $searchPage * $itemsPerPage < $lastResult) {
            $searchPage++;
            $searchResults = $this->catalogClient->catalogSearch($query, $searchParameters + [static::REQUEST_PARAMETER_PAGE => $searchPage]);
            $searchProducts = array_merge($searchProducts, $this->extractSearchProducts($searchResults));
            $searchProductCount = count($searchProducts);
        }

        return [
            static::KEY_TOTAL_RESULTS => $totalResults,
            static::KEY_PRODUCTS => $this->mapProducts(array_slice($searchProducts, 0, $limit)),
        ];
    }

    /**
     * @param array<int|string, mixed> $arguments
     *
     * @return array<int|string, mixed>
     */
    protected function unwrapArguments(array $arguments): array
    {
        if (array_key_exists(0, $arguments) && is_array($arguments[0])) {
            return $arguments[0];
        }

        return $arguments;
    }

    /**
     * @param array<string, mixed> $searchResults
     */
    protected function extractTotalResults(array $searchResults, int $foundProductCount): int
    {
        $paginationSearchResultTransfer = $searchResults[static::SEARCH_RESULT_KEY_PAGINATION] ?? null;

        if (!$paginationSearchResultTransfer instanceof PaginationSearchResultTransfer) {
            $this->getLogger()->warning(static::LOG_MESSAGE_MISSING_PAGINATION);

            return $foundProductCount;
        }

        return (int)$paginationSearchResultTransfer->getNumFound();
    }

    /**
     * @param array<string, mixed> $searchResults
     *
     * @return list<mixed>
     */
    protected function extractSearchProducts(array $searchResults): array
    {
        $searchProducts = $searchResults[static::SEARCH_RESULT_KEY_PRODUCTS] ?? [];

        return is_array($searchProducts) ? array_values($searchProducts) : [];
    }

    /**
     * @param list<mixed> $searchProducts
     *
     * @return list<array<string, mixed>>
     */
    protected function mapProducts(array $searchProducts): array
    {
        $products = [];

        foreach ($searchProducts as $searchProduct) {
            if (!is_array($searchProduct) || !is_numeric($searchProduct[static::SEARCH_PRODUCT_KEY_ID_PRODUCT_ABSTRACT] ?? null)) {
                continue;
            }

            $products[] = [
                static::PRODUCT_KEY_ID_PRODUCT_ABSTRACT => (int)$searchProduct[static::SEARCH_PRODUCT_KEY_ID_PRODUCT_ABSTRACT],
                static::PRODUCT_KEY_SKU => (string)($searchProduct[static::SEARCH_PRODUCT_KEY_SKU] ?? ''),
                static::PRODUCT_KEY_NAME => (string)($searchProduct[static::SEARCH_PRODUCT_KEY_NAME] ?? ''),
                static::PRODUCT_KEY_PRICE => $searchProduct[static::SEARCH_PRODUCT_KEY_PRICE] ?? null,
                static::PRODUCT_KEY_URL => (string)($searchProduct[static::SEARCH_PRODUCT_KEY_URL] ?? ''),
            ];
        }

        return $products;
    }

    /**
     * @return array<string, mixed>
     */
    protected function createEmptyResult(string $assetReference, string $productKind, string $suggestedNextAction): array
    {
        return [
            static::KEY_ASSET_REFERENCE => $assetReference,
            static::KEY_PRODUCT_KIND => $productKind,
            static::KEY_TOTAL_RESULTS => 0,
            static::KEY_PRODUCTS => [],
            static::KEY_SUGGESTED_NEXT_ACTION => $suggestedNextAction,
        ];
    }

    /**
     * @param array<int|string, mixed> $arguments
     */
    protected function extractString(array $arguments, string $key): string
    {
        $value = $arguments[$key] ?? null;

        return is_string($value) ? trim($value) : '';
    }

    /**
     * @param array<int|string, mixed> $arguments
     */
    protected function extractPositiveInteger(array $arguments, string $key): ?int
    {
        $value = $arguments[$key] ?? null;

        if (!is_numeric($value) || (int)$value < 1) {
            return null;
        }

        return (int)$value;
    }
}

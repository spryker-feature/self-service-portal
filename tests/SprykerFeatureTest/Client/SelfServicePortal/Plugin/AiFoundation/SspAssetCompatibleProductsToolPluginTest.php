<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\SelfServicePortal\Plugin\AiFoundation;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Search\PageIndexMap;
use Generated\Shared\Transfer\PaginationSearchResultTransfer;
use PHPUnit\Framework\MockObject\MockObject;
use Spryker\Client\Catalog\CatalogClientInterface;
use Spryker\Client\Catalog\CatalogDependencyProvider;
use Spryker\Client\Catalog\CatalogFactory;
use Spryker\Client\CompanyUser\CompanyUserClientInterface;
use Spryker\Client\Search\SearchClientInterface;
use Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface;
use SprykerFeature\Client\SelfServicePortal\Plugin\AiFoundation\SspAssetCompatibleProductsToolPlugin;
use SprykerFeature\Client\SelfServicePortal\Plugin\Catalog\SspAssetQueryExpanderPlugin;
use SprykerFeature\Client\SelfServicePortal\SelfServicePortalDependencyProvider;
use SprykerFeature\Shared\SelfServicePortal\Plugin\Permission\ViewBusinessUnitSspAssetPermissionPlugin;
use SprykerFeature\Shared\SelfServicePortal\Plugin\Permission\ViewCompanySspAssetPermissionPlugin;
use SprykerFeature\Shared\SelfServicePortal\SelfServicePortalConfig;
use SprykerFeatureTest\Client\SelfServicePortal\SelfServicePortalClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group SelfServicePortal
 * @group Plugin
 * @group AiFoundation
 * @group SspAssetCompatibleProductsToolPluginTest
 */
class SspAssetCompatibleProductsToolPluginTest extends Unit
{
    protected const string PRODUCT_KIND_SPARE_PARTS = 'spare_parts';

    protected const string UNSUPPORTED_PRODUCT_KIND = 'accessories';

    protected const int CATALOG_PRODUCT_COUNT = 80;

    protected SelfServicePortalClientTester $tester;

    /**
     * @dataProvider provideInvalidArguments
     *
     * @param array<string, mixed> $arguments
     */
    public function testGivenInvalidArgumentsWhenExecutingThenNoProductsAreSearched(array $arguments): void
    {
        // Arrange
        $catalogClientMock = $this->mockCatalogClient();

        // Expect
        $catalogClientMock->expects($this->never())->method('catalogSearch');

        // Act
        $result = $this->createPlugin()->execute(...$arguments);

        // Assert
        $this->assertSame(0, $result['totalResults']);
        $this->assertSame([], $result['products']);
        $this->assertNotEmpty($result['suggestedNextAction']);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function provideInvalidArguments(): array
    {
        return [
            'missing asset reference' => [['productKind' => static::PRODUCT_KIND_SPARE_PARTS]],
            'blank asset reference' => [['assetReference' => ' ', 'productKind' => static::PRODUCT_KIND_SPARE_PARTS]],
            'unsupported product kind' => [['assetReference' => SelfServicePortalClientTester::SSP_ASSET_REFERENCE, 'productKind' => static::UNSUPPORTED_PRODUCT_KIND]],
        ];
    }

    public function testGivenCustomerWithoutCompanyUserWhenExecutingThenAssetIsNotAvailableAndNoProductsAreSearched(): void
    {
        // Arrange
        $catalogClientMock = $this->mockCatalogClient();
        $companyUserClientMock = $this->createMock(CompanyUserClientInterface::class);
        $companyUserClientMock->method('findCompanyUser')->willReturn(null);
        $this->tester->setDependency(SelfServicePortalDependencyProvider::CLIENT_COMPANY_USER, $companyUserClientMock);

        // Expect
        $catalogClientMock->expects($this->never())->method('catalogSearch');

        // Act
        $result = $this->createPlugin()->execute(assetReference: SelfServicePortalClientTester::SSP_ASSET_REFERENCE, productKind: static::PRODUCT_KIND_SPARE_PARTS);

        // Assert
        $this->assertFalse($result['isAssetAvailable']);
        $this->assertSame([], $result['products']);
    }

    public function testGivenAssetOfForeignCompanyWhenExecutingThenAssetIsNotAvailableAndNoProductsAreSearched(): void
    {
        // Arrange
        $catalogClientMock = $this->mockCatalogClient();
        $this->tester->haveCompanyUserInSession();
        $this->tester->haveSspAssetInStorage(SelfServicePortalClientTester::ID_FOREIGN_COMPANY, SelfServicePortalClientTester::ID_FOREIGN_COMPANY_BUSINESS_UNIT);
        $this->tester->haveGrantedPermissions([new ViewCompanySspAssetPermissionPlugin(), new ViewBusinessUnitSspAssetPermissionPlugin()]);

        // Expect
        $catalogClientMock->expects($this->never())->method('catalogSearch');

        // Act
        $result = $this->createPlugin()->execute(assetReference: SelfServicePortalClientTester::SSP_ASSET_REFERENCE, productKind: static::PRODUCT_KIND_SPARE_PARTS);

        // Assert
        $this->assertFalse($result['isAssetAvailable']);
        $this->assertSame([], $result['products']);
    }

    public function testGivenAssetOfForeignBusinessUnitAndNoCompanyPermissionWhenExecutingThenAssetIsNotAvailableAndNoProductsAreSearched(): void
    {
        // Arrange
        $catalogClientMock = $this->mockCatalogClient();
        $this->tester->haveCompanyUserInSession();
        $this->tester->haveSspAssetInStorage(SelfServicePortalClientTester::ID_COMPANY, SelfServicePortalClientTester::ID_FOREIGN_COMPANY_BUSINESS_UNIT);
        $this->tester->haveGrantedPermissions([new ViewBusinessUnitSspAssetPermissionPlugin()]);

        // Expect
        $catalogClientMock->expects($this->never())->method('catalogSearch');

        // Act
        $result = $this->createPlugin()->execute(assetReference: SelfServicePortalClientTester::SSP_ASSET_REFERENCE, productKind: static::PRODUCT_KIND_SPARE_PARTS);

        // Assert
        $this->assertFalse($result['isAssetAvailable']);
        $this->assertSame([], $result['products']);
    }

    public function testGivenOwnAssetWhenExecutingThenCatalogIsSearchedWithAssetReferenceAndProductClassAndProductsAreMapped(): void
    {
        // Arrange
        $catalogClientMock = $this->mockCatalogClient();
        $this->haveOwnAssetAccess();
        $expectedSearchParameters = [
            'ssp-asset-reference' => SelfServicePortalClientTester::SSP_ASSET_REFERENCE,
            'product-class-names' => [(new SelfServicePortalConfig())->getSparePartProductClassName()],
        ];

        // Expect
        $catalogClientMock->expects($this->once())
            ->method('catalogSearch')
            ->with('', $this->callback(fn (array $searchParameters): bool => array_intersect_key($searchParameters, $expectedSearchParameters) === $expectedSearchParameters))
            ->willReturnCallback($this->createCatalogSearchCallback());

        // Act
        $result = $this->createPlugin()->execute(assetReference: SelfServicePortalClientTester::SSP_ASSET_REFERENCE, productKind: static::PRODUCT_KIND_SPARE_PARTS);

        // Assert
        $this->assertTrue($result['isAssetAvailable']);
        $this->assertSame(static::CATALOG_PRODUCT_COUNT, $result['totalResults']);
        $this->assertSame(['idProductAbstract' => 1, 'sku' => 'sku-1', 'name' => 'Product 1', 'price' => 100, 'url' => '/en/product-1'], $result['products'][0]);
    }

    /**
     * @dataProvider providePagesAndLimits
     */
    public function testGivenPageAndLimitWhenExecutingThenEveryProductOfTheRequestedWindowIsReturned(int $page, int $limit): void
    {
        // Arrange
        $this->mockCatalogClient()->method('catalogSearch')->willReturnCallback($this->createCatalogSearchCallback());
        $this->haveOwnAssetAccess();

        // Act
        $result = $this->createPlugin()->execute(assetReference: SelfServicePortalClientTester::SSP_ASSET_REFERENCE, productKind: static::PRODUCT_KIND_SPARE_PARTS, page: $page, limit: $limit);

        // Assert
        $this->assertSame($this->getExpectedProductIds($page, $limit), array_column($result['products'], 'idProductAbstract'));
        $this->assertSame($page * $limit < static::CATALOG_PRODUCT_COUNT, $result['hasMoreResults']);
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function providePagesAndLimits(): array
    {
        $pagesAndLimits = [];

        foreach ([1, 2, 3, 5, 12] as $limit) {
            foreach ([1, 2, 3, 7, 15, 16, 17] as $page) {
                $pagesAndLimits[sprintf('page %d, limit %d', $page, $limit)] = [$page, $limit];
            }
        }

        return $pagesAndLimits;
    }

    /**
     * @dataProvider providePagesBeyondResultWindow
     */
    public function testGivenPageBeyondSearchResultWindowWhenExecutingThenNoProductsAreSearched(int|string $page): void
    {
        // Arrange
        $catalogClientMock = $this->mockCatalogClient();
        $this->haveOwnAssetAccess();

        // Expect
        $catalogClientMock->expects($this->never())->method('catalogSearch');

        // Act
        $result = $this->createPlugin()->execute(assetReference: SelfServicePortalClientTester::SSP_ASSET_REFERENCE, productKind: static::PRODUCT_KIND_SPARE_PARTS, page: $page, limit: 5);

        // Assert
        $this->assertTrue($result['isAssetAvailable']);
        $this->assertSame([], $result['products']);
        $this->assertFalse($result['hasMoreResults']);
        $this->assertNotEmpty($result['suggestedNextAction']);
    }

    /**
     * @return array<string, array{int|string}>
     */
    public static function providePagesBeyondResultWindow(): array
    {
        return [
            'first page beyond the window' => [2000],
            'page beyond the integer range' => ['99999999999999999999'],
        ];
    }

    public function testGivenCatalogResultWithoutPaginationWhenExecutingThenTotalResultsFallsBackToFoundProducts(): void
    {
        // Arrange
        $catalogClientMock = $this->mockCatalogClient();
        $this->haveOwnAssetAccess();
        $catalogSearchCallback = $this->createCatalogSearchCallback();
        $foundProductCount = 0;

        // Expect
        $catalogClientMock->expects($this->once())
            ->method('catalogSearch')
            ->willReturnCallback(function (string $searchString, array $requestParameters) use ($catalogSearchCallback, &$foundProductCount): array {
                $searchResults = $catalogSearchCallback($searchString, $requestParameters);
                $foundProductCount = count($searchResults['products']);
                unset($searchResults['pagination']);

                return $searchResults;
            });

        // Act
        $result = $this->createPlugin()->execute(assetReference: SelfServicePortalClientTester::SSP_ASSET_REFERENCE, productKind: static::PRODUCT_KIND_SPARE_PARTS, limit: 5);

        // Assert
        $this->assertGreaterThan(0, $foundProductCount);
        $this->assertSame($foundProductCount, $result['totalResults']);
        $this->assertSame([1, 2, 3, 4, 5], array_column($result['products'], 'idProductAbstract'));
        $this->assertArrayNotHasKey('suggestedNextAction', $result);
    }

    public function testGivenCatalogWithSspAssetQueryExpanderWhenExecutingThenCatalogSearchIsFilteredByWhitelistsOfAssetModels(): void
    {
        // Arrange
        $this->haveOwnAssetAccess();
        // The module suite does not load the project wiring, so the test gives the catalog the SspAssetQueryExpanderPlugin that projects register.
        $searchQueries = $this->captureCatalogSearchQueries([new SspAssetQueryExpanderPlugin()]);

        // Act
        $this->createPlugin()->execute(assetReference: SelfServicePortalClientTester::SSP_ASSET_REFERENCE, productKind: static::PRODUCT_KIND_SPARE_PARTS);

        // Assert
        $this->assertCount(1, $searchQueries);
        $this->assertStringContainsString(
            sprintf('{"terms":{"%s":[%d]}}', PageIndexMap::PRODUCT_LISTS_WHITELISTS, SelfServicePortalClientTester::ID_WHITELIST),
            (string)json_encode(array_map(fn (QueryInterface $searchQuery): array => $searchQuery->getSearchQuery()->toArray(), $searchQueries->getArrayCopy())),
        );
    }

    protected function haveOwnAssetAccess(): void
    {
        $this->tester->haveCompanyUserInSession();
        $this->tester->haveSspAssetInStorage(SelfServicePortalClientTester::ID_COMPANY, SelfServicePortalClientTester::ID_COMPANY_BUSINESS_UNIT);
        $this->tester->haveGrantedPermissions([new ViewBusinessUnitSspAssetPermissionPlugin()]);
    }

    /**
     * @param list<\Spryker\Client\SearchExtension\Dependency\Plugin\QueryExpanderPluginInterface> $catalogSearchQueryExpanderPlugins
     *
     * @return \ArrayObject<int, \Spryker\Client\SearchExtension\Dependency\Plugin\QueryInterface>
     */
    protected function captureCatalogSearchQueries(array $catalogSearchQueryExpanderPlugins): ArrayObject
    {
        $searchQueries = new ArrayObject();
        $searchClient = $this->tester->getLocator()->search()->client();
        $searchClientMock = $this->createMock(SearchClientInterface::class);
        $searchClientMock->method('expandQuery')->willReturnCallback($searchClient->expandQuery(...));
        $searchClientMock->method('search')->willReturnCallback(function (QueryInterface $searchQuery) use ($searchQueries): array {
            $searchQueries->append($searchQuery);

            return [];
        });
        $this->tester->setDependency(CatalogDependencyProvider::CLIENT_SEARCH, $searchClientMock, CatalogFactory::class);
        $this->tester->setDependency(CatalogDependencyProvider::CATALOG_SEARCH_QUERY_EXPANDER_PLUGINS, $catalogSearchQueryExpanderPlugins, CatalogFactory::class);

        return $searchQueries;
    }

    protected function createCatalogSearchCallback(): callable
    {
        return function (string $searchString, array $requestParameters): array {
            $itemsPerPage = $requestParameters['ipp'];
            $firstProductId = ($requestParameters['page'] - 1) * $itemsPerPage + 1;
            $pagination = (new PaginationSearchResultTransfer())->setNumFound(static::CATALOG_PRODUCT_COUNT);

            if ($firstProductId > static::CATALOG_PRODUCT_COUNT) {
                return ['products' => [], 'pagination' => $pagination];
            }

            $products = [];

            foreach (range($firstProductId, min($firstProductId + $itemsPerPage - 1, static::CATALOG_PRODUCT_COUNT)) as $idProductAbstract) {
                $products[] = [
                    'id_product_abstract' => $idProductAbstract,
                    'abstract_sku' => sprintf('sku-%d', $idProductAbstract),
                    'abstract_name' => sprintf('Product %d', $idProductAbstract),
                    'price' => 100,
                    'url' => sprintf('/en/product-%d', $idProductAbstract),
                ];
            }

            return ['products' => $products, 'pagination' => $pagination];
        };
    }

    /**
     * @return list<int>
     */
    protected function getExpectedProductIds(int $page, int $limit): array
    {
        $firstProductId = ($page - 1) * $limit + 1;

        if ($firstProductId > static::CATALOG_PRODUCT_COUNT) {
            return [];
        }

        return range($firstProductId, min($page * $limit, static::CATALOG_PRODUCT_COUNT));
    }

    protected function mockCatalogClient(): CatalogClientInterface&MockObject
    {
        $catalogClientMock = $this->createMock(CatalogClientInterface::class);
        $this->tester->setDependency(SelfServicePortalDependencyProvider::CLIENT_CATALOG, $catalogClientMock);

        return $catalogClientMock;
    }

    protected function createPlugin(): SspAssetCompatibleProductsToolPlugin
    {
        $sspAssetCompatibleProductsToolPlugin = new SspAssetCompatibleProductsToolPlugin();
        $sspAssetCompatibleProductsToolPlugin->setFactory($this->tester->getFactory());

        return $sspAssetCompatibleProductsToolPlugin;
    }
}

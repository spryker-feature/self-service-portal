<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\SelfServicePortal\Plugin\AiFoundation;

use ArrayObject;
use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\SspAssetCollectionTransfer;
use Generated\Shared\Transfer\SspAssetCriteriaTransfer;
use Generated\Shared\Transfer\SspAssetTransfer;
use Spryker\Client\CompanyUser\CompanyUserClientInterface;
use Spryker\Client\ZedRequest\ZedRequestClientInterface;
use SprykerFeature\Client\SelfServicePortal\Asset\Reader\SspAssetFinder;
use SprykerFeature\Client\SelfServicePortal\Plugin\AiFoundation\SspAssetSearchToolPlugin;
use SprykerFeature\Client\SelfServicePortal\SelfServicePortalConfig;
use SprykerFeature\Client\SelfServicePortal\SelfServicePortalDependencyProvider;
use SprykerFeatureTest\Client\SelfServicePortal\SelfServicePortalClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group SelfServicePortal
 * @group Plugin
 * @group AiFoundation
 * @group SspAssetSearchToolPluginTest
 */
class SspAssetSearchToolPluginTest extends Unit
{
    protected const string QUERY = 'OfficeJet';

    protected const string ASSET_NAME = 'OfficeJet Pro 9025e';

    protected const string SECOND_ASSET_NAME = 'OfficeJet Pro 8000';

    protected const string SERIAL_NUMBER = 'CN1234ABCD';

    protected const string SECOND_ASSET_REFERENCE = 'AST--9';

    protected SelfServicePortalClientTester $tester;

    public function testGivenEmptyQueryWhenExecutingThenNoZedRequestIsMadeAndNoAssetIsReturned(): void
    {
        // Arrange
        $zedRequestCalls = $this->haveZedAssetCollection(new SspAssetCollectionTransfer());

        // Act
        $result = $this->createPlugin()->execute(query: '  ');

        // Assert
        $this->assertFalse($result['isAvailable']);
        $this->assertSame([], $result['assets']);
        $this->assertSame(0, $zedRequestCalls->count());
    }

    public function testGivenCustomerWithoutCompanyUserWhenExecutingThenNoZedRequestIsMadeAndNoAssetIsReturned(): void
    {
        // Arrange
        $this->tester->setDependency(
            SelfServicePortalDependencyProvider::CLIENT_COMPANY_USER,
            Stub::makeEmpty(CompanyUserClientInterface::class, ['findCompanyUser' => null]),
        );
        $zedRequestCalls = $this->haveZedAssetCollection(new SspAssetCollectionTransfer());

        // Act
        $result = $this->createPlugin()->execute(query: static::QUERY);

        // Assert
        $this->assertFalse($result['isAvailable']);
        $this->assertSame([], $result['assets']);
        $this->assertSame(0, $zedRequestCalls->count());
    }

    public function testGivenQueryWhenExecutingThenTheSearchIsLimitedToTheCompanyUserAndTheViewableStatuses(): void
    {
        // Arrange
        $companyUserTransfer = $this->tester->haveCompanyUserInSession();
        $zedRequestCalls = $this->haveZedAssetCollection(new SspAssetCollectionTransfer());

        // Act
        $this->createPlugin()->execute(query: sprintf(' %s ', static::QUERY), limit: 50);

        // Assert
        /** @var \Generated\Shared\Transfer\SspAssetCriteriaTransfer $sspAssetCriteriaTransfer */
        $sspAssetCriteriaTransfer = $zedRequestCalls->offsetGet(0);
        $this->assertSame($companyUserTransfer, $sspAssetCriteriaTransfer->getCompanyUser());
        $this->assertSame(static::QUERY, $sspAssetCriteriaTransfer->getSspAssetConditionsOrFail()->getSearchText());
        $this->assertSame((new SelfServicePortalConfig())->getAssetDetailsViewStatuses(), $sspAssetCriteriaTransfer->getSspAssetConditionsOrFail()->getStatuses());
        $this->assertSame(SspAssetFinder::LIMIT_MAX, $sspAssetCriteriaTransfer->getPaginationOrFail()->getMaxPerPage());
    }

    public function testGivenOneMatchingAssetWhenExecutingThenTheAssetIsReturned(): void
    {
        // Arrange
        $this->tester->haveCompanyUserInSession();
        $this->haveZedAssetCollection($this->createSspAssetCollection([
            $this->createSspAsset(SelfServicePortalClientTester::SSP_ASSET_REFERENCE, static::ASSET_NAME, static::SERIAL_NUMBER),
        ], 1));

        // Act
        $result = $this->createPlugin()->execute(query: static::SERIAL_NUMBER);

        // Assert
        $this->assertTrue($result['isAvailable']);
        $this->assertSame(1, $result['totalResults']);
        $this->assertSame(SelfServicePortalClientTester::SSP_ASSET_REFERENCE, $result['assets'][0]['reference']);
        $this->assertSame(static::ASSET_NAME, $result['assets'][0]['name']);
        $this->assertSame(static::SERIAL_NUMBER, $result['assets'][0]['serialNumber']);
    }

    public function testGivenMoreMatchingAssetsThanTheLimitWhenExecutingThenTheTotalAndTheListedAssetsAreReturned(): void
    {
        // Arrange
        $this->tester->haveCompanyUserInSession();
        $this->haveZedAssetCollection($this->createSspAssetCollection([
            $this->createSspAsset(SelfServicePortalClientTester::SSP_ASSET_REFERENCE, static::ASSET_NAME, static::SERIAL_NUMBER),
            $this->createSspAsset(static::SECOND_ASSET_REFERENCE, static::SECOND_ASSET_NAME, null),
        ], 3));

        // Act
        $result = $this->createPlugin()->execute(query: static::QUERY, limit: 2);

        // Assert
        $this->assertSame(3, $result['totalResults']);
        $this->assertSame([SelfServicePortalClientTester::SSP_ASSET_REFERENCE, static::SECOND_ASSET_REFERENCE], array_column($result['assets'], 'reference'));
        $this->assertStringContainsString('3 assets match', $result['suggestedNextAction']);
    }

    public function testGivenNoMatchingAssetWhenExecutingThenNoAssetIsReturned(): void
    {
        // Arrange
        $this->tester->haveCompanyUserInSession();
        $this->haveZedAssetCollection($this->createSspAssetCollection([], 0));

        // Act
        $result = $this->createPlugin()->execute(query: static::QUERY);

        // Assert
        $this->assertTrue($result['isAvailable']);
        $this->assertSame(0, $result['totalResults']);
        $this->assertSame([], $result['assets']);
        $this->assertStringContainsString(static::QUERY, $result['suggestedNextAction']);
    }

    public function testGivenMultiWordQueryWithoutWholeMatchWhenExecutingThenAssetsMatchingItsWordsAreReturnedOnce(): void
    {
        // Arrange
        $this->tester->haveCompanyUserInSession();
        $officeJetSspAssetTransfer = $this->createSspAsset(SelfServicePortalClientTester::SSP_ASSET_REFERENCE, static::ASSET_NAME, static::SERIAL_NUMBER);
        $zedRequestCalls = $this->haveZedAssetCollectionsBySearchText([
            'Pro' => [$officeJetSspAssetTransfer, $this->createSspAsset(static::SECOND_ASSET_REFERENCE, static::SECOND_ASSET_NAME, null)],
            'OfficeJet' => [$officeJetSspAssetTransfer],
        ]);

        // Act
        $result = $this->createPlugin()->execute(query: 'Pro OfficeJet x');

        // Assert
        $this->assertSame(['Pro OfficeJet x', 'OfficeJet', 'Pro'], array_map(
            static fn (SspAssetCriteriaTransfer $sspAssetCriteriaTransfer): ?string => $sspAssetCriteriaTransfer->getSspAssetConditionsOrFail()->getSearchText(),
            $zedRequestCalls->getArrayCopy(),
        ));
        $this->assertSame([SelfServicePortalClientTester::SSP_ASSET_REFERENCE, static::SECOND_ASSET_REFERENCE], array_column($result['assets'], 'reference'));
        $this->assertSame(['OfficeJet', 'Pro'], $result['matchedWords']);
        $this->assertSame(2, $result['totalResults']);
    }

    public function testGivenMultiWordQueryWithoutAnyMatchWhenExecutingThenNoAssetIsReturned(): void
    {
        // Arrange
        $this->tester->haveCompanyUserInSession();
        $this->haveZedAssetCollectionsBySearchText([]);

        // Act
        $result = $this->createPlugin()->execute(query: 'LaserMax 5000');

        // Assert
        $this->assertSame(0, $result['totalResults']);
        $this->assertSame([], $result['assets']);
        $this->assertArrayNotHasKey('matchedWords', $result);
    }

    /**
     * @param array<string, list<\Generated\Shared\Transfer\SspAssetTransfer>> $sspAssetTransfersBySearchText
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\SspAssetCriteriaTransfer>
     */
    protected function haveZedAssetCollectionsBySearchText(array $sspAssetTransfersBySearchText): ArrayObject
    {
        $zedRequestCalls = new ArrayObject();

        $this->tester->setDependency(SelfServicePortalDependencyProvider::CLIENT_ZED_REQUEST, Stub::makeEmpty(ZedRequestClientInterface::class, [
            'call' => function (string $url, SspAssetCriteriaTransfer $sspAssetCriteriaTransfer) use ($zedRequestCalls, $sspAssetTransfersBySearchText): SspAssetCollectionTransfer {
                $zedRequestCalls->append($sspAssetCriteriaTransfer);
                $sspAssetTransfers = $sspAssetTransfersBySearchText[(string)$sspAssetCriteriaTransfer->getSspAssetConditionsOrFail()->getSearchText()] ?? [];

                return $this->createSspAssetCollection($sspAssetTransfers, count($sspAssetTransfers));
            },
        ]));

        return $zedRequestCalls;
    }

    /**
     * @return \ArrayObject<int, \Generated\Shared\Transfer\SspAssetCriteriaTransfer>
     */
    protected function haveZedAssetCollection(SspAssetCollectionTransfer $sspAssetCollectionTransfer): ArrayObject
    {
        $zedRequestCalls = new ArrayObject();

        $this->tester->setDependency(SelfServicePortalDependencyProvider::CLIENT_ZED_REQUEST, Stub::makeEmpty(ZedRequestClientInterface::class, [
            'call' => function (string $url, SspAssetCriteriaTransfer $sspAssetCriteriaTransfer) use ($zedRequestCalls, $sspAssetCollectionTransfer): SspAssetCollectionTransfer {
                $zedRequestCalls->append($sspAssetCriteriaTransfer);

                return $sspAssetCollectionTransfer;
            },
        ]));

        return $zedRequestCalls;
    }

    /**
     * @param list<\Generated\Shared\Transfer\SspAssetTransfer> $sspAssetTransfers
     */
    protected function createSspAssetCollection(array $sspAssetTransfers, int $totalResults): SspAssetCollectionTransfer
    {
        $sspAssetCollectionTransfer = (new SspAssetCollectionTransfer())->setPagination((new PaginationTransfer())->setNbResults($totalResults));

        foreach ($sspAssetTransfers as $sspAssetTransfer) {
            $sspAssetCollectionTransfer->addSspAsset($sspAssetTransfer);
        }

        return $sspAssetCollectionTransfer;
    }

    protected function createSspAsset(string $reference, string $name, ?string $serialNumber): SspAssetTransfer
    {
        return (new SspAssetTransfer())->setReference($reference)->setName($name)->setSerialNumber($serialNumber);
    }

    protected function createPlugin(): SspAssetSearchToolPlugin
    {
        $sspAssetSearchToolPlugin = new SspAssetSearchToolPlugin();
        $sspAssetSearchToolPlugin->setFactory($this->tester->getFactory());

        return $sspAssetSearchToolPlugin;
    }
}

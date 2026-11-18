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
use Generated\Shared\DataBuilder\SspAssetBuilder;
use Generated\Shared\Transfer\CompanyBusinessUnitTransfer;
use Generated\Shared\Transfer\CompanyTransfer;
use Generated\Shared\Transfer\SspAssetCollectionTransfer;
use Generated\Shared\Transfer\SspAssetCriteriaTransfer;
use Generated\Shared\Transfer\SspAssetTransfer;
use Generated\Shared\Transfer\SspModelTransfer;
use Spryker\Client\CompanyUser\CompanyUserClientInterface;
use Spryker\Client\ZedRequest\ZedRequestClientInterface;
use SprykerFeature\Client\SelfServicePortal\Plugin\AiFoundation\SspAssetDetailsToolPlugin;
use SprykerFeature\Client\SelfServicePortal\SelfServicePortalDependencyProvider;
use SprykerFeature\Shared\SelfServicePortal\Plugin\Permission\ViewBusinessUnitSspAssetPermissionPlugin;
use SprykerFeature\Shared\SelfServicePortal\Plugin\Permission\ViewCompanySspAssetPermissionPlugin;
use SprykerFeatureTest\Client\SelfServicePortal\SelfServicePortalClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group SelfServicePortal
 * @group Plugin
 * @group AiFoundation
 * @group SspAssetDetailsToolPluginTest
 */
class SspAssetDetailsToolPluginTest extends Unit
{
    protected const string BUSINESS_UNIT_NAME = 'Spryker Systems Berlin';

    protected const string COMPANY_NAME = 'Spryker Systems';

    protected const string SSP_MODEL_NAME = 'OfficeJet 9000';

    protected const string SSP_MODEL_CODE = 'OJ-9000';

    protected SelfServicePortalClientTester $tester;

    public function testGivenNoAssetReferenceWhenExecutingThenAssetIsNotAvailable(): void
    {
        // Act
        $result = $this->createPlugin()->execute(assetReference: ' ');

        // Assert
        $this->assertFalse($result['isAvailable']);
        $this->assertArrayNotHasKey('asset', $result);
        $this->assertNotEmpty($result['suggestedNextAction']);
    }

    public function testGivenCustomerWithoutCompanyUserWhenExecutingThenNoAssetDataIsReturned(): void
    {
        // Arrange
        $companyUserClientMock = $this->createMock(CompanyUserClientInterface::class);
        $companyUserClientMock->method('findCompanyUser')->willReturn(null);
        $this->tester->setDependency(SelfServicePortalDependencyProvider::CLIENT_COMPANY_USER, $companyUserClientMock);

        // Act
        $result = $this->createPlugin()->execute(assetReference: SelfServicePortalClientTester::SSP_ASSET_REFERENCE);

        // Assert
        $this->assertSame(SelfServicePortalClientTester::SSP_ASSET_REFERENCE, $result['assetReference']);
        $this->assertFalse($result['isAvailable']);
        $this->assertArrayNotHasKey('asset', $result);
    }

    public function testGivenAssetOfForeignCompanyWhenExecutingThenNoAssetDataIsReturned(): void
    {
        // Arrange
        $this->tester->haveCompanyUserInSession();
        $this->tester->haveSspAssetInStorage(SelfServicePortalClientTester::ID_FOREIGN_COMPANY, SelfServicePortalClientTester::ID_FOREIGN_COMPANY_BUSINESS_UNIT);
        $this->tester->haveGrantedPermissions([new ViewCompanySspAssetPermissionPlugin(), new ViewBusinessUnitSspAssetPermissionPlugin()]);

        // Act
        $result = $this->createPlugin()->execute(assetReference: SelfServicePortalClientTester::SSP_ASSET_REFERENCE);

        // Assert
        $this->assertFalse($result['isAvailable']);
        $this->assertArrayNotHasKey('asset', $result);
    }

    public function testGivenAssetOfForeignBusinessUnitAndNoCompanyPermissionWhenExecutingThenNoAssetDataIsReturned(): void
    {
        // Arrange
        $this->tester->haveCompanyUserInSession();
        $this->tester->haveSspAssetInStorage(SelfServicePortalClientTester::ID_COMPANY, SelfServicePortalClientTester::ID_FOREIGN_COMPANY_BUSINESS_UNIT);
        $this->tester->haveGrantedPermissions([new ViewBusinessUnitSspAssetPermissionPlugin()]);

        // Act
        $result = $this->createPlugin()->execute(assetReference: SelfServicePortalClientTester::SSP_ASSET_REFERENCE);

        // Assert
        $this->assertFalse($result['isAvailable']);
        $this->assertArrayNotHasKey('asset', $result);
    }

    public function testGivenOwnAssetWhenExecutingThenAssetDetailsAreReturned(): void
    {
        // Arrange
        $this->tester->haveCompanyUserInSession();
        $this->tester->haveSspAssetInStorage(SelfServicePortalClientTester::ID_COMPANY, SelfServicePortalClientTester::ID_COMPANY_BUSINESS_UNIT);
        $this->tester->haveGrantedPermissions([new ViewBusinessUnitSspAssetPermissionPlugin()]);
        $sspAssetTransfer = $this->haveOwnSspAssetInZed();

        // Act
        $result = $this->createPlugin()->execute(assetReference: SelfServicePortalClientTester::SSP_ASSET_REFERENCE);

        // Assert
        $this->assertTrue($result['isAvailable']);
        $this->assertSame($sspAssetTransfer->getName(), $result['asset']['name']);
        $this->assertSame($sspAssetTransfer->getSerialNumber(), $result['asset']['serialNumber']);
        $this->assertSame(sprintf('%s (%s)', static::BUSINESS_UNIT_NAME, static::COMPANY_NAME), $result['asset']['businessUnitOwner']);
        $this->assertSame([['name' => static::SSP_MODEL_NAME, 'code' => static::SSP_MODEL_CODE]], $result['asset']['models']);
    }

    public function testGivenOwnAssetWhenExecutingThenTheZedRequestCarriesTheCompanyUserWithTheSessionCustomer(): void
    {
        // Arrange
        $companyUserTransfer = $this->tester->haveCompanyUserInSession();
        $this->tester->haveSspAssetInStorage(SelfServicePortalClientTester::ID_COMPANY, SelfServicePortalClientTester::ID_COMPANY_BUSINESS_UNIT);
        $this->tester->haveGrantedPermissions([new ViewBusinessUnitSspAssetPermissionPlugin()]);
        $sspAssetCriteriaTransfers = new ArrayObject();
        $this->tester->setDependency(SelfServicePortalDependencyProvider::CLIENT_ZED_REQUEST, Stub::makeEmpty(ZedRequestClientInterface::class, [
            'call' => function (string $url, SspAssetCriteriaTransfer $sspAssetCriteriaTransfer) use ($sspAssetCriteriaTransfers): SspAssetCollectionTransfer {
                $sspAssetCriteriaTransfers->append($sspAssetCriteriaTransfer);

                return new SspAssetCollectionTransfer();
            },
        ]));

        // Act
        $this->createPlugin()->execute(assetReference: SelfServicePortalClientTester::SSP_ASSET_REFERENCE);

        // Assert
        /** @var \Generated\Shared\Transfer\SspAssetCriteriaTransfer $sspAssetCriteriaTransfer */
        $sspAssetCriteriaTransfer = $sspAssetCriteriaTransfers->offsetGet(0);
        $requestedCompanyUserTransfer = $sspAssetCriteriaTransfer->getCompanyUserOrFail();
        $this->assertSame($companyUserTransfer->getIdCompanyUser(), $requestedCompanyUserTransfer->getIdCompanyUser());
        $this->assertSame(SelfServicePortalClientTester::CUSTOMER_REFERENCE, $requestedCompanyUserTransfer->getCustomerOrFail()->getCustomerReference());
        $this->assertNull($companyUserTransfer->getCustomer());
    }

    protected function haveOwnSspAssetInZed(): SspAssetTransfer
    {
        $sspAssetTransfer = (new SspAssetBuilder([
            SspAssetTransfer::REFERENCE => SelfServicePortalClientTester::SSP_ASSET_REFERENCE,
            SspAssetTransfer::COMPANY_BUSINESS_UNIT => (new CompanyBusinessUnitTransfer())
                ->setName(static::BUSINESS_UNIT_NAME)
                ->setCompany((new CompanyTransfer())->setName(static::COMPANY_NAME)),
        ]))->build()->addSspModel((new SspModelTransfer())->setName(static::SSP_MODEL_NAME)->setCode(static::SSP_MODEL_CODE));
        $this->tester->haveSspAssetInZed($sspAssetTransfer);

        return $sspAssetTransfer;
    }

    protected function createPlugin(): SspAssetDetailsToolPlugin
    {
        $sspAssetDetailsToolPlugin = new SspAssetDetailsToolPlugin();
        $sspAssetDetailsToolPlugin->setFactory($this->tester->getFactory());

        return $sspAssetDetailsToolPlugin;
    }
}

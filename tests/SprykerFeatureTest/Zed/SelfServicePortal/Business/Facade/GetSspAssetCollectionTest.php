<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeatureTest\Zed\SelfServicePortal\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CompanyBusinessUnitTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\PermissionCollectionTransfer;
use Generated\Shared\Transfer\SspAssetBusinessUnitAssignmentTransfer;
use Generated\Shared\Transfer\SspAssetCollectionTransfer;
use Generated\Shared\Transfer\SspAssetConditionsTransfer;
use Generated\Shared\Transfer\SspAssetCriteriaTransfer;
use Generated\Shared\Transfer\SspAssetIncludeTransfer;
use Generated\Shared\Transfer\SspAssetTransfer;
use Spryker\Zed\CompanyRole\Communication\Plugin\PermissionStoragePlugin;
use SprykerFeature\Shared\SelfServicePortal\Plugin\Permission\ViewBusinessUnitSspAssetPermissionPlugin;
use SprykerFeature\Shared\SelfServicePortal\Plugin\Permission\ViewCompanySspAssetPermissionPlugin;
use SprykerFeature\Zed\SelfServicePortal\Business\SelfServicePortalFacade;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group SelfServicePortal
 * @group Business
 * @group Facade
 * @group GetSspAssetCollectionTest
 */
class GetSspAssetCollectionTest extends Unit
{
    /**
     * @var string
     */
    protected const LOCALE_CURRENT = 'LOCALE_CURRENT';

    /**
     * @var string
     */
    protected const PLUGINS_PERMISSION = 'PLUGINS_PERMISSION';

    /**
     * @var \SprykerFeatureTest\Zed\SelfServicePortal\SelfServicePortalBusinessTester
     */
    protected $tester;

    protected SelfServicePortalFacade $selfServicePortalFacade;

    protected function _before(): void
    {
        $this->selfServicePortalFacade = new SelfServicePortalFacade();

        $this->tester->setDependency(static::LOCALE_CURRENT, 'en_US');
        $this->tester->preparePermissionStorageDependency(new PermissionStoragePlugin());
        $this->tester->setDependency(static::PLUGINS_PERMISSION, [
            new ViewCompanySspAssetPermissionPlugin(),
            new ViewBusinessUnitSspAssetPermissionPlugin(),
        ]);
    }

    public function testGetSspAssetCollectionReturnsAssetsWithBusinessUnitSspAssetPermission(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $permissionTransfer = $this->tester->havePermission(new ViewBusinessUnitSspAssetPermissionPlugin());
        $permissionCollectionTransfer = (new PermissionCollectionTransfer())->addPermission($permissionTransfer);
        $companyUserTransfer = $this->tester->haveCompanyUserWithPermissions($companyTransfer, $permissionCollectionTransfer);
        $businessUnitTransfer = $companyUserTransfer->getCompanyBusinessUnit();

        $sspAssetTransfer = $this->tester->haveAsset([
            SspAssetTransfer::NAME => 'Test Asset',
            SspAssetTransfer::SERIAL_NUMBER => 'TEST-123',
            SspAssetTransfer::BUSINESS_UNIT_ASSIGNMENTS => [
                [SspAssetBusinessUnitAssignmentTransfer::COMPANY_BUSINESS_UNIT => $businessUnitTransfer],
            ],
        ]);

        $sspAssetCriteriaTransfer = (new SspAssetCriteriaTransfer())
            ->setCompanyUser($companyUserTransfer)
            ->setInclude((new SspAssetIncludeTransfer())
                ->setWithAssignedBusinessUnits(true));

        // Act
        $sspAssetCollectionTransfer = $this->selfServicePortalFacade->getSspAssetCollection($sspAssetCriteriaTransfer);

        // Assert
        $this->assertInstanceOf(SspAssetCollectionTransfer::class, $sspAssetCollectionTransfer);
        $this->assertCount(1, $sspAssetCollectionTransfer->getSspAssets());
        $this->assertSame('Test Asset', $sspAssetCollectionTransfer->getSspAssets()[0]->getName());
    }

    public function testGetSspAssetCollectionReturnsAssetsWithCompanySspAssetPermission(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $permissionTransfer = $this->tester->havePermission(new ViewCompanySspAssetPermissionPlugin());
        $permissionCollectionTransfer = (new PermissionCollectionTransfer())->addPermission($permissionTransfer);
        $companyUserTransfer = $this->tester->haveCompanyUserWithPermissions($companyTransfer, $permissionCollectionTransfer);
        $businessUnitTransfer = $companyUserTransfer->getCompanyBusinessUnit();

        $sspAssetTransfer = $this->tester->haveAsset([
            SspAssetTransfer::NAME => 'Test Asset',
            SspAssetTransfer::SERIAL_NUMBER => 'TEST-123',
            SspAssetTransfer::BUSINESS_UNIT_ASSIGNMENTS => [
                [SspAssetBusinessUnitAssignmentTransfer::COMPANY_BUSINESS_UNIT => $businessUnitTransfer],
            ],
        ]);

        $sspAssetCriteriaTransfer = (new SspAssetCriteriaTransfer())
            ->setCompanyUser($companyUserTransfer)
            ->setInclude((new SspAssetIncludeTransfer())
                ->setWithAssignedBusinessUnits(true));

        // Act
        $sspAssetCollectionTransfer = $this->selfServicePortalFacade->getSspAssetCollection($sspAssetCriteriaTransfer);

        // Assert
        $this->assertInstanceOf(SspAssetCollectionTransfer::class, $sspAssetCollectionTransfer);
        $this->assertCount(1, $sspAssetCollectionTransfer->getSspAssets());
        $this->assertSame('Test Asset', $sspAssetCollectionTransfer->getSspAssets()[0]->getName());
    }

    public function testGetSspAssetCollectionReturnsEmptyCollectionWithoutPermissions(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $permissionCollectionTransfer = new PermissionCollectionTransfer();
        $companyUserTransfer = $this->tester->haveCompanyUserWithPermissions($companyTransfer, $permissionCollectionTransfer);
        $businessUnitTransfer = $companyUserTransfer->getCompanyBusinessUnit();

        $sspAssetTransfer = $this->tester->haveAsset([
            SspAssetTransfer::NAME => 'Test Asset',
            SspAssetTransfer::SERIAL_NUMBER => 'TEST-123',
            SspAssetTransfer::BUSINESS_UNIT_ASSIGNMENTS => [
                [SspAssetBusinessUnitAssignmentTransfer::COMPANY_BUSINESS_UNIT => $businessUnitTransfer],
            ],
        ]);

        $sspAssetCriteriaTransfer = (new SspAssetCriteriaTransfer())
            ->setCompanyUser($companyUserTransfer);

        // Act
        $sspAssetCollectionTransfer = $this->selfServicePortalFacade->getSspAssetCollection($sspAssetCriteriaTransfer);

        // Assert
        $this->assertInstanceOf(SspAssetCollectionTransfer::class, $sspAssetCollectionTransfer);
        $this->assertCount(0, $sspAssetCollectionTransfer->getSspAssets());
    }

    public function testGetSspAssetCollectionReturnsSpecificAssetWithPermissions(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $permissionCollectionTransfer = new PermissionCollectionTransfer();
        $permissionCollectionTransfer->addPermission($this->tester->havePermission(new ViewBusinessUnitSspAssetPermissionPlugin()));
        $permissionCollectionTransfer->addPermission($this->tester->havePermission(new ViewCompanySspAssetPermissionPlugin()));
        $companyUserTransfer = $this->tester->haveCompanyUserWithPermissions($companyTransfer, $permissionCollectionTransfer);
        $businessUnitTransfer = $companyUserTransfer->getCompanyBusinessUnit();

        $sspAssetTransfer1 = $this->tester->haveAsset([
            SspAssetTransfer::NAME => 'Test Asset 1',
            SspAssetTransfer::SERIAL_NUMBER => 'TEST-123',
            SspAssetTransfer::BUSINESS_UNIT_ASSIGNMENTS => [
                [SspAssetBusinessUnitAssignmentTransfer::COMPANY_BUSINESS_UNIT => $businessUnitTransfer],
            ],
        ]);

        $sspAssetTransfer2 = $this->tester->haveAsset([
            SspAssetTransfer::NAME => 'Test Asset 2',
            SspAssetTransfer::SERIAL_NUMBER => 'TEST-456',
            SspAssetTransfer::BUSINESS_UNIT_ASSIGNMENTS => [
                [SspAssetBusinessUnitAssignmentTransfer::COMPANY_BUSINESS_UNIT => $businessUnitTransfer],
            ],
        ]);

        $sspAssetCriteriaTransfer = (new SspAssetCriteriaTransfer())
            ->setCompanyUser($companyUserTransfer)
            ->setSspAssetConditions((new SspAssetConditionsTransfer())
                ->addIdSspAsset($sspAssetTransfer1->getIdSspAsset()))
            ->setInclude((new SspAssetIncludeTransfer())
                ->setWithAssignedBusinessUnits(true));

        // Act
        $sspAssetCollectionTransfer = $this->selfServicePortalFacade->getSspAssetCollection($sspAssetCriteriaTransfer);

        // Assert
        $this->assertInstanceOf(SspAssetCollectionTransfer::class, $sspAssetCollectionTransfer);
        $this->assertCount(1, $sspAssetCollectionTransfer->getSspAssets());
        $this->assertSame('Test Asset 1', $sspAssetCollectionTransfer->getSspAssets()[0]->getName());
    }

    public function testGivenSearchTextOfNameWhenGettingAssetCollectionThenOnlyMatchingAssetsOfTheCompanyAreReturned(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveCompanyUserWithCompanyAssetPermission();
        $ownSspAssetTransfer = $this->haveAssetAssignedToBusinessUnit('OfficeJet Searchable 9025e', 'SN-SEARCH-OWN', $companyUserTransfer->getCompanyBusinessUnitOrFail());
        $this->haveAssetAssignedToBusinessUnit('Titan Searchable X9', 'SN-SEARCH-OTHER', $companyUserTransfer->getCompanyBusinessUnitOrFail());
        $foreignCompanyBusinessUnitTransfer = $this->tester->haveCompanyBusinessUnit([CompanyBusinessUnitTransfer::FK_COMPANY => $this->tester->haveCompany()->getIdCompanyOrFail()]);
        $this->haveAssetAssignedToBusinessUnit('OfficeJet Searchable 9025e', 'SN-SEARCH-FOREIGN', $foreignCompanyBusinessUnitTransfer);

        // Act
        $sspAssetCollectionTransfer = $this->selfServicePortalFacade->getSspAssetCollection($this->createSearchCriteria($companyUserTransfer, 'OfficeJet Searchable'));

        // Assert
        $this->assertSame([$ownSspAssetTransfer->getReference()], $this->getReferences($sspAssetCollectionTransfer));
        $this->assertSame(1, $sspAssetCollectionTransfer->getPaginationOrFail()->getNbResults());
    }

    public function testGivenSearchTextOfSerialNumberWhenGettingAssetCollectionThenTheAssetIsReturned(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveCompanyUserWithCompanyAssetPermission();
        $this->haveAssetAssignedToBusinessUnit('Printer Searchable', 'SN-SEARCH-AAA', $companyUserTransfer->getCompanyBusinessUnitOrFail());
        $sspAssetTransfer = $this->haveAssetAssignedToBusinessUnit('Printer Searchable', 'SN-SEARCH-BBB', $companyUserTransfer->getCompanyBusinessUnitOrFail());

        // Act
        $sspAssetCollectionTransfer = $this->selfServicePortalFacade->getSspAssetCollection($this->createSearchCriteria($companyUserTransfer, 'SN-SEARCH-BBB'));

        // Assert
        $this->assertSame([$sspAssetTransfer->getReference()], $this->getReferences($sspAssetCollectionTransfer));
    }

    public function testGivenSearchTextOfReferenceWhenGettingAssetCollectionThenTheAssetIsReturned(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveCompanyUserWithCompanyAssetPermission();
        $this->haveAssetAssignedToBusinessUnit('Scanner Searchable', 'SN-SEARCH-CCC', $companyUserTransfer->getCompanyBusinessUnitOrFail());
        $sspAssetTransfer = $this->haveAssetAssignedToBusinessUnit('Scanner Searchable', 'SN-SEARCH-DDD', $companyUserTransfer->getCompanyBusinessUnitOrFail());

        // Act
        $sspAssetCollectionTransfer = $this->selfServicePortalFacade->getSspAssetCollection($this->createSearchCriteria($companyUserTransfer, $sspAssetTransfer->getReferenceOrFail()));

        // Assert
        $this->assertSame([$sspAssetTransfer->getReference()], $this->getReferences($sspAssetCollectionTransfer));
    }

    protected function haveCompanyUserWithCompanyAssetPermission(): CompanyUserTransfer
    {
        $permissionTransfer = $this->tester->havePermission(new ViewCompanySspAssetPermissionPlugin());

        return $this->tester->haveCompanyUserWithPermissions($this->tester->haveCompany(), (new PermissionCollectionTransfer())->addPermission($permissionTransfer));
    }

    protected function haveAssetAssignedToBusinessUnit(
        string $name,
        string $serialNumber,
        CompanyBusinessUnitTransfer $companyBusinessUnitTransfer
    ): SspAssetTransfer {
        return $this->tester->haveAsset([
            SspAssetTransfer::NAME => $name,
            SspAssetTransfer::SERIAL_NUMBER => $serialNumber,
            SspAssetTransfer::COMPANY_BUSINESS_UNIT => $companyBusinessUnitTransfer,
            SspAssetTransfer::BUSINESS_UNIT_ASSIGNMENTS => [
                [SspAssetBusinessUnitAssignmentTransfer::COMPANY_BUSINESS_UNIT => $companyBusinessUnitTransfer],
            ],
        ]);
    }

    protected function createSearchCriteria(CompanyUserTransfer $companyUserTransfer, string $searchText): SspAssetCriteriaTransfer
    {
        return (new SspAssetCriteriaTransfer())
            ->setCompanyUser($companyUserTransfer)
            ->setSspAssetConditions((new SspAssetConditionsTransfer())->setSearchText($searchText))
            ->setPagination((new PaginationTransfer())->setPage(1)->setMaxPerPage(10));
    }

    /**
     * @return list<string>
     */
    protected function getReferences(SspAssetCollectionTransfer $sspAssetCollectionTransfer): array
    {
        $references = [];

        foreach ($sspAssetCollectionTransfer->getSspAssets() as $sspAssetTransfer) {
            $references[] = (string)$sspAssetTransfer->getReference();
        }

        return $references;
    }
}

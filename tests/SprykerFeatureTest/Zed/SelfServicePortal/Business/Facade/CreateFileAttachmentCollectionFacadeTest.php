<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeatureTest\Zed\SelfServicePortal\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CompanyBusinessUnitCollectionTransfer;
use Generated\Shared\Transfer\CompanyBusinessUnitTransfer;
use Generated\Shared\Transfer\CompanyUserCollectionTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\FileAttachmentCollectionRequestTransfer;
use Generated\Shared\Transfer\FileAttachmentTransfer;
use Generated\Shared\Transfer\FileTransfer;
use Generated\Shared\Transfer\SspAssetCollectionTransfer;
use Generated\Shared\Transfer\SspAssetTransfer;
use Orm\Zed\SelfServicePortal\Persistence\SpyCompanyBusinessUnitFileQuery;
use Orm\Zed\SelfServicePortal\Persistence\SpyCompanyUserFileQuery;
use Orm\Zed\SelfServicePortal\Persistence\SpySspAssetFileQuery;
use Spryker\Zed\FileManager\Dependency\Service\FileManagerToFileSystemServiceInterface;
use Spryker\Zed\FileManager\FileManagerDependencyProvider;
use SprykerFeatureTest\Zed\SelfServicePortal\SelfServicePortalBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group SelfServicePortal
 * @group Business
 * @group Facade
 * @group CreateFileAttachmentCollectionFacadeTest
 * Add your own group annotations below this line
 */
class CreateFileAttachmentCollectionFacadeTest extends Unit
{
    protected SelfServicePortalBusinessTester $tester;

    protected function _before(): void
    {
        $this->tester->setDependency(
            FileManagerDependencyProvider::SERVICE_FILE_SYSTEM,
            $this->createMock(FileManagerToFileSystemServiceInterface::class),
        );
    }

    public function testGivenAFileAndAnAssetWhenCreateFileAttachmentCollectionThenTheFileIsAttachedToTheAsset(): void
    {
        // Arrange
        $companyBusinessUnitTransfer = $this->tester->haveCompanyBusinessUnit([
            CompanyBusinessUnitTransfer::FK_COMPANY => $this->tester->haveCompany()->getIdCompanyOrFail(),
        ]);
        $sspAssetTransfer = $this->tester->haveAsset([
            SspAssetTransfer::COMPANY_BUSINESS_UNIT => $companyBusinessUnitTransfer,
        ]);
        $fileTransfer = $this->tester->haveFile();

        $fileAttachmentTransfer = $this->createFileAttachmentTransfer($fileTransfer)
            ->setSspAssetCollection(
                (new SspAssetCollectionTransfer())->addSspAsset(
                    (new SspAssetTransfer())->setIdSspAsset($sspAssetTransfer->getIdSspAssetOrFail()),
                ),
            );

        // Act
        $this->tester->getFacade()->createFileAttachmentCollection(
            (new FileAttachmentCollectionRequestTransfer())->addFileAttachmentToAdd($fileAttachmentTransfer),
        );

        // Assert
        $this->assertSame(1, SpySspAssetFileQuery::create()
            ->filterByFkFile($fileTransfer->getIdFileOrFail())
            ->filterByFkSspAsset($sspAssetTransfer->getIdSspAssetOrFail())
            ->count());
    }

    public function testGivenAFileAndABusinessUnitWhenCreateFileAttachmentCollectionThenTheFileIsAttachedToTheBusinessUnit(): void
    {
        // Arrange
        $companyBusinessUnitTransfer = $this->tester->haveCompanyBusinessUnit([
            CompanyBusinessUnitTransfer::FK_COMPANY => $this->tester->haveCompany()->getIdCompanyOrFail(),
        ]);
        $fileTransfer = $this->tester->haveFile();

        $fileAttachmentTransfer = $this->createFileAttachmentTransfer($fileTransfer)
            ->setBusinessUnitCollection($this->createCompanyBusinessUnitCollectionTransfer([$companyBusinessUnitTransfer]));

        // Act
        $this->tester->getFacade()->createFileAttachmentCollection(
            (new FileAttachmentCollectionRequestTransfer())->addFileAttachmentToAdd($fileAttachmentTransfer),
        );

        // Assert
        $this->assertSame(1, SpyCompanyBusinessUnitFileQuery::create()
            ->filterByFkFile($fileTransfer->getIdFileOrFail())
            ->filterByFkCompanyBusinessUnit($companyBusinessUnitTransfer->getIdCompanyBusinessUnitOrFail())
            ->count());
    }

    public function testGivenAFileAndACompanyUserWhenCreateFileAttachmentCollectionThenTheFileIsAttachedToTheCompanyUser(): void
    {
        // Arrange
        $companyUserTransfer = $this->tester->createCompanyUser();
        $fileTransfer = $this->tester->haveFile();

        $fileAttachmentTransfer = $this->createFileAttachmentTransfer($fileTransfer)
            ->setCompanyUserCollection(
                (new CompanyUserCollectionTransfer())->addCompanyUser(
                    (new CompanyUserTransfer())->setIdCompanyUser($companyUserTransfer->getIdCompanyUserOrFail()),
                ),
            );

        // Act
        $this->tester->getFacade()->createFileAttachmentCollection(
            (new FileAttachmentCollectionRequestTransfer())->addFileAttachmentToAdd($fileAttachmentTransfer),
        );

        // Assert
        $this->assertSame(1, SpyCompanyUserFileQuery::create()
            ->filterByFkFile($fileTransfer->getIdFileOrFail())
            ->filterByFkCompanyUser($companyUserTransfer->getIdCompanyUserOrFail())
            ->count());
    }

    public function testGivenAFileAndSeveralBusinessUnitsWhenCreateFileAttachmentCollectionThenTheFileIsAttachedToEachBusinessUnit(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $firstCompanyBusinessUnitTransfer = $this->tester->haveCompanyBusinessUnit([
            CompanyBusinessUnitTransfer::FK_COMPANY => $companyTransfer->getIdCompanyOrFail(),
        ]);
        $secondCompanyBusinessUnitTransfer = $this->tester->haveCompanyBusinessUnit([
            CompanyBusinessUnitTransfer::FK_COMPANY => $companyTransfer->getIdCompanyOrFail(),
        ]);
        $fileTransfer = $this->tester->haveFile();

        $fileAttachmentTransfer = $this->createFileAttachmentTransfer($fileTransfer)
            ->setBusinessUnitCollection($this->createCompanyBusinessUnitCollectionTransfer([
                $firstCompanyBusinessUnitTransfer,
                $secondCompanyBusinessUnitTransfer,
            ]));

        // Act
        $this->tester->getFacade()->createFileAttachmentCollection(
            (new FileAttachmentCollectionRequestTransfer())->addFileAttachmentToAdd($fileAttachmentTransfer),
        );

        // Assert
        $this->assertSame(2, SpyCompanyBusinessUnitFileQuery::create()
            ->filterByFkFile($fileTransfer->getIdFileOrFail())
            ->filterByFkCompanyBusinessUnit_In([
                $firstCompanyBusinessUnitTransfer->getIdCompanyBusinessUnitOrFail(),
                $secondCompanyBusinessUnitTransfer->getIdCompanyBusinessUnitOrFail(),
            ])
            ->count());
    }

    protected function createFileAttachmentTransfer(FileTransfer $fileTransfer): FileAttachmentTransfer
    {
        return (new FileAttachmentTransfer())
            ->setFile((new FileTransfer())->setIdFile($fileTransfer->getIdFileOrFail()));
    }

    /**
     * @param array<\Generated\Shared\Transfer\CompanyBusinessUnitTransfer> $companyBusinessUnitTransfers
     */
    protected function createCompanyBusinessUnitCollectionTransfer(array $companyBusinessUnitTransfers): CompanyBusinessUnitCollectionTransfer
    {
        $companyBusinessUnitCollectionTransfer = new CompanyBusinessUnitCollectionTransfer();

        foreach ($companyBusinessUnitTransfers as $companyBusinessUnitTransfer) {
            $companyBusinessUnitCollectionTransfer->addCompanyBusinessUnit(
                (new CompanyBusinessUnitTransfer())
                    ->setIdCompanyBusinessUnit($companyBusinessUnitTransfer->getIdCompanyBusinessUnitOrFail()),
            );
        }

        return $companyBusinessUnitCollectionTransfer;
    }
}

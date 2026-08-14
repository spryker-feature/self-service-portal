<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeatureTest\Zed\SelfServicePortal\Communication\CompanyFile\Table;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CompanyBusinessUnitTransfer;
use Generated\Shared\Transfer\CompanyTransfer;
use Generated\Shared\Transfer\FileAttachmentViewDetailTableCriteriaTransfer;
use Orm\Zed\FileManager\Persistence\SpyFile;
use Orm\Zed\FileManager\Persistence\SpyFileQuery;
use Spryker\Service\UtilDateTime\UtilDateTimeServiceInterface;
use SprykerFeature\Zed\SelfServicePortal\Communication\CompanyFile\Formatter\TimeZoneFormatterInterface;
use SprykerFeature\Zed\SelfServicePortal\Communication\CompanyFile\Table\ViewFileDetailTable;
use SprykerFeatureTest\Zed\SelfServicePortal\SelfServicePortalCommunicationTester;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group SelfServicePortal
 * @group Communication
 * @group Table
 * @group ViewFileDetailTableTest
 */
class ViewFileDetailTableTest extends Unit
{
    protected const string COLUMN_ENTITY_ID = 'entity_id';

    protected const string COLUMN_ENTITY_NAME = 'entity_name';

    protected const string COLUMN_ENTITY_TYPE = 'entity_type';

    protected const string ENTITY_TYPE_COMPANY = 'company';

    protected const string FILE_NAME = 'Company attachment.pdf';

    protected SelfServicePortalCommunicationTester $tester;

    public function testCompanyAttachedThroughEveryBusinessUnitIsReturnedAsASingleRow(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $fileEntity = $this->tester->haveFileEntity(['file_name' => static::FILE_NAME]);

        $this->attachFileToNewBusinessUnits($companyTransfer, $fileEntity, 3, 3);

        // Act
        $companyRows = $this->findCompanyRowsOfCompany($fileEntity->getIdFile(), $companyTransfer);

        // Assert
        $this->assertCount(1, $companyRows);

        $companyRow = array_pop($companyRows);
        $this->assertSame($companyTransfer->getNameOrFail(), $companyRow[static::COLUMN_ENTITY_NAME]);
        $this->assertSame(static::ENTITY_TYPE_COMPANY, $companyRow[static::COLUMN_ENTITY_TYPE]);
    }

    public function testCompanyWithOnlySomeBusinessUnitsAttachedIsNotReturned(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $fileEntity = $this->tester->haveFileEntity(['file_name' => static::FILE_NAME]);

        $this->attachFileToNewBusinessUnits($companyTransfer, $fileEntity, 3, 2);

        // Act
        $companyRows = $this->findCompanyRowsOfCompany($fileEntity->getIdFile(), $companyTransfer);

        // Assert
        $this->assertCount(0, $companyRows);
    }

    protected function attachFileToNewBusinessUnits(
        CompanyTransfer $companyTransfer,
        SpyFile $fileEntity,
        int $businessUnitCount,
        int $attachedBusinessUnitCount
    ): void {
        for ($i = 0; $i < $businessUnitCount; $i++) {
            $companyBusinessUnitTransfer = $this->tester->haveCompanyBusinessUnit([
                CompanyBusinessUnitTransfer::FK_COMPANY => $companyTransfer->getIdCompanyOrFail(),
                CompanyBusinessUnitTransfer::COMPANY => $companyTransfer,
            ]);

            if ($i >= $attachedBusinessUnitCount) {
                continue;
            }

            $this->tester->haveCompanyBusinessUnitFileAttachment([
                'idFile' => $fileEntity->getIdFile(),
                'idCompanyBusinessUnit' => $companyBusinessUnitTransfer->getIdCompanyBusinessUnitOrFail(),
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function findCompanyRowsOfCompany(int $idFile, CompanyTransfer $companyTransfer): array
    {
        $companyRows = $this->createViewFileDetailTable($idFile)->findCompanyRows();

        return array_values(array_filter(
            $companyRows,
            fn (array $companyRow): bool => (int)$companyRow[static::COLUMN_ENTITY_ID] === $companyTransfer->getIdCompanyOrFail(),
        ));
    }

    protected function createViewFileDetailTable(int $idFile): ViewFileDetailTable
    {
        $viewFileDetailTable = new class (
            SpyFileQuery::create(),
            $idFile,
            $this->createMock(UtilDateTimeServiceInterface::class),
            $this->createMock(TimeZoneFormatterInterface::class),
            new FileAttachmentViewDetailTableCriteriaTransfer(),
        ) extends ViewFileDetailTable {
            /**
             * @return array<int, array<string, mixed>>
             */
            public function findCompanyRows(): array
            {
                return $this->prepareCompanyFileQuery($this->fileAttachmentViewDetailTableCriteriaTransfer)
                    ->find()
                    ->toArray();
            }

            public function setRequest(Request $request): void
            {
                $this->request = $request;
            }
        };

        $viewFileDetailTable->setRequest(new Request(['search' => ['value' => '']]));

        return $viewFileDetailTable;
    }
}

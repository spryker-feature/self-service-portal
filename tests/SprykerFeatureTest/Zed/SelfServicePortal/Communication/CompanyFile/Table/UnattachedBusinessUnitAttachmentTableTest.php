<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeatureTest\Zed\SelfServicePortal\Communication\CompanyFile\Table;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CompanyBusinessUnitTransfer;
use Orm\Zed\CompanyBusinessUnit\Persistence\Map\SpyCompanyBusinessUnitTableMap;
use Orm\Zed\CompanyBusinessUnit\Persistence\SpyCompanyBusinessUnitQuery;
use SprykerFeature\Zed\SelfServicePortal\Communication\CompanyFile\Table\UnattachedBusinessUnitAttachmentTable;
use SprykerFeatureTest\Zed\SelfServicePortal\SelfServicePortalCommunicationTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group SelfServicePortal
 * @group Communication
 * @group Table
 * @group UnattachedBusinessUnitAttachmentTableTest
 */
class UnattachedBusinessUnitAttachmentTableTest extends Unit
{
    protected const string FILE_NAME = 'Business unit attachment.pdf';

    protected SelfServicePortalCommunicationTester $tester;

    public function testBusinessUnitAttachedToOtherFilesOnlyIsReturnedExactlyOnce(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $companyBusinessUnitTransfer = $this->tester->haveCompanyBusinessUnit([
            CompanyBusinessUnitTransfer::FK_COMPANY => $companyTransfer->getIdCompanyOrFail(),
            CompanyBusinessUnitTransfer::COMPANY => $companyTransfer,
        ]);

        $fileEntity = $this->tester->haveFileEntity(['file_name' => static::FILE_NAME]);

        foreach ([$this->tester->haveFileEntity(['file_name' => static::FILE_NAME]), $this->tester->haveFileEntity(['file_name' => static::FILE_NAME])] as $otherFileEntity) {
            $this->tester->haveCompanyBusinessUnitFileAttachment([
                'idFile' => $otherFileEntity->getIdFile(),
                'idCompanyBusinessUnit' => $companyBusinessUnitTransfer->getIdCompanyBusinessUnitOrFail(),
            ]);
        }

        // Act
        $businessUnitIds = $this->findUnattachedBusinessUnitIds($fileEntity->getIdFile());

        // Assert
        $this->assertSame(
            1,
            count(array_keys($businessUnitIds, $companyBusinessUnitTransfer->getIdCompanyBusinessUnitOrFail(), true)),
        );
    }

    public function testBusinessUnitAttachedToTheFileIsNotReturnedEvenWhenAttachedToOtherFiles(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $companyBusinessUnitTransfer = $this->tester->haveCompanyBusinessUnit([
            CompanyBusinessUnitTransfer::FK_COMPANY => $companyTransfer->getIdCompanyOrFail(),
            CompanyBusinessUnitTransfer::COMPANY => $companyTransfer,
        ]);

        $fileEntity = $this->tester->haveFileEntity(['file_name' => static::FILE_NAME]);
        $otherFileEntity = $this->tester->haveFileEntity(['file_name' => static::FILE_NAME]);

        foreach ([$fileEntity, $otherFileEntity] as $attachedFileEntity) {
            $this->tester->haveCompanyBusinessUnitFileAttachment([
                'idFile' => $attachedFileEntity->getIdFile(),
                'idCompanyBusinessUnit' => $companyBusinessUnitTransfer->getIdCompanyBusinessUnitOrFail(),
            ]);
        }

        // Act
        $businessUnitIds = $this->findUnattachedBusinessUnitIds($fileEntity->getIdFile());

        // Assert
        $this->assertNotContains($companyBusinessUnitTransfer->getIdCompanyBusinessUnitOrFail(), $businessUnitIds);
    }

    /**
     * @return array<int, int>
     */
    protected function findUnattachedBusinessUnitIds(int $idFile): array
    {
        $unattachedBusinessUnitAttachmentTable = new class (SpyCompanyBusinessUnitQuery::create(), $idFile) extends UnattachedBusinessUnitAttachmentTable {
            /**
             * @return array<int, array<string, mixed>>
             */
            public function findRows(): array
            {
                return $this->prepareQuery()->find()->toArray();
            }
        };

        return array_map(
            static fn (array $row): int => (int)$row[SpyCompanyBusinessUnitTableMap::COL_ID_COMPANY_BUSINESS_UNIT],
            $unattachedBusinessUnitAttachmentTable->findRows(),
        );
    }
}

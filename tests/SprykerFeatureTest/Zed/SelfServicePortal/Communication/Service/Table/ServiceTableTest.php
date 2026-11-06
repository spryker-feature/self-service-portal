<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeatureTest\Zed\SelfServicePortal\Communication\Service\Table;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CompanyTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\SalesProductClassTransfer;
use Generated\Shared\Transfer\SaveOrderTransfer;
use Orm\Zed\Sales\Persistence\SpySalesOrderItem;
use Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery;
use Orm\Zed\Sales\Persistence\SpySalesOrderQuery;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use SprykerFeature\Zed\SelfServicePortal\Communication\Service\Table\ServiceTable;
use SprykerFeature\Zed\SelfServicePortal\SelfServicePortalConfig;
use SprykerFeatureTest\Zed\SelfServicePortal\SelfServicePortalCommunicationTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group SelfServicePortal
 * @group Communication
 * @group Service
 * @group Table
 * @group ServiceTableTest
 * Add your own group annotations below this line
 */
class ServiceTableTest extends Unit
{
    protected const string OMS_PROCESS_NAME = 'Test01';

    protected const string SERVICE_ITEM_NAME = 'On-site installation service';

    protected const string OTHER_ITEM_NAME = 'Spare part';

    /**
     * @uses \SprykerFeature\Zed\SelfServicePortal\Communication\Service\Table\ServiceTable::COL_ORDER_REFERENCE
     */
    protected const string COLUMN_ORDER_REFERENCE = 'order_reference';

    /**
     * @uses \SprykerFeature\Zed\SelfServicePortal\Communication\Service\Table\ServiceTable::COL_FIRST_NAME
     */
    protected const string COLUMN_FIRST_NAME = 'first_name';

    /**
     * @uses \SprykerFeature\Zed\SelfServicePortal\Communication\Service\Table\ServiceTable::COL_COMPANY
     */
    protected const string COLUMN_COMPANY = 'company';

    /**
     * @uses \SprykerFeature\Zed\SelfServicePortal\Communication\Service\Table\ServiceTable::COL_SERVICE
     */
    protected const string COLUMN_SERVICE = 'service';

    public const string KEY_ID_SALES_ORDER_ITEM = 'idSalesOrderItem';

    protected SelfServicePortalCommunicationTester $tester;

    protected function _before(): void
    {
        $this->tester->configureTestStateMachine([static::OMS_PROCESS_NAME]);
    }

    public function testGivenAnOrderWithAServiceProductWhenTheServiceTableQueryRunsThenTheRowCarriesOrderReferenceCustomerNameCompanyAndServiceName(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveCustomer();
        $companyTransfer = $this->tester->haveCompany();
        $saveOrderTransfer = $this->haveOrderForCustomerAndCompany($customerTransfer, $companyTransfer);
        $serviceSalesProductClassTransfer = $this->tester->haveSalesProductClass();
        $this->haveSalesOrderItemWithProductClass($saveOrderTransfer, static::SERVICE_ITEM_NAME, $serviceSalesProductClassTransfer);

        // Act
        $serviceRows = $this->findServiceRows($serviceSalesProductClassTransfer->getNameOrFail());

        // Assert
        $this->assertCount(1, $serviceRows);
        $this->assertStringContainsString($saveOrderTransfer->getOrderReferenceOrFail(), $serviceRows[0][static::COLUMN_ORDER_REFERENCE]);
        $this->assertSame(
            sprintf('%s %s', $customerTransfer->getFirstNameOrFail(), $customerTransfer->getLastNameOrFail()),
            $serviceRows[0][static::COLUMN_FIRST_NAME],
        );
        $this->assertSame($companyTransfer->getNameOrFail(), $serviceRows[0][static::COLUMN_COMPANY]);
        $this->assertSame(static::SERVICE_ITEM_NAME, $serviceRows[0][static::COLUMN_SERVICE]);
    }

    public function testGivenAnOrderItemOfAnotherProductClassWhenTheServiceTableQueryRunsThenOnlyTheServiceItemIsListed(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveCustomer();
        $companyTransfer = $this->tester->haveCompany();
        $saveOrderTransfer = $this->haveOrderForCustomerAndCompany($customerTransfer, $companyTransfer);
        $serviceSalesProductClassTransfer = $this->tester->haveSalesProductClass();
        $serviceSalesOrderItemEntity = $this->haveSalesOrderItemWithProductClass($saveOrderTransfer, static::SERVICE_ITEM_NAME, $serviceSalesProductClassTransfer);
        $otherSalesOrderItemEntity = $this->haveSalesOrderItemWithProductClass($saveOrderTransfer, static::OTHER_ITEM_NAME, $this->tester->haveSalesProductClass());

        // Act
        $serviceRows = $this->findServiceRows($serviceSalesProductClassTransfer->getNameOrFail());

        // Assert
        $listedSalesOrderItemIds = array_column($serviceRows, static::KEY_ID_SALES_ORDER_ITEM);
        $this->assertContains($serviceSalesOrderItemEntity->getIdSalesOrderItem(), $listedSalesOrderItemIds);
        $this->assertNotContains($otherSalesOrderItemEntity->getIdSalesOrderItem(), $listedSalesOrderItemIds);
    }

    protected function haveOrderForCustomerAndCompany(CustomerTransfer $customerTransfer, CompanyTransfer $companyTransfer): SaveOrderTransfer
    {
        $saveOrderTransfer = $this->tester->haveOrder([], static::OMS_PROCESS_NAME);

        SpySalesOrderQuery::create()
            ->findPk($saveOrderTransfer->getIdSalesOrderOrFail())
            ->setCustomerReference($customerTransfer->getCustomerReferenceOrFail())
            ->setCompanyUuid($companyTransfer->getUuidOrFail())
            ->save();

        return $saveOrderTransfer;
    }

    protected function haveSalesOrderItemWithProductClass(
        SaveOrderTransfer $saveOrderTransfer,
        string $itemName,
        SalesProductClassTransfer $salesProductClassTransfer
    ): SpySalesOrderItem {
        $salesOrderItemEntity = $this->tester->createSalesOrderItemForOrder($saveOrderTransfer->getIdSalesOrderOrFail(), [
            'name' => $itemName,
            'process' => static::OMS_PROCESS_NAME,
        ]);

        $this->tester->haveSalesOrderItemToProductClass(
            $salesOrderItemEntity->getIdSalesOrderItem(),
            $salesProductClassTransfer->getIdSalesProductClassOrFail(),
        );

        return $salesOrderItemEntity;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function findServiceRows(string $serviceProductClassName): array
    {
        $selfServicePortalConfigMock = $this->createMock(SelfServicePortalConfig::class);
        $selfServicePortalConfigMock->method('getServiceProductClassName')->willReturn($serviceProductClassName);

        $serviceTable = new class (
            SpySalesOrderItemQuery::create(),
            $this->tester->getLocator()->utilDateTime()->service(),
            $selfServicePortalConfigMock,
        ) extends ServiceTable {
            /**
             * @param array<mixed> $item
             */
            protected function buildLinks(array $item): string
            {
                return '';
            }

            /**
             * @return list<array<string, mixed>>
             */
            public function findServiceRows(): array
            {
                $serviceRows = [];

                foreach ($this->prepareQuery()->setFormatter(ModelCriteria::FORMAT_ARRAY)->find() as $row) {
                    $serviceRows[] = $this->prepareTableRow($row) + [
                        ServiceTableTest::KEY_ID_SALES_ORDER_ITEM => (int)$row[static::COL_ID_SALES_ORDER_ITEM],
                    ];
                }

                return $serviceRows;
            }
        };

        return $serviceTable->findServiceRows();
    }
}

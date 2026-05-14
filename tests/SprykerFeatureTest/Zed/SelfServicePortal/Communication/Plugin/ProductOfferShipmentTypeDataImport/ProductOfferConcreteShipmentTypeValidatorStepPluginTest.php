<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\SelfServicePortal\Communication\Plugin\ProductOfferShipmentTypeDataImport;

use Codeception\Test\Unit;
use ReflectionClass;
use Spryker\Zed\DataImport\Business\Exception\InvalidDataException;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\DataImportStepInterface;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSet;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;
use Spryker\Zed\ProductOfferShipmentTypeDataImport\Business\DataSet\ProductOfferShipmentTypeDataSetInterface;
use Spryker\Zed\ProductOfferShipmentTypeDataImport\Dependency\Plugin\ProductOfferShipmentTypeValidatorStepPluginInterface;
use SprykerFeature\Zed\SelfServicePortal\Business\SelfServicePortalBusinessFactory;
use SprykerFeature\Zed\SelfServicePortal\Business\Service\DataImport\Step\ProductOfferConcreteShipmentTypeValidatorStep;
use SprykerFeature\Zed\SelfServicePortal\Communication\Plugin\ProductOfferShipmentTypeDataImport\ProductOfferConcreteShipmentTypeValidatorStepPlugin;
use SprykerFeatureTest\Zed\SelfServicePortal\SelfServicePortalCommunicationTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group SelfServicePortal
 * @group Communication
 * @group Plugin
 * @group ProductOfferShipmentTypeDataImport
 * @group ProductOfferConcreteShipmentTypeValidatorStepPluginTest
 */
class ProductOfferConcreteShipmentTypeValidatorStepPluginTest extends Unit
{
    protected SelfServicePortalCommunicationTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetValidatorStepStaticCache();
    }

    protected function _after(): void
    {
        parent::_after();

        $this->resetValidatorStepStaticCache();
    }

    public function testPluginImplementsProductOfferShipmentTypeValidatorStepPluginInterface(): void
    {
        // Act
        $plugin = new ProductOfferConcreteShipmentTypeValidatorStepPlugin();

        // Assert
        $this->assertInstanceOf(ProductOfferShipmentTypeValidatorStepPluginInterface::class, $plugin);
    }

    public function testExecuteDelegatesDataSetToProductOfferConcreteShipmentTypeValidatorStep(): void
    {
        // Arrange
        $dataSetMock = $this->getMockBuilder(DataSetInterface::class)->getMock();

        $validatorStepMock = $this->getMockBuilder(DataImportStepInterface::class)->getMock();
        $validatorStepMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->identicalTo($dataSetMock));

        $businessFactoryMock = $this->createPartialMock(
            SelfServicePortalBusinessFactory::class,
            ['createProductOfferConcreteShipmentTypeValidatorStep'],
        );
        $businessFactoryMock
            ->expects($this->once())
            ->method('createProductOfferConcreteShipmentTypeValidatorStep')
            ->willReturn($validatorStepMock);

        $plugin = new ProductOfferConcreteShipmentTypeValidatorStepPlugin();
        $plugin->setBusinessFactory($businessFactoryMock);

        // Act
        $plugin->execute($dataSetMock);
    }

    public function testExecuteDoesNotThrowWhenShipmentTypeIsAssignedToTheProductConcreteOfTheOffer(): void
    {
        // Arrange
        $productConcreteTransfer = $this->tester->haveProduct();
        $shipmentTypeTransfer = $this->tester->haveShipmentType();
        $this->tester->haveProductConcreteShipmentType($productConcreteTransfer, $shipmentTypeTransfer);

        $productOfferTransfer = $this->tester->haveProductOffer([
            'concreteSku' => $productConcreteTransfer->getSkuOrFail(),
        ]);

        $dataSet = $this->createDataSet(
            $productOfferTransfer->getProductOfferReferenceOrFail(),
            $shipmentTypeTransfer->getIdShipmentTypeOrFail(),
            $shipmentTypeTransfer->getKeyOrFail(),
        );

        // Act
        (new ProductOfferConcreteShipmentTypeValidatorStepPlugin())->execute($dataSet);

        // Assert
        $this->assertTrue(true, 'Plugin must not throw when shipment type is assigned to the concrete.');
    }

    public function testExecuteThrowsInvalidDataExceptionWhenShipmentTypeIsNotAssignedToTheProductConcreteOfTheOffer(): void
    {
        // Arrange
        $productConcreteTransfer = $this->tester->haveProduct();
        $assignedShipmentTypeTransfer = $this->tester->haveShipmentType();
        $unassignedShipmentTypeTransfer = $this->tester->haveShipmentType();
        $this->tester->haveProductConcreteShipmentType($productConcreteTransfer, $assignedShipmentTypeTransfer);

        $productOfferTransfer = $this->tester->haveProductOffer([
            'concreteSku' => $productConcreteTransfer->getSkuOrFail(),
        ]);

        $dataSet = $this->createDataSet(
            $productOfferTransfer->getProductOfferReferenceOrFail(),
            $unassignedShipmentTypeTransfer->getIdShipmentTypeOrFail(),
            $unassignedShipmentTypeTransfer->getKeyOrFail(),
        );

        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessageMatches(sprintf(
            '/Shipment type "%s" is not assigned to the product concrete for offer "%s"/',
            preg_quote($unassignedShipmentTypeTransfer->getKeyOrFail(), '/'),
            preg_quote($productOfferTransfer->getProductOfferReferenceOrFail(), '/'),
        ));

        // Act
        (new ProductOfferConcreteShipmentTypeValidatorStepPlugin())->execute($dataSet);
    }

    public function testExecuteThrowsInvalidDataExceptionWhenProductConcreteHasNoShipmentTypeAssignments(): void
    {
        // Arrange
        $productConcreteTransfer = $this->tester->haveProduct();
        $shipmentTypeTransfer = $this->tester->haveShipmentType();

        $productOfferTransfer = $this->tester->haveProductOffer([
            'concreteSku' => $productConcreteTransfer->getSkuOrFail(),
        ]);

        $dataSet = $this->createDataSet(
            $productOfferTransfer->getProductOfferReferenceOrFail(),
            $shipmentTypeTransfer->getIdShipmentTypeOrFail(),
            $shipmentTypeTransfer->getKeyOrFail(),
        );

        $this->expectException(InvalidDataException::class);

        // Act
        (new ProductOfferConcreteShipmentTypeValidatorStepPlugin())->execute($dataSet);
    }

    protected function createDataSet(string $productOfferReference, int $idShipmentType, string $shipmentTypeKey): DataSet
    {
        $dataSet = new DataSet();
        $dataSet[ProductOfferShipmentTypeDataSetInterface::COLUMN_PRODUCT_OFFER_REFERENCE] = $productOfferReference;
        $dataSet[ProductOfferShipmentTypeDataSetInterface::ID_SHIPMENT_TYPE] = $idShipmentType;
        $dataSet[ProductOfferShipmentTypeDataSetInterface::COLUMN_SHIPMENT_TYPE_KEY] = $shipmentTypeKey;

        return $dataSet;
    }

    protected function resetValidatorStepStaticCache(): void
    {
        $reflection = new ReflectionClass(ProductOfferConcreteShipmentTypeValidatorStep::class);
        $property = $reflection->getProperty('assignedShipmentTypeIdsByProductOfferReference');
        $property->setValue(null, []);
    }
}

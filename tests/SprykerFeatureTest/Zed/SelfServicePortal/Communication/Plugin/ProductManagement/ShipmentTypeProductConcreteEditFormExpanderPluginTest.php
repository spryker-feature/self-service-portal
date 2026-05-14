<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\SelfServicePortal\Communication\Plugin\ProductManagement;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductOfferShipmentTypeCollectionTransfer;
use Generated\Shared\Transfer\ProductOfferShipmentTypeCriteriaTransfer;
use Generated\Shared\Transfer\ProductOfferShipmentTypeTransfer;
use Generated\Shared\Transfer\ShipmentTypeCollectionTransfer;
use Generated\Shared\Transfer\ShipmentTypeCriteriaTransfer;
use Generated\Shared\Transfer\ShipmentTypeTransfer;
use Spryker\Zed\Product\Business\ProductFacadeInterface;
use Spryker\Zed\ProductOfferShipmentType\Business\ProductOfferShipmentTypeFacadeInterface;
use Spryker\Zed\ShipmentType\Business\ShipmentTypeFacadeInterface;
use SprykerFeature\Zed\SelfServicePortal\Communication\Plugin\ProductManagement\ShipmentTypeProductConcreteEditFormExpanderPlugin;
use SprykerFeature\Zed\SelfServicePortal\Communication\SelfServicePortalCommunicationFactory;
use SprykerFeature\Zed\SelfServicePortal\Communication\Service\Form\EventListener\ShipmentTypeProductConcreteFormEventSubscriber;
use SprykerFeature\Zed\SelfServicePortal\Communication\Service\Form\ShipmentTypeProductConcreteForm;
use SprykerFeatureTest\Zed\SelfServicePortal\SelfServicePortalCommunicationTester;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group SelfServicePortal
 * @group Communication
 * @group Plugin
 * @group ProductManagement
 * @group ShipmentTypeProductConcreteEditFormExpanderPluginTest
 */
class ShipmentTypeProductConcreteEditFormExpanderPluginTest extends Unit
{
    protected const int ID_PRODUCT_CONCRETE = 101;

    protected const string PRODUCT_CONCRETE_SKU = 'concrete-sku-101';

    protected const int ID_SHIPMENT_TYPE_DELIVERY = 1;

    protected const int ID_SHIPMENT_TYPE_PICKUP = 2;

    protected const string SHIPMENT_TYPE_NAME_DELIVERY = 'Delivery';

    protected const string SHIPMENT_TYPE_NAME_PICKUP = 'Pickup';

    protected SelfServicePortalCommunicationTester $tester;

    public function testBuildFormAttachesShipmentTypeProductConcreteFormEventSubscriber(): void
    {
        // Arrange
        $eventSubscriber = $this->createEventSubscriber(
            $this->createMock(ProductOfferShipmentTypeFacadeInterface::class),
            $this->createMock(ShipmentTypeFacadeInterface::class),
            $this->createMock(ProductFacadeInterface::class),
        );

        $factoryMock = $this->createPartialMock(
            SelfServicePortalCommunicationFactory::class,
            ['createShipmentTypeProductConcreteFormEventSubscriber'],
        );
        $factoryMock
            ->expects($this->once())
            ->method('createShipmentTypeProductConcreteFormEventSubscriber')
            ->willReturn($eventSubscriber);

        $plugin = new ShipmentTypeProductConcreteEditFormExpanderPlugin();
        $plugin->setFactory($factoryMock);

        $formBuilder = Forms::createFormFactory()->createBuilder(FormType::class);

        // Act
        $plugin->buildForm($formBuilder, []);

        // Assert
        $this->assertTrue(
            $formBuilder->getEventDispatcher()->hasListeners(FormEvents::POST_SUBMIT),
            'Expected POST_SUBMIT listener to be registered by the event subscriber.',
        );
    }

    public function testEventSubscriberSubscribesToPostSubmit(): void
    {
        // Act
        $subscribedEvents = ShipmentTypeProductConcreteFormEventSubscriber::getSubscribedEvents();

        // Assert
        $this->assertArrayHasKey(FormEvents::POST_SUBMIT, $subscribedEvents);
        $this->assertSame('validateShipmentTypes', $subscribedEvents[FormEvents::POST_SUBMIT]);
    }

    public function testValidateShipmentTypesDoesNothingWhenFormDataIsNotAnArray(): void
    {
        // Arrange
        $shipmentTypeFacadeMock = $this->createMock(ShipmentTypeFacadeInterface::class);
        $shipmentTypeFacadeMock->expects($this->never())->method('getShipmentTypeCollection');

        $productFacadeMock = $this->createMock(ProductFacadeInterface::class);
        $productFacadeMock->expects($this->never())->method('getProductConcreteSkusByConcreteIds');

        $productOfferShipmentTypeFacadeMock = $this->createMock(ProductOfferShipmentTypeFacadeInterface::class);
        $productOfferShipmentTypeFacadeMock->expects($this->never())->method('getProductOfferShipmentTypeCollection');

        $eventSubscriber = $this->createEventSubscriber($productOfferShipmentTypeFacadeMock, $shipmentTypeFacadeMock, $productFacadeMock);
        $form = $this->createForm(null);

        // Act
        $eventSubscriber->validateShipmentTypes(new FormEvent($form, null));

        // Assert
        $this->assertCount(0, $form->getErrors(true));
    }

    public function testValidateShipmentTypesDoesNothingWhenIdProductConcreteIsMissing(): void
    {
        // Arrange
        $shipmentTypeFacadeMock = $this->createMock(ShipmentTypeFacadeInterface::class);
        $shipmentTypeFacadeMock->expects($this->never())->method('getShipmentTypeCollection');

        $eventSubscriber = $this->createEventSubscriber(
            $this->createMock(ProductOfferShipmentTypeFacadeInterface::class),
            $shipmentTypeFacadeMock,
            $this->createMock(ProductFacadeInterface::class),
        );
        $form = $this->createForm([ShipmentTypeProductConcreteForm::FIELD_ID_PRODUCT_CONCRETE => null]);

        // Act
        $eventSubscriber->validateShipmentTypes(new FormEvent($form, null));

        // Assert
        $this->assertCount(0, $form->getErrors(true));
    }

    public function testValidateShipmentTypesAddsNoErrorsWhenNoRemovedShipmentTypeIsInUse(): void
    {
        // Arrange
        $shipmentTypeFacadeMock = $this->createMock(ShipmentTypeFacadeInterface::class);
        $shipmentTypeFacadeMock
            ->method('getShipmentTypeCollection')
            ->with($this->isInstanceOf(ShipmentTypeCriteriaTransfer::class))
            ->willReturn($this->createShipmentTypeCollection());

        $productFacadeMock = $this->createMock(ProductFacadeInterface::class);
        $productFacadeMock
            ->method('getProductConcreteSkusByConcreteIds')
            ->with([static::ID_PRODUCT_CONCRETE])
            ->willReturn([static::PRODUCT_CONCRETE_SKU => static::ID_PRODUCT_CONCRETE]);

        $productOfferShipmentTypeFacadeMock = $this->createMock(ProductOfferShipmentTypeFacadeInterface::class);
        $productOfferShipmentTypeFacadeMock
            ->method('getProductOfferShipmentTypeCollection')
            ->with($this->isInstanceOf(ProductOfferShipmentTypeCriteriaTransfer::class))
            ->willReturn(new ProductOfferShipmentTypeCollectionTransfer());

        $eventSubscriber = $this->createEventSubscriber($productOfferShipmentTypeFacadeMock, $shipmentTypeFacadeMock, $productFacadeMock);

        $form = $this->createForm([
            ShipmentTypeProductConcreteForm::FIELD_ID_PRODUCT_CONCRETE => static::ID_PRODUCT_CONCRETE,
            ShipmentTypeProductConcreteForm::FIELD_SHIPMENT_TYPES => [
                (new ShipmentTypeTransfer())->setIdShipmentType(static::ID_SHIPMENT_TYPE_DELIVERY),
                (new ShipmentTypeTransfer())->setIdShipmentType(static::ID_SHIPMENT_TYPE_PICKUP),
            ],
        ]);

        // Act
        $eventSubscriber->validateShipmentTypes(new FormEvent($form, null));

        // Assert
        $this->assertCount(0, $form->getErrors(true));
    }

    public function testValidateShipmentTypesAddsErrorOnShipmentTypesFieldWhenRemovedShipmentTypeIsStillUsedByOffer(): void
    {
        // Arrange
        $shipmentTypeFacadeMock = $this->createMock(ShipmentTypeFacadeInterface::class);
        $shipmentTypeFacadeMock
            ->method('getShipmentTypeCollection')
            ->willReturn($this->createShipmentTypeCollection());

        $productFacadeMock = $this->createMock(ProductFacadeInterface::class);
        $productFacadeMock
            ->method('getProductConcreteSkusByConcreteIds')
            ->willReturn([static::PRODUCT_CONCRETE_SKU => static::ID_PRODUCT_CONCRETE]);

        $productOfferShipmentTypeFacadeMock = $this->createMock(ProductOfferShipmentTypeFacadeInterface::class);
        $productOfferShipmentTypeFacadeMock
            ->method('getProductOfferShipmentTypeCollection')
            ->willReturn($this->createProductOfferShipmentTypeCollectionWithPickup());

        $eventSubscriber = $this->createEventSubscriber($productOfferShipmentTypeFacadeMock, $shipmentTypeFacadeMock, $productFacadeMock);

        $form = $this->createForm([
            ShipmentTypeProductConcreteForm::FIELD_ID_PRODUCT_CONCRETE => static::ID_PRODUCT_CONCRETE,
            ShipmentTypeProductConcreteForm::FIELD_SHIPMENT_TYPES => [
                (new ShipmentTypeTransfer())->setIdShipmentType(static::ID_SHIPMENT_TYPE_DELIVERY),
            ],
        ]);

        // Act
        $eventSubscriber->validateShipmentTypes(new FormEvent($form, null));

        // Assert
        $shipmentTypesFieldErrors = $form->get(ShipmentTypeProductConcreteForm::FIELD_SHIPMENT_TYPES)->getErrors();
        $this->assertCount(1, $shipmentTypesFieldErrors);
        $this->assertStringContainsString(static::SHIPMENT_TYPE_NAME_PICKUP, $shipmentTypesFieldErrors[0]->getMessage());
        $this->assertCount(0, $form->getErrors(false));
    }

    public function testValidateShipmentTypesDeduplicatesErrorsByShipmentTypeId(): void
    {
        // Arrange
        $shipmentTypeFacadeMock = $this->createMock(ShipmentTypeFacadeInterface::class);
        $shipmentTypeFacadeMock
            ->method('getShipmentTypeCollection')
            ->willReturn($this->createShipmentTypeCollection());

        $productFacadeMock = $this->createMock(ProductFacadeInterface::class);
        $productFacadeMock
            ->method('getProductConcreteSkusByConcreteIds')
            ->willReturn([static::PRODUCT_CONCRETE_SKU => static::ID_PRODUCT_CONCRETE]);

        $productOfferShipmentTypeFacadeMock = $this->createMock(ProductOfferShipmentTypeFacadeInterface::class);
        $productOfferShipmentTypeFacadeMock
            ->method('getProductOfferShipmentTypeCollection')
            ->willReturn($this->createProductOfferShipmentTypeCollectionWithDuplicatePickup());

        $eventSubscriber = $this->createEventSubscriber($productOfferShipmentTypeFacadeMock, $shipmentTypeFacadeMock, $productFacadeMock);

        $form = $this->createForm([
            ShipmentTypeProductConcreteForm::FIELD_ID_PRODUCT_CONCRETE => static::ID_PRODUCT_CONCRETE,
            ShipmentTypeProductConcreteForm::FIELD_SHIPMENT_TYPES => [
                (new ShipmentTypeTransfer())->setIdShipmentType(static::ID_SHIPMENT_TYPE_DELIVERY),
            ],
        ]);

        // Act
        $eventSubscriber->validateShipmentTypes(new FormEvent($form, null));

        // Assert
        $shipmentTypesFieldErrors = $form->get(ShipmentTypeProductConcreteForm::FIELD_SHIPMENT_TYPES)->getErrors();
        $this->assertCount(1, $shipmentTypesFieldErrors);
    }

    protected function createEventSubscriber(
        ProductOfferShipmentTypeFacadeInterface $productOfferShipmentTypeFacade,
        ShipmentTypeFacadeInterface $shipmentTypeFacade,
        ProductFacadeInterface $productFacade
    ): ShipmentTypeProductConcreteFormEventSubscriber {
        return new ShipmentTypeProductConcreteFormEventSubscriber(
            $productOfferShipmentTypeFacade,
            $shipmentTypeFacade,
            $productFacade,
        );
    }

    /**
     * @param array<mixed>|null $data
     */
    protected function createForm(?array $data): FormInterface
    {
        $formFactory = $this->getFormFactory();
        $builder = $formFactory->createBuilder(FormType::class, $data);
        $builder->add(ShipmentTypeProductConcreteForm::FIELD_SHIPMENT_TYPES, FormType::class, [
            'mapped' => false,
            'compound' => false,
        ]);

        return $builder->getForm();
    }

    protected function getFormFactory(): FormFactoryInterface
    {
        return Forms::createFormFactory();
    }

    protected function createShipmentTypeCollection(): ShipmentTypeCollectionTransfer
    {
        return (new ShipmentTypeCollectionTransfer())
            ->setShipmentTypes(new ArrayObject([
                (new ShipmentTypeTransfer())
                    ->setIdShipmentType(static::ID_SHIPMENT_TYPE_DELIVERY)
                    ->setName(static::SHIPMENT_TYPE_NAME_DELIVERY),
                (new ShipmentTypeTransfer())
                    ->setIdShipmentType(static::ID_SHIPMENT_TYPE_PICKUP)
                    ->setName(static::SHIPMENT_TYPE_NAME_PICKUP),
            ]));
    }

    protected function createProductOfferShipmentTypeCollectionWithPickup(): ProductOfferShipmentTypeCollectionTransfer
    {
        $productOfferShipmentTypeTransfer = (new ProductOfferShipmentTypeTransfer())
            ->setShipmentTypes(new ArrayObject([
                (new ShipmentTypeTransfer())->setIdShipmentType(static::ID_SHIPMENT_TYPE_PICKUP),
            ]));

        return (new ProductOfferShipmentTypeCollectionTransfer())
            ->setProductOfferShipmentTypes(new ArrayObject([$productOfferShipmentTypeTransfer]));
    }

    protected function createProductOfferShipmentTypeCollectionWithDuplicatePickup(): ProductOfferShipmentTypeCollectionTransfer
    {
        $productOfferShipmentTypeTransfer1 = (new ProductOfferShipmentTypeTransfer())
            ->setShipmentTypes(new ArrayObject([
                (new ShipmentTypeTransfer())->setIdShipmentType(static::ID_SHIPMENT_TYPE_PICKUP),
            ]));
        $productOfferShipmentTypeTransfer2 = (new ProductOfferShipmentTypeTransfer())
            ->setShipmentTypes(new ArrayObject([
                (new ShipmentTypeTransfer())->setIdShipmentType(static::ID_SHIPMENT_TYPE_PICKUP),
            ]));

        return (new ProductOfferShipmentTypeCollectionTransfer())
            ->setProductOfferShipmentTypes(new ArrayObject([
                $productOfferShipmentTypeTransfer1,
                $productOfferShipmentTypeTransfer2,
            ]));
    }
}

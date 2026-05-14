<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\SelfServicePortal\Communication\Service\Form\EventListener;

use Generated\Shared\Transfer\ProductOfferShipmentTypeConditionsTransfer;
use Generated\Shared\Transfer\ProductOfferShipmentTypeCriteriaTransfer;
use Generated\Shared\Transfer\ShipmentTypeCollectionTransfer;
use Generated\Shared\Transfer\ShipmentTypeCriteriaTransfer;
use Spryker\Zed\Product\Business\ProductFacadeInterface;
use Spryker\Zed\ProductOfferShipmentType\Business\ProductOfferShipmentTypeFacadeInterface;
use Spryker\Zed\ShipmentType\Business\ShipmentTypeFacadeInterface;
use SprykerFeature\Zed\SelfServicePortal\Communication\Service\Form\ShipmentTypeProductConcreteForm;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class ShipmentTypeProductConcreteFormEventSubscriber implements EventSubscriberInterface
{
    protected const string MESSAGE_SHIPMENT_TYPE_IN_USE = 'Shipment type "%s" cannot be removed from this product variant because product offers are using it. Please remove the shipment type from those offers first.';

    public function __construct(
        protected ProductOfferShipmentTypeFacadeInterface $productOfferShipmentTypeFacade,
        protected ShipmentTypeFacadeInterface $shipmentTypeFacade,
        protected ProductFacadeInterface $productFacade,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SUBMIT => 'validateShipmentTypes',
        ];
    }

    public function validateShipmentTypes(FormEvent $event): void
    {
        $formData = $event->getForm()->getData();
        if (!is_array($formData)) {
            return;
        }

        $idProductConcrete = $formData[ShipmentTypeProductConcreteForm::FIELD_ID_PRODUCT_CONCRETE] ?? null;
        if (!$idProductConcrete) {
            return;
        }

        $shipmentTypeCollectionTransfer = $this->shipmentTypeFacade->getShipmentTypeCollection(
            (new ShipmentTypeCriteriaTransfer())->setShipmentTypeConditions(),
        );

        $shipmentTypesIndexedById = $this->indexShipmentTypesById($shipmentTypeCollectionTransfer);
        $newShipmentTypeIds = $this->extractSubmittedShipmentTypeIds($formData);
        $removedShipmentTypeIds = array_diff(array_keys($shipmentTypesIndexedById), $newShipmentTypeIds);

        $concreteSkus = $this->productFacade->getProductConcreteSkusByConcreteIds([(int)$idProductConcrete]);
        $productOfferShipmentTypeCollectionTransfer = $this->productOfferShipmentTypeFacade->getProductOfferShipmentTypeCollection(
            (new ProductOfferShipmentTypeCriteriaTransfer())->setProductOfferShipmentTypeConditions(
                (new ProductOfferShipmentTypeConditionsTransfer())
                    ->setProductConcreteSkus(array_keys($concreteSkus))
                    ->setShipmentTypeIds($removedShipmentTypeIds),
            ),
        );

        if ($productOfferShipmentTypeCollectionTransfer->getProductOfferShipmentTypes()->count() === 0) {
            return;
        }

        $conflictingShipmentTypeIds = $this->extractConflictingShipmentTypeIds(
            $productOfferShipmentTypeCollectionTransfer->getProductOfferShipmentTypes()->getArrayCopy(),
        );

        $this->addShipmentTypeFormErrors($event->getForm(), $conflictingShipmentTypeIds, $shipmentTypesIndexedById);
    }

    /**
     * @param array<mixed> $formData
     *
     * @return array<int>
     */
    protected function extractSubmittedShipmentTypeIds(array $formData): array
    {
        $shipmentTypes = $formData[ShipmentTypeProductConcreteForm::FIELD_SHIPMENT_TYPES] ?? null;
        if (!$shipmentTypes) {
            return [];
        }

        $ids = [];
        foreach ($shipmentTypes as $shipmentTypeTransfer) {
            $id = $shipmentTypeTransfer->getIdShipmentType();
            if ($id !== null) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * @return array<int, \Generated\Shared\Transfer\ShipmentTypeTransfer>
     */
    protected function indexShipmentTypesById(ShipmentTypeCollectionTransfer $shipmentTypeCollectionTransfer): array
    {
        $shipmentTypesIndexedById = [];
        foreach ($shipmentTypeCollectionTransfer->getShipmentTypes() as $shipmentTypeTransfer) {
            $shipmentTypesIndexedById[$shipmentTypeTransfer->getIdShipmentTypeOrFail()] = $shipmentTypeTransfer;
        }

        return $shipmentTypesIndexedById;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ProductOfferShipmentTypeTransfer> $productOfferShipmentTypeTransfers
     *
     * @return array<int>
     */
    protected function extractConflictingShipmentTypeIds(array $productOfferShipmentTypeTransfers): array
    {
        $shipmentTypeIds = [];
        foreach ($productOfferShipmentTypeTransfers as $productOfferShipmentTypeTransfer) {
            foreach ($productOfferShipmentTypeTransfer->getShipmentTypes() as $shipmentTypeTransfer) {
                $shipmentTypeIds[] = $shipmentTypeTransfer->getIdShipmentTypeOrFail();
            }
        }

        return array_unique($shipmentTypeIds);
    }

    /**
     * @param array<int> $conflictingShipmentTypeIds
     * @param array<int, \Generated\Shared\Transfer\ShipmentTypeTransfer> $shipmentTypesIndexedById
     */
    protected function addShipmentTypeFormErrors(FormInterface $form, array $conflictingShipmentTypeIds, array $shipmentTypesIndexedById): void
    {
        foreach ($conflictingShipmentTypeIds as $conflictingShipmentTypeId) {
            $message = sprintf(
                static::MESSAGE_SHIPMENT_TYPE_IN_USE,
                $shipmentTypesIndexedById[$conflictingShipmentTypeId]->getNameOrFail(),
            );
            $formError = new FormError($message);

            if ($form->has(ShipmentTypeProductConcreteForm::FIELD_SHIPMENT_TYPES)) {
                $form->get(ShipmentTypeProductConcreteForm::FIELD_SHIPMENT_TYPES)->addError($formError);

                continue;
            }

            $form->addError($formError);
        }
    }
}

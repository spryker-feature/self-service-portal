<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Communication\Service\Form\EventListener;

use ArrayObject;
use Generated\Shared\Transfer\ProductOfferTransfer;
use Generated\Shared\Transfer\ServicePointTransfer;
use Generated\Shared\Transfer\ServiceTransfer;
use SprykerFeature\Zed\SelfServicePortal\Communication\Service\Form\CreateOfferForm;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ServicePointEditOfferFormEventSubscriber implements EventSubscriberInterface
{
    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SET_DATA => 'expandFormWithServices',
            FormEvents::PRE_SUBMIT => 'expandProductOfferWithServicesOnPreSubmitEvent',
            FormEvents::SUBMIT => 'expandProductOfferWithServices',
        ];
    }

    public function expandFormWithServices(FormEvent $event): void
    {
        $productOfferTransfer = $event->getData();

        if (!($productOfferTransfer instanceof ProductOfferTransfer)) {
            return;
        }

        $form = $event->getForm();
        $services = $productOfferTransfer->getServices();

        if ($services->count() === 0) {
            return;
        }

        $servicePointId = $this->findServicePointId($services);

        if ($servicePointId !== null && $form->has(CreateOfferForm::FIELD_SERVICE_POINT)) {
            $form->get(CreateOfferForm::FIELD_SERVICE_POINT)->setData($servicePointId);
        }
        if ($form->has(CreateOfferForm::FIELD_SERVICE_POINT_SERVICES)) {
            $form->get(CreateOfferForm::FIELD_SERVICE_POINT_SERVICES)->setData($this->extractServiceUuids($services));
        }
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ServiceTransfer> $services
     *
     * @return array<string>
     */
    protected function extractServiceUuids(ArrayObject $services): array
    {
        $serviceUuids = [];

        foreach ($services as $serviceTransfer) {
            if ($serviceTransfer->getUuid() !== null) {
                $serviceUuids[] = $serviceTransfer->getUuid();
            }
        }

        return $serviceUuids;
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ServiceTransfer> $services
     */
    protected function findServicePointId(ArrayObject $services): ?string
    {
        foreach ($services as $serviceTransfer) {
            $idServicePoint = $serviceTransfer->getServicePoint()?->getIdServicePoint();

            if ($idServicePoint !== null) {
                return (string)$idServicePoint;
            }
        }

        return null;
    }

    public function expandProductOfferWithServices(FormEvent $event): void
    {
        $productOfferTransfer = $event->getData();

        if (!($productOfferTransfer instanceof ProductOfferTransfer)) {
            return;
        }

        $form = $event->getForm();

        $servicePointServices = $form->get(CreateOfferForm::FIELD_SERVICE_POINT_SERVICES)->getData();
        if ($productOfferTransfer->getServices()->count() > 0 && $servicePointServices === []) {
            $form->remove(CreateOfferForm::FIELD_SERVICE_POINT_SERVICES);
        }

        if (!$servicePointServices || !is_array($servicePointServices)) {
            return;
        }

        $services = new ArrayObject();

        foreach ($servicePointServices as $serviceUuid) {
            $serviceTransfer = new ServiceTransfer();
            $serviceTransfer->setUuid($serviceUuid);

            $services->append($serviceTransfer);
        }

        $productOfferTransfer->setServices($services);
    }

    public function expandProductOfferWithServicesOnPreSubmitEvent(FormEvent $event): void
    {
        $formData = $event->getData();
        if (!is_array($formData)) {
            return;
        }

        $productOfferTransfer = $event->getForm()->getData();
        if (!($productOfferTransfer instanceof ProductOfferTransfer)) {
            return;
        }

        if (!isset($formData[CreateOfferForm::FIELD_SERVICE_POINT_SERVICES], $formData[CreateOfferForm::FIELD_SERVICE_POINT])) {
            $productOfferTransfer->setServices(new ArrayObject());

            return;
        }

        $servicePointServices = $formData[CreateOfferForm::FIELD_SERVICE_POINT_SERVICES];
        $servicePoint = $formData[CreateOfferForm::FIELD_SERVICE_POINT];

        $serviceTransfers = new ArrayObject();

        foreach ($servicePointServices as $serviceUuid) {
            $serviceTransfer = (new ServiceTransfer())
                ->setUuid($serviceUuid)
                ->setServicePoint((new ServicePointTransfer())->setIdServicePoint($servicePoint));

            $serviceTransfers->append($serviceTransfer);
        }

        $productOfferTransfer->setServices($serviceTransfers);
    }
}

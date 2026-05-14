<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\SelfServicePortal\Communication\Plugin\ProductOfferShipmentTypeDataImport;

use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\ProductOfferShipmentTypeDataImport\Dependency\Plugin\ProductOfferShipmentTypeValidatorStepPluginInterface;

/**
 * @method \SprykerFeature\Zed\SelfServicePortal\Business\SelfServicePortalFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\SelfServicePortal\SelfServicePortalConfig getConfig()
 * @method \SprykerFeature\Zed\SelfServicePortal\Communication\SelfServicePortalCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\SelfServicePortal\Business\SelfServicePortalBusinessFactory getBusinessFactory()
 */
class ProductOfferConcreteShipmentTypeValidatorStepPlugin extends AbstractPlugin implements ProductOfferShipmentTypeValidatorStepPluginInterface
{
    /**
     * {@inheritDoc}
     * - Validates that the shipment type is assigned to the product concrete of the product offer.
     *
     * @api
     *
     * @throws \Spryker\Zed\DataImport\Business\Exception\InvalidDataException
     */
    public function execute(DataSetInterface $dataSet): void
    {
        $this->getBusinessFactory()->createProductOfferConcreteShipmentTypeValidatorStep()->execute($dataSet);
    }
}

<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\SelfServicePortal\Business\Service\DataImport\Step;

use Orm\Zed\Product\Persistence\Map\SpyProductTableMap;
use Orm\Zed\ProductOffer\Persistence\Map\SpyProductOfferTableMap;
use Orm\Zed\ProductOffer\Persistence\SpyProductOfferQuery;
use Orm\Zed\SelfServicePortal\Persistence\Map\SpyProductShipmentTypeTableMap;
use Propel\Runtime\ActiveQuery\Criteria;
use Spryker\Zed\DataImport\Business\Exception\InvalidDataException;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\DataImportStepInterface;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;
use Spryker\Zed\ProductOfferShipmentTypeDataImport\Business\DataSet\ProductOfferShipmentTypeDataSetInterface;

class ProductOfferConcreteShipmentTypeValidatorStep implements DataImportStepInterface
{
    protected const string MESSAGE_SHIPMENT_TYPE_NOT_ALLOWED = 'Shipment type "%s" is not assigned to the product concrete for offer "%s". Assign it to the product concrete first.';

    /**
     * SELECT alias for {@see SpyProductShipmentTypeTableMap::COL_FK_SHIPMENT_TYPE}.
     */
    protected const string AS_COLUMN_FK_SHIPMENT_TYPE = 'FkShipmentType';

    /**
     * @var array<string, array<int, true>>
     */
    protected static array $assignedShipmentTypeIdsByProductOfferReference = [];

    protected SpyProductOfferQuery $productOfferQuery;

    public function __construct()
    {
        $this->productOfferQuery = SpyProductOfferQuery::create();
    }

    /**
     * @throws \Spryker\Zed\DataImport\Business\Exception\InvalidDataException
     */
    public function execute(DataSetInterface $dataSet): void
    {
        $productOfferReference = $dataSet[ProductOfferShipmentTypeDataSetInterface::COLUMN_PRODUCT_OFFER_REFERENCE];
        $idShipmentType = (int)$dataSet[ProductOfferShipmentTypeDataSetInterface::ID_SHIPMENT_TYPE];

        if (!isset(static::$assignedShipmentTypeIdsByProductOfferReference[$productOfferReference])) {
            static::$assignedShipmentTypeIdsByProductOfferReference[$productOfferReference] = $this->fetchAssignedShipmentTypeIdsByProductOfferReference($productOfferReference);
        }

        $assignedShipmentTypeIds = static::$assignedShipmentTypeIdsByProductOfferReference[$productOfferReference];

        if (isset($assignedShipmentTypeIds[$idShipmentType])) {
            return;
        }

        $shipmentTypeKey = $dataSet[ProductOfferShipmentTypeDataSetInterface::COLUMN_SHIPMENT_TYPE_KEY];

        throw new InvalidDataException(
            sprintf(static::MESSAGE_SHIPMENT_TYPE_NOT_ALLOWED, $shipmentTypeKey, $productOfferReference),
        );
    }

    /**
     * @param string $productOfferReference
     *
     * @return array<int, true>
     */
    protected function fetchAssignedShipmentTypeIdsByProductOfferReference(string $productOfferReference): array
    {
        $rows = $this->productOfferQuery
            ->clear()
            ->addAsColumn(static::AS_COLUMN_FK_SHIPMENT_TYPE, SpyProductShipmentTypeTableMap::COL_FK_SHIPMENT_TYPE)
            ->select([static::AS_COLUMN_FK_SHIPMENT_TYPE])
            ->filterByProductOfferReference($productOfferReference)
            ->addJoin(SpyProductOfferTableMap::COL_CONCRETE_SKU, SpyProductTableMap::COL_SKU, Criteria::INNER_JOIN)
            ->addJoin(SpyProductTableMap::COL_ID_PRODUCT, SpyProductShipmentTypeTableMap::COL_FK_PRODUCT, Criteria::INNER_JOIN)
            ->find();

        $ids = [];
        foreach ($rows as $row) {
            $id = is_array($row) ? (int)$row[static::AS_COLUMN_FK_SHIPMENT_TYPE] : (int)$row;
            $ids[$id] = true;
        }

        return $ids;
    }
}

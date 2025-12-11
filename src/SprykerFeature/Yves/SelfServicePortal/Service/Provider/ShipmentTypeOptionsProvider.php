<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\SelfServicePortal\Service\Provider;

use SprykerFeature\Yves\SelfServicePortal\SelfServicePortalConfig;
use SprykerFeature\Yves\SelfServicePortal\Service\Sorter\ShipmentTypeGroupSorterInterface;

class ShipmentTypeOptionsProvider implements ShipmentTypeOptionsProviderInterface
{
    /**
     * @var string
     */
    protected const OPTION_LABEL = 'label';

    /**
     * @var string
     */
    protected const OPTION_VALUE = 'value';

    /**
     * @var string
     */
    protected const OPTION_IS_SERVICE_POINT_REQUIRED = 'isServicePointRequired';

    /**
     * @var string
     */
    protected const OPTION_SHIPMENT_TYPE_UUID = 'shipmentTypeUuid';

    /**
     * @var string
     */
    protected const OPTION_SERVICE_TYPE_KEY = 'serviceTypeKey';

    /**
     * @var string
     */
    protected const OPTION_SERVICE_TYPE_UUID = 'serviceTypeUuid';

    /**
     * @var string
     */
    protected const OPTION_IS_DEFAULT_SELECTED = 'isDefaultSelected';

    public function __construct(
        protected SelfServicePortalConfig $selfServicePortalConfig,
        protected ShipmentTypeGroupSorterInterface $shipmentTypeGroupSorter
    ) {
    }

    /**
     * @param array<\Generated\Shared\Transfer\ShipmentTypeStorageTransfer> $shipmentTypeStorageTransfers
     *
     * @return array<int, array<string, mixed>>
     */
    public function provideShipmentTypeOptions(array $shipmentTypeStorageTransfers): array
    {
        $servicePointRequiredShipmentTypeKeys = $this->selfServicePortalConfig->getShipmentTypeKeysRequiringServicePoint();
        $isServicePointRequiredMap = array_combine($servicePointRequiredShipmentTypeKeys, $servicePointRequiredShipmentTypeKeys);
        $defaultSelectedShipmentTypeKey = $this->selfServicePortalConfig->getDefaultSelectedShipmentTypeKey();
        $hasSingleOption = count($shipmentTypeStorageTransfers) === 1;

        $options = [];
        $hasDefaultSelectedKey = false;
        foreach ($shipmentTypeStorageTransfers as $shipmentTypeStorageTransfer) {
            $shipmentTypeKey = $shipmentTypeStorageTransfer->getKeyOrFail();
            $isDefaultSelected = $hasSingleOption || $shipmentTypeKey === $defaultSelectedShipmentTypeKey;
            if ($isDefaultSelected) {
                $hasDefaultSelectedKey = true;
            }

            $options[$shipmentTypeKey] = [
                static::OPTION_LABEL => $shipmentTypeStorageTransfer->getNameOrFail(),
                static::OPTION_VALUE => $shipmentTypeStorageTransfer->getUuidOrFail(),
                static::OPTION_SHIPMENT_TYPE_UUID => $shipmentTypeStorageTransfer->getUuidOrFail(),
                static::OPTION_SERVICE_TYPE_KEY => $shipmentTypeStorageTransfer->getServiceType()?->getKey(),
                static::OPTION_SERVICE_TYPE_UUID => $shipmentTypeStorageTransfer->getServiceType()?->getUuid(),
                static::OPTION_IS_SERVICE_POINT_REQUIRED => $isServicePointRequiredMap[$shipmentTypeKey] ?? false,
                static::OPTION_IS_DEFAULT_SELECTED => $isDefaultSelected,
            ];
        }

        $sortedOptions = array_values($this->shipmentTypeGroupSorter->sortShipmentTypeGroups($options));

        if (!$hasDefaultSelectedKey && !$hasSingleOption && count($sortedOptions) > 0) {
            $sortedOptions[0][static::OPTION_IS_DEFAULT_SELECTED] = true;
        }

        return $sortedOptions;
    }

    /**
     * @param array<int, array<string, mixed>> $shipmentTypeOptions
     *
     * @return string|null
     */
    public function getDefaultSelectedShipmentTypeUuid(array $shipmentTypeOptions): ?string
    {
        foreach ($shipmentTypeOptions as $option) {
            if ($option[static::OPTION_IS_DEFAULT_SELECTED] ?? false) {
                return $option[static::OPTION_VALUE];
            }
        }

        return $shipmentTypeOptions[array_key_last($shipmentTypeOptions)][static::OPTION_VALUE] ?? null;
    }
}

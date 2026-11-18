<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\SelfServicePortal\Asset\Reader;

use Generated\Shared\Transfer\CompanyBusinessUnitTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\SspAssetConditionsTransfer;
use Generated\Shared\Transfer\SspAssetCriteriaTransfer;
use Generated\Shared\Transfer\SspAssetIncludeTransfer;
use Generated\Shared\Transfer\SspAssetTransfer;
use Spryker\Client\Customer\CustomerClientInterface;
use SprykerFeature\Client\SelfServicePortal\Asset\Access\SspAssetAccessResolverInterface;
use SprykerFeature\Client\SelfServicePortal\SelfServicePortalConfig;
use SprykerFeature\Client\SelfServicePortal\Zed\SelfServicePortalStubInterface;

class SspAssetDetailsReader implements SspAssetDetailsReaderInterface
{
    public const string ARGUMENT_ASSET_REFERENCE = 'assetReference';

    protected const string KEY_ASSET_REFERENCE = 'assetReference';

    protected const string KEY_IS_AVAILABLE = 'isAvailable';

    protected const string KEY_SUGGESTED_NEXT_ACTION = 'suggestedNextAction';

    protected const string KEY_ASSET = 'asset';

    protected const string KEY_REFERENCE = 'reference';

    protected const string KEY_NAME = 'name';

    protected const string KEY_SERIAL_NUMBER = 'serialNumber';

    protected const string KEY_STATUS = 'status';

    protected const string KEY_NOTE = 'note';

    protected const string KEY_BUSINESS_UNIT_OWNER = 'businessUnitOwner';

    protected const string KEY_ASSIGNED_BUSINESS_UNITS = 'assignedBusinessUnits';

    protected const string KEY_MODELS = 'models';

    protected const string KEY_CODE = 'code';

    protected const string KEY_SERVICES = 'bookedServices';

    protected const string KEY_SERVICES_NOTE = 'bookedServicesNote';

    protected const string KEY_PRODUCT_NAME = 'productName';

    protected const string KEY_STATE = 'state';

    protected const string KEY_SCHEDULED_AT = 'scheduledAt';

    protected const string BUSINESS_UNIT_WITH_COMPANY_FORMAT = '%s (%s)';

    protected const string NEXT_ACTION_MISSING_REFERENCE = 'Pass the reference of the asset the customer asks about as `assetReference`. When the customer names the asset by its name or serial number, find the reference with `ssp_asset_search`; otherwise ask the customer to open or name the asset.';

    protected const string NEXT_ACTION_NOT_AVAILABLE = 'No asset with this reference is available to the customer. If the value is a name or a serial number, find the asset with `ssp_asset_search`. Otherwise tell the customer that the asset is not available to them, give no data of it, and offer to find their own assets by name or serial number.';

    protected const string NOTE_NO_SERVICES = 'There are no services at the moment.';

    protected const string NOTE_MORE_SERVICES = 'Only the first %d booked services are listed. The customer sees all of them on the asset details page.';

    public function __construct(
        protected SspAssetAccessResolverInterface $sspAssetAccessResolver,
        protected SelfServicePortalStubInterface $selfServicePortalStub,
        protected CustomerClientInterface $customerClient,
        protected SelfServicePortalConfig $selfServicePortalConfig
    ) {
    }

    /**
     * @param array<int|string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public function readAssetDetails(array $arguments): array
    {
        $assetReference = $this->extractAssetReference($arguments);

        if ($assetReference === '') {
            return [static::KEY_IS_AVAILABLE => false, static::KEY_SUGGESTED_NEXT_ACTION => static::NEXT_ACTION_MISSING_REFERENCE];
        }

        $companyUserTransfer = $this->sspAssetAccessResolver->findCompanyUserWithAssetAccess($assetReference);
        $sspAssetTransfer = $companyUserTransfer !== null ? $this->findSspAsset($assetReference, $companyUserTransfer) : null;

        if ($sspAssetTransfer === null) {
            return [
                static::KEY_ASSET_REFERENCE => $assetReference,
                static::KEY_IS_AVAILABLE => false,
                static::KEY_SUGGESTED_NEXT_ACTION => static::NEXT_ACTION_NOT_AVAILABLE,
            ];
        }

        return [
            static::KEY_ASSET_REFERENCE => $assetReference,
            static::KEY_IS_AVAILABLE => true,
            static::KEY_ASSET => $this->mapSspAsset($sspAssetTransfer),
        ];
    }

    /**
     * @param array<int|string, mixed> $arguments
     */
    protected function extractAssetReference(array $arguments): string
    {
        if (array_key_exists(0, $arguments) && is_array($arguments[0])) {
            $arguments = $arguments[0];
        }

        $assetReference = $arguments[static::ARGUMENT_ASSET_REFERENCE] ?? null;

        return is_string($assetReference) ? trim($assetReference) : '';
    }

    protected function findSspAsset(string $assetReference, CompanyUserTransfer $companyUserTransfer): ?SspAssetTransfer
    {
        $sspAssetCriteriaTransfer = (new SspAssetCriteriaTransfer())
            ->setCompanyUser((clone $companyUserTransfer)->setCustomer($this->customerClient->getCustomer()))
            ->setSspAssetConditions(
                (new SspAssetConditionsTransfer())
                    ->addReference($assetReference)
                    ->setStatuses($this->selfServicePortalConfig->getAssetDetailsViewStatuses()),
            )
            ->setInclude(
                (new SspAssetIncludeTransfer())
                    ->setWithOwnerCompanyBusinessUnit(true)
                    ->setWithAssignedBusinessUnits(true)
                    ->setWithSspModels(true)
                    ->setWithServicesCount($this->selfServicePortalConfig->getAssetDetailsServiceCount()),
            );

        foreach ($this->selfServicePortalStub->getSspAssetCollection($sspAssetCriteriaTransfer)->getSspAssets() as $sspAssetTransfer) {
            if ($sspAssetTransfer->getReference() === $assetReference) {
                return $sspAssetTransfer;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapSspAsset(SspAssetTransfer $sspAssetTransfer): array
    {
        return [
            static::KEY_REFERENCE => $sspAssetTransfer->getReference(),
            static::KEY_NAME => $sspAssetTransfer->getName(),
            static::KEY_SERIAL_NUMBER => $sspAssetTransfer->getSerialNumber(),
            static::KEY_STATUS => $this->selfServicePortalConfig->getAssetDetailsStatusNames()[(string)$sspAssetTransfer->getStatus()] ?? $sspAssetTransfer->getStatus(),
            static::KEY_NOTE => $sspAssetTransfer->getNote(),
            static::KEY_BUSINESS_UNIT_OWNER => $this->formatBusinessUnit($sspAssetTransfer->getCompanyBusinessUnit()),
            static::KEY_ASSIGNED_BUSINESS_UNITS => $this->mapAssignedBusinessUnits($sspAssetTransfer),
            static::KEY_MODELS => $this->mapModels($sspAssetTransfer),
        ] + $this->mapServices($sspAssetTransfer);
    }

    protected function formatBusinessUnit(?CompanyBusinessUnitTransfer $companyBusinessUnitTransfer): ?string
    {
        if ($companyBusinessUnitTransfer === null || (string)$companyBusinessUnitTransfer->getName() === '') {
            return null;
        }

        $companyName = (string)$companyBusinessUnitTransfer->getCompany()?->getName();

        if ($companyName === '') {
            return $companyBusinessUnitTransfer->getName();
        }

        return sprintf(static::BUSINESS_UNIT_WITH_COMPANY_FORMAT, $companyBusinessUnitTransfer->getName(), $companyName);
    }

    /**
     * @return list<string>
     */
    protected function mapAssignedBusinessUnits(SspAssetTransfer $sspAssetTransfer): array
    {
        $businessUnits = [];

        foreach ($sspAssetTransfer->getBusinessUnitAssignments() as $sspAssetBusinessUnitAssignmentTransfer) {
            $businessUnit = $this->formatBusinessUnit($sspAssetBusinessUnitAssignmentTransfer->getCompanyBusinessUnit());

            if ($businessUnit !== null) {
                $businessUnits[] = $businessUnit;
            }
        }

        return $businessUnits;
    }

    /**
     * @return list<array<string, string|null>>
     */
    protected function mapModels(SspAssetTransfer $sspAssetTransfer): array
    {
        $models = [];

        foreach ($sspAssetTransfer->getSspModels() as $sspModelTransfer) {
            $models[] = [
                static::KEY_NAME => $sspModelTransfer->getName(),
                static::KEY_CODE => $sspModelTransfer->getCode(),
            ];
        }

        return $models;
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapServices(SspAssetTransfer $sspAssetTransfer): array
    {
        $services = [];
        $sspServiceCollectionTransfer = $sspAssetTransfer->getSspServiceCollection();

        foreach ($sspServiceCollectionTransfer?->getServices() ?? [] as $sspServiceTransfer) {
            $services[] = [
                static::KEY_PRODUCT_NAME => $sspServiceTransfer->getProductName(),
                static::KEY_STATE => $sspServiceTransfer->getStateDisplayName() ?? $sspServiceTransfer->getStateName(),
                static::KEY_SCHEDULED_AT => $sspServiceTransfer->getScheduledAt(),
            ];
        }

        if ($services === []) {
            return [static::KEY_SERVICES => [], static::KEY_SERVICES_NOTE => static::NOTE_NO_SERVICES];
        }

        $serviceCount = $this->selfServicePortalConfig->getAssetDetailsServiceCount();
        $totalServiceCount = $sspServiceCollectionTransfer?->getPagination()?->getNbResults();

        if ($totalServiceCount !== null && $totalServiceCount > $serviceCount) {
            return [static::KEY_SERVICES => $services, static::KEY_SERVICES_NOTE => sprintf(static::NOTE_MORE_SERVICES, $serviceCount)];
        }

        return [static::KEY_SERVICES => $services];
    }
}

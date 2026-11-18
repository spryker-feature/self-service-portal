<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeatureTest\Client\SelfServicePortal;

use Codeception\Actor;
use Codeception\Stub;
use Generated\Shared\DataBuilder\CompanyUserBuilder;
use Generated\Shared\Transfer\CompanyBusinessUnitTransfer;
use Generated\Shared\Transfer\CompanyRoleCollectionTransfer;
use Generated\Shared\Transfer\CompanyRoleTransfer;
use Generated\Shared\Transfer\CompanyTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\PermissionCollectionTransfer;
use Generated\Shared\Transfer\SspAssetCollectionTransfer;
use Generated\Shared\Transfer\SspAssetTransfer;
use Spryker\Client\CompanyUser\CompanyUserClientInterface;
use Spryker\Client\Customer\CustomerClientInterface;
use Spryker\Client\Permission\PermissionClientInterface;
use Spryker\Client\Storage\StorageClientInterface;
use Spryker\Client\ZedRequest\ZedRequestClientInterface;
use SprykerFeature\Client\SelfServicePortal\SelfServicePortalDependencyProvider;
use SprykerFeature\Shared\SelfServicePortal\SelfServicePortalConfig;

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 * @method \SprykerFeature\Client\SelfServicePortal\SelfServicePortalClientInterface getClient(?string $moduleName = NULL)
 *
 * @SuppressWarnings(\SprykerFeatureTest\Client\SelfServicePortal\PHPMD)
 */
class SelfServicePortalClientTester extends Actor
{
    use _generated\SelfServicePortalClientTesterActions;

    public const string SSP_ASSET_REFERENCE = 'AST--3';

    public const string CUSTOMER_REFERENCE = 'DE--21';

    public const int ID_COMPANY = 1001;

    public const int ID_COMPANY_BUSINESS_UNIT = 2001;

    public const int ID_FOREIGN_COMPANY = 1002;

    public const int ID_FOREIGN_COMPANY_BUSINESS_UNIT = 2002;

    public const int ID_SSP_MODEL = 3001;

    public const int ID_WHITELIST = 4001;

    public function haveCompanyUserInSession(): CompanyUserTransfer
    {
        $companyUserTransfer = (new CompanyUserBuilder([
            CompanyUserTransfer::FK_COMPANY => static::ID_COMPANY,
            CompanyUserTransfer::FK_COMPANY_BUSINESS_UNIT => static::ID_COMPANY_BUSINESS_UNIT,
        ]))->build();

        $this->setDependency(
            SelfServicePortalDependencyProvider::CLIENT_COMPANY_USER,
            Stub::makeEmpty(CompanyUserClientInterface::class, ['findCompanyUser' => $companyUserTransfer]),
        );
        $this->setDependency(
            SelfServicePortalDependencyProvider::CLIENT_CUSTOMER,
            Stub::makeEmpty(CustomerClientInterface::class, ['getCustomer' => (new CustomerTransfer())->setCustomerReference(static::CUSTOMER_REFERENCE)]),
        );

        return $companyUserTransfer;
    }

    public function haveSspAssetInStorage(int $idCompany, int $idCompanyBusinessUnit): void
    {
        $sspAssetStorageData = json_encode([
            'reference' => static::SSP_ASSET_REFERENCE,
            'id_owner_company_id' => $idCompany,
            'id_owner_business_unit' => $idCompanyBusinessUnit,
            'company_ids' => [$idCompany],
            'business_unit_ids' => [$idCompanyBusinessUnit],
            'model_ids' => [static::ID_SSP_MODEL],
        ]);
        $sspModelStorageData = json_encode([
            'id_model' => static::ID_SSP_MODEL,
            'whitelist_ids' => [static::ID_WHITELIST],
        ]);

        $this->setDependency(SelfServicePortalDependencyProvider::CLIENT_STORAGE, Stub::makeEmpty(StorageClientInterface::class, [
            'getMulti' => function (array $keys) use ($sspAssetStorageData, $sspModelStorageData): array {
                $storageData = [];

                foreach ($keys as $key) {
                    $storageData[$key] = str_starts_with($key, SelfServicePortalConfig::SSP_MODEL_RESOURCE_NAME) ? $sspModelStorageData : $sspAssetStorageData;
                }

                return $storageData;
            },
        ]));
    }

    /**
     * @param list<\Spryker\Shared\PermissionExtension\Dependency\Plugin\ExecutablePermissionPluginInterface> $grantedPermissionPlugins
     */
    public function haveGrantedPermissions(array $grantedPermissionPlugins): void
    {
        $this->setDependency(SelfServicePortalDependencyProvider::CLIENT_PERMISSION, Stub::makeEmpty(PermissionClientInterface::class, [
            'can' => function (string $permissionKey, mixed $context = null) use ($grantedPermissionPlugins): bool {
                foreach ($grantedPermissionPlugins as $grantedPermissionPlugin) {
                    if ($grantedPermissionPlugin->getKey() === $permissionKey) {
                        return $grantedPermissionPlugin->can([], $context);
                    }
                }

                return false;
            },
        ]));
    }

    public function haveSspAssetInZed(SspAssetTransfer $sspAssetTransfer): void
    {
        $this->setDependency(
            SelfServicePortalDependencyProvider::CLIENT_ZED_REQUEST,
            Stub::makeEmpty(ZedRequestClientInterface::class, ['call' => (new SspAssetCollectionTransfer())->addSspAsset($sspAssetTransfer)]),
        );
    }

    public function generateSspModelStorageKey(int $modelId, string $storeName): string
    {
        return sprintf('ssp_model:%s:%d', $storeName, $modelId);
    }

    public function haveCompanyUserWithPermissions(
        CompanyTransfer $companyTransfer,
        PermissionCollectionTransfer $permissionCollectionTransfer
    ): CompanyUserTransfer {
        $companyRoleTransfer = $this->haveCompanyRole([
            CompanyRoleTransfer::FK_COMPANY => $companyTransfer->getIdCompany(),
            CompanyRoleTransfer::PERMISSION_COLLECTION => $permissionCollectionTransfer,
        ]);

        $businessUnitTransfer = $this->haveCompanyBusinessUnit([
            CompanyBusinessUnitTransfer::FK_COMPANY => $companyTransfer->getIdCompany(),
        ]);

        $companyRoleCollection = (new CompanyRoleCollectionTransfer())->addRole($companyRoleTransfer);

        $customerTransfer = $this->haveCustomer();
        $companyUserTransfer = $this->haveCompanyUser([
            CompanyUserTransfer::CUSTOMER => $customerTransfer,
            CompanyUserTransfer::FK_COMPANY => $companyTransfer->getIdCompany(),
            CompanyUserTransfer::FK_COMPANY_BUSINESS_UNIT => $businessUnitTransfer->getIdCompanyBusinessUnit(),
        ]);

        $companyUserTransfer->setCompanyRoleCollection($companyRoleCollection);
        $companyUserTransfer->setCompany($companyTransfer);
        $companyUserTransfer->setCompanyBusinessUnit($businessUnitTransfer);
        $companyUserTransfer->setCustomer($customerTransfer);

        $this->assignCompanyRolesToCompanyUser($companyUserTransfer);

        return $companyUserTransfer;
    }
}

<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\SelfServicePortal\Helper;

use Codeception\Module;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\SspAssetBusinessUnitAssignmentTransfer;
use Generated\Shared\Transfer\SspAssetTransfer;
use Generated\Shared\Transfer\SspInquiryTransfer;
use SprykerFeature\Glue\SelfServicePortal\SelfServicePortalConfig;
use SprykerFeature\Shared\SelfServicePortal\Plugin\Permission\CreateSspAssetPermissionPlugin;
use SprykerFeature\Shared\SelfServicePortal\Plugin\Permission\CreateSspInquiryPermissionPlugin;
use SprykerFeature\Shared\SelfServicePortal\Plugin\Permission\ViewBusinessUnitSspAssetPermissionPlugin;
use SprykerFeature\Shared\SelfServicePortal\Plugin\Permission\ViewCompanySspAssetPermissionPlugin;
use SprykerFeature\Shared\SelfServicePortal\SelfServicePortalConfig as SharedSelfServicePortalConfig;
use SprykerFeatureTest\Shared\SelfServicePortal\Helper\SelfServicePortalHelper;
use SprykerTest\ApiPlatform\Helper\ApiRequestHelperTrait;
use SprykerTest\Glue\CompanyUsersRestApi\Helper\CompanyUserDataHelper;
use Symfony\Component\HttpFoundation\Response;

/**
 * The assets and inquiries a self-service-portal scenario arranges, the identity each resource is
 * permission-gated by, and the requests sent against them.
 */
class SelfServicePortalDataHelper extends Module
{
    use ApiRequestHelperTrait;

    protected const string URI_SSP_ASSET_COLLECTION = '/ssp-assets';

    protected const string URI_SSP_INQUIRY_COLLECTION = '/ssp-inquiries';

    /**
     * The permissions the Self-Service Portal narrows an asset read and write by; without them Zed
     * hands back an empty collection rather than an error.
     *
     * @var array<class-string<\Spryker\Shared\PermissionExtension\Dependency\Plugin\PermissionPluginInterface>>
     */
    protected const array SSP_ASSET_PERMISSION_PLUGINS = [
        ViewCompanySspAssetPermissionPlugin::class,
        ViewBusinessUnitSspAssetPermissionPlugin::class,
        CreateSspAssetPermissionPlugin::class,
    ];

    /**
     * Creating an inquiry is permission-gated; reading is not - the criteria expander narrows an
     * unpermitted reader to the inquiries the company user raised.
     *
     * @var array<class-string<\Spryker\Shared\PermissionExtension\Dependency\Plugin\PermissionPluginInterface>>
     */
    protected const array SSP_INQUIRY_PERMISSION_PLUGINS = [
        CreateSspInquiryPermissionPlugin::class,
    ];

    protected const string ASSET_EXTERNAL_IMAGE_URL = 'https://example.com/asset.jpg';

    public function haveAssetOwnedBy(CompanyUserTransfer $companyUserTransfer): SspAssetTransfer
    {
        $companyBusinessUnitTransfer = $companyUserTransfer->getCompanyBusinessUnitOrFail();
        $sspAssetTransfer = $this->getSelfServicePortalHelper()->buildAsset();

        return $this->getSelfServicePortalHelper()->haveAsset([
            SspAssetTransfer::NAME => $sspAssetTransfer->getNameOrFail(),
            SspAssetTransfer::SERIAL_NUMBER => $sspAssetTransfer->getSerialNumberOrFail(),
            SspAssetTransfer::NOTE => $sspAssetTransfer->getNoteOrFail(),
            // The builder leaves this empty, and the resource answers it on every read.
            SspAssetTransfer::EXTERNAL_IMAGE_URL => static::ASSET_EXTERNAL_IMAGE_URL,
            SspAssetTransfer::COMPANY_BUSINESS_UNIT => $companyBusinessUnitTransfer,
            SspAssetTransfer::BUSINESS_UNIT_ASSIGNMENTS => [
                [SspAssetBusinessUnitAssignmentTransfer::COMPANY_BUSINESS_UNIT => $companyBusinessUnitTransfer],
            ],
        ]);
    }

    public function haveInquiryRaisedBy(CompanyUserTransfer $companyUserTransfer): SspInquiryTransfer
    {
        return $this->getSelfServicePortalHelper()->haveSspInquiryForCompanyUser($companyUserTransfer, [
            SspInquiryTransfer::TYPE => SharedSelfServicePortalConfig::GENERAL_SSP_INQUIRY_SOURCE,
        ]);
    }

    /**
     * A payload the whole validation chain accepts, so a test can knock out exactly the one attribute
     * it is about.
     *
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    public function validSspInquiryAttributes(array $overrides = []): array
    {
        $sspInquiryTransfer = $this->getSelfServicePortalHelper()->buildSspInquiry();

        return $overrides + [
            SspInquiryTransfer::TYPE => SharedSelfServicePortalConfig::GENERAL_SSP_INQUIRY_SOURCE,
            SspInquiryTransfer::SUBJECT => $sspInquiryTransfer->getSubjectOrFail(),
            SspInquiryTransfer::DESCRIPTION => $sspInquiryTransfer->getDescriptionOrFail(),
        ];
    }

    protected function getSelfServicePortalHelper(): SelfServicePortalHelper
    {
        /** @var \SprykerFeatureTest\Shared\SelfServicePortal\Helper\SelfServicePortalHelper $selfServicePortalHelper */
        $selfServicePortalHelper = $this->getModule('\\' . SelfServicePortalHelper::class);

        return $selfServicePortalHelper;
    }

    /**
     * A published, authenticated company user carrying the permissions an asset read and write are
     * narrowed by.
     */
    public function arrangeAuthenticatedAssetOwner(): CompanyUserTransfer
    {
        return $this->getCompanyUserDataHelper()
            ->arrangeAuthenticatedCompanyUserWithPermissions(static::SSP_ASSET_PERMISSION_PLUGINS);
    }

    /**
     * The same identity, deliberately not authenticated - for the colleague whose asset the acting
     * company user must not see.
     */
    public function haveAssetOwner(): CompanyUserTransfer
    {
        return $this->getCompanyUserDataHelper()
            ->haveCompanyUserForNewCustomerWithPermissions(static::SSP_ASSET_PERMISSION_PLUGINS);
    }

    public function arrangeAuthenticatedInquiryOwner(): CompanyUserTransfer
    {
        return $this->getCompanyUserDataHelper()
            ->arrangeAuthenticatedCompanyUserWithPermissions(static::SSP_INQUIRY_PERMISSION_PLUGINS);
    }

    public function arrangeAuthenticatedAssetInquiryOwner(): CompanyUserTransfer
    {
        return $this->getCompanyUserDataHelper()->arrangeAuthenticatedCompanyUserWithPermissions(
            array_merge(static::SSP_ASSET_PERMISSION_PLUGINS, static::SSP_INQUIRY_PERMISSION_PLUGINS),
        );
    }

    public function haveInquiryOwner(): CompanyUserTransfer
    {
        return $this->getCompanyUserDataHelper()
            ->haveCompanyUserForNewCustomerWithPermissions(static::SSP_INQUIRY_PERMISSION_PLUGINS);
    }

    public function getSspAssets(): Response
    {
        return $this->handleApiRequest('GET', static::URI_SSP_ASSET_COLLECTION);
    }

    public function getSspAsset(string $reference): Response
    {
        return $this->handleApiRequest('GET', $this->sspAssetUri($reference));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function postSspAsset(array $attributes): Response
    {
        return $this->handleApiRequest(
            'POST',
            static::URI_SSP_ASSET_COLLECTION,
            $this->encodeJsonApiBody(SelfServicePortalConfig::RESOURCE_SSP_ASSETS, $attributes),
        );
    }

    public function getSspInquiries(): Response
    {
        return $this->handleApiRequest('GET', static::URI_SSP_INQUIRY_COLLECTION);
    }

    public function getSspInquiry(string $reference): Response
    {
        return $this->handleApiRequest('GET', $this->sspInquiryUri($reference));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function postSspInquiry(array $attributes): Response
    {
        return $this->handleApiRequest(
            'POST',
            static::URI_SSP_INQUIRY_COLLECTION,
            $this->encodeJsonApiBody(SelfServicePortalConfig::RESOURCE_SSP_INQUIRIES, $attributes),
        );
    }

    /**
     * The resource path a self-link ends with, the one part of an IRI that is not a harness
     * artifact of the router's RequestContext.
     */
    public function sspAssetPath(string $reference): string
    {
        return $this->sspAssetUri($reference);
    }

    /**
     * The inquiry equivalent of {@see sspAssetPath()}.
     */
    public function sspInquiryPath(string $reference): string
    {
        return $this->sspInquiryUri($reference);
    }

    protected function sspAssetUri(string $reference): string
    {
        return sprintf('%s/%s', static::URI_SSP_ASSET_COLLECTION, $reference);
    }

    protected function sspInquiryUri(string $reference): string
    {
        return sprintf('%s/%s', static::URI_SSP_INQUIRY_COLLECTION, $reference);
    }

    protected function getCompanyUserDataHelper(): CompanyUserDataHelper
    {
        /** @var \SprykerTest\Glue\CompanyUsersRestApi\Helper\CompanyUserDataHelper $companyUserDataHelper */
        $companyUserDataHelper = $this->getModule('\\' . CompanyUserDataHelper::class);

        return $companyUserDataHelper;
    }
}

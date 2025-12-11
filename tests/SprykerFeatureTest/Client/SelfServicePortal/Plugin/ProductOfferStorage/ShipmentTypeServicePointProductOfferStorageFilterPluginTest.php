<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Client\SelfServicePortal\Plugin\ProductOfferStorage;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductOfferStorageCollectionTransfer;
use Generated\Shared\Transfer\ProductOfferStorageCriteriaTransfer;
use Generated\Shared\Transfer\ProductOfferStorageTransfer;
use Generated\Shared\Transfer\ServicePointStorageTransfer;
use Generated\Shared\Transfer\ServiceStorageTransfer;
use Generated\Shared\Transfer\ShipmentTypeStorageTransfer;
use SprykerFeature\Client\SelfServicePortal\Plugin\ProductOfferStorage\ShipmentTypeServicePointProductOfferStorageFilterPlugin;
use SprykerFeatureTest\Client\SelfServicePortal\SelfServicePortalClientTester;

/**
 * @group SprykerFeatureTest
 * @group Client
 * @group SelfServicePortal
 * @group Plugin
 * @group ProductOfferStorage
 * @group ShipmentTypeServicePointProductOfferStorageFilterPluginTest
 */
class ShipmentTypeServicePointProductOfferStorageFilterPluginTest extends Unit
{
    /**
     * @var string
     */
    protected const SHIPMENT_TYPE_UUID_1 = 'shipment-type-uuid-1';

    /**
     * @var string
     */
    protected const SHIPMENT_TYPE_UUID_2 = 'shipment-type-uuid-2';

    /**
     * @var string
     */
    protected const SHIPMENT_TYPE_UUID_3 = 'shipment-type-uuid-3';

    /**
     * @var string
     */
    protected const SERVICE_POINT_UUID_1 = 'service-point-uuid-1';

    /**
     * @var string
     */
    protected const SERVICE_POINT_UUID_2 = 'service-point-uuid-2';

    /**
     * @var string
     */
    protected const SERVICE_POINT_UUID_3 = 'service-point-uuid-3';

    /**
     * @var string
     */
    protected const PRODUCT_OFFER_REFERENCE_1 = 'offer-ref-1';

    /**
     * @var string
     */
    protected const PRODUCT_OFFER_REFERENCE_2 = 'offer-ref-2';

    /**
     * @var string
     */
    protected const PRODUCT_OFFER_REFERENCE_3 = 'offer-ref-3';

    /**
     * @var \SprykerFeatureTest\Client\SelfServicePortal\SelfServicePortalClientTester
     */
    protected SelfServicePortalClientTester $tester;

    /**
     * @var \SprykerFeature\Client\SelfServicePortal\Plugin\ProductOfferStorage\ShipmentTypeServicePointProductOfferStorageFilterPlugin
     */
    protected ShipmentTypeServicePointProductOfferStorageFilterPlugin $plugin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plugin = new ShipmentTypeServicePointProductOfferStorageFilterPlugin();
    }

    public function testFilterReturnsOriginalCollectionWhenBothCriteriaAreEmpty(): void
    {
        // Arrange
        $productOfferStorageCollectionTransfer = $this->createProductOfferStorageCollectionWithShipmentTypesAndServicePoints();
        $productOfferStorageCriteriaTransfer = (new ProductOfferStorageCriteriaTransfer())
            ->setShipmentTypeUuids([])
            ->setServicePointUuids([]);

        // Act
        $productOfferStorageCollectionTransfer = $this->plugin->filter($productOfferStorageCollectionTransfer, $productOfferStorageCriteriaTransfer);

        // Assert
        $this->assertCount(3, $productOfferStorageCollectionTransfer->getProductOffers());
    }

    public function testFilterReturnsOriginalCollectionWhenBothCriteriaAreNull(): void
    {
        // Arrange
        $productOfferStorageCollectionTransfer = $this->createProductOfferStorageCollectionWithShipmentTypesAndServicePoints();
        $productOfferStorageCriteriaTransfer = new ProductOfferStorageCriteriaTransfer();

        // Act
        $productOfferStorageCollectionTransfer = $this->plugin->filter($productOfferStorageCollectionTransfer, $productOfferStorageCriteriaTransfer);

        // Assert
        $this->assertCount(3, $productOfferStorageCollectionTransfer->getProductOffers());
    }

    public function testFilterByShipmentTypeUuidsOnly(): void
    {
        // Arrange
        $productOfferStorageCollectionTransfer = $this->createProductOfferStorageCollectionWithShipmentTypesAndServicePoints();
        $productOfferStorageCriteriaTransfer = (new ProductOfferStorageCriteriaTransfer())
            ->setShipmentTypeUuids([static::SHIPMENT_TYPE_UUID_1]);

        // Act
        $productOfferStorageCollectionTransfer = $this->plugin->filter($productOfferStorageCollectionTransfer, $productOfferStorageCriteriaTransfer);

        // Assert
        $this->assertCount(2, $productOfferStorageCollectionTransfer->getProductOffers());
        $productOfferReferences = $this->extractProductOfferReferences($productOfferStorageCollectionTransfer);
        $this->assertContains(static::PRODUCT_OFFER_REFERENCE_1, $productOfferReferences);
        $this->assertContains(static::PRODUCT_OFFER_REFERENCE_2, $productOfferReferences);
    }

    public function testFilterByServicePointUuidsOnly(): void
    {
        // Arrange
        $productOfferStorageCollectionTransfer = $this->createProductOfferStorageCollectionWithShipmentTypesAndServicePoints();
        $productOfferStorageCriteriaTransfer = (new ProductOfferStorageCriteriaTransfer())
            ->setServicePointUuids([static::SERVICE_POINT_UUID_1]);

        // Act
        $productOfferStorageCollectionTransfer = $this->plugin->filter($productOfferStorageCollectionTransfer, $productOfferStorageCriteriaTransfer);

        // Assert
        $this->assertCount(2, $productOfferStorageCollectionTransfer->getProductOffers());
        $productOfferReferences = $this->extractProductOfferReferences($productOfferStorageCollectionTransfer);
        $this->assertContains(static::PRODUCT_OFFER_REFERENCE_1, $productOfferReferences);
        $this->assertContains(static::PRODUCT_OFFER_REFERENCE_3, $productOfferReferences);
    }

    public function testFilterByBothShipmentTypeAndServicePointUuids(): void
    {
        // Arrange
        $productOfferStorageCollectionTransfer = $this->createProductOfferStorageCollectionWithShipmentTypesAndServicePoints();
        $productOfferStorageCriteriaTransfer = (new ProductOfferStorageCriteriaTransfer())
            ->setShipmentTypeUuids([static::SHIPMENT_TYPE_UUID_1])
            ->setServicePointUuids([static::SERVICE_POINT_UUID_1]);

        // Act
        $productOfferStorageCollectionTransfer = $this->plugin->filter($productOfferStorageCollectionTransfer, $productOfferStorageCriteriaTransfer);

        // Assert
        $this->assertCount(1, $productOfferStorageCollectionTransfer->getProductOffers());
        $productOfferReferences = $this->extractProductOfferReferences($productOfferStorageCollectionTransfer);
        $this->assertContains(static::PRODUCT_OFFER_REFERENCE_1, $productOfferReferences);
    }

    public function testFilterReturnsEmptyCollectionWhenNoOffersMatchShipmentTypeCriteria(): void
    {
        // Arrange
        $productOfferStorageCollectionTransfer = $this->createProductOfferStorageCollectionWithShipmentTypesAndServicePoints();
        $productOfferStorageCriteriaTransfer = (new ProductOfferStorageCriteriaTransfer())
            ->setShipmentTypeUuids(['non-existent-shipment-type-uuid']);

        // Act
        $productOfferStorageCollectionTransfer = $this->plugin->filter($productOfferStorageCollectionTransfer, $productOfferStorageCriteriaTransfer);

        // Assert
        $this->assertCount(0, $productOfferStorageCollectionTransfer->getProductOffers());
    }

    public function testFilterReturnsEmptyCollectionWhenNoOffersMatchServicePointCriteria(): void
    {
        // Arrange
        $productOfferStorageCollectionTransfer = $this->createProductOfferStorageCollectionWithShipmentTypesAndServicePoints();
        $productOfferStorageCriteriaTransfer = (new ProductOfferStorageCriteriaTransfer())
            ->setServicePointUuids(['non-existent-service-point-uuid']);

        // Act
        $productOfferStorageCollectionTransfer = $this->plugin->filter($productOfferStorageCollectionTransfer, $productOfferStorageCriteriaTransfer);

        // Assert
        $this->assertCount(0, $productOfferStorageCollectionTransfer->getProductOffers());
    }

    public function testFilterReturnsEmptyCollectionWhenNeitherCriteriaMatch(): void
    {
        // Arrange
        $productOfferStorageCollectionTransfer = $this->createProductOfferStorageCollectionWithShipmentTypesAndServicePoints();
        $productOfferStorageCriteriaTransfer = (new ProductOfferStorageCriteriaTransfer())
            ->setShipmentTypeUuids(['non-existent-shipment-type-uuid'])
            ->setServicePointUuids(['non-existent-service-point-uuid']);

        // Act
        $productOfferStorageCollectionTransfer = $this->plugin->filter($productOfferStorageCollectionTransfer, $productOfferStorageCriteriaTransfer);

        // Assert
        $this->assertCount(0, $productOfferStorageCollectionTransfer->getProductOffers());
    }

    public function testFilterWithMultipleShipmentTypeUuids(): void
    {
        // Arrange
        $productOfferStorageCollectionTransfer = $this->createProductOfferStorageCollectionWithShipmentTypesAndServicePoints();
        $productOfferStorageCriteriaTransfer = (new ProductOfferStorageCriteriaTransfer())
            ->setShipmentTypeUuids([static::SHIPMENT_TYPE_UUID_1, static::SHIPMENT_TYPE_UUID_3]);

        // Act
        $productOfferStorageCollectionTransfer = $this->plugin->filter($productOfferStorageCollectionTransfer, $productOfferStorageCriteriaTransfer);

        // Assert
        $this->assertCount(3, $productOfferStorageCollectionTransfer->getProductOffers());
    }

    public function testFilterWithMultipleServicePointUuids(): void
    {
        // Arrange
        $productOfferStorageCollectionTransfer = $this->createProductOfferStorageCollectionWithShipmentTypesAndServicePoints();
        $productOfferStorageCriteriaTransfer = (new ProductOfferStorageCriteriaTransfer())
            ->setServicePointUuids([static::SERVICE_POINT_UUID_1, static::SERVICE_POINT_UUID_2]);

        // Act
        $productOfferStorageCollectionTransfer = $this->plugin->filter($productOfferStorageCollectionTransfer, $productOfferStorageCriteriaTransfer);

        // Assert
        $this->assertCount(2, $productOfferStorageCollectionTransfer->getProductOffers());
        $productOfferReferences = $this->extractProductOfferReferences($productOfferStorageCollectionTransfer);
        $this->assertContains(static::PRODUCT_OFFER_REFERENCE_1, $productOfferReferences);
        $this->assertContains(static::PRODUCT_OFFER_REFERENCE_3, $productOfferReferences);
    }

    public function testFilterExcludesOffersWithoutShipmentTypesWhenShipmentTypeCriteriaProvided(): void
    {
        // Arrange
        $productOfferWithoutShipmentTypes = (new ProductOfferStorageTransfer())
            ->setProductOfferReference('offer-without-shipment-types')
            ->setShipmentTypes(new ArrayObject())
            ->setServices(new ArrayObject([
                $this->createServiceStorage(static::SERVICE_POINT_UUID_1),
            ]));

        $productOfferStorageCollectionTransfer = (new ProductOfferStorageCollectionTransfer())
            ->addProductOffer($productOfferWithoutShipmentTypes);

        $productOfferStorageCriteriaTransfer = (new ProductOfferStorageCriteriaTransfer())
            ->setShipmentTypeUuids([static::SHIPMENT_TYPE_UUID_1]);

        // Act
        $productOfferStorageCollectionTransfer = $this->plugin->filter($productOfferStorageCollectionTransfer, $productOfferStorageCriteriaTransfer);

        // Assert
        $this->assertCount(0, $productOfferStorageCollectionTransfer->getProductOffers());
    }

    public function testFilterExcludesOffersWithoutServicesWhenServicePointCriteriaProvided(): void
    {
        // Arrange
        $productOfferWithoutServices = (new ProductOfferStorageTransfer())
            ->setProductOfferReference('offer-without-services')
            ->setShipmentTypes(new ArrayObject([
                $this->createShipmentTypeStorage(static::SHIPMENT_TYPE_UUID_1),
            ]))
            ->setServices(new ArrayObject());

        $productOfferStorageCollectionTransfer = (new ProductOfferStorageCollectionTransfer())
            ->addProductOffer($productOfferWithoutServices);

        $productOfferStorageCriteriaTransfer = (new ProductOfferStorageCriteriaTransfer())
            ->setServicePointUuids([static::SERVICE_POINT_UUID_1]);

        // Act
        $productOfferStorageCollectionTransfer = $this->plugin->filter($productOfferStorageCollectionTransfer, $productOfferStorageCriteriaTransfer);

        // Assert
        $this->assertCount(0, $productOfferStorageCollectionTransfer->getProductOffers());
    }

    public function testFilterHandlesOffersWithNullServicePoint(): void
    {
        // Arrange
        $serviceWithoutServicePoint = (new ServiceStorageTransfer())
            ->setServicePoint(null);

        $productOfferStorage = (new ProductOfferStorageTransfer())
            ->setProductOfferReference('offer-with-null-service-point')
            ->setShipmentTypes(new ArrayObject([
                $this->createShipmentTypeStorage(static::SHIPMENT_TYPE_UUID_1),
            ]))
            ->setServices(new ArrayObject([$serviceWithoutServicePoint]));

        $productOfferStorageCollectionTransfer = (new ProductOfferStorageCollectionTransfer())
            ->addProductOffer($productOfferStorage);

        $productOfferStorageCriteriaTransfer = (new ProductOfferStorageCriteriaTransfer())
            ->setServicePointUuids([static::SERVICE_POINT_UUID_1]);

        // Act
        $productOfferStorageCollectionTransfer = $this->plugin->filter($productOfferStorageCollectionTransfer, $productOfferStorageCriteriaTransfer);

        // Assert
        $this->assertCount(0, $productOfferStorageCollectionTransfer->getProductOffers());
    }

    public function testFilterHandlesEmptyCollection(): void
    {
        // Arrange
        $productOfferStorageCollectionTransfer = new ProductOfferStorageCollectionTransfer();
        $productOfferStorageCriteriaTransfer = (new ProductOfferStorageCriteriaTransfer())
            ->setShipmentTypeUuids([static::SHIPMENT_TYPE_UUID_1])
            ->setServicePointUuids([static::SERVICE_POINT_UUID_1]);

        // Act
        $productOfferStorageCollectionTransfer = $this->plugin->filter($productOfferStorageCollectionTransfer, $productOfferStorageCriteriaTransfer);

        // Assert
        $this->assertCount(0, $productOfferStorageCollectionTransfer->getProductOffers());
    }

    public function testFilterWithShipmentTypeCriteriaOnlyExcludesOffersWithNonMatchingShipmentTypes(): void
    {
        // Arrange
        $productOfferStorageCollectionTransfer = $this->createProductOfferStorageCollectionWithShipmentTypesAndServicePoints();
        $productOfferStorageCriteriaTransfer = (new ProductOfferStorageCriteriaTransfer())
            ->setShipmentTypeUuids([static::SHIPMENT_TYPE_UUID_3]);

        // Act
        $productOfferStorageCollectionTransfer = $this->plugin->filter($productOfferStorageCollectionTransfer, $productOfferStorageCriteriaTransfer);

        // Assert
        $this->assertCount(1, $productOfferStorageCollectionTransfer->getProductOffers());
        $productOfferReferences = $this->extractProductOfferReferences($productOfferStorageCollectionTransfer);
        $this->assertContains(static::PRODUCT_OFFER_REFERENCE_3, $productOfferReferences);
    }

    public function testFilterWithServicePointCriteriaOnlyExcludesOffersWithNonMatchingServicePoints(): void
    {
        // Arrange
        $productOfferStorageCollectionTransfer = $this->createProductOfferStorageCollectionWithShipmentTypesAndServicePoints();
        $productOfferStorageCriteriaTransfer = (new ProductOfferStorageCriteriaTransfer())
            ->setServicePointUuids([static::SERVICE_POINT_UUID_3]);

        // Act
        $productOfferStorageCollectionTransfer = $this->plugin->filter($productOfferStorageCollectionTransfer, $productOfferStorageCriteriaTransfer);

        // Assert
        $this->assertCount(1, $productOfferStorageCollectionTransfer->getProductOffers());
        $productOfferReferences = $this->extractProductOfferReferences($productOfferStorageCollectionTransfer);
        $this->assertContains(static::PRODUCT_OFFER_REFERENCE_2, $productOfferReferences);
    }

    public function testFilterMatchesOfferWithMultipleShipmentTypesWhenOnlyOneMatches(): void
    {
        // Arrange
        $productOfferStorage = (new ProductOfferStorageTransfer())
            ->setProductOfferReference('offer-with-multiple-shipment-types')
            ->setShipmentTypes(new ArrayObject([
                $this->createShipmentTypeStorage(static::SHIPMENT_TYPE_UUID_1),
                $this->createShipmentTypeStorage('non-matching-uuid'),
            ]))
            ->setServices(new ArrayObject([
                $this->createServiceStorage(static::SERVICE_POINT_UUID_1),
            ]));

        $productOfferStorageCollectionTransfer = (new ProductOfferStorageCollectionTransfer())
            ->addProductOffer($productOfferStorage);

        $productOfferStorageCriteriaTransfer = (new ProductOfferStorageCriteriaTransfer())
            ->setShipmentTypeUuids([static::SHIPMENT_TYPE_UUID_1]);

        // Act
        $productOfferStorageCollectionTransfer = $this->plugin->filter($productOfferStorageCollectionTransfer, $productOfferStorageCriteriaTransfer);

        // Assert
        $this->assertCount(1, $productOfferStorageCollectionTransfer->getProductOffers());
    }

    public function testFilterMatchesOfferWithMultipleServicesWhenOnlyOneServicePointMatches(): void
    {
        // Arrange
        $productOfferStorage = (new ProductOfferStorageTransfer())
            ->setProductOfferReference('offer-with-multiple-services')
            ->setShipmentTypes(new ArrayObject([
                $this->createShipmentTypeStorage(static::SHIPMENT_TYPE_UUID_1),
            ]))
            ->setServices(new ArrayObject([
                $this->createServiceStorage(static::SERVICE_POINT_UUID_1),
                $this->createServiceStorage('non-matching-uuid'),
            ]));

        $productOfferStorageCollectionTransfer = (new ProductOfferStorageCollectionTransfer())
            ->addProductOffer($productOfferStorage);

        $productOfferStorageCriteriaTransfer = (new ProductOfferStorageCriteriaTransfer())
            ->setServicePointUuids([static::SERVICE_POINT_UUID_1]);

        // Act
        $productOfferStorageCollectionTransfer = $this->plugin->filter($productOfferStorageCollectionTransfer, $productOfferStorageCriteriaTransfer);

        // Assert
        $this->assertCount(1, $productOfferStorageCollectionTransfer->getProductOffers());
    }

    protected function createProductOfferStorageCollectionWithShipmentTypesAndServicePoints(): ProductOfferStorageCollectionTransfer
    {
        $productOfferStorage1 = (new ProductOfferStorageTransfer())
            ->setProductOfferReference(static::PRODUCT_OFFER_REFERENCE_1)
            ->setShipmentTypes(new ArrayObject([
                $this->createShipmentTypeStorage(static::SHIPMENT_TYPE_UUID_1),
                $this->createShipmentTypeStorage(static::SHIPMENT_TYPE_UUID_2),
            ]))
            ->setServices(new ArrayObject([
                $this->createServiceStorage(static::SERVICE_POINT_UUID_1),
            ]));

        $productOfferStorage2 = (new ProductOfferStorageTransfer())
            ->setProductOfferReference(static::PRODUCT_OFFER_REFERENCE_2)
            ->setShipmentTypes(new ArrayObject([
                $this->createShipmentTypeStorage(static::SHIPMENT_TYPE_UUID_1),
            ]))
            ->setServices(new ArrayObject([
                $this->createServiceStorage(static::SERVICE_POINT_UUID_3),
            ]));

        $productOfferStorage3 = (new ProductOfferStorageTransfer())
            ->setProductOfferReference(static::PRODUCT_OFFER_REFERENCE_3)
            ->setShipmentTypes(new ArrayObject([
                $this->createShipmentTypeStorage(static::SHIPMENT_TYPE_UUID_3),
            ]))
            ->setServices(new ArrayObject([
                $this->createServiceStorage(static::SERVICE_POINT_UUID_1),
                $this->createServiceStorage(static::SERVICE_POINT_UUID_2),
            ]));

        return (new ProductOfferStorageCollectionTransfer())
            ->addProductOffer($productOfferStorage1)
            ->addProductOffer($productOfferStorage2)
            ->addProductOffer($productOfferStorage3);
    }

    protected function createShipmentTypeStorage(string $uuid): ShipmentTypeStorageTransfer
    {
        return (new ShipmentTypeStorageTransfer())->setUuid($uuid);
    }

    protected function createServiceStorage(string $servicePointUuid): ServiceStorageTransfer
    {
        $servicePointStorage = (new ServicePointStorageTransfer())->setUuid($servicePointUuid);

        return (new ServiceStorageTransfer())->setServicePoint($servicePointStorage);
    }

    /**
     * @param \Generated\Shared\Transfer\ProductOfferStorageCollectionTransfer $productOfferStorageCollectionTransfer
     *
     * @return array<string>
     */
    protected function extractProductOfferReferences(ProductOfferStorageCollectionTransfer $productOfferStorageCollectionTransfer): array
    {
        $references = [];
        foreach ($productOfferStorageCollectionTransfer->getProductOffers() as $productOfferStorageTransfer) {
            $references[] = $productOfferStorageTransfer->getProductOfferReference();
        }

        return $references;
    }
}

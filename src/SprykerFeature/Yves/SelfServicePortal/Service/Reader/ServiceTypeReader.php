<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\SelfServicePortal\Service\Reader;

use Generated\Shared\Transfer\ServiceTypeStorageCollectionTransfer;
use Generated\Shared\Transfer\ServiceTypeStorageConditionsTransfer;
use Generated\Shared\Transfer\ServiceTypeStorageCriteriaTransfer;
use Spryker\Client\ServicePointStorage\ServicePointStorageClientInterface;

class ServiceTypeReader implements ServiceTypeReaderInterface
{
    public function __construct(
        protected ServicePointStorageClientInterface $servicePointStorageClient
    ) {
    }

    /**
     * @param array<int, string> $serviceTypeUuids
     *
     * @return \Generated\Shared\Transfer\ServiceTypeStorageCollectionTransfer
     */
    public function getServiceTypeStorageCollection(array $serviceTypeUuids): ServiceTypeStorageCollectionTransfer
    {
        $serviceTypeStorageCriteriaTransfer = $this->createServiceTypeStorageCriteriaTransfer($serviceTypeUuids);

        return $this->servicePointStorageClient->getServiceTypeStorageCollection($serviceTypeStorageCriteriaTransfer);
    }

    /**
     * @param array<int, string> $serviceTypeUuids
     *
     * @return \Generated\Shared\Transfer\ServiceTypeStorageCriteriaTransfer
     */
    protected function createServiceTypeStorageCriteriaTransfer(array $serviceTypeUuids): ServiceTypeStorageCriteriaTransfer
    {
        $serviceTypeStorageConditionsTransfer = (new ServiceTypeStorageConditionsTransfer())
            ->setUuids($serviceTypeUuids);

        return (new ServiceTypeStorageCriteriaTransfer())
            ->setServiceTypeStorageConditions($serviceTypeStorageConditionsTransfer);
    }
}

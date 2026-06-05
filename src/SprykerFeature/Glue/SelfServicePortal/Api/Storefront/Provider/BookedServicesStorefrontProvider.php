<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\SelfServicePortal\Api\Storefront\Provider;

use Generated\Api\Storefront\BookedServicesStorefrontResource;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\SspServiceTransfer;
use Spryker\ApiPlatform\State\Provider\AbstractStorefrontProvider;
use Spryker\Service\Serializer\SerializerServiceInterface;
use SprykerFeature\Glue\SelfServicePortal\Api\Storefront\Reader\BookedServicesStorefrontReaderInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class BookedServicesStorefrontProvider extends AbstractStorefrontProvider
{
    public function __construct(
        protected BookedServicesStorefrontReaderInterface $reader,
        protected SerializerServiceInterface $serializer,
    ) {
    }

    /**
     * @return array<\Generated\Api\Storefront\BookedServicesStorefrontResource>
     */
    protected function provideCollection(): array
    {
        $companyUserTransfer = $this->resolveCompanyUser();
        $limit = $this->getPaginationLimit();
        $offset = $this->getPaginationOffset();

        $sspServiceCollectionTransfer = $this->reader->getBookedServices($companyUserTransfer, $this->getCustomer(), $limit, $offset);

        $resources = [];

        foreach ($sspServiceCollectionTransfer->getServices() as $sspServiceTransfer) {
            $resources[] = $this->mapTransferToResource($sspServiceTransfer);
        }

        $pagination = $sspServiceCollectionTransfer->getPagination();

        if ($pagination !== null && count($resources) > 0) {
            $resources[0]->pagination = $this->calculatePagination($offset, $limit, $pagination->getNbResults() ?? 0);
        }

        return $resources;
    }

    protected function provideItem(): ?object
    {
        return null;
    }

    protected function mapTransferToResource(SspServiceTransfer $sspServiceTransfer): BookedServicesStorefrontResource
    {
        return $this->serializer->denormalize(
            $sspServiceTransfer->toArray(true, true),
            BookedServicesStorefrontResource::class,
        );
    }

    protected function resolveCompanyUser(): CompanyUserTransfer
    {
        if (!$this->hasCustomer()) {
            throw new AccessDeniedException();
        }

        $companyUserTransfer = $this->getCustomer()->getCompanyUserTransfer();

        if ($companyUserTransfer === null) {
            throw new AccessDeniedException();
        }

        return $companyUserTransfer;
    }
}

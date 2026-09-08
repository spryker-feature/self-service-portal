<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\SelfServicePortal\Business\Product\Validator;

use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\ProductClassConditionsTransfer;
use Generated\Shared\Transfer\ProductClassCriteriaTransfer;
use Generated\Shared\Transfer\ProductConcreteCollectionRequestTransfer;
use Generated\Shared\Transfer\ProductConcreteCollectionResponseTransfer;
use SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalRepositoryInterface;

class ProductClassValidator implements ProductClassValidatorInterface
{
    public function __construct(
        protected readonly SelfServicePortalRepositoryInterface $selfServicePortalRepository,
    ) {
    }

    public function validateProductConcreteCollection(
        ProductConcreteCollectionRequestTransfer $productConcreteCollectionRequestTransfer,
        ProductConcreteCollectionResponseTransfer $productConcreteCollectionResponseTransfer
    ): ProductConcreteCollectionResponseTransfer {
        $keysByEntity = [];

        foreach ($productConcreteCollectionRequestTransfer->getProducts() as $productConcreteTransfer) {
            $sku = (string)$productConcreteTransfer->getSku();
            foreach ($productConcreteTransfer->getProductClasses() as $productClassTransfer) {
                $key = $productClassTransfer->getKey();

                if ($key !== null && $key !== '') {
                    $keysByEntity[$sku][$key] = true;
                }
            }
        }

        if ($keysByEntity === []) {
            return $productConcreteCollectionResponseTransfer;
        }

        $knownKeys = $this->getKnownKeys($this->flattenKeys($keysByEntity));

        foreach ($keysByEntity as $sku => $keys) {
            foreach (array_diff(array_keys($keys), $knownKeys) as $unknownKey) {
                $productConcreteCollectionResponseTransfer->addError($this->createError((string)$sku, (string)$unknownKey));
            }
        }

        return $productConcreteCollectionResponseTransfer;
    }

    /**
     * @param array<string, array<string, true>> $keysByEntity
     *
     * @return list<string>
     */
    protected function flattenKeys(array $keysByEntity): array
    {
        $keys = [];

        foreach ($keysByEntity as $entityKeys) {
            // array_keys() returns int for a numeric-string product class key; ProductClassConditions::keys is string[].
            $keys = array_merge($keys, array_map('strval', array_keys($entityKeys)));
        }

        return array_values(array_unique($keys));
    }

    /**
     * @param list<string> $keys
     *
     * @return list<string>
     */
    protected function getKnownKeys(array $keys): array
    {
        $productClassCriteriaTransfer = (new ProductClassCriteriaTransfer())
            ->setProductClassConditions(
                (new ProductClassConditionsTransfer())->setKeys($keys),
            );

        $knownKeys = [];

        foreach ($this->selfServicePortalRepository->getProductClassCollection($productClassCriteriaTransfer)->getProductClasses() as $productClassTransfer) {
            $key = $productClassTransfer->getKey();

            if ($key !== null) {
                $knownKeys[] = $key;
            }
        }

        return $knownKeys;
    }

    protected function createError(?string $entityIdentifier, string $unknownKey): ErrorTransfer
    {
        return (new ErrorTransfer())
            ->setEntityIdentifier($entityIdentifier)
            ->setMessage(sprintf('Product class with key "%s" does not exist.', $unknownKey));
    }
}

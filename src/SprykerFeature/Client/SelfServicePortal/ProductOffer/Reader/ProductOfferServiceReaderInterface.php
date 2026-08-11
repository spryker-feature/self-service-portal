<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\SelfServicePortal\ProductOffer\Reader;

interface ProductOfferServiceReaderInterface
{
    /**
     * @return list<string>
     */
    public function getProductOfferReferencesWithServiceShipmentTypes(string $sku): array;
}

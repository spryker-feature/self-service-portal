<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Glue\SelfServicePortal\Processor\StorefrontApi\RestApi\Creator;

use Generated\Shared\Transfer\RestSspInquiriesAttributesTransfer;
use Spryker\Glue\GlueApplication\Rest\JsonApi\RestResponseInterface;
use Spryker\Glue\GlueApplication\Rest\Request\Data\RestRequestInterface;
use SprykerFeature\Client\SelfServicePortal\SelfServicePortalClientInterface;
use SprykerFeature\Glue\SelfServicePortal\Processor\StorefrontApi\RestApi\Builder\SspInquiriesResponseBuilderInterface;
use SprykerFeature\Glue\SelfServicePortal\Processor\StorefrontApi\RestApi\Mapper\SspInquiriesMapperInterface;
use SprykerFeature\Glue\SelfServicePortal\Processor\StorefrontApi\RestApi\Validator\SspRequestValidatorInterface;

class SspInquiriesCreator implements SspInquiriesCreatorInterface
{
    public function __construct(
        protected SelfServicePortalClientInterface $selfServicePortalClient,
        protected SspInquiriesResponseBuilderInterface $sspInquiriesResponseBuilder,
        protected SspInquiriesMapperInterface $sspInquiriesMapper,
        protected SspRequestValidatorInterface $sspRequestValidator
    ) {
    }

    public function create(RestRequestInterface $restRequest, RestSspInquiriesAttributesTransfer $restSspInquiriesAttributesTransfer): RestResponseInterface
    {
        $localeName = $restRequest->getMetadata()->getLocale();

        $sspInquiryCollectionResponseTransfer = $this->sspRequestValidator->validateSspInquiryCreateRequest($restSspInquiriesAttributesTransfer);

        if ($sspInquiryCollectionResponseTransfer->getErrors()->count() > 0) {
            return $this->sspInquiriesResponseBuilder
                ->createInquiryRestResponseFromSspInquiryCollectionResponseTransfer($sspInquiryCollectionResponseTransfer, $localeName);
        }

        $sspInquiryCollectionRequestTransfer = $this->sspInquiriesMapper
            ->mapRestSspInquiriesAttributesToSspInquiryCollectionRequestTransfer($restSspInquiriesAttributesTransfer, $restRequest);

        $sspInquiryCollectionResponseTransfer = $this->selfServicePortalClient->createSspInquiryCollection($sspInquiryCollectionRequestTransfer);

        return $this->sspInquiriesResponseBuilder->createInquiryRestResponseFromSspInquiryCollectionResponseTransfer($sspInquiryCollectionResponseTransfer, $localeName);
    }
}

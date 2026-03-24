<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Communication\Plugin\Sales;

use Generated\Shared\Transfer\OrderTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\SalesExtension\Dependency\Plugin\SalesDetailBlockRendererPluginInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerFeature\Zed\SelfServicePortal\Communication\SelfServicePortalCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\SelfServicePortal\Business\SelfServicePortalFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalRepositoryInterface getRepository()
 */
class SelfServicePortalOrderInquiryListBlockRendererPlugin extends AbstractPlugin implements SalesDetailBlockRendererPluginInterface
{
    protected const string BLOCK_URL = '/self-service-portal/list-order-inquiry';

    /**
     * {@inheritDoc}
     * - Checks if the block URL is '/self-service-portal/list-order-inquiry'.
     *
     * @api
     *
     * @param string $blockUrl
     *
     * @return bool
     */
    public function isApplicable(string $blockUrl): bool
    {
        return $blockUrl === static::BLOCK_URL;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param string $blockUrl
     *
     * @return string
     */
    public function getTemplatePath(string $blockUrl): string
    {
        return '@SelfServicePortal/ListOrderInquiry/index.twig';
    }

    /**
     * {@inheritDoc}
     * - Returns order inquiry table for the order as template data.
     *
     * @api
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Generated\Shared\Transfer\OrderTransfer $orderTransfer
     * @param string $blockUrl
     *
     * @return array<string, mixed>
     */
    public function getData(Request $request, OrderTransfer $orderTransfer, string $blockUrl): array
    {
        $sspInquiryTable = $this->getFactory()->createOrderSspInquiryTable($orderTransfer);

        return ['orderInquiryTable' => $sspInquiryTable->render()];
    }
}

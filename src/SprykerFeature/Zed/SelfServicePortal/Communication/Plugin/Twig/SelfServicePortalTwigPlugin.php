<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Communication\Plugin\Twig;

use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\TwigExtension\Dependency\Plugin\TwigPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Twig\Environment;
use Twig\TwigFilter;

/**
 * @method \SprykerFeature\Zed\SelfServicePortal\Communication\SelfServicePortalCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\SelfServicePortal\SelfServicePortalConfig getConfig()
 * @method \SprykerFeature\Zed\SelfServicePortal\Business\SelfServicePortalFacadeInterface getFacade()
 */
class SelfServicePortalTwigPlugin extends AbstractPlugin implements TwigPluginInterface
{
    protected const string FILTER_NAME_FORMAT_FILE_SIZE = 'formatFileSize';

    protected const int NUMBER_OF_DECIMALS = 2;

    /**
     * {@inheritDoc}
     * - Adds `formatFileSize` filter to format file size into a human-readable format.
     *
     * @api
     */
    public function extend(Environment $twig, ContainerInterface $container): Environment
    {
        $twig = $this->addFilters($twig);

        return $twig;
    }

    protected function addFilters(Environment $twig): Environment
    {
        $twig->addFilter($this->createFormatFileSizeFilter());

        return $twig;
    }

    protected function createFormatFileSizeFilter(): TwigFilter
    {
        return new TwigFilter(
            static::FILTER_NAME_FORMAT_FILE_SIZE,
            function (int $fileSize, int $numberOfDecimals = self::NUMBER_OF_DECIMALS): string {
                return $this->getFactory()->createFileSizeFormatter()->formatFileSize($fileSize, $numberOfDecimals);
            },
        );
    }
}

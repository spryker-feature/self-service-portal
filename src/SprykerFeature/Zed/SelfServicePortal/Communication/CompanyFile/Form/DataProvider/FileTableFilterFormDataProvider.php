<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Communication\CompanyFile\Form\DataProvider;

use Spryker\Service\UtilDateTime\UtilDateTimeServiceInterface;
use SprykerFeature\Zed\SelfServicePortal\Communication\CompanyFile\Form\FileTableFilterForm;
use SprykerFeature\Zed\SelfServicePortal\SelfServicePortalConfig;

class FileTableFilterFormDataProvider
{
    public const string OPTION_CURRENT_TIMEZONE = 'current_timezone';

    public function __construct(
        protected SelfServicePortalConfig $config,
        protected UtilDateTimeServiceInterface $utilDateTimeService
    ) {
    }

    /**
     * @return array<string, array<string, string>|string>
     */
    public function getOptions(): array
    {
        $fileExtensions = array_map(function (string $fileExtension) {
            return trim($fileExtension, '.');
        }, $this->config->getCompanyFileAllowedFileExtensions());

        return [
            FileTableFilterForm::OPTION_EXTENSIONS => array_combine($fileExtensions, $fileExtensions),
            static::OPTION_CURRENT_TIMEZONE => $this->utilDateTimeService->getTimezone(),
        ];
    }
}

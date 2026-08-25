<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\SelfServicePortal\Inquiry\Form\DataProvider;

use Codeception\Test\Unit;
use SprykerFeature\Yves\SelfServicePortal\Inquiry\Form\DataProvider\SspInquiryFormDataProvider;
use SprykerFeature\Yves\SelfServicePortal\Inquiry\Form\SspInquiryForm;
use SprykerFeature\Yves\SelfServicePortal\SelfServicePortalConfig;
use Symfony\Component\Mime\MimeTypes;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group SelfServicePortal
 * @group Inquiry
 * @group Form
 * @group DataProvider
 * @group SspInquiryFormDataProviderTest
 */
class SspInquiryFormDataProviderTest extends Unit
{
    protected SelfServicePortalConfig $selfServicePortalConfig;

    protected SspInquiryFormDataProvider $sspInquiryFormDataProvider;

    protected function _before(): void
    {
        $this->selfServicePortalConfig = new SelfServicePortalConfig();
        $this->sspInquiryFormDataProvider = new SspInquiryFormDataProvider($this->selfServicePortalConfig);
    }

    public function testGetOptionsReturnsSspInquiryAllowedFileMimeTypes(): void
    {
        // Act
        $options = $this->sspInquiryFormDataProvider->getOptions();

        // Assert
        $this->assertSame(
            $this->selfServicePortalConfig->getSspInquiryAllowedFileMimeTypes(),
            $options[SspInquiryForm::OPTION_ALLOWED_MIME_TYPES],
        );
    }

    public function testGetOptionsReturnsSspInquiryAllowedFileExtensions(): void
    {
        // Act
        $options = $this->sspInquiryFormDataProvider->getOptions();

        // Assert
        $this->assertSame(
            $this->selfServicePortalConfig->getSspInquiryAllowedFileExtensions(),
            $options[SspInquiryForm::OPTION_ALLOWED_EXTENSIONS],
        );
    }

    public function testGetOptionsProvidesMatchingMimeTypeForEveryAllowedExtension(): void
    {
        // Arrange
        $options = $this->sspInquiryFormDataProvider->getOptions();
        $allowedMimeTypes = $options[SspInquiryForm::OPTION_ALLOWED_MIME_TYPES];

        // Act
        foreach ($options[SspInquiryForm::OPTION_ALLOWED_EXTENSIONS] as $allowedExtension) {
            $extensionMimeTypes = MimeTypes::getDefault()->getMimeTypes($allowedExtension);

            // Assert
            $this->assertNotEmpty(
                array_intersect($extensionMimeTypes, $allowedMimeTypes),
                sprintf(
                    'None of the mime types of the allowed extension "%s" (%s) is allowed, so uploading such a file fails.',
                    $allowedExtension,
                    implode(', ', $extensionMimeTypes),
                ),
            );
        }
    }
}

<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeatureTest\Zed\SelfServicePortal\Communication\CompanyFile;

use Codeception\Test\Unit;
use SprykerFeature\Zed\SelfServicePortal\Communication\CompanyFile\FormDataNormalizer;
use SprykerFeature\Zed\SelfServicePortal\Communication\CompanyFile\Processor\FormDataProcessor;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group SelfServicePortal
 * @group Communication
 * @group CompanyFile
 * @group FormDataProcessorTest
 */
class FormDataProcessorTest extends Unit
{
    public function testPreprocessRequestDataFlattensIdFieldsOfPostRequest(): void
    {
        // Arrange
        $request = new Request([], [
            'fileAttachment' => [
                'companyIds' => [[1, 2], [2, 3]],
                'companyUserIds' => [[4]],
                'companyBusinessUnitIds' => [[5, 6]],
                'other' => 'value',
            ],
            'sspAssetFileAttachment' => [
                'sspAssetIds' => [[7], [8]],
            ],
        ]);
        $request->setMethod(Request::METHOD_POST);

        // Act
        (new FormDataProcessor(new FormDataNormalizer()))->preprocessRequestData($request);

        // Assert
        $this->assertSame([1, 2, 3], $request->request->all('fileAttachment')['companyIds']);
        $this->assertSame([4], $request->request->all('fileAttachment')['companyUserIds']);
        $this->assertSame([5, 6], $request->request->all('fileAttachment')['companyBusinessUnitIds']);
        $this->assertSame('value', $request->request->all('fileAttachment')['other']);
        $this->assertSame([7, 8], $request->request->all('sspAssetFileAttachment')['sspAssetIds']);
    }

    public function testPreprocessRequestDataDoesNothingForNonPostRequest(): void
    {
        // Arrange
        $request = new Request([], ['fileAttachment' => ['companyIds' => [[1]]]]);
        $request->setMethod(Request::METHOD_GET);

        // Act
        (new FormDataProcessor(new FormDataNormalizer()))->preprocessRequestData($request);

        // Assert
        $this->assertSame([[1]], $request->request->all('fileAttachment')['companyIds']);
    }
}

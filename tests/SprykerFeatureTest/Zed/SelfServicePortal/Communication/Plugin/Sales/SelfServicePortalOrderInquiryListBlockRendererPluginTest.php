<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeatureTest\Zed\SelfServicePortal\Communication\Plugin\Sales;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\OrderTransfer;
use SprykerFeature\Zed\SelfServicePortal\Communication\Plugin\Sales\SelfServicePortalOrderInquiryListBlockRendererPlugin;
use SprykerFeatureTest\Zed\SelfServicePortal\SelfServicePortalCommunicationTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Loader\ChainLoader;
use Twig\Loader\LoaderInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group SelfServicePortal
 * @group Communication
 * @group Plugin
 * @group Sales
 * @group SelfServicePortalOrderInquiryListBlockRendererPluginTest
 * Add your own group annotations below this line
 */
class SelfServicePortalOrderInquiryListBlockRendererPluginTest extends Unit
{
    protected const string BLOCK_URL = '/self-service-portal/list-order-inquiry';

    protected const string OTHER_URL = '/other/url';

    protected const string DEFAULT_OMS_PROCESS_NAME = 'Test01';

    /**
     * @uses \Spryker\Zed\Twig\Communication\Plugin\Application\TwigApplicationPlugin::SERVICE_TWIG
     */
    protected const string SERVICE_TWIG = 'twig';

    protected const string SERVICE_REQUEST_STACK = 'request_stack';

    protected SelfServicePortalCommunicationTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerTwigServiceMock();
        $this->registerRequestStack();
    }

    public function getBlockRendererPlugin(): SelfServicePortalOrderInquiryListBlockRendererPlugin
    {
        return new SelfServicePortalOrderInquiryListBlockRendererPlugin();
    }

    public function testIsApplicableReturnsTrueForMatchingUrl(): void
    {
        // Arrange
        $plugin = $this->getBlockRendererPlugin();

        // Act
        $result = $plugin->isApplicable(static::BLOCK_URL);

        // Assert
        $this->assertTrue($result);
    }

    public function testIsApplicableReturnsFalseForNonMatchingUrl(): void
    {
        // Arrange
        $plugin = $this->getBlockRendererPlugin();

        // Act
        $result = $plugin->isApplicable(static::OTHER_URL);

        // Assert
        $this->assertFalse($result);
    }

    public function testGetTemplatePathReturnsExpectedPath(): void
    {
        // Arrange
        $plugin = $this->getBlockRendererPlugin();

        // Act
        $result = $plugin->getTemplatePath(static::BLOCK_URL);

        // Assert
        $this->assertSame('@SelfServicePortal/ListOrderInquiry/index.twig', $result);
    }

    public function testGetDataReturnsOrderInquiryTable(): void
    {
        // Arrange
        $plugin = $this->getBlockRendererPlugin();
        $orderTransfer = (new OrderTransfer())->setIdSalesOrder(0);

        // Act
        $result = $plugin->getData(new Request(), $orderTransfer, static::BLOCK_URL);

        // Assert
        $this->assertArrayHasKey('orderInquiryTable', $result);
    }

    public function testGetDataReturnsRenderedTableForExistingOrder(): void
    {
        // Arrange
        $this->tester->configureTestStateMachine([static::DEFAULT_OMS_PROCESS_NAME]);
        $saveOrderTransfer = $this->tester->haveOrder([], static::DEFAULT_OMS_PROCESS_NAME);
        $orderTransfer = (new OrderTransfer())->setIdSalesOrder($saveOrderTransfer->getIdSalesOrder());

        $plugin = $this->getBlockRendererPlugin();

        // Act
        $result = $plugin->getData(new Request(), $orderTransfer, static::BLOCK_URL);

        // Assert
        $this->assertArrayHasKey('orderInquiryTable', $result);
        $this->assertIsString($result['orderInquiryTable']);
    }

    protected function registerTwigServiceMock(): void
    {
        $this->tester->getContainer()->set(static::SERVICE_TWIG, $this->getTwigMock());
    }

    protected function registerRequestStack(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/'));
        $this->tester->getContainer()->set(static::SERVICE_REQUEST_STACK, $requestStack);
    }

    protected function getTwigMock(): Environment
    {
        $twigMock = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $twigMock->method('render')->willReturn('');
        $twigMock->method('getLoader')->willReturn($this->createChainLoader());

        return $twigMock;
    }

    protected function createChainLoader(): LoaderInterface
    {
        return new ChainLoader();
    }
}

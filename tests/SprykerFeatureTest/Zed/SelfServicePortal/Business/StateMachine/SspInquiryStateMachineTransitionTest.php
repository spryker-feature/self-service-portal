<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeatureTest\Zed\SelfServicePortal\Business\StateMachine;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\SspInquiryCollectionRequestTransfer;
use Generated\Shared\Transfer\SspInquiryConditionsTransfer;
use Generated\Shared\Transfer\SspInquiryCriteriaTransfer;
use Generated\Shared\Transfer\SspInquiryTransfer;
use Generated\Shared\Transfer\StateMachineItemTransfer;
use Spryker\Zed\Mail\Business\MailFacadeInterface;
use Spryker\Zed\StateMachine\Business\StateMachineBusinessFactory;
use Spryker\Zed\StateMachine\Business\StateMachineFacade;
use Spryker\Zed\StateMachine\Business\StateMachineFacadeInterface;
use Spryker\Zed\StateMachine\StateMachineConfig;
use Spryker\Zed\StateMachine\StateMachineDependencyProvider;
use SprykerFeature\Shared\SelfServicePortal\SelfServicePortalConfig as SharedSelfServicePortalConfig;
use SprykerFeature\Zed\SelfServicePortal\Business\SelfServicePortalBusinessFactory;
use SprykerFeature\Zed\SelfServicePortal\Business\SelfServicePortalFacade;
use SprykerFeature\Zed\SelfServicePortal\Communication\Plugin\StateMachine\ApproveSspInquiryCommandPlugin;
use SprykerFeature\Zed\SelfServicePortal\Communication\Plugin\StateMachine\RejectSspInquiryCommandPlugin;
use SprykerFeature\Zed\SelfServicePortal\Communication\Plugin\StateMachine\SspInquiryStateMachineHandlerPlugin;
use SprykerFeature\Zed\SelfServicePortal\SelfServicePortalConfig;
use SprykerFeature\Zed\SelfServicePortal\SelfServicePortalDependencyProvider;
use SprykerFeatureTest\Zed\SelfServicePortal\SelfServicePortalBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group SelfServicePortal
 * @group Business
 * @group StateMachine
 * @group SspInquiryStateMachineTransitionTest
 *
 * Add your own group annotations below this line
 */
class SspInquiryStateMachineTransitionTest extends Unit
{
    protected const string STATE_MACHINE_XML_DIRECTORY = 'StateMachine';

    protected const string PROCESS_NAME = 'SspInquiryDefaultStateMachine';

    protected const string INQUIRY_TYPE = 'general';

    protected const string INITIAL_STATE = 'created';

    protected const string EVENT_START_REVIEW = 'start_review';

    protected const string EVENT_APPROVE = 'approve';

    protected const string EVENT_REJECT = 'reject';

    protected const string EVENT_CANCEL = 'cancel';

    protected const string STATE_APPROVED = 'approved';

    protected const string STATE_REJECTED = 'rejected';

    protected const string STATE_CANCELED = 'canceled';

    protected const string COMMAND_APPROVE = 'SspInquiry/Approve';

    protected const string COMMAND_REJECT = 'SspInquiry/Reject';

    protected SelfServicePortalBusinessTester $tester;

    protected SelfServicePortalConfig $selfServicePortalConfig;

    protected SelfServicePortalFacade $selfServicePortalFacade;

    protected StateMachineFacadeInterface $stateMachineFacade;

    protected function _before(): void
    {
        parent::_before();

        $this->selfServicePortalConfig = $this->createSelfServicePortalConfig();
        $selfServicePortalBusinessFactory = (new SelfServicePortalBusinessFactory())->setConfig($this->selfServicePortalConfig);
        $this->selfServicePortalFacade = (new SelfServicePortalFacade())->setFactory($selfServicePortalBusinessFactory);

        $stateMachineConfigMock = $this->createPartialMock(StateMachineConfig::class, ['getPathToStateMachineXmlFiles']);
        $stateMachineConfigMock->method('getPathToStateMachineXmlFiles')->willReturn(codecept_data_dir(static::STATE_MACHINE_XML_DIRECTORY));
        $this->stateMachineFacade = (new StateMachineFacade())->setFactory((new StateMachineBusinessFactory())->setConfig($stateMachineConfigMock));

        $this->tester->setDependency(SelfServicePortalDependencyProvider::FACADE_STATE_MACHINE, $this->stateMachineFacade, SelfServicePortalBusinessFactory::class);
        $this->tester->setDependency(SelfServicePortalDependencyProvider::FACADE_MAIL, $this->createMock(MailFacadeInterface::class), SelfServicePortalBusinessFactory::class);
        $this->tester->setDependency(SelfServicePortalDependencyProvider::PLUGINS_STATE_MACHINE_COMMAND, [
            static::COMMAND_APPROVE => (new ApproveSspInquiryCommandPlugin())->setBusinessFactory($selfServicePortalBusinessFactory),
            static::COMMAND_REJECT => (new RejectSspInquiryCommandPlugin())->setBusinessFactory($selfServicePortalBusinessFactory),
        ]);
        $this->tester->setDependency(StateMachineDependencyProvider::PLUGINS_STATE_MACHINE_HANDLERS, [
            (new SspInquiryStateMachineHandlerPlugin())->setConfig($this->selfServicePortalConfig),
        ]);
    }

    public function testGivenAnInquiryInReviewWhenTheApproveEventIsTriggeredThenTheInquiryIsApproved(): void
    {
        // Arrange
        $sspInquiryTransfer = $this->haveSspInquiryInReview();

        // Act
        $affectedItemCount = $this->stateMachineFacade->triggerEvent(
            static::EVENT_APPROVE,
            $this->createStateMachineItemTransfer($sspInquiryTransfer),
        );

        // Assert
        $this->assertSame(1, $affectedItemCount);
        $this->assertSame(static::STATE_APPROVED, $this->findSspInquiry($sspInquiryTransfer)->getStatus());
    }

    public function testGivenAnInquiryInReviewWhenTheRejectEventIsTriggeredThenTheInquiryIsRejected(): void
    {
        // Arrange
        $sspInquiryTransfer = $this->haveSspInquiryInReview();

        // Act
        $affectedItemCount = $this->stateMachineFacade->triggerEvent(
            static::EVENT_REJECT,
            $this->createStateMachineItemTransfer($sspInquiryTransfer),
        );

        // Assert
        $this->assertSame(1, $affectedItemCount);
        $this->assertSame(static::STATE_REJECTED, $this->findSspInquiry($sspInquiryTransfer)->getStatus());
    }

    public function testGivenAPendingInquiryWhenCancelSspInquiryCollectionThenTheInquiryIsCanceled(): void
    {
        // Arrange
        $sspInquiryTransfer = $this->haveSspInquiry();

        // Act
        $sspInquiryCollectionResponseTransfer = $this->selfServicePortalFacade->cancelSspInquiryCollection(
            (new SspInquiryCollectionRequestTransfer())
                ->addSspInquiry((new SspInquiryTransfer())->setReference($sspInquiryTransfer->getReferenceOrFail())),
        );

        // Assert
        $this->assertCount(0, $sspInquiryCollectionResponseTransfer->getErrors());
        $this->assertSame(static::STATE_CANCELED, $this->findSspInquiry($sspInquiryTransfer)->getStatus());
    }

    protected function haveSspInquiry(): SspInquiryTransfer
    {
        $customerTransfer = $this->tester->haveCustomer();
        $companyUserTransfer = $this->tester->haveCompanyUser([
            CompanyUserTransfer::FK_CUSTOMER => $customerTransfer->getIdCustomer(),
            CompanyUserTransfer::CUSTOMER => $customerTransfer,
            CompanyUserTransfer::FK_COMPANY => $this->tester->haveCompany()->getIdCompany(),
        ]);

        $sspInquiryCollectionResponseTransfer = $this->selfServicePortalFacade->createSspInquiryCollection(
            (new SspInquiryCollectionRequestTransfer())->addSspInquiry(
                (new SspInquiryTransfer())
                    ->setCompanyUser($companyUserTransfer)
                    ->setType(static::INQUIRY_TYPE)
                    ->setSubject('Inquiry subject')
                    ->setDescription('Inquiry description'),
            ),
        );

        return $this->findSspInquiry($sspInquiryCollectionResponseTransfer->getSspInquiries()->getIterator()->current());
    }

    protected function haveSspInquiryInReview(): SspInquiryTransfer
    {
        $sspInquiryTransfer = $this->haveSspInquiry();

        $this->stateMachineFacade->triggerEvent(
            static::EVENT_START_REVIEW,
            $this->createStateMachineItemTransfer($sspInquiryTransfer),
        );

        return $this->findSspInquiry($sspInquiryTransfer);
    }

    protected function findSspInquiry(SspInquiryTransfer $sspInquiryTransfer): SspInquiryTransfer
    {
        return $this->selfServicePortalFacade->getSspInquiryCollection(
            (new SspInquiryCriteriaTransfer())->setSspInquiryConditions(
                (new SspInquiryConditionsTransfer())->setSspInquiryIds([$sspInquiryTransfer->getIdSspInquiryOrFail()]),
            ),
        )->getSspInquiries()->getIterator()->current();
    }

    protected function createStateMachineItemTransfer(SspInquiryTransfer $sspInquiryTransfer): StateMachineItemTransfer
    {
        return (new StateMachineItemTransfer())
            ->setIdentifier($sspInquiryTransfer->getIdSspInquiryOrFail())
            ->setIdItemState((int)$sspInquiryTransfer->getStateMachineItemStateOrFail()->getIdStateMachineItemStateOrFail())
            ->setStateMachineName($this->selfServicePortalConfig->getInquiryStateMachineName())
            ->setProcessName(static::PROCESS_NAME);
    }

    protected function createSelfServicePortalConfig(): SelfServicePortalConfig
    {
        $selfServicePortalConfigMock = $this->getMockBuilder(SelfServicePortalConfig::class)
            ->onlyMethods([
                'getSspInquiryStateMachineProcessInquiryTypeMap',
                'getInquiryInitialStateMachineMap',
                'getSspInquiryCancelStateMachineEventName',
                'getSelectableSspInquiryTypes',
                'getYvesBaseUrl',
            ])
            ->getMock();

        $selfServicePortalConfigMock->method('getSspInquiryStateMachineProcessInquiryTypeMap')->willReturn([static::INQUIRY_TYPE => static::PROCESS_NAME]);
        $selfServicePortalConfigMock->method('getInquiryInitialStateMachineMap')->willReturn([static::PROCESS_NAME => static::INITIAL_STATE]);
        $selfServicePortalConfigMock->method('getSspInquiryCancelStateMachineEventName')->willReturn(static::EVENT_CANCEL);
        $selfServicePortalConfigMock->method('getSelectableSspInquiryTypes')->willReturn([static::INQUIRY_TYPE => [static::INQUIRY_TYPE]]);
        $selfServicePortalConfigMock->method('getYvesBaseUrl')->willReturn('http://yves.test');
        $selfServicePortalConfigMock->setSharedConfig(new SharedSelfServicePortalConfig());

        return $selfServicePortalConfigMock;
    }
}

<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Business\Inquiry\Hooks\PostCreate;

use Generated\Shared\Transfer\SspInquiryTransfer;
use Generated\Shared\Transfer\StateMachineProcessTransfer;
use InvalidArgumentException;
use Spryker\Zed\StateMachine\Business\StateMachineFacadeInterface;
use SprykerFeature\Zed\SelfServicePortal\SelfServicePortalConfig;

class StateMachineSspInquiryPostCreateHook implements SspInquiryPostCreateHookInterface
{
    public function __construct(
        protected SelfServicePortalConfig $selfServicePortalConfig,
        protected StateMachineFacadeInterface $stateMachineFacade
    ) {
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function execute(SspInquiryTransfer $sspInquiryTransfer): SspInquiryTransfer
    {
        $processName = $this->selfServicePortalConfig->getSspInquiryStateMachineProcessInquiryTypeMap()[$sspInquiryTransfer->getType()] ?? null;
        if (!$processName) {
            throw new InvalidArgumentException(
                sprintf('There is no process name for inquiry type %s', $sspInquiryTransfer->getType()),
            );
        }

        $stateMachineProcessTransfer = (new StateMachineProcessTransfer())
            ->setProcessName($this->selfServicePortalConfig->getSspInquiryStateMachineProcessInquiryTypeMap()[$sspInquiryTransfer->getType()])
            ->setStateMachineName($this->selfServicePortalConfig->getInquiryStateMachineName());

        $this->stateMachineFacade->triggerForNewStateMachineItem(
            $stateMachineProcessTransfer,
            (int)$sspInquiryTransfer->getIdSspInquiry(),
        );

        return $sspInquiryTransfer;
    }

    public function isApplicable(SspInquiryTransfer $sspInquiryTransfer): bool
    {
        return $sspInquiryTransfer->getStatus() === null;
    }
}

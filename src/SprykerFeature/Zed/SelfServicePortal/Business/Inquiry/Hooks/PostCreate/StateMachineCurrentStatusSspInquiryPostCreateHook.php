<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Business\Inquiry\Hooks\PostCreate;

use Exception;
use Generated\Shared\Transfer\SspInquiryTransfer;
use Generated\Shared\Transfer\StateMachineItemTransfer;
use Generated\Shared\Transfer\StateMachineProcessTransfer;
use Spryker\Zed\StateMachine\Business\StateMachineFacadeInterface;
use SprykerFeature\Zed\SelfServicePortal\SelfServicePortalConfig;

class StateMachineCurrentStatusSspInquiryPostCreateHook implements SspInquiryPostCreateHookInterface
{
     /**
      * @var array<string, int>
      */
    protected static array $sspInquiryTypeIdStateMachineProcessMapCache = [];

    public function __construct(
        protected SelfServicePortalConfig $selfServicePortalConfig,
        protected StateMachineFacadeInterface $stateMachineFacade
    ) {
    }

    public function execute(SspInquiryTransfer $sspInquiryTransfer): SspInquiryTransfer
    {
        $statusHistory = $this->stateMachineFacade->getStateHistoryByStateItemIdentifier(
            $this->getIdProcessByInquiryType($sspInquiryTransfer->getTypeOrFail()),
            $sspInquiryTransfer->getIdSspInquiryOrFail(),
        );

        $currentSspInquiryStatus = end($statusHistory);

        if (!$currentSspInquiryStatus) {
            return $sspInquiryTransfer;
        }

        $sspInquiryTransfer->setStatus($currentSspInquiryStatus->getStateName());

        $this->setIsCancellable($sspInquiryTransfer);

        return $sspInquiryTransfer;
    }

    public function isApplicable(SspInquiryTransfer $sspInquiryTransfer): bool
    {
        return $sspInquiryTransfer->getStatus() === null;
    }

    protected function setIsCancellable(SspInquiryTransfer $sspInquiryTransfer): SspInquiryTransfer
    {
        $stateMachineItemTransfer = new StateMachineItemTransfer();
        $stateMachineItemTransfer->setIdentifier($sspInquiryTransfer->getIdSspInquiry())
            ->setStateMachineName($this->selfServicePortalConfig->getInquiryStateMachineName())
            ->setProcessName($this->selfServicePortalConfig->getSspInquiryStateMachineProcessInquiryTypeMap()[$sspInquiryTransfer->getType()])
            ->setStateName($sspInquiryTransfer->getStatus());

        $manualEventsPerIdentifier = $this->stateMachineFacade->getManualEventsForStateMachineItem(
            $stateMachineItemTransfer,
        );

        $sspInquiryTransfer
            ->setManualEvents($manualEventsPerIdentifier)
            ->setIsCancellable(
                $this->selfServicePortalConfig->getSspInquiryCancelStateMachineEventName()
                && in_array($this->selfServicePortalConfig->getSspInquiryCancelStateMachineEventName(), $sspInquiryTransfer->getManualEvents()),
            );

        return $sspInquiryTransfer;
    }

    protected function getIdProcessByInquiryType(string $type): int
    {
        if (in_array($type, static::$sspInquiryTypeIdStateMachineProcessMapCache)) {
            return static::$sspInquiryTypeIdStateMachineProcessMapCache[$type];
        }

        if (!isset($this->selfServicePortalConfig->getSspInquiryStateMachineProcessInquiryTypeMap()[$type])) {
            throw new Exception(sprintf('Process name for inquiry type %s not found', $type));
        }

        $idProcess = $this->stateMachineFacade->getStateMachineProcessId(
            (new StateMachineProcessTransfer())
                ->setProcessName($this->selfServicePortalConfig->getSspInquiryStateMachineProcessInquiryTypeMap()[$type])
                ->setStateMachineName($this->selfServicePortalConfig->getInquiryStateMachineName()),
        );

        static::$sspInquiryTypeIdStateMachineProcessMapCache[$type] = $idProcess;

        return $idProcess;
    }
}

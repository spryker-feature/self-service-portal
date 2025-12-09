<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Business\Asset\Writer;

use Generated\Shared\Transfer\CompanyBusinessUnitTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\SspAssetCollectionRequestTransfer;
use Generated\Shared\Transfer\SspAssetCollectionResponseTransfer;
use Generated\Shared\Transfer\SspAssetTransfer;
use Spryker\Zed\CompanyBusinessUnit\Business\CompanyBusinessUnitFacadeInterface;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use Spryker\Zed\SequenceNumber\Business\SequenceNumberFacadeInterface;
use SprykerFeature\Zed\SelfServicePortal\Business\Asset\Validator\SspAssetValidatorInterface;
use SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalEntityManagerInterface;
use SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalRepositoryInterface;
use SprykerFeature\Zed\SelfServicePortal\SelfServicePortalConfig;

class SspAssetWriter implements SspAssetWriterInterface
{
    use TransactionTrait;

    protected const string MESSAGE_ASSET_UPDATE_ACCESS_DENIED = 'self_service_portal.asset.access.denied';

    protected const string MESSAGE_ASSET_SELF_ASSET_ASSIGMENT_DELETE = 'ssp_asset.validation.cannot_delete_own_assignment';

    protected const string MESSAGE_ASSET_BUSINESS_UNIT_UNASSIGNMENT_DENIED = 'self_service_portal.asset.business_unit_unassignment.denied';

    protected const string MESSAGE_ASSET_BUSINESS_UNIT_ASSIGNMENT_DENIED = 'self_service_portal.asset.business_unit_assignment.denied';

    protected const string MESSAGE_ASSET_BUSINESS_UNIT_NOT_FOUND = 'self_service_portal.asset.business_unit.not_found';

    /**
     * @var array<string, \Generated\Shared\Transfer\CompanyBusinessUnitTransfer>
     */
    protected array $companyBusinessUnitTransferIndexedByUuid;

    public function __construct(
        protected SelfServicePortalEntityManagerInterface $entityManager,
        protected SelfServicePortalRepositoryInterface $repository,
        protected SspAssetValidatorInterface $sspAssetValidator,
        protected SequenceNumberFacadeInterface $sequenceNumberFacade,
        protected SelfServicePortalConfig $config,
        protected FileSspAssetWriterInterface $fileSspAssetWriter,
        protected CompanyBusinessUnitFacadeInterface $companyBusinessUnitFacade
    ) {
    }

    public function createSspAssetCollection(
        SspAssetCollectionRequestTransfer $sspAssetCollectionRequestTransfer
    ): SspAssetCollectionResponseTransfer {
        $sspAssetCollectionResponseTransfer = $this->sspAssetValidator->validateRequestGrantedToCreateAsset(
            new SspAssetCollectionResponseTransfer(),
            $sspAssetCollectionRequestTransfer->getCompanyUser(),
        );

        if ($sspAssetCollectionResponseTransfer->getErrors()->count() > 0) {
            return $sspAssetCollectionResponseTransfer;
        }

        foreach ($sspAssetCollectionRequestTransfer->getSspAssets() as $sspAssetTransfer) {
            if (!$this->sspAssetValidator->validateAssetTransfer($sspAssetTransfer, $sspAssetCollectionResponseTransfer)) {
                continue;
            }

            $sspAssetCollectionResponseTransfer = $this->executeAssetCreation($sspAssetTransfer, $sspAssetCollectionResponseTransfer);
        }

        return $sspAssetCollectionResponseTransfer;
    }

    public function updateSspAssetCollection(
        SspAssetCollectionRequestTransfer $sspAssetCollectionRequestTransfer
    ): SspAssetCollectionResponseTransfer {
        $sspAssetCollectionResponseTransfer = new SspAssetCollectionResponseTransfer();

        foreach ($sspAssetCollectionRequestTransfer->getSspAssets() as $sspAssetTransfer) {
            if (!$this->sspAssetValidator->isAssetUpdateGranted($sspAssetTransfer, $sspAssetCollectionRequestTransfer->getCompanyUser())) {
                $sspAssetCollectionResponseTransfer->addError(
                    (new ErrorTransfer())
                        ->setEntityIdentifier($sspAssetTransfer->getReferenceOrFail())
                        ->setMessage(static::MESSAGE_ASSET_UPDATE_ACCESS_DENIED),
                );

                continue;
            }

            if (!$this->sspAssetValidator->validateAssetUpdateTransfer($sspAssetTransfer, $sspAssetCollectionResponseTransfer)) {
                continue;
            }

            $sspAssetTransfer = $this->executeAssetUpdate($sspAssetTransfer);
            $sspAssetCollectionResponseTransfer->addSspAsset($sspAssetTransfer);
        }

        $this->deleteBusinessUnitAssignments($sspAssetCollectionRequestTransfer, $sspAssetCollectionResponseTransfer);
        $this->createBusinessUnitAssignments($sspAssetCollectionRequestTransfer, $sspAssetCollectionResponseTransfer);

        return $sspAssetCollectionResponseTransfer;
    }

    protected function executeAssetCreation(
        SspAssetTransfer $sspAssetTransfer,
        SspAssetCollectionResponseTransfer $sspAssetCollectionResponseTransfer
    ): SspAssetCollectionResponseTransfer {
        $sspAssetTransfer
            ->setReference($this->sequenceNumberFacade->generate($this->config->getAssetSequenceNumberSettings()))
            ->setStatus($sspAssetTransfer->getStatus() ?: $this->config->getInitialAssetStatus());

        return $this->getTransactionHandler()->handleTransaction(function () use ($sspAssetTransfer, $sspAssetCollectionResponseTransfer) {
            $sspAssetOwnerCompanyBusinessUnitTransfer = $this->resolveIdCompanyBusinessUnitForCompanyBusinessUnitTransfer($sspAssetTransfer->getCompanyBusinessUnitOrFail());

            if (!$sspAssetOwnerCompanyBusinessUnitTransfer) {
                return $sspAssetCollectionResponseTransfer->addError(
                    (new ErrorTransfer())
                        ->setEntityIdentifier($sspAssetTransfer->getReferenceOrFail())
                        ->setMessage(static::MESSAGE_ASSET_BUSINESS_UNIT_NOT_FOUND),
                );
            }

            $sspAssetTransfer->setCompanyBusinessUnit($sspAssetOwnerCompanyBusinessUnitTransfer);

            $companyBusinessUnitIds = [];
            if ($sspAssetTransfer->getBusinessUnitAssignments()->count() > 0) {
                foreach ($sspAssetTransfer->getBusinessUnitAssignments() as $sspAssetAssignmentTransfer) {
                    if (!$sspAssetAssignmentTransfer->getCompanyBusinessUnit()) {
                        continue;
                    }

                    $companyBusinessUnitTransfer = $this->resolveIdCompanyBusinessUnitForCompanyBusinessUnitTransfer(
                        $sspAssetAssignmentTransfer->getCompanyBusinessUnit(),
                    );

                    if (!$companyBusinessUnitTransfer) {
                        return $sspAssetCollectionResponseTransfer->addError(
                            (new ErrorTransfer())
                                ->setEntityIdentifier($sspAssetTransfer->getReferenceOrFail())
                                ->setMessage(static::MESSAGE_ASSET_BUSINESS_UNIT_NOT_FOUND),
                        );
                    }

                    $companyBusinessUnitIds[] = $companyBusinessUnitTransfer->getIdCompanyBusinessUnitOrFail();
                }
            }

            $sspAssetTransfer = $this->fileSspAssetWriter->createFile($sspAssetTransfer);

            $sspAssetTransfer = $this->entityManager->createSspAsset($sspAssetTransfer);

            if ($companyBusinessUnitIds) {
                $this->entityManager->createAssetToCompanyBusinessUnitRelation(
                    $sspAssetTransfer->getIdSspAssetOrFail(),
                    $companyBusinessUnitIds,
                );
            }

            return $sspAssetCollectionResponseTransfer->addSspAsset($sspAssetTransfer);
        });
    }

    protected function executeAssetUpdate(SspAssetTransfer $sspAssetTransfer): SspAssetTransfer
    {
        return $this->getTransactionHandler()->handleTransaction(function () use ($sspAssetTransfer) {
            $sspAssetTransfer = $this->fileSspAssetWriter->updateFile($sspAssetTransfer);

            return $this->entityManager->updateSspAsset($sspAssetTransfer);
        });
    }

    protected function deleteBusinessUnitAssignments(
        SspAssetCollectionRequestTransfer $sspAssetCollectionRequestTransfer,
        SspAssetCollectionResponseTransfer $sspAssetCollectionResponseTransfer
    ): SspAssetCollectionResponseTransfer {
        $businessUnitToDeleteGroupedBySspAssetId = [];
        foreach ($sspAssetCollectionRequestTransfer->getBusinessUnitAssignmentsToDelete() as $sspAssetAssignmentTransfer) {
            $sspAssetTransfer = $sspAssetAssignmentTransfer->getSspAssetOrFail();
            $companyBusinessUnitTransfer = $sspAssetAssignmentTransfer->getCompanyBusinessUnit();

            if (!$companyBusinessUnitTransfer) {
                continue;
            }

            if ($companyBusinessUnitTransfer->getIdCompanyBusinessUnit() === $sspAssetTransfer->getCompanyBusinessUnit()?->getIdCompanyBusinessUnit()) {
                $sspAssetCollectionResponseTransfer->addError(
                    (new ErrorTransfer())->setMessage(static::MESSAGE_ASSET_SELF_ASSET_ASSIGMENT_DELETE),
                );

                continue;
            }

            if (!$this->sspAssetValidator->isAssetUpdateGranted($sspAssetTransfer, $sspAssetCollectionRequestTransfer->getCompanyUser())) {
                $sspAssetCollectionResponseTransfer->addError(
                    (new ErrorTransfer())
                        ->setEntityIdentifier($sspAssetTransfer->getReferenceOrFail())
                        ->setMessage(static::MESSAGE_ASSET_BUSINESS_UNIT_UNASSIGNMENT_DENIED),
                );

                continue;
            }

            $businessUnitToDeleteGroupedBySspAssetId[$sspAssetAssignmentTransfer->getSspAssetOrFail()->getIdSspAssetOrFail()][] = $companyBusinessUnitTransfer->getIdCompanyBusinessUnitOrFail();
        }

        foreach ($businessUnitToDeleteGroupedBySspAssetId as $idSspAsset => $companyBusinessUnitIds) {
            $this->entityManager->deleteAssetToCompanyBusinessUnitRelations($idSspAsset, $companyBusinessUnitIds);
        }

        return $sspAssetCollectionResponseTransfer;
    }

    protected function createBusinessUnitAssignments(
        SspAssetCollectionRequestTransfer $sspAssetCollectionRequestTransfer,
        SspAssetCollectionResponseTransfer $sspAssetCollectionResponseTransfer
    ): SspAssetCollectionResponseTransfer {
        $businessUnitToAddGroupedBySspAssetId = [];
        foreach ($sspAssetCollectionRequestTransfer->getBusinessUnitAssignmentsToAdd() as $sspAssetAssignmentTransfer) {
            $sspAssetTransfer = $sspAssetAssignmentTransfer->getSspAssetOrFail();
            $companyBusinessUnitTransfer = $sspAssetAssignmentTransfer->getCompanyBusinessUnit();

            if (!$companyBusinessUnitTransfer) {
                continue;
            }

            if (!$this->sspAssetValidator->isAssetUpdateGranted($sspAssetTransfer, $sspAssetCollectionRequestTransfer->getCompanyUser())) {
                $sspAssetCollectionResponseTransfer->addError(
                    (new ErrorTransfer())
                        ->setEntityIdentifier($sspAssetTransfer->getReferenceOrFail())
                        ->setMessage(static::MESSAGE_ASSET_BUSINESS_UNIT_ASSIGNMENT_DENIED),
                );

                continue;
            }

            $businessUnitToAddGroupedBySspAssetId[$sspAssetTransfer->getIdSspAssetOrFail()][] = $companyBusinessUnitTransfer->getIdCompanyBusinessUnitOrFail();
        }

        foreach ($businessUnitToAddGroupedBySspAssetId as $idSspAsset => $companyBusinessUnitIds) {
            $this->entityManager->createAssetToCompanyBusinessUnitRelation($idSspAsset, $companyBusinessUnitIds);
        }

        return $sspAssetCollectionResponseTransfer;
    }

    protected function resolveIdCompanyBusinessUnitForCompanyBusinessUnitTransfer(
        CompanyBusinessUnitTransfer $companyBusinessUnitTransfer
    ): ?CompanyBusinessUnitTransfer {
        if ($companyBusinessUnitTransfer->getIdCompanyBusinessUnit()) {
            return $companyBusinessUnitTransfer;
        }

        if (!$companyBusinessUnitTransfer->getUuid()) {
            return null;
        }

        if (isset($this->companyBusinessUnitTransferIndexedByUuid[$companyBusinessUnitTransfer->getUuid()])) {
            return $this->companyBusinessUnitTransferIndexedByUuid[$companyBusinessUnitTransfer->getUuid()];
        }

        $companyBusinessUnitResponseTransfer = $this->companyBusinessUnitFacade
            ->findCompanyBusinessUnitByUuid(
                (new CompanyBusinessUnitTransfer())->setUuid($companyBusinessUnitTransfer->getUuid()),
            );

        $this->companyBusinessUnitTransferIndexedByUuid[$companyBusinessUnitTransfer->getUuid()] = $companyBusinessUnitResponseTransfer->getCompanyBusinessUnitTransferOrFail();

        return $companyBusinessUnitResponseTransfer->getCompanyBusinessUnitTransferOrFail();
    }
}

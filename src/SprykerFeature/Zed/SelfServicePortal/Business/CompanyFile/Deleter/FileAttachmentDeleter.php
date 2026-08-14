<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Business\CompanyFile\Deleter;

use Generated\Shared\Transfer\CompanyBusinessUnitCollectionTransfer;
use Generated\Shared\Transfer\CompanyBusinessUnitTransfer;
use Generated\Shared\Transfer\FileAttachmentCollectionRequestTransfer;
use Generated\Shared\Transfer\FileAttachmentCollectionResponseTransfer;
use Generated\Shared\Transfer\FileAttachmentTransfer;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalEntityManagerInterface;
use SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalRepositoryInterface;

class FileAttachmentDeleter implements FileAttachmentDeleterInterface
{
    use TransactionTrait;

    public function __construct(
        protected SelfServicePortalRepositoryInterface $selfServicePortalRepository,
        protected SelfServicePortalEntityManagerInterface $entityManager
    ) {
    }

    public function deleteFileAttachmentCollection(
        FileAttachmentCollectionRequestTransfer $fileAttachmentCollectionRequestTransfer
    ): FileAttachmentCollectionResponseTransfer {
        $this->getTransactionHandler()->handleTransaction(function () use ($fileAttachmentCollectionRequestTransfer): void {
            if ($fileAttachmentCollectionRequestTransfer->getFileIdsToDeleteAttachments()) {
                $this->entityManager->deleteAllFileAttachmentCollection($fileAttachmentCollectionRequestTransfer->getFileIdsToDeleteAttachments());

                return;
            }

            foreach ($fileAttachmentCollectionRequestTransfer->getFileAttachmentsToDelete() as $fileAttachmentToDelete) {
                $this->entityManager->deleteFileAttachmentCollection(
                    $this->expandBusinessUnitCollectionWithCompanyBusinessUnits($fileAttachmentToDelete),
                );
            }
        });

        return new FileAttachmentCollectionResponseTransfer();
    }

    protected function expandBusinessUnitCollectionWithCompanyBusinessUnits(
        FileAttachmentTransfer $fileAttachmentTransfer
    ): FileAttachmentTransfer {
        $companyIds = [];
        foreach ($fileAttachmentTransfer->getCompanyCollection()?->getCompanies() ?? [] as $companyTransfer) {
            $companyIds[] = $companyTransfer->getIdCompanyOrFail();
        }

        if (!$companyIds) {
            return $fileAttachmentTransfer;
        }

        $companyBusinessUnitCollectionTransfer = $fileAttachmentTransfer->getBusinessUnitCollection()
            ?? new CompanyBusinessUnitCollectionTransfer();

        foreach ($this->selfServicePortalRepository->getBusinessUnitIdsForCompanies($companyIds) as $idCompanyBusinessUnit) {
            $companyBusinessUnitCollectionTransfer->addCompanyBusinessUnit(
                (new CompanyBusinessUnitTransfer())->setIdCompanyBusinessUnit($idCompanyBusinessUnit),
            );
        }

        return $fileAttachmentTransfer->setBusinessUnitCollection($companyBusinessUnitCollectionTransfer);
    }
}

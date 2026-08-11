<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Business\Inquiry\Validator;

use ArrayObject;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\FileTransfer;
use Generated\Shared\Transfer\FileUploadTransfer;
use Generated\Shared\Transfer\SspInquiryCollectionResponseTransfer;
use Generated\Shared\Transfer\SspInquiryTransfer;
use Spryker\Zed\Kernel\PermissionAwareTrait;
use SprykerFeature\Shared\SelfServicePortal\Plugin\Permission\CreateSspInquiryPermissionPlugin;
use SprykerFeature\Zed\SelfServicePortal\SelfServicePortalConfig;

class SspInquiryValidator implements SspInquiryValidatorInterface
{
    use PermissionAwareTrait;

    /**
     * @var string
     */
    protected const MESSAGE_INQUIRY_CREATION_ACCESS_DENIED = 'self_service_portal.inquiry.access.denied';

    public function __construct(protected SelfServicePortalConfig $selfServicePortalConfig)
    {
    }

    /**
     * @return \ArrayObject<int, \Generated\Shared\Transfer\ErrorTransfer>
     */
    public function validateSspInquiry(SspInquiryTransfer $sspInquiryTransfer): ArrayObject
    {
        $validationErrors = new ArrayObject();

        $this->validateCompanyUser($sspInquiryTransfer, $validationErrors);
        $this->validateType($sspInquiryTransfer, $validationErrors);
        $this->validateSubject($sspInquiryTransfer, $validationErrors);
        $this->validateDescription($sspInquiryTransfer, $validationErrors);
        $this->validateFiles($sspInquiryTransfer, $validationErrors);

        return $validationErrors;
    }

    public function validateRequestGrantedToCreateInquiry(
        SspInquiryCollectionResponseTransfer $sspInquiryCollectionResponseTransfer,
        ?CompanyUserTransfer $companyUserTransfer
    ): SspInquiryCollectionResponseTransfer {
        if (!$companyUserTransfer) {
            return $sspInquiryCollectionResponseTransfer;
        }

        if (
            !$this->can(
                CreateSspInquiryPermissionPlugin::KEY,
                $companyUserTransfer->getIdCompanyUserOrFail(),
            )
        ) {
            return $sspInquiryCollectionResponseTransfer->addError(
                (new ErrorTransfer())->setMessage(static::MESSAGE_INQUIRY_CREATION_ACCESS_DENIED),
            );
        }

        return $sspInquiryCollectionResponseTransfer;
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ErrorTransfer> $validationErrors
     */
    protected function validateCompanyUser(SspInquiryTransfer $sspInquiryTransfer, ArrayObject $validationErrors): void
    {
        if (!$sspInquiryTransfer->getCompanyUser() || !$sspInquiryTransfer->getCompanyUser()->getIdCompanyUser()) {
            $validationErrors->append(
                (new ErrorTransfer())->setMessage('self_service_portal.inquiry.validation.company_user.not_set'),
            );
        }
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ErrorTransfer> $validationErrors
     */
    protected function validateType(SspInquiryTransfer $sspInquiryTransfer, ArrayObject $validationErrors): void
    {
        if (!$sspInquiryTransfer->getType()) {
            $validationErrors->append(
                (new ErrorTransfer())->setMessage('self_service_portal.inquiry.validation.type.not_set'),
            );

            return;
        }

        $allSelectableSspInquiryTypes = array_merge(...array_values($this->selfServicePortalConfig->getSelectableSspInquiryTypes()));

        if (!in_array($sspInquiryTransfer->getType(), $allSelectableSspInquiryTypes)) {
            $validationErrors->append(
                (new ErrorTransfer())->setMessage('self_service_portal.inquiry.validation.type.invalid'),
            );
        }
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ErrorTransfer> $validationErrors
     */
    protected function validateSubject(SspInquiryTransfer $sspInquiryTransfer, ArrayObject $validationErrors): void
    {
        if (!$sspInquiryTransfer->getSubject()) {
            $validationErrors->append(
                (new ErrorTransfer())->setMessage('self_service_portal.inquiry.validation.subject.not_set'),
            );

            return;
        }

        if (mb_strlen($sspInquiryTransfer->getSubject()) > $this->selfServicePortalConfig->getSspInquirySubjectMaxLength()) {
            $validationErrors->append(
                (new ErrorTransfer())->setMessage('self_service_portal.inquiry.validation.subject.too_long'),
            );
        }
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ErrorTransfer> $validationErrors
     */
    protected function validateDescription(SspInquiryTransfer $sspInquiryTransfer, ArrayObject $validationErrors): void
    {
        if (!$sspInquiryTransfer->getDescription()) {
            $validationErrors->append(
                (new ErrorTransfer())->setMessage('self_service_portal.inquiry.validation.description.not_set'),
            );

            return;
        }

        if (mb_strlen($sspInquiryTransfer->getDescription()) > $this->selfServicePortalConfig->getSspInquiryDescriptionMaxLength()) {
            $validationErrors->append(
                (new ErrorTransfer())->setMessage('self_service_portal.inquiry.validation.description.too_long'),
            );
        }
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ErrorTransfer> $validationErrors
     */
    protected function validateFiles(SspInquiryTransfer $sspInquiryTransfer, ArrayObject $validationErrors): void
    {
        $fileUploads = array_map(fn (FileTransfer $fileTransfer) => $fileTransfer->getFileUploadOrFail(), $sspInquiryTransfer->getFiles()->getArrayCopy());

        if (count($fileUploads) === 0) {
            return;
        }

        $this->validateFileCount($fileUploads, $validationErrors);
        $this->validateFileTotalSize($fileUploads, $validationErrors);
        $this->validateFileIndividualSizes($fileUploads, $validationErrors);
        $this->validateFileTypes($fileUploads, $validationErrors);
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\FileUploadTransfer> $fileUploads
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ErrorTransfer> $validationErrors
     */
    protected function validateFileCount(array $fileUploads, ArrayObject $validationErrors): void
    {
        $maxFileCount = $this->selfServicePortalConfig->getSspInquiryFileMaxCount();

        if (count($fileUploads) > $maxFileCount) {
            $validationErrors->append(
                (new ErrorTransfer())->setMessage('self_service_portal.inquiry.error.file.count.invalid'),
            );
        }
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\FileUploadTransfer> $fileUploads
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ErrorTransfer> $validationErrors
     */
    protected function validateFileTotalSize(array $fileUploads, ArrayObject $validationErrors): void
    {
        $totalMaxSize = $this->normalizeBinaryFormat($this->selfServicePortalConfig->getSspInquiryFilesMaxSize());
        $totalSize = 0;

        foreach ($fileUploads as $fileUpload) {
            $totalSize += $fileUpload->getSize();
        }

        if ($totalSize > $totalMaxSize) {
            $validationErrors->append(
                (new ErrorTransfer())->setMessage('self_service_portal.inquiry.error.file.size.invalid'),
            );
        }
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\FileUploadTransfer> $fileUploads
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ErrorTransfer> $validationErrors
     */
    protected function validateFileIndividualSizes(array $fileUploads, ArrayObject $validationErrors): void
    {
        $maxFileSize = $this->normalizeBinaryFormat($this->selfServicePortalConfig->getSspInquiryFileMaxSize());

        foreach ($fileUploads as $fileUpload) {
            if ($fileUpload->getSize() > $maxFileSize) {
                $validationErrors->append(
                    (new ErrorTransfer())
                        ->setMessage('self_service_portal.inquiry.error.file.individual_size.invalid')
                        ->setParameters([
                            'name' => $fileUpload->getClientOriginalName(),
                        ]),
                );
            }
        }
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\FileUploadTransfer> $fileUploads
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ErrorTransfer> $validationErrors
     */
    protected function validateFileTypes(array $fileUploads, ArrayObject $validationErrors): void
    {
        $allowedMimeTypes = $this->selfServicePortalConfig->getSspInquiryAllowedFileMimeTypes();
        $allowedExtensions = $this->selfServicePortalConfig->getSspInquiryAllowedFileExtensions();

        foreach ($fileUploads as $fileUpload) {
            $this->validateFileMimeType($fileUpload, $allowedMimeTypes, $validationErrors);
            $this->validateFileExtension($fileUpload, $allowedExtensions, $validationErrors);
        }
    }

    /**
     * @param array<string> $allowedMimeTypes
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ErrorTransfer> $validationErrors
     */
    protected function validateFileMimeType(FileUploadTransfer $fileUpload, array $allowedMimeTypes, ArrayObject $validationErrors): void
    {
        if (!in_array($fileUpload->getMimeTypeName(), $allowedMimeTypes)) {
            $validationErrors->append(
                (new ErrorTransfer())
                    ->setMessage('self_service_portal.inquiry.file.mime_type.error')
                    ->setParameters([
                        'name' => $fileUpload->getClientOriginalName(),
                    ]),
            );
        }
    }

    /**
     * @param array<string> $allowedExtensions
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ErrorTransfer> $validationErrors
     */
    protected function validateFileExtension(FileUploadTransfer $fileUpload, array $allowedExtensions, ArrayObject $validationErrors): void
    {
        $extension = strtolower($fileUpload->getClientOriginalExtensionOrFail());

        if (!in_array($extension, $allowedExtensions)) {
            $validationErrors->append(
                (new ErrorTransfer())
                    ->setMessage('self_service_portal.inquiry.file.extension.error')
                    ->setParameters([
                        'name' => $fileUpload->getClientOriginalName(),
                    ]),
            );
        }
    }

    protected function normalizeBinaryFormat(int|string $size): int
    {
        $factors = [
            'k' => 1000,
            'ki' => 1 << 10,
            'm' => 1000 * 1000,
            'mi' => 1 << 20,
            'g' => 1000 * 1000 * 1000,
            'gi' => 1 << 30,
        ];

        if (ctype_digit((string)$size)) {
            return (int)$size;
        }

        if (preg_match('/^(\d++)(' . implode('|', array_keys($factors)) . ')$/i', (string)$size, $matches)) {
            return (int)$matches[1] * $factors[strtolower($matches[2])];
        }

        return (int)$size;
    }
}

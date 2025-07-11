<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\SelfServicePortal\Controller;

use Generated\Shared\Transfer\FileAttachmentConditionsTransfer;
use Generated\Shared\Transfer\FileAttachmentCriteriaTransfer;
use Generated\Shared\Transfer\FileAttachmentSearchConditionsTransfer;
use Generated\Shared\Transfer\FileAttachmentTransfer;
use Spryker\Yves\Kernel\PermissionAwareTrait;
use SprykerFeature\Shared\SelfServicePortal\Plugin\Permission\DownloadCompanyFilesPermissionPlugin;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @method \SprykerFeature\Client\SelfServicePortal\SelfServicePortalClientInterface getClient()
 * @method \SprykerFeature\Yves\SelfServicePortal\SelfServicePortalFactory getFactory()
 * @method \SprykerFeature\Yves\SelfServicePortal\SelfServicePortalConfig getConfig()
 */
class DownloadCompanyFileController extends AbstractController
{
    use PermissionAwareTrait;

    /**
     * @var string
     */
    protected const PARAM_ID_FILE = 'id-file';

    /**
     * @var string
     */
    protected const HEADER_CONTENT_TYPE = 'Content-Type';

    /**
     * @var string
     */
    protected const HEADER_CONTENT_DISPOSITION = 'Content-Disposition';

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function indexAction(Request $request): StreamedResponse
    {
        if (!$request->query->has(static::PARAM_ID_FILE)) {
            throw new BadRequestHttpException();
        }

        if (!$this->can(DownloadCompanyFilesPermissionPlugin::KEY)) {
            throw new AccessDeniedHttpException();
        }

        $fileAttachmentCriteriaTransfer = $this->createFileAttachmentCriteriaTransfer($request);

        $fileAttachmentCollectionTransfer = $this->getClient()
            ->getFileAttachmentCollection($fileAttachmentCriteriaTransfer);

        if (!$fileAttachmentCollectionTransfer->getFileAttachments()->count()) {
            throw new NotFoundHttpException();
        }

        return $this->createDownloadResponse($fileAttachmentCollectionTransfer->getFileAttachments()->offsetGet(0));
    }

    /**
     * @param \Generated\Shared\Transfer\FileAttachmentTransfer $fileAttachmentTransfer
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    protected function createDownloadResponse(FileAttachmentTransfer $fileAttachmentTransfer): StreamedResponse
    {
        /** @var \Generated\Shared\Transfer\FileInfoTransfer $fileInfoTransfer */
        $fileInfoTransfer = $fileAttachmentTransfer->getFileOrFail()->getFileInfo()->offsetGet(0);

        $fileStream = $this->getFactory()
            ->getFileManagerService()
            ->readStream($fileInfoTransfer->getStorageFileNameOrFail(), $fileInfoTransfer->getStorageNameOrFail());

        $chunkSize = max(1, $this->getFactory()->getConfig()->getCompanyFileDownloadChunkSize());

        $response = new StreamedResponse(function () use ($fileStream, $chunkSize): void {
            while (!feof($fileStream)) {
                echo fread($fileStream, $chunkSize);
                flush();
            }
            fclose($fileStream);
        });

        $fileName = basename($fileAttachmentTransfer->getFileOrFail()->getFileNameOrFail());
        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName);

        $response->headers->set(static::HEADER_CONTENT_DISPOSITION, $disposition);
        $response->headers->set(static::HEADER_CONTENT_TYPE, $fileInfoTransfer->getTypeOrFail());

        return $response;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Generated\Shared\Transfer\FileAttachmentCriteriaTransfer
     */
    protected function createFileAttachmentCriteriaTransfer(Request $request): FileAttachmentCriteriaTransfer
    {
        $uuidFile = (string)$request->query->get(static::PARAM_ID_FILE);

        return (new FileAttachmentCriteriaTransfer())
            ->setCompanyUser($this->getFactory()->createCompanyUserReader()->getCurrentCompanyUser())
            ->setFileAttachmentSearchConditions(new FileAttachmentSearchConditionsTransfer())
            ->setFileAttachmentConditions(
                (new FileAttachmentConditionsTransfer())->setUuids([$uuidFile]),
            )
            ->setWithCompanyRelation(true)
            ->setWithBusinessUnitRelation(true)
            ->setWithCompanyUserRelation(true)
            ->setWithSspAssetRelation(true);
    }
}

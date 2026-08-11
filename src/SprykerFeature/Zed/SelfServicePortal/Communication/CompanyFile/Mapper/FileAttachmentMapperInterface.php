<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Communication\CompanyFile\Mapper;

use Generated\Shared\Transfer\FileAttachmentCollectionRequestTransfer;
use Generated\Shared\Transfer\FileAttachmentTransfer;

interface FileAttachmentMapperInterface
{
    /**
     * @param array<string, mixed> $formData
     */
    public function mapFormDataToFileAttachmentCollectionTransfer(
        FileAttachmentTransfer $fileAttachmentTransfer,
        array $formData,
        int $idFile
    ): FileAttachmentCollectionRequestTransfer;

    /**
     * @return array<string, array<int>>
     */
    public function mapFileAttachmentCollectionTransferToFormData(FileAttachmentTransfer $fileAttachmentTransfer): array;
}

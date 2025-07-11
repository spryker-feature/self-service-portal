<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\SelfServicePortal\CompanyFile\Form\Handler;

use Generated\Shared\Transfer\FileAttachmentCollectionTransfer;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

interface FileSearchFilterFormHandlerInterface
{
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\Form\FormInterface $fileSearchFilterForm
     *
     * @return \Generated\Shared\Transfer\FileAttachmentCollectionTransfer
     */
    public function handleSearchFormSubmit(
        Request $request,
        FormInterface $fileSearchFilterForm
    ): FileAttachmentCollectionTransfer;
}

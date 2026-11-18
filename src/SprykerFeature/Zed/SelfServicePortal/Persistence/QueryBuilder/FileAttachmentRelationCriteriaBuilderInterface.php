<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\SelfServicePortal\Persistence\QueryBuilder;

use Generated\Shared\Transfer\FileAttachmentConditionsTransfer;
use Generated\Shared\Transfer\FileAttachmentCriteriaTransfer;
use Orm\Zed\FileManager\Persistence\SpyFileQuery;
use Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion;

interface FileAttachmentRelationCriteriaBuilderInterface
{
    public function getBusinessUnitFileCriteria(
        SpyFileQuery $fileQuery,
        FileAttachmentConditionsTransfer $fileAttachmentConditionsTransfer
    ): ?AbstractCriterion;

    public function getCompanyUserFileCriteria(
        SpyFileQuery $fileQuery,
        FileAttachmentConditionsTransfer $fileAttachmentConditionsTransfer
    ): ?AbstractCriterion;

    public function getSspAssetFileCriteria(SpyFileQuery $fileQuery, FileAttachmentCriteriaTransfer $criteriaTransfer): ?AbstractCriterion;

    public function getSspModelToFileCriteria(
        SpyFileQuery $fileQuery,
        FileAttachmentConditionsTransfer $fileAttachmentConditionsTransfer
    ): ?AbstractCriterion;
}

<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\SelfServicePortal\Persistence\QueryBuilder;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\FileAttachmentConditionsTransfer;
use Orm\Zed\FileManager\Persistence\SpyFileQuery;
use SprykerFeature\Zed\SelfServicePortal\Persistence\QueryBuilder\FileAttachmentRelationCriteriaBuilder;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group SelfServicePortal
 * @group Persistence
 * @group QueryBuilder
 * @group FileAttachmentRelationCriteriaBuilderTest
 */
class FileAttachmentRelationCriteriaBuilderTest extends Unit
{
    public function testGetSspModelToFileCriteriaReturnsNullWhenNoModelIdsGiven(): void
    {
        // Act
        $criterion = $this->createBuilder()->getSspModelToFileCriteria(SpyFileQuery::create(), new FileAttachmentConditionsTransfer());

        // Assert
        $this->assertNull($criterion);
    }

    public function testGetSspModelToFileCriteriaReturnsCriterionWhenModelIdsGiven(): void
    {
        // Arrange
        $conditions = (new FileAttachmentConditionsTransfer())->setSspModelIds([1, 2]);

        // Act
        $criterion = $this->createBuilder()->getSspModelToFileCriteria(SpyFileQuery::create(), $conditions);

        // Assert
        $this->assertNotNull($criterion);
    }

    public function testGetBusinessUnitFileCriteriaReturnsNullWhenNoConditionsGiven(): void
    {
        // Act
        $criterion = $this->createBuilder()->getBusinessUnitFileCriteria(SpyFileQuery::create(), new FileAttachmentConditionsTransfer());

        // Assert
        $this->assertNull($criterion);
    }

    public function testGetBusinessUnitFileCriteriaReturnsCriterionWhenCompanyIdsGiven(): void
    {
        // Arrange
        $conditions = (new FileAttachmentConditionsTransfer())->setCompanyIds([1]);

        // Act
        $criterion = $this->createBuilder()->getBusinessUnitFileCriteria(SpyFileQuery::create(), $conditions);

        // Assert
        $this->assertNotNull($criterion);
    }

    public function testGetCompanyUserFileCriteriaReturnsNullWhenNoConditionsGiven(): void
    {
        // Act
        $criterion = $this->createBuilder()->getCompanyUserFileCriteria(SpyFileQuery::create(), new FileAttachmentConditionsTransfer());

        // Assert
        $this->assertNull($criterion);
    }

    protected function createBuilder(): FileAttachmentRelationCriteriaBuilder
    {
        return new FileAttachmentRelationCriteriaBuilder();
    }
}

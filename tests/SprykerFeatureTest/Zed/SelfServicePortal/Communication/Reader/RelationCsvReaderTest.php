<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeatureTest\Zed\SelfServicePortal\Communication\Reader;

use Codeception\Test\Unit;
use org\bovigo\vfs\vfsStream;
use SprykerFeature\Zed\SelfServicePortal\Communication\Reader\RelationCsvReader;
use SprykerFeature\Zed\SelfServicePortal\Communication\Reader\RelationCsvReaderInterface;
use SprykerFeatureTest\Zed\SelfServicePortal\SelfServicePortalCommunicationTester;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group SelfServicePortal
 * @group Communication
 * @group Reader
 * @group RelationCsvReaderTest
 * Add your own group annotations below this line
 */
class RelationCsvReaderTest extends Unit
{
    protected const string VIRTUAL_ROOT_DIRECTORY = 'root';

    protected const string CSV_FILE_NAME = 'relations.csv';

    protected SelfServicePortalCommunicationTester $tester;

    public function testGivenARelationCsvWhenReadRelationsThenTheFirstColumnIsReturnedAsIdentifiersToBeAttached(): void
    {
        // Arrange
        $uploadedFile = $this->createUploadedCsvFile("To be attached,To be unattached\nASSET-1,ASSET-3\nASSET-2,ASSET-4\n");

        // Act
        $relations = $this->createRelationCsvReader()->readRelations($uploadedFile);

        // Assert
        $this->assertSame(['ASSET-1', 'ASSET-2'], $relations[RelationCsvReaderInterface::KEY_ENTITY_IDENTIFIERS_TO_BE_ATTACHED]);
    }

    public function testGivenARelationCsvWhenReadRelationsThenTheSecondColumnIsReturnedAsIdentifiersToBeUnattached(): void
    {
        // Arrange
        $uploadedFile = $this->createUploadedCsvFile("To be attached,To be unattached\nASSET-1,ASSET-3\nASSET-2,ASSET-4\n");

        // Act
        $relations = $this->createRelationCsvReader()->readRelations($uploadedFile);

        // Assert
        $this->assertSame(['ASSET-3', 'ASSET-4'], $relations[RelationCsvReaderInterface::KEY_ENTITY_IDENTIFIERS_TO_BE_UNATTACHED]);
    }

    public function testGivenIdentifiersWithSurroundingWhitespaceWhenReadRelationsThenTheIdentifiersAreTrimmed(): void
    {
        // Arrange
        $uploadedFile = $this->createUploadedCsvFile("To be attached,To be unattached\n  12  ,  34  \n");

        // Act
        $relations = $this->createRelationCsvReader()->readRelations($uploadedFile);

        // Assert
        $this->assertSame(['12'], $relations[RelationCsvReaderInterface::KEY_ENTITY_IDENTIFIERS_TO_BE_ATTACHED]);
        $this->assertSame(['34'], $relations[RelationCsvReaderInterface::KEY_ENTITY_IDENTIFIERS_TO_BE_UNATTACHED]);
    }

    public function testGivenBlankCellsAndEmptyLinesWhenReadRelationsThenOnlyFilledCellsAreReturned(): void
    {
        // Arrange
        $uploadedFile = $this->createUploadedCsvFile("To be attached,To be unattached\n1,\n , 2\n\n3\n\n");

        // Act
        $relations = $this->createRelationCsvReader()->readRelations($uploadedFile);

        // Assert
        $this->assertSame(['1', '3'], $relations[RelationCsvReaderInterface::KEY_ENTITY_IDENTIFIERS_TO_BE_ATTACHED]);
        $this->assertSame(['2'], $relations[RelationCsvReaderInterface::KEY_ENTITY_IDENTIFIERS_TO_BE_UNATTACHED]);
    }

    public function testGivenACsvWithTheHeaderRowOnlyWhenReadRelationsThenNoIdentifiersAreReturned(): void
    {
        // Arrange
        $uploadedFile = $this->createUploadedCsvFile("To be attached,To be unattached\n");

        // Act
        $relations = $this->createRelationCsvReader()->readRelations($uploadedFile);

        // Assert
        $this->assertSame(
            [
                RelationCsvReaderInterface::KEY_ENTITY_IDENTIFIERS_TO_BE_ATTACHED => [],
                RelationCsvReaderInterface::KEY_ENTITY_IDENTIFIERS_TO_BE_UNATTACHED => [],
            ],
            $relations,
        );
    }

    protected function createUploadedCsvFile(string $csvContent): UploadedFile
    {
        $virtualDirectory = vfsStream::setup(static::VIRTUAL_ROOT_DIRECTORY, null, [
            static::CSV_FILE_NAME => $csvContent,
        ]);

        return new UploadedFile(
            sprintf('%s/%s', $virtualDirectory->url(), static::CSV_FILE_NAME),
            static::CSV_FILE_NAME,
            'text/csv',
            null,
            true,
        );
    }

    protected function createRelationCsvReader(): RelationCsvReaderInterface
    {
        return new RelationCsvReader($this->tester->getLocator()->utilCsv()->service());
    }
}

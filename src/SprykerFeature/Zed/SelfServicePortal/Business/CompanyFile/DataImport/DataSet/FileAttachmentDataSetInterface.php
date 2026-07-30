<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Business\CompanyFile\DataImport\DataSet;

interface FileAttachmentDataSetInterface
{
    public const string COLUMN_FILE_REFERENCE = 'file_reference';

    public const string COLUMN_ENTITY_TYPE = 'entity_type';

    public const string COLUMN_ENTITY_KEY = 'entity_key';

    public const string ID_FILE = 'id_file';

    public const string ID_ENTITY = 'id_entity';
}

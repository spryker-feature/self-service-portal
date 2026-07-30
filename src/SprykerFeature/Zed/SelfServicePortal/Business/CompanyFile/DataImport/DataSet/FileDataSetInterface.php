<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Business\CompanyFile\DataImport\DataSet;

interface FileDataSetInterface
{
    public const string COLUMN_FILE_REFERENCE = 'file_reference';

    public const string COLUMN_FILE_NAME = 'file_name';

    public const string COLUMN_PATH = 'path';

    public const string COLUMN_MIME_TYPE = 'mime_type';

    public const string COLUMN_EXTENSION = 'extension';

    public const string CONTENT = 'content';

    public const string ID_FILE = 'id_file';
}

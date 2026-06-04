<?php

declare(strict_types=1);

namespace Mheads\Yii\Filestorage\ActiveRecord\Tests\Driver\Sqlite;

use Mheads\Yii\Filestorage\ActiveRecord\Tests\Driver\Common\FileUploadTestCase;
use Mheads\Yii\Filestorage\ActiveRecord\Tests\Support\SqliteHelper;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * @internal
 */
#[AllowMockObjectsWithoutExpectations]
final class FileUploadTest extends FileUploadTestCase
{
	protected static function createConnection(): ConnectionInterface
	{
		return (new SqliteHelper())->createConnection();
	}
}

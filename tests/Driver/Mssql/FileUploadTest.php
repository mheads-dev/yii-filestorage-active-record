<?php

declare(strict_types=1);

namespace Mheads\Yii\Filestorage\ActiveRecord\Tests\Driver\Mssql;

use Mheads\Yii\Filestorage\ActiveRecord\Tests\Driver\Common\FileUploadTestCase;
use Mheads\Yii\Filestorage\ActiveRecord\Tests\Support\MssqlHelper;
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
		return (new MssqlHelper())->createConnection();
	}
}

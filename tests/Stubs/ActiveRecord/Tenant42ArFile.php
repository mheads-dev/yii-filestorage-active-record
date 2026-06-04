<?php

declare(strict_types=1);

namespace Mheads\Yii\Filestorage\ActiveRecord\Tests\Stubs\ActiveRecord;

use Mheads\Yii\Filestorage\ActiveRecord\ArFile;
use Mheads\Yii\Filestorage\StorageInterface;
use Mheads\Yii\Filestorage\StorageProvider;

final class Tenant42ArFile extends ArFile
{
	public static function storage(): StorageInterface
	{
		return StorageProvider::get('tenant-42');
	}
}

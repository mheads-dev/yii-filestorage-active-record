<?php

declare(strict_types=1);

namespace App\Examples\Support;

use Mheads\Yii\Filestorage\ActiveRecord\ArFile;
use Mheads\Yii\Filestorage\StorageInterface;
use Mheads\Yii\Filestorage\StorageProvider;

final class TenantArFile extends ArFile
{
	public static function storage(): StorageInterface
	{
		return StorageProvider::get('tenant-42');
	}
}

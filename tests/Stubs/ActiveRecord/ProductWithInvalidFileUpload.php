<?php

declare(strict_types=1);

namespace Mheads\Yii\Filestorage\ActiveRecord\Tests\Stubs\ActiveRecord;

use Mheads\Yii\Filestorage\ActiveRecord\ArFile;
use Mheads\Yii\Filestorage\ActiveRecord\EventHandler\FileUpload;
use Yiisoft\ActiveRecord\ActiveRecord;
use Yiisoft\ActiveRecord\Trait\EventsTrait;

#[FileUpload('products', 'upload', true, ArFile::class, 'picture_id', 'manual_id')]
final class ProductWithInvalidFileUpload extends ActiveRecord
{
	use EventsTrait;

	public ?int $id;
	public string $name = '';
	public int|string|null $picture_id = null;
	public int|string|null $manual_id = null;

	public function tableName(): string
	{
		return Product::TABLE;
	}
}

<?php

declare(strict_types=1);

namespace Mheads\Yii\Filestorage\ActiveRecord\Tests\Stubs\ActiveRecord;

use Mheads\Yii\Filestorage\ActiveRecord\ArFile;
use Mheads\Yii\Filestorage\ActiveRecord\Contract\PendingUploadedFileOwnerInterface;
use Mheads\Yii\Filestorage\ActiveRecord\Contract\PendingUploadedFileOwnerTrait;
use Mheads\Yii\Filestorage\ActiveRecord\EventHandler\FileUpload;
use Psr\Http\Message\UploadedFileInterface;
use Yiisoft\ActiveRecord\ActiveQuery;
use Yiisoft\ActiveRecord\ActiveQueryInterface;
use Yiisoft\ActiveRecord\ActiveRecord;
use Yiisoft\ActiveRecord\Trait\EventsTrait;

final class ProductWithoutAutoCleaning extends ActiveRecord implements PendingUploadedFileOwnerInterface
{
	use EventsTrait;
	use PendingUploadedFileOwnerTrait;

	public ?int $id;
	public string $name = '';
	#[FileUpload(groupName: 'products', storeName: 'upload', fileClass: ArFile::class, enableAutoCleaning: false)]
	public int|string|null $picture_id = null;
	public int|string|null $manual_id = null;

	public function tableName(): string
	{
		return Product::TABLE;
	}

	public function setPicture(?UploadedFileInterface $uploadedFile): void
	{
		$this->queuePendingUploadedFile('picture_id', $uploadedFile);
		if($uploadedFile === null)
		{
			$this->set('picture_id', null);
		}
	}

	public function getPicture(): ?ArFile
	{
		/** @var ArFile|null */
		return $this->relation('picture');
	}

	public function getPictureQuery(): ActiveQuery
	{
		return $this->hasOne(ArFile::class, ['id' => 'picture_id']);
	}

	public function relationQuery(string $name): ActiveQueryInterface
	{
		return match ($name)
		{
			'picture' => $this->getPictureQuery(),
			default   => parent::relationQuery($name),
		};
	}
}

<?php

declare(strict_types=1);

namespace App\Examples\Support;

use Mheads\Yii\Filestorage\ActiveRecord\Contract\PendingUploadedFileOwnerInterface;
use Mheads\Yii\Filestorage\ActiveRecord\Contract\PendingUploadedFileOwnerTrait;
use Mheads\Yii\Filestorage\ActiveRecord\EventHandler\FileUpload;
use Psr\Http\Message\UploadedFileInterface;
use Yiisoft\ActiveRecord\ActiveQuery;
use Yiisoft\ActiveRecord\ActiveQueryInterface;
use Yiisoft\ActiveRecord\ActiveRecord;
use Yiisoft\ActiveRecord\Trait\EventsTrait;

final class ProductWithTenantFile extends ActiveRecord implements PendingUploadedFileOwnerInterface
{
	use EventsTrait;
	use PendingUploadedFileOwnerTrait;

	public const string TABLE = 'product';

	public int|string|null $id = null;
	public string $name = '';
	#[FileUpload(groupName: 'products', storeName: 'upload', fileClass: TenantArFile::class)]
	public int|string|null $picture_id = null;
	public int|string|null $manual_id = null;

	public function tableName(): string
	{
		return self::TABLE;
	}

	public function setPicture(?UploadedFileInterface $uploadedFile): void
	{
		$this->queuePendingUploadedFile('picture_id', $uploadedFile);
		if($uploadedFile === null)
		{
			$this->set('picture_id', null);
		}
	}

	public function getPicture(): ?TenantArFile
	{
		/** @var TenantArFile|null */
		return $this->relation('picture');
	}

	public function getPictureQuery(): ActiveQuery
	{
		return $this->hasOne(TenantArFile::class, ['id' => 'picture_id']);
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

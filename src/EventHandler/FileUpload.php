<?php

declare(strict_types=1);

namespace Mheads\Yii\Filestorage\ActiveRecord\EventHandler;

use ArgumentCountError;
use Attribute;
use Mheads\Yii\Filestorage\ActiveRecord\ArFile;
use Mheads\Yii\Filestorage\ActiveRecord\Contract\PendingUploadedFileOwnerInterface;
use Mheads\Yii\Filestorage\Entity\FileInterface;
use Mheads\Yii\Filestorage\Exception\AddException;
use Mheads\Yii\Filestorage\Exception\InvalidConfigException;
use Mheads\Yii\Filestorage\StorageInterface;
use Mheads\Yii\Filestorage\StorageProvider;
use Override;
use SplObjectStorage;
use Throwable;
use Yiisoft\ActiveRecord\ActiveRecordInterface;
use Yiisoft\ActiveRecord\Event\AfterDelete;
use Yiisoft\ActiveRecord\Event\AfterSave;
use Yiisoft\ActiveRecord\Event\BeforeSave;
use Yiisoft\ActiveRecord\Event\Handler\AttributeHandlerProvider;

use function array_is_list;
use function count;
use function in_array;
use function is_a;
use function is_bool;
use function is_callable;
use function is_float;
use function is_int;
use function is_numeric;
use function is_string;

/**
 * Upload handler for AR-model file id attributes (`picture_id`, `avatar_id`, ...).
 *
 * Usage on Product:
 *
 * #[FileUpload(groupName: 'products')]
 * public ?int $picture_id = null;
 *
 * public function setPicture(?UploadedFileInterface $file): void
 * {
 *     $this->queuePendingUploadedFile('picture_id', $file);
 *     if($file === null)
 *     {
 *         $this->set('picture_id', null);
 *     }
 * }
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class FileUpload extends AttributeHandlerProvider
{
	/** @var callable|string|null */
	private readonly mixed $groupName;
	/** @var callable|string|null */
	private readonly mixed $storeName;
	/** @var class-string<FileInterface> */
	private readonly string $fileClass;

	private SplObjectStorage $pendingAddedFileIds;
	private SplObjectStorage $pendingCleanupFileIds;

	/**
	 * @throws InvalidConfigException
	 */
	public function __construct(
		callable|string|null $groupName = null,
		callable|string|null $storeName = null,
		private readonly bool $enableAutoCleaning = true,
		string $fileClass = ArFile::class,
		string ...$propertyNames,
	) {
		if(!is_a($fileClass, FileInterface::class, true))
		{
			throw new InvalidConfigException(
				'FileUpload::$fileClass must implement ' . FileInterface::class . '.',
			);
		}

		$this->groupName = $groupName;
		$this->storeName = $storeName;
		$this->fileClass = $fileClass;

		parent::__construct(...$propertyNames);
		$this->pendingAddedFileIds = new SplObjectStorage();
		$this->pendingCleanupFileIds = new SplObjectStorage();
		register_shutdown_function($this->cleanupOnShutdown(...));
	}

	#[Override]
	public function getEventHandlers(): array
	{
		return [
			BeforeSave::class  => $this->beforeSave(...),
			AfterSave::class   => $this->afterSave(...),
			AfterDelete::class => $this->afterDelete(...),
		];
	}

	/**
	 * @throws InvalidConfigException
	 * @throws AddException
	 */
	private function beforeSave(BeforeSave $event): void
	{
		$model = $event->model;
		$fileIdAttribute = $this->resolveFileIdAttribute();

		$oldFileId = $model->isNew()
			? null
			: $this->toNullableInt($model->oldValue($fileIdAttribute));
		$newFileId = $this->toNullableInt($model->get($fileIdAttribute));

		if($model instanceof PendingUploadedFileOwnerInterface)
		{
			/** @var ActiveRecordInterface&PendingUploadedFileOwnerInterface $model */
			$uploadedFile = $model->pullPendingUploadedFile($fileIdAttribute);
			if($uploadedFile !== null)
			{
				$groupName = $this->resolveOptionString($this->groupName, $model, $fileIdAttribute);
				$storeName = $this->resolveOptionString($this->storeName, $model, $fileIdAttribute);

				$file = $this->resolveStorage($model, $fileIdAttribute)->add(
					$uploadedFile,
					$groupName,
					$storeName,
				);
				$newFileId = $file->getId();
				$model->set($fileIdAttribute, $newFileId);
				$this->pendingAddedFileIds[$model] = $newFileId;

				if($event->properties !== null)
				{
					if(array_is_list($event->properties))
					{
						if(!in_array($fileIdAttribute, $event->properties, true))
						{
							$event->properties[] = $fileIdAttribute;
						}
					}
					else
					{
						$event->properties[$fileIdAttribute] = $newFileId;
					}
				}
			}
		}

		if($this->enableAutoCleaning && $oldFileId !== null && $oldFileId !== $newFileId)
		{
			$this->pendingCleanupFileIds[$model] = $oldFileId;
		}
	}

	private function afterSave(AfterSave $event): void
	{
		$model = $event->model;

		if($this->pendingAddedFileIds->offsetExists($model))
		{
			$this->pendingAddedFileIds->offsetUnset($model);
		}

		if($this->enableAutoCleaning && $this->pendingCleanupFileIds->offsetExists($model))
		{
			$this->removeFileByIdSilently((int)$this->pendingCleanupFileIds[$model], $model);
			$this->pendingCleanupFileIds->offsetUnset($model);
		}
	}

	/**
	 * @throws InvalidConfigException
	 */
	private function afterDelete(AfterDelete $event): void
	{
		if(!$this->enableAutoCleaning || $event->count <= 0)
		{
			return;
		}

		$fileIdAttribute = $this->resolveFileIdAttribute();
		$fileId = $this->toNullableInt($event->model->get($fileIdAttribute))
			?? $this->toNullableInt($event->model->oldValue($fileIdAttribute));
		if($fileId !== null)
		{
			$this->removeFileByIdSilently($fileId, $event->model);
		}
	}

	private function cleanupOnShutdown(): void
	{
		foreach($this->pendingAddedFileIds as $model)
		{
			/** @var ActiveRecordInterface $model */
			$this->removeFileByIdSilently((int)$this->pendingAddedFileIds[$model], $model);
		}
	}

	/**
	 * @throws InvalidConfigException
	 */
	private function resolveFileIdAttribute(): string
	{
		$propertyNames = $this->getPropertyNames();
		if($propertyNames === [])
		{
			throw new InvalidConfigException(
				'FileUpload attribute requires file id property name. Use it on target property or pass property name.',
			);
		}
		if(count($propertyNames) > 1)
		{
			throw new InvalidConfigException('FileUpload attribute supports exactly one file id property.');
		}

		return $propertyNames[0];
	}

	private function removeFileByIdSilently(int $fileId, ActiveRecordInterface $model): void
	{
		try
		{
			$this->resolveStorage($model, $this->resolveFileIdAttribute())->removeById($fileId);
		}
		catch(Throwable)
		{
		}
	}

	private function resolveStorage(ActiveRecordInterface $model, string $fileIdAttribute): StorageInterface
	{
		$storageName = $this->resolveStorageName();
		return StorageProvider::get($storageName);
	}

	private function resolveStorageName(): string
	{
		$storageName = $this->resolveStorageNameFromFileClass();
		if($storageName !== null)
		{
			return $storageName;
		}

		return StorageProvider::DEFAULT;
	}

	private function resolveStorageNameFromFileClass(): ?string
	{
		$fileClass = $this->fileClass;

		try
		{
			$storage = $fileClass::storage();

			foreach(StorageProvider::all() as $name => $candidate)
			{
				if($candidate === $storage)
				{
					return $name;
				}
			}
		}
		catch(Throwable)
		{
		}

		return null;
	}

	private function resolveOptionString(
		mixed $value,
		ActiveRecordInterface $model,
		string $fileIdAttribute,
	): ?string {
		if($value === null)
		{
			return null;
		}

		if(is_string($value))
		{
			return $value;
		}
		if(!is_callable($value))
		{
			return null;
		}

		$result = $this->invokeResolver($value, $model, $fileIdAttribute);
		return is_string($result) ? $result : null;
	}

	private function invokeResolver(callable $resolver, ActiveRecordInterface $model, string $fileIdAttribute): mixed
	{
		try
		{
			return $resolver($model, $fileIdAttribute);
		}
		catch(ArgumentCountError)
		{
			try
			{
				return $resolver($model);
			}
			catch(ArgumentCountError)
			{
				return $resolver();
			}
		}
	}

	private function toNullableInt(mixed $value): ?int
	{
		if($value === null)
		{
			return null;
		}

		if(is_int($value))
		{
			return $value;
		}

		if(is_string($value) || is_float($value) || is_bool($value))
		{
			return is_numeric($value) ? (int)$value : null;
		}

		return null;
	}
}

<?php

declare(strict_types=1);

namespace Mheads\Yii\Filestorage\ActiveRecord;

use Mheads\Yii\Filestorage\Entity\FileInterface;
use Mheads\Yii\Filestorage\Exception\AddException;
use Mheads\Yii\Filestorage\Exception\FindException;
use Mheads\Yii\Filestorage\Exception\InvalidConfigException;
use Mheads\Yii\Filestorage\Exception\RemoveException;
use Mheads\Yii\Filestorage\Helper;
use Mheads\Yii\Filestorage\Repository\RepositoryInterface;
use Override;
use Psr\Http\Message\UploadedFileInterface;
use Throwable;
use Yiisoft\ActiveRecord\ActiveRecordInterface;

use function is_a;
use function sprintf;

final readonly class ActiveRecordRepository implements RepositoryInterface
{
	/**
	 * @param class-string<ActiveRecordInterface&FileInterface> $fileClass
	 * @throws InvalidConfigException
	 */
	public function __construct(
		private string $fileClass = ArFile::class,
	) {
		if(!is_a($this->fileClass, FileInterface::class, true))
		{
			throw new InvalidConfigException(
				sprintf(
					'File class must implement %s, %s given',
					FileInterface::class,
					$this->fileClass,
				),
			);
		}
		if(!is_a($this->fileClass, ActiveRecordInterface::class, true))
		{
			throw new InvalidConfigException(
				sprintf(
					'File class must implement %s, %s given',
					ActiveRecordInterface::class,
					$this->fileClass,
				),
			);
		}
	}

	#[Override]
	public function findById(int|string $id): ?FileInterface
	{
		try
		{
			return $this->findRecordById($id);
		}
		catch(FindException $e)
		{
			throw $e;
		}
		catch(Throwable $e)
		{
			throw new FindException('Failed to find file: ' . $e->getMessage(), 0, $e);
		}
	}

	#[Override]
	public function add(FileInterface $file): int|string
	{
		try
		{
			if($file->getId() !== null)
			{
				throw new AddException('File already added');
			}

			$record = $this->toActiveRecord($file);
			$record->save();

			$id = $record->getId();
			if($id === null || $id === '')
			{
				throw new AddException('ActiveRecord did not return valid inserted id');
			}

			$file->assignId($id);

			return $id;
		}
		catch(AddException $e)
		{
			throw $e;
		}
		catch(Throwable $e)
		{
			throw new AddException('Failed to save file: ' . $e->getMessage(), 0, $e);
		}
	}

	#[Override]
	public function remove(FileInterface $file): void
	{
		try
		{
			$id = $file->getId();
			if($id === null)
			{
				throw new RemoveException('Cannot remove file without ID');
			}

			$record = $this->findRecordById($id);
			if($record === null)
			{
				throw new RemoveException("File with id {$id} not found");
			}

			$deleted = $record->delete();
			if($deleted === 0)
			{
				throw new RemoveException("File with id {$id} not found");
			}
		}
		catch(RemoveException $e)
		{
			throw $e;
		}
		catch(Throwable $e)
		{
			throw new RemoveException('Failed to remove file: ' . $e->getMessage(), 0, $e);
		}
	}

	#[Override]
	public function createFromUploadedFile(
		UploadedFileInterface $uploadedFile,
		string $groupName,
		string $storeName,
		?string $description = null,
	): FileInterface {
		return Helper::createFileFromUploadedFile(
			$uploadedFile,
			$groupName,
			$storeName,
			$this->fileClass,
			$description,
		);
	}

	private function toActiveRecord(FileInterface $file): FileInterface&ActiveRecordInterface
	{
		if($file instanceof ActiveRecordInterface && $file instanceof $this->fileClass)
		{
			return $file;
		}

		/** @var class-string<ActiveRecordInterface&FileInterface> $fileClass */
		$fileClass = $this->fileClass;
		/** @var ActiveRecordInterface&FileInterface $record */
		$record = new $fileClass();
		$record->setStoreName($file->getStoreName());
		$record->setExternalId($file->getExternalId());
		$record->setGroupName($file->getGroupName());
		$record->setRelativePath($file->getRelativePath());
		$record->setOriginalName($file->getOriginalName());
		$record->setHeight($file->getHeight());
		$record->setWidth($file->getWidth());
		$record->setFileSize($file->getFileSize());
		$record->setContentType($file->getContentType());
		$record->setDescription($file->getDescription());
		$record->setCreatedAt($file->getCreatedAt());
		$record->setUpdatedAt($file->getUpdatedAt());

		return $record;
	}

	private function findRecordById(int|string $id): (FileInterface&ActiveRecordInterface)|null
	{
		/** @var class-string<ActiveRecordInterface&FileInterface> $fileClass */
		$fileClass = $this->fileClass;
		$record = $fileClass::query()->findByPk($id);
		if($record === null)
		{
			return null;
		}
		/** @var ActiveRecordInterface&FileInterface $record */

		return $record;
	}
}

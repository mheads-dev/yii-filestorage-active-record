<?php

declare(strict_types=1);

namespace Mheads\Yii\Filestorage\ActiveRecord;

use DateTimeImmutable;
use Mheads\Yii\Filestorage\Db\FileTable;
use Mheads\Yii\Filestorage\Entity\FileInterface;
use Mheads\Yii\Filestorage\StorageInterface;
use Mheads\Yii\Filestorage\StorageProvider;
use Override;
use Yiisoft\ActiveRecord\ActiveRecord;
use Yiisoft\ActiveRecord\Event\Handler\DefaultDateTimeOnInsert;
use Yiisoft\ActiveRecord\Event\Handler\SetDateTimeOnUpdate;
use Yiisoft\ActiveRecord\Trait\EventsTrait;
use Yiisoft\ActiveRecord\Trait\MagicPropertiesTrait;

use function is_numeric;
use function is_scalar;

/**
 * @psalm-suppress ClassMustBeFinal
 * @psalm-suppress MissingConstructor
 */
#[DefaultDateTimeOnInsert([self::class, 'currentUnixTimestamp'], FileTable::COL_CREATED_AT, FileTable::COL_UPDATED_AT)]
#[SetDateTimeOnUpdate([self::class, 'currentUnixTimestamp'], FileTable::COL_UPDATED_AT)]
class ArFile extends ActiveRecord implements FileInterface
{
	use EventsTrait;
	use MagicPropertiesTrait;

	public static function currentUnixTimestamp(mixed $event = null): int
	{
		return time();
	}

	#[Override]
	public static function storage(): StorageInterface
	{
		return StorageProvider::get();
	}

	#[Override]
	public function tableName(): string
	{
		return FileTable::TABLE;
	}

	#[Override]
	public function getId(): int|string|null
	{
		return $this->asNullableString(FileTable::COL_ID);
	}

	#[Override]
	public function assignId(int|string $id): void
	{
		$this->set(FileTable::COL_ID, $id);
	}

	#[Override]
	public function getStoreName(): string
	{
		return $this->asString(FileTable::COL_STORE_NAME);
	}

	#[Override]
	public function setStoreName(string $value): void
	{
		$this->set(FileTable::COL_STORE_NAME, $value);
	}

	#[Override]
	public function getExternalId(): ?string
	{
		return $this->asNullableString(FileTable::COL_EXTERNAL_ID);
	}

	#[Override]
	public function setExternalId(?string $value): void
	{
		$this->set(FileTable::COL_EXTERNAL_ID, $value);
	}

	#[Override]
	public function getGroupName(): string
	{
		return $this->asString(FileTable::COL_GROUP_NAME);
	}

	#[Override]
	public function setGroupName(string $value): void
	{
		$this->set(FileTable::COL_GROUP_NAME, $value);
	}

	#[Override]
	public function getRelativePath(): ?string
	{
		return $this->asNullableString(FileTable::COL_RELATIVE_PATH);
	}

	#[Override]
	public function setRelativePath(?string $value): void
	{
		$this->set(FileTable::COL_RELATIVE_PATH, $value);
	}

	#[Override]
	public function getOriginalName(): string
	{
		return $this->asString(FileTable::COL_ORIGINAL_NAME);
	}

	#[Override]
	public function setOriginalName(string $value): void
	{
		$this->set(FileTable::COL_ORIGINAL_NAME, $value);
	}

	#[Override]
	public function getHeight(): ?int
	{
		return $this->asNullableInt(FileTable::COL_HEIGHT);
	}

	#[Override]
	public function setHeight(?int $value): void
	{
		$this->set(FileTable::COL_HEIGHT, $value);
	}

	#[Override]
	public function getWidth(): ?int
	{
		return $this->asNullableInt(FileTable::COL_WIDTH);
	}

	#[Override]
	public function setWidth(?int $value): void
	{
		$this->set(FileTable::COL_WIDTH, $value);
	}

	#[Override]
	public function getFileSize(): ?int
	{
		return $this->asNullableInt(FileTable::COL_FILE_SIZE);
	}

	#[Override]
	public function setFileSize(?int $value): void
	{
		$this->set(FileTable::COL_FILE_SIZE, $value);
	}

	#[Override]
	public function getContentType(): ?string
	{
		return $this->asNullableString(FileTable::COL_CONTENT_TYPE);
	}

	#[Override]
	public function setContentType(?string $value): void
	{
		$this->set(FileTable::COL_CONTENT_TYPE, $value);
	}

	#[Override]
	public function getDescription(): ?string
	{
		return $this->asNullableString(FileTable::COL_DESCRIPTION);
	}

	#[Override]
	public function setDescription(?string $value): void
	{
		$this->set(FileTable::COL_DESCRIPTION, $value);
	}

	#[Override]
	public function getUpdatedAt(): ?DateTimeImmutable
	{
		$timestamp = $this->asNullableInt(FileTable::COL_UPDATED_AT);
		if($timestamp === null)
		{
			return null;
		}

		$value = DateTimeImmutable::createFromFormat('U', (string)$timestamp);
		return $value === false ? null : $value;
	}

	#[Override]
	public function setUpdatedAt(?DateTimeImmutable $value): void
	{
		$this->set(FileTable::COL_UPDATED_AT, $value?->getTimestamp());
	}

	#[Override]
	public function getCreatedAt(): ?DateTimeImmutable
	{
		$timestamp = $this->asNullableInt(FileTable::COL_CREATED_AT);
		if($timestamp === null)
		{
			return null;
		}

		$value = DateTimeImmutable::createFromFormat('U', (string)$timestamp);
		return $value === false ? null : $value;
	}

	#[Override]
	public function setCreatedAt(?DateTimeImmutable $value): void
	{
		$this->set(FileTable::COL_CREATED_AT, $value?->getTimestamp());
	}

	#[Override]
	public function getUrl(): ?string
	{
		return static::storage()->getUrl($this);
	}

	#[Override]
	public function getContent(): ?string
	{
		return static::storage()->getContent($this);
	}

	#[Override]
	public function getResource()
	{
		return static::storage()->getResource($this);
	}

	private function asString(string $column): string
	{
		$value = $this->get($column);
		if($value === null)
		{
			return '';
		}

		return is_scalar($value) ? (string)$value : '';
	}

	private function asNullableString(string $column): ?string
	{
		$value = $this->get($column);
		if($value === null)
		{
			return null;
		}

		return is_scalar($value) ? (string)$value : null;
	}

	private function asNullableInt(string $column): ?int
	{
		$value = $this->get($column);
		if($value === null)
		{
			return null;
		}
		if(is_numeric($value))
		{
			return (int)$value;
		}

		return null;
	}

}

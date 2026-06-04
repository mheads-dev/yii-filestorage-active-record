<?php

declare(strict_types=1);

namespace Mheads\Yii\Filestorage\ActiveRecord\Contract;

use Psr\Http\Message\UploadedFileInterface;

trait PendingUploadedFileOwnerTrait
{
	/** @var array<string, UploadedFileInterface|null> */
	private array $pendingUploadedFiles = [];

	protected function queuePendingUploadedFile(string $fileIdAttribute, ?UploadedFileInterface $uploadedFile): void
	{
		$this->pendingUploadedFiles[$fileIdAttribute] = $uploadedFile;
	}

	public function pullPendingUploadedFile(string $fileIdAttribute): ?UploadedFileInterface
	{
		if(!array_key_exists($fileIdAttribute, $this->pendingUploadedFiles))
		{
			return null;
		}

		$uploadedFile = $this->pendingUploadedFiles[$fileIdAttribute];
		unset($this->pendingUploadedFiles[$fileIdAttribute]);
		return $uploadedFile;
	}
}

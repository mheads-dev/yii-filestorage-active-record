<?php

declare(strict_types=1);

namespace Mheads\Yii\Filestorage\ActiveRecord\Contract;

use Psr\Http\Message\UploadedFileInterface;

interface PendingUploadedFileOwnerInterface
{
	public function pullPendingUploadedFile(string $fileIdAttribute): ?UploadedFileInterface;
}

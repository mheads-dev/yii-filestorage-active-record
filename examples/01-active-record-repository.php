<?php

declare(strict_types=1);

use Mheads\Yii\Filestorage\ActiveRecord\ArFile;
use App\Examples\Support\UploadedFileFactory;
use Mheads\Yii\Filestorage\ActiveRecord\ActiveRecordRepository;
use Mheads\Yii\Filestorage\Storage;
use Mheads\Yii\Filestorage\StorageProvider;
use Mheads\Yii\Filestorage\Store\FileSystem\PublicFileSystemStore;
use Yiisoft\Db\Connection\ConnectionProvider;


require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/support/UploadedFileFactory.php';
require __DIR__ . '/support/getDbConnection.php';

/**
 * Example:
 * - metadata is saved via ActiveRecordRepository (ArFile).
 * - physical file is stored in PublicFileSystemStore.
 */

$db = getDbConnection();
ConnectionProvider::set($db);

$repository = new ActiveRecordRepository(ArFile::class);

$publicRoot = __DIR__ . '/runtime/ar-public-upload';
if(!is_dir($publicRoot))
{
    mkdir($publicRoot, 0o777, true);
}

$publicStore = new PublicFileSystemStore(
    name: 'upload',
    path: $publicRoot,
    baseUrl: 'https://cdn.example.com/ar-upload',
);

$storage = new Storage(
    repository: $repository,
    stores: [$publicStore],
    defaultStoreName: 'upload',
    defaultGroupName: 'products',
);

StorageProvider::set($storage);

$sourcePath = __DIR__ . '/runtime/source-ar.txt';
file_put_contents($sourcePath, "Hello from ActiveRecordRepository example.\n");

$file = $storage->add(
    uploadedFile: UploadedFileFactory::fromLocalPath($sourcePath, 'demo-ar.txt', 'text/plain'),
    groupName: 'products',
);

printf("Saved id: %d\n", (int)$file->getId());
printf("Class: %s\n", $file::class);
printf("Relative path: %s\n", (string)$file->getRelativePath());
printf("Public URL: %s\n", (string)$file->getUrl());

/** @var ArFile|null $found */
$found = $repository->findById((int)$file->getId());
printf("Found by repository: %s\n", $found?->getOriginalName() ?? 'not found');

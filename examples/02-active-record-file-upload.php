<?php

declare(strict_types=1);

use Mheads\Yii\Filestorage\ActiveRecord\ArFile;
use App\Examples\Support\Product;
use App\Examples\Support\UploadedFileFactory;
use Mheads\Yii\Filestorage\ActiveRecord\ActiveRecordRepository;
use Mheads\Yii\Filestorage\Storage;
use Mheads\Yii\Filestorage\StorageProvider;
use Mheads\Yii\Filestorage\Store\FileSystem\PublicFileSystemStore;
use Yiisoft\Db\Connection\ConnectionProvider;

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/support/UploadedFileFactory.php';
require __DIR__ . '/support/Product.php';
require __DIR__ . '/support/getDbConnection.php';

/**
 * AR handler example:
 * - Product::setPicture() accepts UploadedFileInterface.
 * - #[FileUpload] on picture_id uploads file automatically and writes id into picture_id column on save().
 * - getPicture() returns ArFile via relation.
 */

$db = getDbConnection();
ConnectionProvider::set($db);

$uploadRoot = __DIR__ . '/runtime/ar-handler-upload';
if(!is_dir($uploadRoot))
{
    mkdir($uploadRoot, 0o777, true);
}

$storage = new Storage(
    repository: new ActiveRecordRepository(ArFile::class),
    stores: [
        new PublicFileSystemStore(
            name: 'upload',
            path: $uploadRoot,
            baseUrl: 'https://cdn.example.com/product-upload',
        ),
    ],
    defaultStoreName: 'upload',
    defaultGroupName: 'products',
);

StorageProvider::set($storage);

$sourcePath = __DIR__ . '/runtime/source-product-picture.txt';
file_put_contents($sourcePath, "product-image-content\n");

$product = new Product();
$product->name = 'Phone';
$product->setPicture(
    UploadedFileFactory::fromLocalPath($sourcePath, 'phone.png', 'image/png'),
);
$product->save();

printf("Product id: %d\n", (int)$product->id);
printf("picture_id after save: %d\n", (int)$product->picture_id);

/** @var Product|null $reloaded */
$reloaded = Product::query()->with('picture')->findByPk((int)$product->id);
$picture = $reloaded?->getPicture();

printf("Picture AR class: %s\n", $picture instanceof ArFile ? $picture::class : 'null');
printf("Picture original name: %s\n", $picture?->getOriginalName() ?? 'not found');
printf("Picture URL: %s\n", (string)$picture?->getUrl());

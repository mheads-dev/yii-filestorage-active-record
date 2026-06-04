<?php

declare(strict_types=1);

use Mheads\Yii\Filestorage\ActiveRecord\ArFile;
use App\Examples\Support\ProductWithTwoFiles;
use App\Examples\Support\UploadedFileFactory;
use Mheads\Yii\Filestorage\ActiveRecord\ActiveRecordRepository;
use Mheads\Yii\Filestorage\Storage;
use Mheads\Yii\Filestorage\StorageProvider;
use Mheads\Yii\Filestorage\Store\FileSystem\PublicFileSystemStore;
use Yiisoft\Db\Connection\ConnectionProvider;

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/support/UploadedFileFactory.php';
require __DIR__ . '/support/ProductWithTwoFiles.php';
require __DIR__ . '/support/getDbConnection.php';

/**
 * Example of two FileUpload attributes in one AR model.
 *
 * In ProductWithTwoFiles:
 * - picture_id is handled by first FileUpload.
 * - manual_id is handled by second FileUpload.
 * Both ids are written during one save().
 */

$db = getDbConnection();
ConnectionProvider::set($db);

$uploadRoot = __DIR__ . '/runtime/ar-two-upload';
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

$pictureSourcePath = __DIR__ . '/runtime/source-product-picture-2.txt';
$manualSourcePath = __DIR__ . '/runtime/source-product-manual-2.txt';
file_put_contents($pictureSourcePath, "product-image-content\n");
file_put_contents($manualSourcePath, "product-manual-content\n");

$product = new ProductWithTwoFiles();
$product->name = 'Phone Pro';
$product->setPicture(
    UploadedFileFactory::fromLocalPath($pictureSourcePath, 'phone-pro.png', 'image/png'),
);
$product->setManual(
    UploadedFileFactory::fromLocalPath($manualSourcePath, 'manual.pdf', 'application/pdf'),
);
$product->save();

printf("Product id: %d\n", (int)$product->id);
printf("picture_id: %d\n", (int)$product->picture_id);
printf("manual_id: %d\n", (int)$product->manual_id);

/** @var ProductWithTwoFiles|null $reloaded */
$reloaded = ProductWithTwoFiles::query()->with('picture', 'manual')->findByPk((int)$product->id);

$picture = $reloaded?->getPicture();
$manual = $reloaded?->getManual();

printf("Picture name: %s\n", $picture?->getOriginalName() ?? 'not found');
printf("Manual name: %s\n", $manual?->getOriginalName() ?? 'not found');

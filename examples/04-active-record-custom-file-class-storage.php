<?php

declare(strict_types=1);

use Mheads\Yii\Filestorage\ActiveRecord\ArFile;
use App\Examples\Support\ProductWithTenantFile;
use App\Examples\Support\TenantArFile;
use App\Examples\Support\UploadedFileFactory;
use Mheads\Yii\Filestorage\ActiveRecord\ActiveRecordRepository;
use Mheads\Yii\Filestorage\Storage;
use Mheads\Yii\Filestorage\StorageProvider;
use Mheads\Yii\Filestorage\Store\FileSystem\PublicFileSystemStore;
use Yiisoft\Db\Connection\ConnectionProvider;

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/support/UploadedFileFactory.php';
require __DIR__ . '/support/TenantArFile.php';
require __DIR__ . '/support/ProductWithTenantFile.php';
require __DIR__ . '/support/getDbConnection.php';

/**
 * Example:
 * - app registers default + named storage ('tenant-42');
 * - file entity (TenantArFile) overrides storage() and is bound to named storage;
 * - FileUpload uses fileClass => TenantArFile::class.
 */

$db = getDbConnection();
ConnectionProvider::set($db);

$defaultUploadRoot = __DIR__ . '/runtime/ar-default-upload';
if(!is_dir($defaultUploadRoot))
{
    mkdir($defaultUploadRoot, 0o777, true);
}
$tenantUploadRoot = __DIR__ . '/runtime/ar-tenant-upload';
if(!is_dir($tenantUploadRoot))
{
    mkdir($tenantUploadRoot, 0o777, true);
}

$defaultStorage = new Storage(
    repository: new ActiveRecordRepository(ArFile::class),
    stores: [
        new PublicFileSystemStore(
            name: 'upload',
            path: $defaultUploadRoot,
            baseUrl: 'https://cdn.example.com/default-upload',
        ),
    ],
    defaultStoreName: 'upload',
    defaultGroupName: 'products',
);
StorageProvider::set($defaultStorage);

$tenantStorage = new Storage(
    repository: new ActiveRecordRepository(TenantArFile::class),
    stores: [
        new PublicFileSystemStore(
            name: 'upload',
            path: $tenantUploadRoot,
            baseUrl: 'https://cdn.example.com/tenant-42-upload',
        ),
    ],
    defaultStoreName: 'upload',
    defaultGroupName: 'products',
);
StorageProvider::set($tenantStorage, 'tenant-42');

$sourcePath = __DIR__ . '/runtime/source-product-picture-tenant.txt';
file_put_contents($sourcePath, "product-tenant-image-content\n");

$product = new ProductWithTenantFile();
$product->name = 'Phone';
$product->setPicture(
    UploadedFileFactory::fromLocalPath($sourcePath, 'phone-tenant.png', 'image/png'),
);
$product->save();

printf("Product id: %d\n", (int)$product->id);
printf("picture_id after save: %d\n", (int)$product->picture_id);

/** @var ProductWithTenantFile|null $reloaded */
$reloaded = ProductWithTenantFile::query()->with('picture')->findByPk((int)$product->id);
$picture = $reloaded?->getPicture();

printf("Picture AR class: %s\n", $picture instanceof TenantArFile ? $picture::class : 'null');
printf("Picture original name: %s\n", $picture?->getOriginalName() ?? 'not found');
printf("Picture URL (tenant storage): %s\n", (string)$picture?->getUrl());

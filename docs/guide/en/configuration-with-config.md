# Configuration with yiisoft/config

Minimal DI config for the ActiveRecord adapter:

```php
<?php

use Mheads\Yii\Filestorage\ActiveRecord\ActiveRecordRepository;
use Mheads\Yii\Filestorage\ActiveRecord\ArFile;
use Mheads\Yii\Filestorage\Repository\RepositoryInterface;
use Mheads\Yii\Filestorage\Storage;
use Mheads\Yii\Filestorage\StorageInterface;
use Mheads\Yii\Filestorage\Store\FileSystem\PrivateFileSystemStore;
use Mheads\Yii\Filestorage\Store\FileSystem\PublicFileSystemStore;

return [
    RepositoryInterface::class => [
        'class' => ActiveRecordRepository::class,
        '__construct()' => [
            'fileClass' => ArFile::class,
        ],
    ],
    StorageInterface::class => [
        'class' => Storage::class,
        '__construct()' => [
            'stores' => [
                new PublicFileSystemStore(
                    name: Storage::DEFAULT_STORE_NAME,
                    path: dirname(__DIR__, 3) . '/public/upload',
                    baseUrl: '/upload',
                ),
                new PrivateFileSystemStore(
                    name: Storage::DEFAULT_STORE_NAME . '_private',
                    path: dirname(__DIR__, 3) . '/upload-private',
                ),
            ],
        ],
    ],
];
```

Bootstrap:

```php
<?php

declare(strict_types=1);

use Mheads\Yii\Filestorage\StorageInterface;
use Mheads\Yii\Filestorage\StorageProvider;
use Psr\Container\ContainerInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Connection\ConnectionProvider;

return [
    static function (ContainerInterface $container): void {
        ConnectionProvider::set($container->get(ConnectionInterface::class));
        StorageProvider::set($container->get(StorageInterface::class));
    },
];
```

Notes:

- `ConnectionProvider::set(...)` is required for `ArFile`, `ActiveRecordRepository`, AR relations, and AR queries.
- `StorageProvider::set(...)` is required for `FileInterface::getUrl()/getContent()/getResource()` and for `#[FileUpload]`.
- For named storage instances, register each storage with `StorageProvider::set($storage, 'name')` and bind a custom file class through `ArFile::storage()`.

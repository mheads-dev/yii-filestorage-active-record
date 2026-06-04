# mheads/yii-filestorage-active-record

ActiveRecord adapter for [`mheads/yii-filestorage`](https://github.com/mheads-dev/yii-filestorage).

The package provides:

- `ActiveRecordRepository` - a `RepositoryInterface` implementation that stores file metadata through `yiisoft/active-record`.
- `ArFile` - an ActiveRecord file entity for the `mh_filestorage_file` table.
- `#[FileUpload]` - an ActiveRecord event handler that uploads pending files on save and cleans old files on replace/delete.
- `PendingUploadedFileOwnerInterface` and `PendingUploadedFileOwnerTrait` - a small model-side queue for uploaded files.

Physical file storage is still handled by the core package stores, for example `PublicFileSystemStore` and `PrivateFileSystemStore`.
The file metadata table schema and `DbRepository` come from `mheads/yii-filestorage-db`, which is installed as a dependency.

## Installation

```shell
composer require mheads/yii-filestorage-active-record
```

Also install the database driver used by your project:

```shell
composer require yiisoft/db-mysql
```

Before using this ActiveRecord adapter, complete the `mheads/yii-filestorage-db` [DB adapter setup](https://github.com/mheads-dev/yii-filestorage-db#installation), including the DB driver and file metadata table.
For the demo product table used in examples, see [example migrations](examples/README.md#preparation).

## Quick Start

```php
use Mheads\Yii\Filestorage\ActiveRecord\ActiveRecordRepository;
use Mheads\Yii\Filestorage\ActiveRecord\ArFile;
use Mheads\Yii\Filestorage\Storage;
use Mheads\Yii\Filestorage\StorageProvider;
use Yiisoft\Db\Connection\ConnectionProvider;

ConnectionProvider::set($db);

$storage = new Storage(
    repository: new ActiveRecordRepository(ArFile::class),
    stores: [$publicStore],
    defaultStoreName: 'upload',
    defaultGroupName: 'products',
);
StorageProvider::set($storage);

$product = new Product();
$product->setPicture($uploadedFile);
$product->save();
```

`$publicStore` is a core package store, for example `PublicFileSystemStore`.
`Product` should use `#[FileUpload]` and `PendingUploadedFileOwnerTrait`; see the lifecycle guide below.

`ConnectionProvider::set()` is required for `ArFile`, `ActiveRecordRepository`, and AR relations.
`StorageProvider::set()` is required for `#[FileUpload]` and direct file methods such as `getUrl()`.

See the full flow in [AR file upload lifecycle](docs/guide/en/active-record-file-upload.md) and [02-active-record-file-upload.php](examples/02-active-record-file-upload.php).

## Documentation

- [Guide](docs/guide/en/README.md)
- [Examples](examples/README.md)
- [Internals](docs/internals.md)

## License

[BSD-3-Clause](LICENSE.md)

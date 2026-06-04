# Repositories

Repositories handle file metadata. This package adds the ActiveRecord repository and file entity.

## ActiveRecordRepository

`ActiveRecordRepository` stores metadata through `ArFile` or your custom `ArFile` class.

```php
use Mheads\Yii\Filestorage\ActiveRecord\ActiveRecordRepository;
use Mheads\Yii\Filestorage\ActiveRecord\ArFile;

$repository = new ActiveRecordRepository(ArFile::class);
```

Use it when your application needs ActiveRecord relations, `ArFile::query()`, or custom file ActiveRecord classes.

`ActiveRecordRepository` requires its file class to implement both:

- `Mheads\Yii\Filestorage\Entity\FileInterface`
- `Yiisoft\ActiveRecord\ActiveRecordInterface`

## ArFile

`ArFile` maps to the `mh_filestorage_file` table from `mheads/yii-filestorage-db`.
It implements `FileInterface`, uses ActiveRecord date handlers for `created_at` / `updated_at`, and delegates these methods to storage:

- `getUrl()`
- `getContent()`
- `getResource()`

Register storage before calling these methods on the file object. See [Configuration with yiisoft/config](configuration-with-config.md).

## DbRepository Compatibility

`mheads/yii-filestorage-db` is installed as a dependency, so `DbRepository` is also available.
It can create `ArFile` instances when configured with `fileClass: ArFile::class`:

```php
use Mheads\Yii\Filestorage\ActiveRecord\ArFile;
use Mheads\Yii\Filestorage\Db\DbRepository;

$repository = new DbRepository($db, fileClass: ArFile::class);
```

Prefer `ActiveRecordRepository` for ActiveRecord integration. Use `DbRepository` only when you specifically want DB-layer metadata writes while still returning an AR-compatible file class.

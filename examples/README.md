# Examples

`examples/*` are reference/demo scripts for this ActiveRecord adapter.
In real projects, adapt them to your runtime: DB connection, paths, bootstrap, and launch method.

## Example Map

| Scenario | File |
| --- | --- |
| ActiveRecord repository (`ArFile`) | [01-active-record-repository.php](01-active-record-repository.php) |
| `#[FileUpload]` in AR model | [02-active-record-file-upload.php](02-active-record-file-upload.php) |
| Two `#[FileUpload]` attributes in one model | [03-active-record-two-file-upload.php](03-active-record-two-file-upload.php) |
| Multi-storage + custom `ArFile::storage()` | [04-active-record-custom-file-class-storage.php](04-active-record-custom-file-class-storage.php) |

## Preparation

1. Prepare `ConnectionInterface` from your application DI container.
2. Complete the `mheads/yii-filestorage-db` [DB adapter setup](https://github.com/mheads-dev/yii-filestorage-db#installation), including the DB driver and file metadata table.
3. Replace [support/getDbConnection.php](support/getDbConnection.php) with your project connection provider.
4. `examples/support/UploadedFileFactory.php` requires `httpsoft/http-message`.
5. Examples `02`/`03`/`04` require a `product` table:
   - fields: `id`, `name`, `picture_id`, `manual_id`
   - SQL samples: [mysql](migrations/mysql-product.sql), [pgsql](migrations/pgsql-product.sql), [mssql](migrations/mssql-product.sql)
6. Create file storage directories or keep directory creation from the samples.

## How To Run In Your Project

1. Copy `examples` into your project or adapt the scripts in place.
2. Implement `getDbConnection()` in [support/getDbConnection.php](support/getDbConnection.php).
3. Run a script via your PHP launch flow: CLI, container, or project build scripts.

Important: samples are not intended to run directly from `vendor/.../examples` without path/environment adaptation.

## Minimal Connection Example

All scripts use shared helper [support/getDbConnection.php](support/getDbConnection.php). Replace the stub with your real app connection.

This snippet additionally requires `yiisoft/cache`.

```php
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Driver;
use Yiisoft\Db\Mysql\Dsn;

function getDbConnection(): Connection
{
    return new Connection(
        new Driver(
            new Dsn('mysql', 'db', 'app', '3306'),
            'user',
            'supersecretpassword',
        ),
        new SchemaCache(new ArrayCache()),
    );
}
```

## Notes

- Samples demonstrate API and lifecycle, not production architecture.

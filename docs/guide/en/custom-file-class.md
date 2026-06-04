# Custom file class

Use a custom `ArFile` subclass when you need to bind a file model to a non-default storage or add project-specific AR behavior.

Example:

```php
use Mheads\Yii\Filestorage\ActiveRecord\ArFile;
use Mheads\Yii\Filestorage\ActiveRecord\ActiveRecordRepository;
use Mheads\Yii\Filestorage\StorageInterface;
use Mheads\Yii\Filestorage\StorageProvider;

final class TenantArFile extends ArFile
{
    public static function storage(): StorageInterface
    {
        return StorageProvider::get('tenant-42');
    }
}
```

Then use this class in `FileUpload` and repository configuration:

```php
#[FileUpload(fileClass: TenantArFile::class)]
public int|string|null $picture_id = null;
```

```php
$repository = new ActiveRecordRepository(TenantArFile::class);
```

`#[FileUpload]` resolves the storage name from `fileClass::storage()` when that storage is registered in `StorageProvider`.

Reference files:

- [04-active-record-custom-file-class-storage.php](../../../examples/04-active-record-custom-file-class-storage.php)
- [support/TenantArFile.php](../../../examples/support/TenantArFile.php)

# AR file upload lifecycle

`#[FileUpload]` automates the file-id field lifecycle in ActiveRecord models.

Recommended model pattern:

1. Model implements `PendingUploadedFileOwnerInterface`.
2. Model uses `PendingUploadedFileOwnerTrait`.
3. Model uses `EventsTrait` so ActiveRecord event handlers run.
4. The upload field setter calls `queuePendingUploadedFile('<file_id_column>', $uploadedFile)`.
5. On `null`, the setter clears the file-id column: `$this->set('<file_id_column>', null)`.

Example:

```php
use Mheads\Yii\Filestorage\ActiveRecord\ArFile;
use Mheads\Yii\Filestorage\ActiveRecord\EventHandler\FileUpload;
use Psr\Http\Message\UploadedFileInterface;

#[FileUpload(groupName: 'products', storeName: 'upload', fileClass: ArFile::class)]
public int|string|null $picture_id = null;

public function setPicture(?UploadedFileInterface $uploadedFile): void
{
    $this->queuePendingUploadedFile('picture_id', $uploadedFile);
    if($uploadedFile === null) {
        $this->set('picture_id', null);
    }
}
```

Lifecycle:

- `BeforeSave`: uploads pending file, writes new id to model.
- `AfterSave`: removes old file when `enableAutoCleaning=true` and id changed.
- `AfterDelete`: removes linked file when `enableAutoCleaning=true`.
- Shutdown cleanup: if `BeforeSave` uploaded a new file but the save flow did not finish successfully, the handler attempts to remove the newly uploaded file.

`groupName` and `storeName` may be strings or callables. Callable resolvers may accept `($model, $fileIdAttribute)`, `($model)`, or no arguments.

Reference files:

- [02-active-record-file-upload.php](../../../examples/02-active-record-file-upload.php)
- [support/Product.php](../../../examples/support/Product.php)

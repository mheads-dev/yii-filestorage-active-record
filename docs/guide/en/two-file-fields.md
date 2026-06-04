# Two file fields in one AR model

Limitation: one `#[FileUpload]` handles exactly one file-id field.

If a model has two fields (`picture_id`, `manual_id`), use two attributes:

```php
#[FileUpload(groupName: 'products', storeName: 'upload', fileClass: ArFile::class)]
public int|string|null $picture_id = null;

#[FileUpload(groupName: 'products', storeName: 'upload', fileClass: ArFile::class)]
public int|string|null $manual_id = null;
```

Each field needs its own setter that calls `queuePendingUploadedFile(...)` with the matching column name.

Reference files:

- [03-active-record-two-file-upload.php](../../../examples/03-active-record-two-file-upload.php)
- [support/ProductWithTwoFiles.php](../../../examples/support/ProductWithTwoFiles.php)

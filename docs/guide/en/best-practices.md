# Best practices

## Keep upload state explicit

Use `PendingUploadedFileOwnerTrait` for temporary uploaded-file state. Keep persisted file ids in regular AR columns such as `picture_id`.

## Register providers during bootstrap

Set both providers before using AR file objects or `#[FileUpload]`. See [Configuration with yiisoft/config](configuration-with-config.md).

## Prefer ActiveRecordRepository in AR integrations

Use `ActiveRecordRepository` when your application relies on `ArFile` relations, `ArFile::query()`, or custom `ArFile` subclasses.
Use `DbRepository` only when you intentionally want DB-layer metadata writes. See [Repositories](repositories.md).

## Clean up replaced files intentionally

For ActiveRecord uploads, keep `enableAutoCleaning=true` unless your business logic needs historical files. Make setters clear the file-id field on `null`.

## Keep relations typed

Define explicit AR relations from your business model to `ArFile` or your custom file class:

```php
public function getPictureQuery(): ActiveQuery
{
    return $this->hasOne(ArFile::class, ['id' => 'picture_id']);
}
```

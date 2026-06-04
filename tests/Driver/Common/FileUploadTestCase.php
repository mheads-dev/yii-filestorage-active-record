<?php

declare(strict_types=1);

namespace Mheads\Yii\Filestorage\ActiveRecord\Tests\Driver\Common;

use Mheads\Yii\Filestorage\ActiveRecord\ArFile;
use Mheads\Yii\Filestorage\ActiveRecord\Tests\Stubs\ActiveRecord\Product;
use Mheads\Yii\Filestorage\ActiveRecord\Tests\Stubs\ActiveRecord\ProductWithInvalidFileUpload;
use Mheads\Yii\Filestorage\ActiveRecord\Tests\Stubs\ActiveRecord\ProductWithoutAutoCleaning;
use Mheads\Yii\Filestorage\ActiveRecord\Tests\Stubs\ActiveRecord\ProductWithTenantFile;
use Mheads\Yii\Filestorage\ActiveRecord\Tests\Stubs\ActiveRecord\ProductWithTwoFiles;
use Mheads\Yii\Filestorage\ActiveRecord\Tests\Stubs\ActiveRecord\Tenant42ArFile;
use Mheads\Yii\Filestorage\ActiveRecord\Tests\Support\CreateUploadedFileMockTrait;
use Mheads\Yii\Filestorage\ActiveRecord\Tests\TestCase;
use Mheads\Yii\Filestorage\Db\DbRepository;
use Mheads\Yii\Filestorage\Exception\InvalidConfigException;
use Mheads\Yii\Filestorage\Storage;
use Mheads\Yii\Filestorage\StorageProvider;
use Mheads\Yii\Filestorage\Store\FileSystem\PublicFileSystemStore;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Yiisoft\ActiveRecord\Event\EventDispatcherProvider;
use Yiisoft\Files\FileHelper;

use function is_dir;
use function is_file;
use function sys_get_temp_dir;

#[AllowMockObjectsWithoutExpectations]
abstract class FileUploadTestCase extends TestCase
{
	use CreateUploadedFileMockTrait;

	private string $uploadRoot;
	private string $tenantUploadRoot;

	protected function setUp(): void
	{
		parent::setUp();
		EventDispatcherProvider::reset();
		StorageProvider::clear();

		$this->uploadRoot = sys_get_temp_dir() . '/mheads-yii-filestorage-ar-upload';
		if(is_dir($this->uploadRoot))
		{
			FileHelper::removeDirectory($this->uploadRoot);
		}
		FileHelper::ensureDirectory($this->uploadRoot);
		$this->tenantUploadRoot = sys_get_temp_dir() . '/mheads-yii-filestorage-ar-upload-tenant-42';
		if(is_dir($this->tenantUploadRoot))
		{
			FileHelper::removeDirectory($this->tenantUploadRoot);
		}
		FileHelper::ensureDirectory($this->tenantUploadRoot);

		$storage = new Storage(
			repository: new DbRepository(static::db(), fileClass: ArFile::class),
			stores: [
				new PublicFileSystemStore(
					name: 'upload',
					path: $this->uploadRoot,
					baseUrl: 'https://example.test/upload',
				),
			],
			defaultStoreName: 'upload',
			defaultGroupName: 'products',
		);
		StorageProvider::set($storage);

		$tenantStorage = new Storage(
			repository: new DbRepository(static::db(), fileClass: Tenant42ArFile::class),
			stores: [
				new PublicFileSystemStore(
					name: 'upload',
					path: $this->tenantUploadRoot,
					baseUrl: 'https://tenant-42.example.test/upload',
				),
			],
			defaultStoreName: 'upload',
			defaultGroupName: 'products',
		);
		StorageProvider::set($tenantStorage, 'tenant-42');
	}

	protected function tearDown(): void
	{
		EventDispatcherProvider::reset();
		StorageProvider::clear();

		if(is_dir($this->uploadRoot))
		{
			FileHelper::removeDirectory($this->uploadRoot);
		}
		if(is_dir($this->tenantUploadRoot))
		{
			FileHelper::removeDirectory($this->tenantUploadRoot);
		}

		parent::tearDown();
	}

	public function testSaveUploadsFileAndAssignsPictureId(): void
	{
		$model = new Product();
		$model->name = 'Phone';
		$model->setPicture($this->createUploadedFileMock('phone.png', 'image-content'));
		$model->save();

		self::assertNotNull($model->picture_id);

		$file = StorageProvider::get()->findById($model->picture_id);
		self::assertNotNull($file);
		self::assertSame('upload', $file->getStoreName());
		self::assertSame('products', $file->getGroupName());
		self::assertSame('phone.png', $file->getOriginalName());
		self::assertNotNull($file->getRelativePath());
		self::assertTrue(is_file($this->uploadRoot . '/' . $file->getRelativePath()));
	}

	public function testSaveWithNewPictureRemovesPreviousFile(): void
	{
		$model = new Product();
		$model->name = 'Phone';
		$model->setPicture($this->createUploadedFileMock('old.png', 'old-content'));
		$model->save();

		$oldId = $model->picture_id;
		self::assertNotNull($oldId);
		$oldFile = StorageProvider::get()->findById($oldId);
		self::assertNotNull($oldFile);
		$oldPath = $oldFile->getRelativePath();
		self::assertNotNull($oldPath);

		$model->setPicture($this->createUploadedFileMock('new.png', 'new-content'));
		$model->save();

		self::assertNotNull($model->picture_id);
		self::assertNotSame($oldId, $model->picture_id);
		self::assertNull(StorageProvider::get()->findById($oldId));
		self::assertFalse(is_file($this->uploadRoot . '/' . $oldPath));
	}

	public function testDeleteModelRemovesLinkedFile(): void
	{
		$model = new Product();
		$model->name = 'Phone';
		$model->setPicture($this->createUploadedFileMock('to-delete.png', 'content'));
		$model->save();

		$fileId = $model->picture_id;
		self::assertNotNull($fileId);
		$file = StorageProvider::get()->findById($fileId);
		self::assertNotNull($file);
		$path = $file->getRelativePath();
		self::assertNotNull($path);

		$model->delete();

		self::assertNull(StorageProvider::get()->findById($fileId));
		self::assertFalse(is_file($this->uploadRoot . '/' . $path));
	}

	public function testGetPictureReturnsArFile(): void
	{
		$model = new Product();
		$model->name = 'Phone';
		$model->setPicture($this->createUploadedFileMock('picture-relation.png', 'content'));
		$model->save();

		$fileId = $model->picture_id;
		self::assertNotNull($fileId);
		self::assertNotNull($model->id);

		/** @var Product|null $reloaded */
		$reloaded = Product::query()->with('picture')->findByPk((int)$model->id);
		self::assertNotNull($reloaded);

		$picture = $reloaded->getPicture();
		self::assertInstanceOf(ArFile::class, $picture);
		self::assertSame((string)$fileId, (string)$picture->getId());
		self::assertSame('picture-relation.png', $picture->getOriginalName());
	}

	public function testSaveWithNewPictureDoesNotRemovePreviousFileWhenAutoCleaningDisabled(): void
	{
		$model = new ProductWithoutAutoCleaning();
		$model->name = 'Phone';
		$model->setPicture($this->createUploadedFileMock('old-no-clean.png', 'old-content'));
		$model->save();

		$oldId = $model->picture_id;
		self::assertNotNull($oldId);
		$oldFile = StorageProvider::get()->findById($oldId);
		self::assertNotNull($oldFile);
		$oldPath = $oldFile->getRelativePath();
		self::assertNotNull($oldPath);

		$model->setPicture($this->createUploadedFileMock('new-no-clean.png', 'new-content'));
		$model->save();

		self::assertNotNull($model->picture_id);
		self::assertNotSame($oldId, $model->picture_id);
		self::assertNotNull(StorageProvider::get()->findById($oldId));
		self::assertTrue(is_file($this->uploadRoot . '/' . $oldPath));
	}

	public function testSaveUploadsTwoDifferentAttributesInOneModel(): void
	{
		$model = new ProductWithTwoFiles();
		$model->name = 'Phone';
		$model->setPicture($this->createUploadedFileMock('front.png', 'front-content'));
		$model->setManual($this->createUploadedFileMock('manual.pdf', 'manual-content'));
		$model->save();

		self::assertNotNull($model->picture_id);
		self::assertNotNull($model->manual_id);
		self::assertNotSame((int)$model->picture_id, (int)$model->manual_id);

		$pictureFile = StorageProvider::get()->findById((int)$model->picture_id);
		$manualFile = StorageProvider::get()->findById((int)$model->manual_id);

		self::assertNotNull($pictureFile);
		self::assertNotNull($manualFile);
		self::assertSame('front.png', $pictureFile->getOriginalName());
		self::assertSame('manual.pdf', $manualFile->getOriginalName());

		/** @var ProductWithTwoFiles|null $reloaded */
		$reloaded = ProductWithTwoFiles::query()
			->with('picture', 'manual')
			->findByPk((int)$model->id);
		self::assertNotNull($reloaded);

		$picture = $reloaded->getPicture();
		$manual = $reloaded->getManual();

		self::assertInstanceOf(ArFile::class, $picture);
		self::assertInstanceOf(ArFile::class, $manual);
		self::assertSame((string)$model->picture_id, (string)$picture->getId());
		self::assertSame((string)$model->manual_id, (string)$manual->getId());
	}

	public function testSaveThrowsWhenFileUploadHasMultipleAttributesConfigured(): void
	{
		$this->expectException(InvalidConfigException::class);
		$this->expectExceptionMessage('FileUpload attribute supports exactly one file id property.');

		$model = new ProductWithInvalidFileUpload();
		$model->name = 'Phone';
		$model->save();
	}

	public function testFileUploadUsesStorageFromCustomFileClass(): void
	{
		$model = new ProductWithTenantFile();
		$model->name = 'Phone';
		$model->setPicture($this->createUploadedFileMock('tenant-phone.png', 'tenant-content'));
		$model->save();

		self::assertNotNull($model->picture_id);
		self::assertNotNull($model->id);

		$defaultFile = StorageProvider::get()->findById((int)$model->picture_id);
		self::assertNotNull($defaultFile);
		self::assertStringStartsWith('https://example.test/upload', (string)$defaultFile->getUrl());

		$tenantFile = StorageProvider::get('tenant-42')->findById((int)$model->picture_id);
		self::assertInstanceOf(Tenant42ArFile::class, $tenantFile);
		self::assertStringStartsWith('https://tenant-42.example.test/upload', (string)$tenantFile->getUrl());
		self::assertNotNull($tenantFile->getRelativePath());
		self::assertTrue(is_file($this->tenantUploadRoot . '/' . $tenantFile->getRelativePath()));

		/** @var ProductWithTenantFile|null $reloaded */
		$reloaded = ProductWithTenantFile::query()->with('picture')->findByPk((int)$model->id);
		self::assertNotNull($reloaded);
		$picture = $reloaded->getPicture();
		self::assertInstanceOf(Tenant42ArFile::class, $picture);
		self::assertStringStartsWith('https://tenant-42.example.test/upload', (string)$picture->getUrl());
	}
}

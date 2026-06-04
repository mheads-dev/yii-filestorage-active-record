<?php

declare(strict_types=1);

namespace Mheads\Yii\Filestorage\ActiveRecord\Tests\Driver\Common;

use Mheads\Yii\Filestorage\ActiveRecord\ActiveRecordRepository;
use Mheads\Yii\Filestorage\ActiveRecord\ArFile;
use Mheads\Yii\Filestorage\ActiveRecord\Tests\Support\CreateUploadedFileMockTrait;
use Mheads\Yii\Filestorage\ActiveRecord\Tests\TestCase;
use Mheads\Yii\Filestorage\Entity\File;
use Mheads\Yii\Filestorage\Exception\AddException;
use Mheads\Yii\Filestorage\Exception\InvalidConfigException;
use Mheads\Yii\Filestorage\Exception\RemoveException;
use stdClass;

abstract class ActiveRecordRepositoryTestCase extends TestCase
{
	use CreateUploadedFileMockTrait;

	public function testAddFindAndRemove(): void
	{
		$repository = new ActiveRecordRepository(ArFile::class);

		$uploadedFile = $this->createUploadedFileMock('image.png', 'content');
		$file = $repository->createFromUploadedFile(
			$uploadedFile,
			'avatars',
			'upload',
			'Avatar',
		);

		$id = $repository->add($file);
		self::assertGreaterThan(0, $id);
		self::assertSame($id, $file->getId());

		$found = $repository->findById($id);
		self::assertInstanceOf(ArFile::class, $found);
		self::assertSame('avatars', $found->getGroupName());
		self::assertSame('upload', $found->getStoreName());
		self::assertSame('image.png', $found->getOriginalName());
		self::assertSame('Avatar', $found->getDescription());

		$repository->remove($found);
		self::assertNull($repository->findById($id));
	}

	public function testConstructThrowsWhenFileClassIsNotActiveRecord(): void
	{
		$this->expectException(InvalidConfigException::class);
		new ActiveRecordRepository(File::class);
	}

	public function testConstructThrowsWhenFileClassIsInvalid(): void
	{
		$this->expectException(InvalidConfigException::class);
		new ActiveRecordRepository(stdClass::class);
	}

	public function testAddThrowsWhenFileAlreadyHasIdWithoutWrapping(): void
	{
		$repository = new ActiveRecordRepository(ArFile::class);
		$file = new ArFile();
		$file->assignId(1);
		$file->setStoreName('upload');
		$file->setGroupName('avatars');
		$file->setOriginalName('image.png');

		try
		{
			$repository->add($file);
			self::fail('AddException expected.');
		}
		catch(AddException $e)
		{
			self::assertSame('File already added', $e->getMessage());
			self::assertNull($e->getPrevious());
		}
	}

	public function testRemoveThrowsWhenFileHasNoIdWithoutWrapping(): void
	{
		$repository = new ActiveRecordRepository(ArFile::class);
		$file = new ArFile();
		$file->setStoreName('upload');
		$file->setGroupName('avatars');
		$file->setOriginalName('image.png');

		try
		{
			$repository->remove($file);
			self::fail('RemoveException expected.');
		}
		catch(RemoveException $e)
		{
			self::assertSame('Cannot remove file without ID', $e->getMessage());
			self::assertNull($e->getPrevious());
		}
	}
}

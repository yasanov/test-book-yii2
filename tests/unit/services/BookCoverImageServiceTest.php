<?php

namespace tests\unit\services;

use app\services\BookCoverImageService;
use app\services\StorageInterface;

class BookCoverImageServiceTest extends \Codeception\Test\Unit
{
    public function testGetUrlByPathReturnsNullForEmptyPath(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects($this->never())->method('getFileUrl');

        $service = new BookCoverImageService($storage);

        verify($service->getUrlByPath(null))->null();
        verify($service->getUrlByPath(''))->null();
    }

    public function testGetUrlByPathUsesStorageService(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects($this->once())
            ->method('getFileUrl')
            ->with('2026/03/test.jpg')
            ->willReturn('/uploads/2026/03/test.jpg');

        $service = new BookCoverImageService($storage);

        verify($service->getUrlByPath('2026/03/test.jpg'))->equals('/uploads/2026/03/test.jpg');
    }
}

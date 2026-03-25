<?php

declare(strict_types=1);

namespace app\services;

use app\models\Book;
use Yii;

class BookCoverImageService
{
    public function __construct(
        private readonly StorageInterface $storageService
    ) {
    }

    public function getUrl(Book $book): ?string
    {
        return $this->getUrlByPath($book->cover_image);
    }

    public function getUrlByPath(?string $coverImagePath): ?string
    {
        if ($coverImagePath === null || $coverImagePath === '') {
            return null;
        }

        try {
            return $this->storageService->getFileUrl($coverImagePath);
        } catch (\Exception $e) {
            Yii::error('Ошибка получения URL изображения: ' . $e->getMessage(), 'book');

            return null;
        }
    }
}

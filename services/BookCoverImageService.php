<?php

declare(strict_types=1);

namespace app\services;

use app\models\Book;
use Yii;

class BookCoverImageService
{
    public function __construct(
        private readonly LocalStorageService $storageService
    ) {
    }

    public function getUrl(Book $book): ?string
    {
        if ($book->cover_image === null || $book->cover_image === '') {
            return null;
        }

        try {
            return $this->storageService->getFileUrl($book->cover_image);
        } catch (\Exception $e) {
            Yii::error('Ошибка получения URL изображения: ' . $e->getMessage(), 'book');

            return null;
        }
    }
}

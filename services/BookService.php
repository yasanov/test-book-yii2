<?php

declare(strict_types=1);

namespace app\services;

use app\exceptions\NotFoundException;
use app\exceptions\ServiceException;
use app\models\AuthorSubscription;
use app\models\Book;
use app\repositories\AuthorSubscriptionRepository;
use app\repositories\BookAuthorRepository;
use app\repositories\BookRepository;
use app\services\notifications\EmailNotificationStrategy;
use app\services\notifications\SmsNotificationStrategy;
use yii\data\ActiveDataProvider;
use yii\web\UploadedFile;

class BookService
{
    private array $notificationStrategies = [];

    public function __construct(
        private readonly BookRepository $bookRepository,
        private readonly BookAuthorRepository $bookAuthorRepository,
        private readonly AuthorSubscriptionRepository $subscriptionRepository,
        private readonly StorageInterface $storageService,
        SmsNotificationStrategy $smsStrategy,
        EmailNotificationStrategy $emailStrategy
    ) {
        $this->notificationStrategies = [
            $smsStrategy,
            $emailStrategy,
        ];
    }

    public function getDataProvider(int $pageSize = 20): ActiveDataProvider
    {
        return $this->bookRepository->getDataProvider($pageSize);
    }

    public function getById(int $id): Book
    {
        $book = $this->bookRepository->findByIdWithAuthors($id);
        if ($book === null) {
            throw new NotFoundException('Книга не найдена.');
        }

        return $book;
    }

    public function create(array $data, array $authorIds, ?UploadedFile $coverImageFile = null): Book
    {
        $book = new Book();
        $book->loadDefaultValues();
        $book->load($data);
        
        if ($coverImageFile !== null) {
            $filePath = $this->storageService->uploadFile($coverImageFile);
            $book->cover_image = $filePath;
        }

        if (!$book->validate()) {
            $errors = $book->getFirstErrors();
            $errorMessage = !empty($errors) ? reset($errors) : 'Ошибка валидации данных книги.';
            throw new ServiceException($errorMessage);
        }

        if (!$this->bookRepository->save($book)) {
            throw new ServiceException('Не удалось сохранить книгу.');
        }

        $this->bookAuthorRepository->replace($book->id, $authorIds);

        $bookWithAuthors = $this->bookRepository->findByIdWithAuthors($book->id);
        if ($bookWithAuthors !== null) {
            $this->notifySubscribers($bookWithAuthors);

            return $bookWithAuthors;
        }

        return $book;
    }

    public function update(int $id, array $data, array $authorIds, ?UploadedFile $coverImageFile = null): Book
    {
        $book = $this->getById($id);
        $oldCoverImage = $book->cover_image;
        
        unset($data['coverImageFile']);
        $book->load($data);

        if ($coverImageFile !== null) {
            $filePath = $this->storageService->uploadFile($coverImageFile, $oldCoverImage);
            $book->cover_image = $filePath;
        }

        if (!$book->validate()) {
            $errors = $book->getFirstErrors();
            $errorMessage = !empty($errors) ? reset($errors) : 'Ошибка валидации данных книги.';
            throw new ServiceException($errorMessage);
        }

        if (!$this->bookRepository->save($book)) {
            throw new ServiceException('Не удалось обновить книгу.');
        }

        $this->bookAuthorRepository->replace($book->id, $authorIds);

        return $book;
    }

    public function delete(int $id): void
    {
        $book = $this->getById($id);
        $coverImage = $book->cover_image;
        
        if (!$this->bookRepository->delete($book)) {
            throw new ServiceException('Не удалось удалить книгу.');
        }

        if ($coverImage !== null && $coverImage !== '') {
            $this->storageService->deleteFile($coverImage);
        }
    }

    public function getSelectedAuthorIds(Book $book): array
    {
        return array_map(function ($author) {
            return $author->id;
        }, $book->authors);
    }

    private function notifySubscribers(Book $book): void
    {
        if (empty($book->authors)) {
            return;
        }

        $authorsNames = $book->getAuthorsNames();
        $subject = "Новая книга от {$authorsNames}";
        $message = "Новая книга от {$authorsNames}: \"{$book->title}\" ({$book->year})";

        foreach ($book->authors as $author) {
            foreach ($this->subscriptionRepository->findByAuthorIdBatch($author->id) as $subscriptions) {
                foreach ($subscriptions as $subscription) {
                    $this->sendNotificationToSubscriber($subscription, $subject, $message, $author->id);
                }
            }
        }
    }

    private function sendNotificationToSubscriber(
        AuthorSubscription $subscription,
        string $subject,
        string $message,
        int $authorId
    ): void {
        foreach ($this->notificationStrategies as $strategy) {
            if ($strategy->canSend($subscription)) {
                $strategy->send($subscription, $subject, $message);
            }
        }
    }

}

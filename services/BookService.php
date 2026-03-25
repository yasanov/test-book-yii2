<?php

declare(strict_types=1);

namespace app\services;

use app\exceptions\NotFoundException;
use app\exceptions\ServiceException;
use app\models\AuthorSubscription;
use app\models\Book;
use app\models\BookForm;
use app\repositories\AuthorSubscriptionRepository;
use app\repositories\BookAuthorRepository;
use app\repositories\BookRepository;
use app\services\notifications\EmailNotificationStrategy;
use app\services\notifications\NotificationStrategyInterface;
use app\services\notifications\SmsNotificationStrategy;
use Throwable;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\IntegrityException;

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
            throw new NotFoundException('РљРЅРёРіР° РЅРµ РЅР°Р№РґРµРЅР°.');
        }

        return $book;
    }

    public function create(BookForm $form): Book
    {
        if (!$form->validate()) {
            $errors = $form->getFirstErrors();
            $errorMessage = !empty($errors) ? reset($errors) : 'РћС€РёР±РєР° РІР°Р»РёРґР°С†РёРё РґР°РЅРЅС‹С… РєРЅРёРіРё.';
            throw new ServiceException($errorMessage);
        }

        $book = new Book();
        $book->loadDefaultValues();
        $this->applyFormToBook($book, $form);
        $this->validateBook($book);

        $uploadedCoverImagePath = null;
        $transaction = Yii::$app->db->beginTransaction();

        try {
            if ($form->coverImageFile !== null) {
                $uploadedCoverImagePath = $this->storageService->uploadFile($form->coverImageFile);
                $book->cover_image = $uploadedCoverImagePath;
            }

            if (!$this->bookRepository->save($book)) {
                throw new ServiceException('РќРµ СѓРґР°Р»РѕСЃСЊ СЃРѕС…СЂР°РЅРёС‚СЊ РєРЅРёРіСѓ.');
            }

            $this->bookAuthorRepository->batchInsert($book->id, $form->authorIds);

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();

            if ($uploadedCoverImagePath !== null) {
                $this->storageService->deleteFile($uploadedCoverImagePath);
            }

            if ($e instanceof IntegrityException) {
                throw $this->mapPersistenceException($e);
            }

            throw $e;
        }

        $bookWithAuthors = $this->bookRepository->findByIdWithAuthors($book->id);
        if ($bookWithAuthors !== null) {
            $this->notifySubscribers($bookWithAuthors);

            return $bookWithAuthors;
        }

        return $book;
    }

    public function update(int $id, BookForm $form): Book
    {
        if (!$form->validate()) {
            $errors = $form->getFirstErrors();
            $errorMessage = !empty($errors) ? reset($errors) : 'РћС€РёР±РєР° РІР°Р»РёРґР°С†РёРё РґР°РЅРЅС‹С… РєРЅРёРіРё.';
            throw new ServiceException($errorMessage);
        }

        $book = $this->getById($id);
        $oldCoverImage = $book->cover_image;
        $newCoverImagePath = null;
        $this->applyFormToBook($book, $form);
        $this->validateBook($book);

        $transaction = Yii::$app->db->beginTransaction();

        try {
            if ($form->coverImageFile !== null) {
                $newCoverImagePath = $this->storageService->uploadFile($form->coverImageFile);
                $book->cover_image = $newCoverImagePath;
            }

            if (!$this->bookRepository->save($book)) {
                throw new ServiceException('РќРµ СѓРґР°Р»РѕСЃСЊ РѕР±РЅРѕРІРёС‚СЊ РєРЅРёРіСѓ.');
            }

            $this->bookAuthorRepository->sync($book->id, $form->authorIds);

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();

            if ($newCoverImagePath !== null) {
                $this->storageService->deleteFile($newCoverImagePath);
            }

            $book->cover_image = $oldCoverImage;

            if ($e instanceof IntegrityException) {
                throw $this->mapPersistenceException($e);
            }

            throw $e;
        }

        if ($newCoverImagePath !== null && $oldCoverImage !== null && $oldCoverImage !== '') {
            $this->storageService->deleteFile($oldCoverImage);
        }

        return $book;
    }

    public function delete(int $id): void
    {
        $book = $this->getById($id);
        $coverImage = $book->cover_image;

        $transaction = Yii::$app->db->beginTransaction();

        try {
            if (!$this->bookRepository->delete($book)) {
                throw new ServiceException('Не удалось удалить книгу.');
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        if ($coverImage !== null && $coverImage !== '') {
            $this->storageService->deleteFile($coverImage);
        }
    }

    private function notifySubscribers(Book $book): void
    {
        if (empty($book->authors)) {
            return;
        }

        $authorsNames = $book->getAuthorsNames();
        $subject = "Новая книга от {$authorsNames}";
        $message = "Новая книга от {$authorsNames}: \"{$book->title}\" ({$book->year})";

        foreach ($this->collectUniqueSubscribers($book) as $notification) {
            $notification['strategy']->send($notification['subscription'], $subject, $message);
        }
    }

    /**
     * @return array<int, array{strategy: NotificationStrategyInterface, subscription: AuthorSubscription}>
     */
    private function collectUniqueSubscribers(Book $book): array
    {
        $notifications = [];

        foreach ($book->authors as $author) {
            foreach ($this->subscriptionRepository->findByAuthorIdBatch($author->id) as $subscriptions) {
                foreach ($subscriptions as $subscription) {
                    foreach ($this->notificationStrategies as $strategy) {
                        if (!$strategy->canSend($subscription)) {
                            continue;
                        }

                        $recipientKey = $this->buildRecipientKey($strategy, $subscription);
                        if (isset($notifications[$recipientKey])) {
                            continue;
                        }

                        $notifications[$recipientKey] = [
                            'strategy' => $strategy,
                            'subscription' => $subscription,
                        ];
                    }
                }
            }
        }

        return array_values($notifications);
    }

    private function buildRecipientKey(NotificationStrategyInterface $strategy, AuthorSubscription $subscription): string
    {
        $email = trim((string)($subscription->email ?? ''));
        $phone = trim((string)($subscription->phone ?? ''));

        if ($strategy instanceof EmailNotificationStrategy) {
            return 'email:' . mb_strtolower($email);
        }

        if ($strategy instanceof SmsNotificationStrategy) {
            return 'sms:' . $phone;
        }

        return get_class($strategy) . ':' . (string)($subscription->id ?? '');
    }

    private function applyFormToBook(Book $book, BookForm $form): void
    {
        $book->title = $form->title;
        $book->year = $form->year;
        $book->isbn = $form->isbn;
        $book->description = $form->description;
    }

    private function validateBook(Book $book): void
    {
        if ($book->validate()) {
            return;
        }

        $errors = $book->getFirstErrors();
        $errorMessage = !empty($errors) ? reset($errors) : 'Ошибка валидации данных книги.';
        throw new ServiceException($errorMessage);
    }

    private function mapPersistenceException(IntegrityException $e): ServiceException
    {
        $message = $e->getMessage();

        if (str_contains($message, 'books.isbn') || str_contains($message, 'Duplicate entry')) {
            return new ServiceException('Книга с таким ISBN уже существует.', 0, $e);
        }

        return new ServiceException('Не удалось сохранить книгу из-за ограничения целостности данных.', 0, $e);
    }
}

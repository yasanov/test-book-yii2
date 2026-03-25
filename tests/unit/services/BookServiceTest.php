<?php

namespace tests\unit\services;

use app\exceptions\ServiceException;
use app\models\Author;
use app\models\AuthorSubscription;
use app\models\Book;
use app\models\BookForm;
use app\repositories\AuthorSubscriptionRepository;
use app\repositories\BookAuthorRepository;
use app\repositories\BookRepository;
use app\services\BookService;
use app\services\notifications\EmailNotificationStrategy;
use app\services\notifications\SmsNotificationStrategy;
use app\services\StorageInterface;

class BookServiceTest extends \Codeception\Test\Unit
{
    public function testCreateThrowsReadableValidationErrorForInvalidForm(): void
    {
        $service = new BookService(
            $this->createMock(BookRepository::class),
            $this->createMock(BookAuthorRepository::class),
            $this->createMock(AuthorSubscriptionRepository::class),
            $this->createMock(StorageInterface::class),
            $this->createMock(SmsNotificationStrategy::class),
            $this->createMock(EmailNotificationStrategy::class),
        );

        $form = new BookForm();

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Название cannot be blank.');

        $service->create($form);
    }

    public function testNotifySubscribersDoesNotDuplicateNotificationsForCoAuthors(): void
    {
        $subscriptionRepository = $this->createMock(AuthorSubscriptionRepository::class);
        $subscriptionRepository
            ->method('findByAuthorIdBatch')
            ->willReturnCallback(function (int $authorId): \Generator {
                $subscription = new class() extends AuthorSubscription {
                    public int $id = 0;
                    public ?string $email = null;
                    public ?string $phone = null;
                };
                $subscription->id = $authorId;
                $subscription->email = 'reader@example.com';
                $subscription->phone = '+79990000000';

                yield [$subscription];
            });

        $smsStrategy = $this->createMock(SmsNotificationStrategy::class);
        $smsStrategy->method('canSend')->willReturn(true);
        $smsStrategy->expects($this->once())->method('send');

        $emailStrategy = $this->createMock(EmailNotificationStrategy::class);
        $emailStrategy->method('canSend')->willReturn(true);
        $emailStrategy->expects($this->once())->method('send');

        $service = new BookService(
            $this->createMock(BookRepository::class),
            $this->createMock(BookAuthorRepository::class),
            $subscriptionRepository,
            $this->createMock(StorageInterface::class),
            $smsStrategy,
            $emailStrategy,
        );

        $firstAuthor = new class() extends Author {
            public int $id = 0;
            public string $full_name = '';
        };
        $firstAuthor->id = 1;
        $firstAuthor->full_name = 'First Author';

        $secondAuthor = new class() extends Author {
            public int $id = 0;
            public string $full_name = '';
        };
        $secondAuthor->id = 2;
        $secondAuthor->full_name = 'Second Author';

        $book = new class() extends Book {
            public string $title = '';
            public int $year = 0;
            public array $authors = [];

            public function getAuthorsNames(): string
            {
                return implode(', ', array_map(
                    static fn(Author $author): string => $author->full_name,
                    $this->authors
                ));
            }
        };
        $book->title = 'Test Book';
        $book->year = 2025;
        $book->authors = [$firstAuthor, $secondAuthor];

        $method = new \ReflectionMethod(BookService::class, 'notifySubscribers');
        $method->setAccessible(true);
        $method->invoke($service, $book);
    }
}

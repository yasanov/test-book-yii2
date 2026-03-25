<?php

namespace tests\unit\services;

use app\exceptions\ServiceException;
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
}

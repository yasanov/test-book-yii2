<?php

declare(strict_types=1);

namespace app\components;

use app\repositories\AuthorRepository;
use app\repositories\AuthorSubscriptionRepository;
use app\repositories\BookAuthorRepository;
use app\repositories\BookRepository;
use app\repositories\ReportRepository;
use app\services\AuthorService;
use app\services\BookCoverImageService;
use app\services\BookService;
use app\services\EmailService;
use app\services\LocalStorageService;
use app\services\notifications\EmailNotificationStrategy;
use app\services\notifications\SmsNotificationStrategy;
use app\services\ReportService;
use app\services\SmsService;
use app\services\StorageInterface;
use app\services\StorageService;
use app\services\SubscriptionService;
use Yii;

class DIBootstrap implements \yii\base\BootstrapInterface
{
    public function bootstrap($app): void
    {
        Yii::$container->setSingletons([
            BookRepository::class => BookRepository::class,
            AuthorRepository::class => AuthorRepository::class,
            AuthorSubscriptionRepository::class => AuthorSubscriptionRepository::class,
            BookAuthorRepository::class => BookAuthorRepository::class,
            ReportRepository::class => ReportRepository::class,
        ]);

        Yii::$container->setDefinitions([
            LocalStorageService::class => LocalStorageService::class,
            StorageService::class => StorageService::class,
            StorageInterface::class => function ($container) use ($app) {
                $driver = $app->params['storage']['driver'] ?? 'local';

                return match ($driver) {
                    's3' => $container->get(StorageService::class),
                    default => $container->get(LocalStorageService::class),
                };
            },
            BookCoverImageService::class => function ($container) {
                return new BookCoverImageService(
                    $container->get(StorageInterface::class)
                );
            },
            SmsService::class => SmsService::class,
            EmailService::class => EmailService::class,
            SmsNotificationStrategy::class => function ($container) {
                return new SmsNotificationStrategy(
                    $container->get(SmsService::class)
                );
            },
            EmailNotificationStrategy::class => function ($container) {
                return new EmailNotificationStrategy(
                    $container->get(EmailService::class)
                );
            },
            BookService::class => function ($container, $params, $config) {
                return new BookService(
                    $container->get(BookRepository::class),
                    $container->get(AuthorRepository::class),
                    $container->get(BookAuthorRepository::class),
                    $container->get(AuthorSubscriptionRepository::class),
                    $container->get(StorageInterface::class),
                    $container->get(SmsNotificationStrategy::class),
                    $container->get(EmailNotificationStrategy::class)
                );
            },
            AuthorService::class => function ($container, $params, $config) {
                return new AuthorService(
                    $container->get(AuthorRepository::class)
                );
            },
            SubscriptionService::class => function ($container, $params, $config) {
                return new SubscriptionService(
                    $container->get(AuthorRepository::class),
                    $container->get(AuthorSubscriptionRepository::class)
                );
            },
            ReportService::class => function ($container, $params, $config) {
                return new ReportService(
                    $container->get(ReportRepository::class)
                );
            },
        ]);
    }
}

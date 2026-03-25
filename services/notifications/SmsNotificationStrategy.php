<?php

declare(strict_types=1);

namespace app\services\notifications;

use app\exceptions\ServiceException;
use app\models\AuthorSubscription;
use app\services\SmsService;
use Yii;

class SmsNotificationStrategy implements NotificationStrategyInterface
{
    public function __construct(
        private readonly SmsService $smsService
    ) {
    }

    public function canSend(AuthorSubscription $subscription): bool
    {
        $phone = trim($subscription->phone ?? '');

        return !empty($phone) && $this->smsService->isConfigured();
    }

    public function send(AuthorSubscription $subscription, string $subject, string $message): void
    {
        $phone = trim($subscription->phone ?? '');
        
        if (empty($phone)) {
            return;
        }

        try {
            $this->smsService->sendSms($phone, $message);
        } catch (ServiceException $e) {
            Yii::error(
                "Ошибка отправки SMS подписчику {$phone} (ID: {$subscription->id}): " . $e->getMessage(),
                'sms'
            );
        }
    }
}

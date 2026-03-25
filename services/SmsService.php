<?php

declare(strict_types=1);

namespace app\services;

use app\exceptions\ServiceException;
use Yii;

class SmsService
{
    private string $apiKey;
    private string $apiUrl;

    public function __construct()
    {
        $smsConfig = Yii::$app->params['sms'] ?? [];
        
        $this->apiKey = $smsConfig['apiKey'] ?? '';
        $this->apiUrl = $smsConfig['apiUrl'] ?? 'https://smspilot.ru/api.php';
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function sendSms(string $phone, string $message): bool
    {
        if (!$this->isConfigured()) {
            throw new ServiceException('SMS API ключ не настроен. Проверьте параметры в config/params.php');
        }

        $phone = $this->normalizePhone($phone);
        
        if (empty($phone)) {
            throw new ServiceException('Некорректный номер телефона');
        }

        try {
            $params = [
                'send' => $message,
                'to' => $phone,
                'apikey' => $this->apiKey,
                'format' => 'json',
            ];

            $url = $this->apiUrl . '?' . http_build_query($params);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new ServiceException('Ошибка отправки SMS: ' . $error);
            }

            if ($httpCode !== 200) {
                throw new ServiceException("Ошибка отправки SMS: HTTP код {$httpCode}");
            }

            $result = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ServiceException('Ошибка парсинга ответа от SMS API');
            }

            if (isset($result['error'])) {
                $errorMessage = $result['error']['description'] ?? 'Неизвестная ошибка';
                throw new ServiceException('Ошибка SMS API: ' . $errorMessage);
            }

            if (isset($result['send'][0]['server_id'])) {
                Yii::info("SMS успешно отправлено на {$phone}, server_id: {$result['send'][0]['server_id']}", 'sms');
                return true;
            }

            throw new ServiceException('Неожиданный формат ответа от SMS API');
        } catch (ServiceException $e) {
            throw $e;
        } catch (\Exception $e) {
            Yii::error('Ошибка отправки SMS: ' . $e->getMessage(), 'sms');
            throw new ServiceException('Ошибка отправки SMS: ' . $e->getMessage());
        }
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^\d]/', '', $phone);
        
        if (strpos($phone, '8') === 0 && strlen($phone) === 11) {
            $phone = '7' . substr($phone, 1);
        }
        
        if (strlen($phone) === 10 && strpos($phone, '7') !== 0) {
            $phone = '7' . $phone;
        }
        
        return $phone;
    }
}

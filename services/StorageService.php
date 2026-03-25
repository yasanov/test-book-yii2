<?php

declare(strict_types=1);

namespace app\services;

use app\exceptions\ServiceException;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Yii;
use yii\web\UploadedFile;

class StorageService implements StorageInterface
{
    private ?S3Client $s3Client = null;
    private string $bucket;
    private string $pathPrefix;

    public function __construct()
    {
        $s3Config = Yii::$app->params['s3'] ?? [];
        
        if (empty($s3Config['bucket']) || empty($s3Config['accessKey']) || empty($s3Config['secretKey'])) {
            throw new ServiceException('S3 конфигурация не настроена. Проверьте параметры в config/params.php');
        }

        $this->bucket = $s3Config['bucket'];
        $this->pathPrefix = $s3Config['pathPrefix'] ?? '';

        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region' => $s3Config['region'] ?? 'ru-central1',
            'endpoint' => $s3Config['endpoint'] ?? 'https://storage.yandexcloud.net',
            'credentials' => [
                'key' => $s3Config['accessKey'],
                'secret' => $s3Config['secretKey'],
            ],
            'use_path_style_endpoint' => true,
            'signature_version' => 'v4',
        ]);

        // Проверяем доступность bucket
        try {
            $this->s3Client->headBucket(['Bucket' => $this->bucket]);
        } catch (AwsException $e) {
            Yii::error('Ошибка доступа к bucket "' . $this->bucket . '": ' . $e->getMessage(), 'storage');
        }
    }

    /**
     * @param UploadedFile $file
     * @param string|null $oldPath
     * @return string
     * @throws ServiceException
     */
    public function uploadFile(UploadedFile $file, ?string $oldPath = null): string
    {
        $fileName = $this->generateFileName($file);
        $key = $this->pathPrefix . $fileName;

        try {
            // Удаляем старый файл, если указан
            if ($oldPath !== null && $oldPath !== '') {
                $this->deleteFile($oldPath);
            }

            // Загружаем файл в S3
            $putObjectParams = [
                'Bucket' => $this->bucket,
                'Key' => $key,
                'Body' => file_get_contents($file->tempName),
                'ContentType' => $file->type ?: 'application/octet-stream',
            ];

            // Для Yandex Object Storage ACL может не поддерживаться или требовать специальных настроек
            // Убираем ACL, если bucket настроен на публичный доступ через bucket policy
            // 'ACL' => 'public-read',

            $result = $this->s3Client->putObject($putObjectParams);

            return $key;
        } catch (AwsException $e) {
            $errorDetails = [
                'Bucket' => $this->bucket,
                'Key' => $key,
                'Error Code' => $e->getAwsErrorCode(),
                'Error Message' => $e->getAwsErrorMessage(),
                'Request ID' => $e->getAwsRequestId(),
            ];
            
            Yii::error('Ошибка загрузки файла в S3: ' . json_encode($errorDetails, JSON_UNESCAPED_UNICODE), 'storage');
            
            $message = 'Ошибка загрузки файла в S3: ' . $e->getAwsErrorMessage();
            if ($e->getAwsErrorCode() === 'AccessDenied') {
                $message .= '. Проверьте права доступа сервисного аккаунта к bucket "' . $this->bucket . '"';
            }
            
            throw new ServiceException($message);
        }
    }

    /**
     * @param string $path
     * @return void
     */
    public function deleteFile(string $path): void
    {
        if (empty($path)) {
            return;
        }

        // Если путь начинается с /, убираем его
        $key = ltrim($path, '/');
        
        // Если путь не содержит префикс, добавляем его
        if (!empty($this->pathPrefix) && strpos($key, $this->pathPrefix) !== 0) {
            $key = $this->pathPrefix . $key;
        }

        try {
            $this->s3Client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);
        } catch (AwsException $e) {
            Yii::error('Ошибка удаления файла из S3: ' . $e->getMessage(), 'storage');
        }
    }

    /**
     * @param string $path
     * @return string|null
     */
    public function getFileUrl(string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        // Если путь начинается с /, убираем его
        $key = ltrim($path, '/');
        
        // Если путь не содержит префикс, добавляем его
        if (!empty($this->pathPrefix) && strpos($key, $this->pathPrefix) !== 0) {
            $key = $this->pathPrefix . $key;
        }

        try {
            return $this->s3Client->getObjectUrl($this->bucket, $key);
        } catch (\Exception $e) {
            Yii::error('Ошибка получения URL файла из S3: ' . $e->getMessage(), 'storage');
            return null;
        }
    }

    /**
     * @param UploadedFile $file
     * @return string
     */
    private function generateFileName(UploadedFile $file): string
    {
        return (string)time() . '_' . Yii::$app->security->generateRandomString(10) . '.' . $file->extension;
    }
}

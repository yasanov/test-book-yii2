<?php

declare(strict_types=1);

namespace app\services;

use app\exceptions\ServiceException;
use Yii;
use yii\web\UploadedFile;

class LocalStorageService implements StorageInterface
{
    private string $basePath;
    private string $webPath;
    private string $subDirectory;

    public function __construct()
    {
        $this->basePath = Yii::getAlias('@webroot/uploads');
        $this->webPath = '/uploads';
        $this->subDirectory = date('Y/m');

        $this->ensureDirectoryExists();
    }

    public function uploadFile(UploadedFile $file, ?string $oldPath = null): string
    {
        if ($file->hasError) {
            throw new ServiceException('Ошибка загрузки файла: файл содержит ошибки');
        }

        if (!file_exists($file->tempName)) {
            throw new ServiceException('Временный файл не найден. Возможно, файл был удален.');
        }

        if ($oldPath !== null && $oldPath !== '') {
            $this->deleteFile($oldPath);
        }

        $fileName = $this->generateFileName($file);
        $relativePath = $this->subDirectory . '/' . $fileName;
        $fullPath = $this->basePath . '/' . $relativePath;

        $directory = dirname($fullPath);
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                $error = error_get_last();
                $errorMessage = $error ? $error['message'] : 'Неизвестная ошибка';
                throw new ServiceException(
                    'Не удалось создать директорию для загрузки файла: ' . $directory . '. ' . $errorMessage
                );
            }
            @chmod($directory, 0755);
        }

        if (!$file->saveAs($fullPath)) {
            throw new ServiceException('Не удалось сохранить файл: ' . $fullPath);
        }

        return $relativePath;
    }

    public function deleteFile(string $path): void
    {
        if (empty($path)) {
            return;
        }

        $fullPath = $this->basePath . '/' . ltrim($path, '/');

        $realBasePath = realpath($this->basePath);
        $realFilePath = realpath($fullPath);

        if ($realFilePath === false || strpos($realFilePath, $realBasePath) !== 0) {
            Yii::warning('Попытка удалить файл вне разрешенной директории: ' . $path, 'storage');
            return;
        }

        if (file_exists($fullPath) && is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    public function getFileUrl(string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        $fullPath = $this->basePath . '/' . ltrim($path, '/');

        if (!file_exists($fullPath) || !is_file($fullPath)) {
            Yii::warning('Файл не найден: ' . $path, 'storage');
            return null;
        }

        $realBasePath = realpath($this->basePath);
        $realFilePath = realpath($fullPath);

        if ($realFilePath === false || strpos($realFilePath, $realBasePath) !== 0) {
            Yii::warning('Попытка получить URL файла вне разрешенной директории: ' . $path, 'storage');
            return null;
        }

        return $this->webPath . '/' . ltrim($path, '/');
    }

    private function generateFileName(UploadedFile $file): string
    {
        $extension = $file->extension ?: 'bin';
        $randomString = Yii::$app->security->generateRandomString(16);
        $timestamp = time();

        return sprintf('%d_%s.%s', $timestamp, $randomString, $extension);
    }

    private function ensureDirectoryExists(): void
    {
        if (!is_dir($this->basePath)) {
            if (!mkdir($this->basePath, 0755, true) && !is_dir($this->basePath)) {
                $error = error_get_last();
                $errorMessage = $error ? $error['message'] : 'Неизвестная ошибка';
                throw new ServiceException(
                    'Не удалось создать директорию для загрузки файлов: ' . $this->basePath . '. ' . $errorMessage
                );
            }
            @chmod($this->basePath, 0755);
        }
    }
}

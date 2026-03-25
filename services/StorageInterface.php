<?php

declare(strict_types=1);

namespace app\services;

use yii\web\UploadedFile;

interface StorageInterface
{
    public function uploadFile(UploadedFile $file, ?string $oldPath = null): string;

    public function deleteFile(string $path): void;

    public function getFileUrl(string $path): ?string;
}

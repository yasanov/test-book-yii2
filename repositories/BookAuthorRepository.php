<?php

declare(strict_types=1);

namespace app\repositories;

use app\exceptions\RepositoryException;
use Yii;

class BookAuthorRepository
{
    public function getAuthorIdsByBookId(int $bookId): array
    {
        return array_map(
            'intval',
            Yii::$app->db->createCommand(
                'SELECT [[author_id]] FROM {{%book_author}} WHERE [[book_id]] = :bookId',
                [':bookId' => $bookId]
            )->queryColumn()
        );
    }

    public function deleteByBookId(int $bookId): void
    {
        $result = Yii::$app->db->createCommand()
            ->delete('{{%book_author}}', ['book_id' => $bookId])
            ->execute();

        if ($result === false) {
            throw new RepositoryException('Не удалось удалить связи книги с авторами.');
        }
    }

    public function deleteByBookIdAndAuthorIds(int $bookId, array $authorIds): void
    {
        $authorIds = $this->normalizeAuthorIds($authorIds);
        if (empty($authorIds)) {
            return;
        }

        $result = Yii::$app->db->createCommand()
            ->delete('{{%book_author}}', [
                'book_id' => $bookId,
                'author_id' => $authorIds,
            ])
            ->execute();

        if ($result === false) {
            throw new RepositoryException('Не удалось удалить часть связей книги с авторами.');
        }
    }

    public function normalizeAuthorIds(array $authorIds): array
    {
        $validAuthorIds = [];
        foreach ($authorIds as $authorId) {
            $authorId = (int)$authorId;
            if ($authorId > 0) {
                $validAuthorIds[] = $authorId;
            }
        }

        $validAuthorIds = array_values(array_unique($validAuthorIds));
        sort($validAuthorIds);

        return $validAuthorIds;
    }

    public function batchInsert(int $bookId, array $authorIds): void
    {
        $validAuthorIds = $this->normalizeAuthorIds($authorIds);
        if (empty($validAuthorIds)) {
            return;
        }

        $rows = [];
        foreach ($validAuthorIds as $authorId) {
            $rows[] = [$bookId, $authorId];
        }

        $result = Yii::$app->db->createCommand()
            ->batchInsert('{{%book_author}}', ['book_id', 'author_id'], $rows)
            ->execute();

        if ($result === false) {
            throw new RepositoryException('Не удалось сохранить связи книги с авторами.');
        }
    }

    public function replace(int $bookId, array $authorIds): void
    {
        $this->deleteByBookId($bookId);
        $this->batchInsert($bookId, $authorIds);
    }

    public function sync(int $bookId, array $authorIds): void
    {
        $currentAuthorIds = $this->getAuthorIdsByBookId($bookId);
        $currentAuthorIds = array_values(array_unique($currentAuthorIds));
        sort($currentAuthorIds);

        $targetAuthorIds = $this->normalizeAuthorIds($authorIds);

        $authorIdsToDelete = array_values(array_diff($currentAuthorIds, $targetAuthorIds));
        $authorIdsToInsert = array_values(array_diff($targetAuthorIds, $currentAuthorIds));

        $this->deleteByBookIdAndAuthorIds($bookId, $authorIdsToDelete);
        $this->batchInsert($bookId, $authorIdsToInsert);
    }
}

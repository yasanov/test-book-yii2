<?php

declare(strict_types=1);

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

class Book extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%books}}';
    }

    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules(): array
    {
        return [
            [['title', 'year'], 'required'],
            [['title'], 'string', 'max' => 255],
            [['title'], 'trim'],
            [['year'], 'integer', 'min' => 1000, 'max' => 9999],
            [['description'], 'string'],
            [['isbn'], 'string', 'max' => 20],
            [['isbn'], 'match', 'pattern' => '/^[0-9\-X]+$/', 'message' => 'ISBN должен содержать только цифры, дефисы и X'],
            [['isbn'], 'unique', 'skipOnEmpty' => true, 'message' => 'Книга с таким ISBN уже существует.'],
            [['cover_image'], 'string', 'max' => 500],
            [['created_at', 'updated_at'], 'integer'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'title' => 'Название',
            'year' => 'Год выпуска',
            'description' => 'Описание',
            'isbn' => 'ISBN',
            'cover_image' => 'Обложка',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлен',
        ];
    }

    public function getAuthors(): ActiveQuery
    {
        return $this->hasMany(Author::class, ['id' => 'author_id'])
            ->viaTable('{{%book_author}}', ['book_id' => 'id']);
    }

    public function getAuthorsNames(): string
    {
        $authors = $this->authors;
        if (empty($authors)) {
            return '';
        }

        return implode(', ', array_map(function (Author $author): string {
            return $author->full_name;
        }, $authors));
    }
}

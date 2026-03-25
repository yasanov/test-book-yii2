<?php

declare(strict_types=1);

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

class AuthorSubscription extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%author_subscriptions}}';
    }

    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['author_id'], 'required'],
            [['author_id', 'created_at'], 'integer'],
            [['email', 'phone'], 'filter', 'filter' => static function ($value) {
                if ($value === null) {
                    return null;
                }

                $value = trim((string)$value);

                return $value === '' ? null : $value;
            }],
            [['email'], 'string', 'max' => 255],
            [['email'], 'email', 'skipOnEmpty' => true],
            [['phone'], 'string', 'max' => 20],
            [['phone'], 'match', 'pattern' => '/^[\d\s\-\+\(\)]+$/', 'message' => 'Некорректный формат телефона', 'skipOnEmpty' => true],
            [['email', 'phone'], 'validateAtLeastOne', 'skipOnEmpty' => false],
            [['author_id'], 'exist', 'skipOnError' => true, 'targetClass' => Author::class, 'targetAttribute' => ['author_id' => 'id']],
        ];
    }

    public function validateAtLeastOne(string $attribute, array $params): void
    {
        $email = $this->email;
        $phone = $this->phone;

        if ($email === null && $phone === null) {
            $this->addError($attribute, 'Необходимо указать хотя бы одно из полей: Email или Телефон.');
        }
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'author_id' => 'Автор',
            'email' => 'Email',
            'phone' => 'Телефон',
            'created_at' => 'Дата подписки',
        ];
    }

    public function getAuthor(): ActiveQuery
    {
        return $this->hasOne(Author::class, ['id' => 'author_id']);
    }
}

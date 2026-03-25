<?php

declare(strict_types=1);

namespace app\models;

use yii\base\Model;
use yii\web\UploadedFile;

class BookForm extends Model
{
    public string $title = '';
    public ?int $year = null;
    public ?string $isbn = null;
    public ?string $description = null;
    public array $authorIds = [];
    public ?UploadedFile $coverImageFile = null;
    public ?string $currentCoverImagePath = null;

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
            [['authorIds'], 'default', 'value' => []],
            [['authorIds'], 'each', 'rule' => ['integer', 'min' => 1]],
            [['coverImageFile'], 'image', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg, gif', 'maxSize' => 5 * 1024 * 1024],
            [['currentCoverImagePath'], 'string', 'max' => 500],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'title' => 'Название',
            'year' => 'Год выпуска',
            'description' => 'Описание',
            'isbn' => 'ISBN',
            'coverImageFile' => 'Файл обложки',
        ];
    }

    public function load($data, $formName = null): bool
    {
        $formName = $formName ?: $this->formName();

        if (isset($data[$formName]) && is_array($data[$formName])) {
            unset($data[$formName]['coverImageFile']);

            if (!isset($data[$formName]['authorIds']) && isset($data['author_ids']) && is_array($data['author_ids'])) {
                $data[$formName]['authorIds'] = $data['author_ids'];
            }
        }

        if (isset($data['coverImageFile'])) {
            unset($data['coverImageFile']);
        }

        return parent::load($data, $formName);
    }

    public static function fromBook(Book $book): self
    {
        $form = new self();
        $form->title = $book->title;
        $form->year = $book->year;
        $form->isbn = $book->isbn;
        $form->description = $book->description;
        $form->currentCoverImagePath = $book->cover_image;
        $form->authorIds = array_map(static fn(Author $author): int => (int)$author->id, $book->authors);

        return $form;
    }
}

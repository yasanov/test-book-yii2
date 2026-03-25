<?php

namespace tests\unit\models;

use app\models\BookForm;

class BookFormTest extends \Codeception\Test\Unit
{
    public function testLoadMapsLegacyAuthorIdsAndSkipsCoverImageFile(): void
    {
        $form = new BookForm();

        $result = $form->load([
            'BookForm' => [
                'title' => 'Book title',
                'year' => '2024',
                'isbn' => '123-456',
                'description' => 'Description',
                'coverImageFile' => '',
            ],
            'author_ids' => ['3', '1', '3'],
        ]);

        verify($result)->true();
        verify($form->title)->equals('Book title');
        verify($form->year)->equals(2024);
        verify($form->isbn)->equals('123-456');
        verify($form->description)->equals('Description');
        verify($form->authorIds)->equals(['3', '1', '3']);
        verify($form->coverImageFile)->null();
    }
}

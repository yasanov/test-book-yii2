<?php

/** @var yii\web\View $this */
/** @var app\models\Book $book */
/** @var app\models\BookForm $model */
/** @var app\services\BookCoverImageService $bookCoverImageService */

use yii\bootstrap5\Html;

$this->title = 'Редактировать книгу: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Книги', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $book->title, 'url' => ['view', 'id' => $book->id]];
$this->params['breadcrumbs'][] = 'Редактировать';
?>
<div class="book-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'bookCoverImageService' => $bookCoverImageService,
    ]) ?>

</div>

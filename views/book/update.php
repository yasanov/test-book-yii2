<?php

/** @var yii\web\View $this */
/** @var app\models\Book $model */
/** @var array $selectedAuthorIds */
/** @var app\services\BookCoverImageService $bookCoverImageService */

use yii\bootstrap5\Html;

$this->title = 'Редактировать книгу: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Книги', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Редактировать';
?>
<div class="book-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'selectedAuthorIds' => $selectedAuthorIds,
        'bookCoverImageService' => $bookCoverImageService,
    ]) ?>

</div>

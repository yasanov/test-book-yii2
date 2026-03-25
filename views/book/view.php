<?php

/** @var yii\web\View $this */
/** @var app\models\Book $model */
/** @var app\services\BookCoverImageService $bookCoverImageService */

use yii\bootstrap5\Html;

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Книги', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="book-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?php if (!Yii::$app->user->isGuest && Yii::$app->user->can('updateBook')): ?>
            <?= Html::a('Редактировать', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?php endif; ?>
        <?php if (!Yii::$app->user->isGuest && Yii::$app->user->can('deleteBook')): ?>
            <?= Html::a('Удалить', ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => 'Вы уверены, что хотите удалить эту книгу?',
                    'method' => 'post',
                ],
            ]) ?>
        <?php endif; ?>
    </p>

    <div class="row">
        <div class="col-md-3">
            <?php $coverImageUrl = $bookCoverImageService->getUrl($model); ?>
            <?php if ($coverImageUrl !== null): ?>
                <?= Html::img($coverImageUrl, ['class' => 'img-fluid', 'alt' => $model->title]) ?>
            <?php else: ?>
                <div class="text-muted">Нет обложки</div>
            <?php endif; ?>
        </div>
        <div class="col-md-9">
            <table class="table table-striped table-bordered">
                <tr>
                    <th>Название</th>
                    <td><?= Html::encode($model->title) ?></td>
                </tr>
                <tr>
                    <th>Год выпуска</th>
                    <td><?= Html::encode($model->year) ?></td>
                </tr>
                <?php if ($model->isbn): ?>
                <tr>
                    <th>ISBN</th>
                    <td><?= Html::encode($model->isbn) ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th>Авторы</th>
                    <td><?= Html::encode($model->getAuthorsNames()) ?></td>
                </tr>
                <?php if ($model->description): ?>
                <tr>
                    <th>Описание</th>
                    <td><?= nl2br(Html::encode($model->description)) ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

</div>

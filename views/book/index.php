<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var app\services\BookCoverImageService $bookCoverImageService */

use yii\bootstrap5\Html;
use yii\grid\GridView;

$this->title = 'Книги';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="book-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?php if (!Yii::$app->user->isGuest && Yii::$app->user->can('createBook')): ?>
            <?= Html::a('Добавить книгу', ['create'], ['class' => 'btn btn-success']) ?>
        <?php endif; ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'attribute' => 'cover_image',
                'format' => 'raw',
                'value' => function ($model) use ($bookCoverImageService) {
                    if ($model->cover_image) {
                        $coverImageUrl = $bookCoverImageService->getUrl($model);
                        if ($coverImageUrl !== null) {
                            return Html::img($coverImageUrl, ['style' => 'max-width: 100px; max-height: 100px;']);
                        }
                    }
                    return 'Нет обложки';
                },
            ],
            'title',
            'year',
            'isbn',
            [
                'attribute' => 'authors',
                'value' => function ($model) {
                    return $model->getAuthorsNames();
                },
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {update} {delete}',
                'visibleButtons' => [
                    'update' => function ($model, $key, $index) {
                        return !Yii::$app->user->isGuest && Yii::$app->user->can('updateBook');
                    },
                    'delete' => function ($model, $key, $index) {
                        return !Yii::$app->user->isGuest && Yii::$app->user->can('deleteBook');
                    },
                ],
            ],
        ],
    ]); ?>

</div>

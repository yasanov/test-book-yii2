<?php

/** @var yii\web\View $this */
/** @var app\models\Book $model */
/** @var yii\widgets\ActiveForm $form */
/** @var array $selectedAuthorIds */
/** @var app\services\BookCoverImageService $bookCoverImageService */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

?>

<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

<?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>

<?= $form->field($model, 'year')->textInput(['type' => 'number', 'min' => 1000, 'max' => 9999]) ?>

<?= $form->field($model, 'isbn')->textInput(['maxlength' => true]) ?>

<?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>

<?= $form->field($model, 'coverImageFile')->fileInput(['accept' => 'image/*']) ?>

<?php $coverImageUrl = $bookCoverImageService->getUrl($model); ?>
<?php if ($coverImageUrl !== null): ?>
    <div class="form-group">
        <?= Html::img($coverImageUrl, ['style' => 'max-width: 200px; max-height: 200px;']) ?>
        <p class="text-muted">Текущая обложка</p>
    </div>
<?php endif; ?>

<?php
use app\assets\BookFormAsset;
use yii\helpers\Url;

BookFormAsset::register($this);
?>

<div class="form-group">
    <label class="control-label">Авторы</label>
    <div id="authors-list"
         data-selected-ids="<?= htmlspecialchars(json_encode($selectedAuthorIds ?? []), ENT_QUOTES, 'UTF-8') ?>"
         data-list-url="<?= htmlspecialchars(Url::to(['author/list']), ENT_QUOTES, 'UTF-8') ?>">
        <p class="text-muted">Загрузка авторов...</p>
    </div>
</div>

<div class="form-group">
    <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
</div>

<?php ActiveForm::end(); ?>

<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use app\exceptions\NotFoundException;
use app\models\Book;
use app\services\BookCoverImageService;
use app\services\BookService;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\validators\ImageValidator;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;

class BookController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly BookService $bookService,
        private readonly BookCoverImageService $bookCoverImageService,
        $config = []
    ) {
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['index', 'view', 'create', 'update', 'delete'],
                'rules' => [
                    [
                        'actions' => ['index', 'view'],
                        'allow' => true,
                        'roles' => ['?', '@'],
                    ],
                    [
                        'actions' => ['create'],
                        'allow' => true,
                        'roles' => ['createBook'],
                    ],
                    [
                        'actions' => ['update'],
                        'allow' => true,
                        'roles' => ['updateBook'],
                    ],
                    [
                        'actions' => ['delete'],
                        'allow' => true,
                        'roles' => ['deleteBook'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $dataProvider = $this->bookService->getDataProvider();

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'bookCoverImageService' => $this->bookCoverImageService,
        ]);
    }

    public function actionView(int $id): string
    {
        $model = $this->bookService->getById($id);

        return $this->render('view', [
            'model' => $model,
            'bookCoverImageService' => $this->bookCoverImageService,
        ]);
    }

    public function actionCreate(): string|Response
    {
        $model = new Book();
        $model->loadDefaultValues();

        if (Yii::$app->request->post()) {
            try {
                $coverImageFile = UploadedFile::getInstance($model, 'coverImageFile');
                $this->validateImageFile($coverImageFile);
                
                $authorIds = Yii::$app->request->post('author_ids', []);
                
                $book = $this->bookService->create(
                    Yii::$app->request->post(),
                    $authorIds,
                    $coverImageFile
                );
                
                Yii::$app->session->setFlash('success', 'Книга успешно создана.');

                return $this->redirect(['view', 'id' => $book->id]);
            } catch (\Exception $e) {
                Yii::$app->session->setFlash('error', $e->getMessage());
                $model->load(Yii::$app->request->post());
            }
        }

        return $this->render('create', [
            'model' => $model,
            'bookCoverImageService' => $this->bookCoverImageService,
        ]);
    }

    public function actionUpdate(int $id): string|Response
    {
        $book = $this->bookService->getById($id);

        if (Yii::$app->request->post()) {
            try {
                $coverImageFile = UploadedFile::getInstance($book, 'coverImageFile');
                $this->validateImageFile($coverImageFile);
                
                $authorIds = Yii::$app->request->post('author_ids', []);
                
                $book = $this->bookService->update(
                    $id,
                    Yii::$app->request->post(),
                    $authorIds,
                    $coverImageFile
                );
                
                Yii::$app->session->setFlash('success', 'Книга успешно обновлена.');

                return $this->redirect(['view', 'id' => $book->id]);
            } catch (\Exception $e) {
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        $selectedAuthorIds = $this->bookService->getSelectedAuthorIds($book);

        return $this->render('update', [
            'model' => $book,
            'selectedAuthorIds' => $selectedAuthorIds,
            'bookCoverImageService' => $this->bookCoverImageService,
        ]);
    }

    public function actionDelete(int $id): Response
    {
        try {
            $this->bookService->delete($id);
            Yii::$app->session->setFlash('success', 'Книга успешно удалена.');
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirect(['index']);
    }

    private function validateImageFile(?UploadedFile $file): void
    {
        if ($file === null) {
            return;
        }

        $model = new Book();
        $model->coverImageFile = $file;

        if (!$model->validate(['coverImageFile'])) {
            $errors = $model->getErrors('coverImageFile');
            $errorMessage = !empty($errors) ? implode(', ', $errors) : 'Неизвестная ошибка валидации файла';
            throw new \yii\base\Exception('Ошибка валидации файла: ' . $errorMessage);
        }
    }
}

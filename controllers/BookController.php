<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\BookForm;
use app\services\BookCoverImageService;
use app\services\BookService;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
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
        $form = new BookForm();

        if ($form->load(Yii::$app->request->post())) {
            try {
                $form->coverImageFile = UploadedFile::getInstance($form, 'coverImageFile');
                $book = $this->bookService->create($form);

                Yii::$app->session->setFlash('success', 'Книга успешно создана.');

                return $this->redirect(['view', 'id' => $book->id]);
            } catch (\Exception $e) {
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('create', [
            'model' => $form,
            'bookCoverImageService' => $this->bookCoverImageService,
        ]);
    }

    public function actionUpdate(int $id): string|Response
    {
        $book = $this->bookService->getById($id);
        $form = BookForm::fromBook($book);

        if ($form->load(Yii::$app->request->post())) {
            try {
                $form->currentCoverImagePath = $book->cover_image;
                $form->coverImageFile = UploadedFile::getInstance($form, 'coverImageFile');
                $book = $this->bookService->update($id, $form);

                Yii::$app->session->setFlash('success', 'Книга успешно обновлена.');

                return $this->redirect(['view', 'id' => $book->id]);
            } catch (\Exception $e) {
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('update', [
            'book' => $book,
            'model' => $form,
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
}

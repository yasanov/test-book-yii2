<?php

declare(strict_types=1);

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use yii\rbac\DbManager;

class RbacController extends Controller
{
    public function actionInit(): int
    {
        /** @var DbManager $auth */
        $auth = Yii::$app->authManager;

        $auth->removeAll();

        $viewBook = $auth->createPermission('viewBook');
        $viewBook->description = 'View books';
        $auth->add($viewBook);

        $createBook = $auth->createPermission('createBook');
        $createBook->description = 'Create books';
        $auth->add($createBook);

        $updateBook = $auth->createPermission('updateBook');
        $updateBook->description = 'Update books';
        $auth->add($updateBook);

        $deleteBook = $auth->createPermission('deleteBook');
        $deleteBook->description = 'Delete books';
        $auth->add($deleteBook);

        $viewAuthor = $auth->createPermission('viewAuthor');
        $viewAuthor->description = 'View authors';
        $auth->add($viewAuthor);

        $createAuthor = $auth->createPermission('createAuthor');
        $createAuthor->description = 'Create authors';
        $auth->add($createAuthor);

        $updateAuthor = $auth->createPermission('updateAuthor');
        $updateAuthor->description = 'Update authors';
        $auth->add($updateAuthor);

        $deleteAuthor = $auth->createPermission('deleteAuthor');
        $deleteAuthor->description = 'Delete authors';
        $auth->add($deleteAuthor);

        $subscribeToAuthor = $auth->createPermission('subscribeToAuthor');
        $subscribeToAuthor->description = 'Subscribe to authors';
        $auth->add($subscribeToAuthor);

        $viewReport = $auth->createPermission('viewReport');
        $viewReport->description = 'View top authors report';
        $auth->add($viewReport);

        $guest = $auth->createRole('guest');
        $guest->description = 'Guest';
        $auth->add($guest);
        $auth->addChild($guest, $viewBook);
        $auth->addChild($guest, $viewAuthor);
        $auth->addChild($guest, $subscribeToAuthor);
        $auth->addChild($guest, $viewReport);

        $user = $auth->createRole('user');
        $user->description = 'Authenticated user';
        $auth->add($user);
        $auth->addChild($user, $guest);
        $auth->addChild($user, $createBook);
        $auth->addChild($user, $updateBook);
        $auth->addChild($user, $deleteBook);
        $auth->addChild($user, $createAuthor);
        $auth->addChild($user, $updateAuthor);
        $auth->addChild($user, $deleteAuthor);

        $admin = $auth->createRole('admin');
        $admin->description = 'Administrator';
        $auth->add($admin);
        $auth->addChild($admin, $user);

        $this->stdout("RBAC initialized successfully.\n", Console::FG_GREEN);
        $this->stdout("Created roles: guest, user, admin\n");
        $this->stdout("Created CRUD permissions for books and authors.\n");

        return ExitCode::OK;
    }
}

<?php

namespace tests\unit\models;

use app\models\LoginForm;
use app\models\User;

class LoginFormTest extends \Codeception\Test\Unit
{
    private function makeUser(string $username, string $password): User
    {
        $user = new class() extends User {
            public int $id = 0;
            public string $username = '';
            public string $email = '';
            public string $password_hash = '';
            public string $auth_key = '';
            public string $access_token = '';
            public int $created_at = 0;
            public int $updated_at = 0;
        };
        $user->id = 100;
        $user->username = $username;
        $user->setPassword($password);
        $user->auth_key = 'test100key';

        return $user;
    }

    private function makeLoginForm(array $attributes, ?User $user): LoginForm
    {
        return new class($attributes, $user) extends LoginForm {
            public function __construct(array $config, private ?User $testUser)
            {
                parent::__construct($config);
            }

            public function getUser(): ?User
            {
                return $this->testUser;
            }
        };
    }

    protected function _after()
    {
        \Yii::$app->user->logout();
    }

    public function testLoginNoUser(): void
    {
        $model = $this->makeLoginForm([
            'username' => 'not_existing_username',
            'password' => 'not_existing_password',
        ], null);

        verify($model->login())->false();
        verify(\Yii::$app->user->isGuest)->true();
    }

    public function testLoginWrongPassword(): void
    {
        $model = $this->makeLoginForm([
            'username' => 'demo',
            'password' => 'wrong_password',
        ], $this->makeUser('demo', 'demo'));

        verify($model->login())->false();
        verify(\Yii::$app->user->isGuest)->true();
        verify($model->errors)->arrayHasKey('password');
    }

    public function testLoginCorrect(): void
    {
        $model = $this->makeLoginForm([
            'username' => 'demo',
            'password' => 'demo',
        ], $this->makeUser('demo', 'demo'));

        verify($model->login())->true();
        verify(\Yii::$app->user->isGuest)->false();
        verify($model->errors)->arrayHasNotKey('password');
    }
}

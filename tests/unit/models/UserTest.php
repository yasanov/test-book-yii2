<?php

namespace tests\unit\models;

use app\models\User;

class UserTest extends \Codeception\Test\Unit
{
    private function makeUser(): User
    {
        return new class() extends User {
            public int $id = 0;
            public string $username = '';
            public string $email = '';
            public string $password_hash = '';
            public string $auth_key = '';
            public string $access_token = '';
            public int $created_at = 0;
            public int $updated_at = 0;
        };
    }

    public function testValidateAuthKey(): void
    {
        $user = $this->makeUser();
        $user->auth_key = 'test100key';

        verify($user->validateAuthKey('test100key'))->true();
        verify($user->validateAuthKey('wrong-key'))->false();
    }

    public function testSetPasswordAndValidatePassword(): void
    {
        $user = $this->makeUser();
        $user->setPassword('admin');

        verify($user->validatePassword('admin'))->true();
        verify($user->validatePassword('123456'))->false();
    }

    public function testGenerateAccessToken(): void
    {
        $user = $this->makeUser();
        $user->generateAccessToken();

        verify($user->access_token)->notEmpty();
        verify(is_string($user->access_token))->true();
    }
}

<?php

class LoginFormCest
{
    private function skipIfDbDriverUnavailable(\FunctionalTester $I): void
    {
        if (!extension_loaded('pdo_mysql')) {
            $I->markTestSkipped('Functional login scenarios require pdo_mysql in the current test environment.');
        }
    }

    public function _before(\FunctionalTester $I)
    {
        $I->amOnRoute('site/login');
    }

    public function openLoginPage(\FunctionalTester $I)
    {
        $I->see('Login', 'h1');
    }

    public function internalLoginById(\FunctionalTester $I)
    {
        $this->skipIfDbDriverUnavailable($I);

        $I->amLoggedInAs(100);
        $I->amOnPage('/');
        $I->see('Logout (admin)');
    }

    public function internalLoginByInstance(\FunctionalTester $I)
    {
        $this->skipIfDbDriverUnavailable($I);

        $I->amLoggedInAs(\app\models\User::findByUsername('admin'));
        $I->amOnPage('/');
        $I->see('Logout (admin)');
    }

    public function loginWithEmptyCredentials(\FunctionalTester $I)
    {
        $I->submitForm('#login-form', []);
        $I->expectTo('see validations errors');
        $I->see('Username cannot be blank.');
        $I->see('Password cannot be blank.');
    }

    public function loginWithWrongCredentials(\FunctionalTester $I)
    {
        $this->skipIfDbDriverUnavailable($I);

        $I->submitForm('#login-form', [
            'LoginForm[username]' => 'admin',
            'LoginForm[password]' => 'wrong',
        ]);
        $I->expectTo('see validations errors');
        $I->see('Incorrect username or password.');
    }

    public function loginSuccessfully(\FunctionalTester $I)
    {
        $this->skipIfDbDriverUnavailable($I);

        $I->submitForm('#login-form', [
            'LoginForm[username]' => 'admin',
            'LoginForm[password]' => 'admin',
        ]);
        $I->see('Logout (admin)');
        $I->dontSeeElement('form#login-form');
    }
}

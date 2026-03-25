<?php

use yii\db\Migration;
use yii\rbac\DbManager;
use yii\rbac\Permission;
use yii\rbac\Role;

/**
 * Seeds initial RBAC data and default users.
 */
class m260325_150000_seed_initial_auth_data extends Migration
{
    private const ADMIN_USERNAME = 'admin';
    private const ADMIN_EMAIL = 'admin@example.com';
    private const ADMIN_PASSWORD = 'admin';

    private const USER_USERNAME = 'user';
    private const USER_EMAIL = 'user@example.com';
    private const USER_PASSWORD = 'user';

    public function safeUp()
    {
        /** @var DbManager $auth */
        $auth = Yii::$app->authManager;
        $timestamp = time();

        $permissions = $this->createPermissions($auth);
        $roles = $this->createRoles($auth, $permissions);

        $adminId = $this->upsertUser(
            self::ADMIN_USERNAME,
            self::ADMIN_EMAIL,
            self::ADMIN_PASSWORD,
            $timestamp
        );
        $userId = $this->upsertUser(
            self::USER_USERNAME,
            self::USER_EMAIL,
            self::USER_PASSWORD,
            $timestamp
        );

        $this->assignRole($auth, $roles['admin'], $adminId);
        $this->assignRole($auth, $roles['user'], $userId);
    }

    public function safeDown()
    {
        /** @var DbManager $auth */
        $auth = Yii::$app->authManager;

        $adminId = $this->findUserId(self::ADMIN_USERNAME, self::ADMIN_EMAIL);
        if ($adminId !== null) {
            $auth->revokeAll($adminId);
        }

        $userId = $this->findUserId(self::USER_USERNAME, self::USER_EMAIL);
        if ($userId !== null) {
            $auth->revokeAll($userId);
        }

        $this->delete('{{%users}}', [
            'username' => self::ADMIN_USERNAME,
            'email' => self::ADMIN_EMAIL,
        ]);
        $this->delete('{{%users}}', [
            'username' => self::USER_USERNAME,
            'email' => self::USER_EMAIL,
        ]);

        foreach (['admin', 'user', 'guest'] as $roleName) {
            $role = $auth->getRole($roleName);
            if ($role !== null) {
                $auth->remove($role);
            }
        }

        foreach ([
            'viewBook',
            'createBook',
            'updateBook',
            'deleteBook',
            'viewAuthor',
            'createAuthor',
            'updateAuthor',
            'deleteAuthor',
            'subscribeToAuthor',
            'viewReport',
        ] as $permissionName) {
            $permission = $auth->getPermission($permissionName);
            if ($permission !== null) {
                $auth->remove($permission);
            }
        }
    }

    /**
     * @return array<string, Permission>
     */
    private function createPermissions(DbManager $auth): array
    {
        $definitions = [
            'viewBook' => 'View books',
            'createBook' => 'Create books',
            'updateBook' => 'Update books',
            'deleteBook' => 'Delete books',
            'viewAuthor' => 'View authors',
            'createAuthor' => 'Create authors',
            'updateAuthor' => 'Update authors',
            'deleteAuthor' => 'Delete authors',
            'subscribeToAuthor' => 'Subscribe to authors',
            'viewReport' => 'View top authors report',
        ];

        $permissions = [];
        foreach ($definitions as $name => $description) {
            $permission = $auth->getPermission($name);
            if ($permission === null) {
                $permission = $auth->createPermission($name);
                $permission->description = $description;
                $auth->add($permission);
            }

            $permissions[$name] = $permission;
        }

        return $permissions;
    }

    /**
     * @param array<string, Permission> $permissions
     * @return array<string, Role>
     */
    private function createRoles(DbManager $auth, array $permissions): array
    {
        $guest = $auth->getRole('guest');
        if ($guest === null) {
            $guest = $auth->createRole('guest');
            $guest->description = 'Guest';
            $auth->add($guest);
        }

        foreach (['viewBook', 'viewAuthor', 'subscribeToAuthor', 'viewReport'] as $permissionName) {
            if (!$auth->hasChild($guest, $permissions[$permissionName])) {
                $auth->addChild($guest, $permissions[$permissionName]);
            }
        }

        $user = $auth->getRole('user');
        if ($user === null) {
            $user = $auth->createRole('user');
            $user->description = 'Authenticated user';
            $auth->add($user);
        }

        if (!$auth->hasChild($user, $guest)) {
            $auth->addChild($user, $guest);
        }

        foreach (['createBook', 'updateBook', 'deleteBook', 'createAuthor', 'updateAuthor', 'deleteAuthor'] as $permissionName) {
            if (!$auth->hasChild($user, $permissions[$permissionName])) {
                $auth->addChild($user, $permissions[$permissionName]);
            }
        }

        $admin = $auth->getRole('admin');
        if ($admin === null) {
            $admin = $auth->createRole('admin');
            $admin->description = 'Administrator';
            $auth->add($admin);
        }

        if (!$auth->hasChild($admin, $user)) {
            $auth->addChild($admin, $user);
        }

        return [
            'guest' => $guest,
            'user' => $user,
            'admin' => $admin,
        ];
    }

    private function upsertUser(string $username, string $email, string $password, int $timestamp): int
    {
        $existingId = $this->findUserId($username, $email);
        if ($existingId !== null) {
            return $existingId;
        }

        $this->insert('{{%users}}', [
            'username' => $username,
            'email' => $email,
            'password_hash' => Yii::$app->security->generatePasswordHash($password),
            'auth_key' => Yii::$app->security->generateRandomString(),
            'access_token' => Yii::$app->security->generateRandomString(),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        return $this->findUserId($username, $email);
    }

    private function assignRole(DbManager $auth, Role $role, int $userId): void
    {
        if ($auth->getAssignment($role->name, (string) $userId) === null) {
            $auth->assign($role, $userId);
        }
    }

    private function findUserId(string $username, string $email): ?int
    {
        $id = (new \yii\db\Query())
            ->from('{{%users}}')
            ->select('id')
            ->where([
                'username' => $username,
                'email' => $email,
            ])
            ->scalar($this->db);

        return $id === false ? null : (int) $id;
    }
}

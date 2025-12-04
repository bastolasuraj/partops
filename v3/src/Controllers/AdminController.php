<?php
declare(strict_types=1);

namespace PartOps\Controllers;

use PartOps\Models\User;
use PartOps\Services\Auth;
use PartOps\Services\Validator;
use PartOps\Services\AuditLog;

class AdminController extends BaseController
{
    public function index(): void
    {
        $users = User::getActive();

        $this->render('admin.index', [
            'users' => $users,
            'pageTitle' => 'Admin - Users'
        ]);
    }

    public function createUser(): void
    {
        $this->render('admin.user_form', [
            'user' => null,
            'pageTitle' => 'New Admin User'
        ]);
    }

    public function storeUser(): void
    {
        $input = $this->getInput();
        
        $validator = new Validator($input);
        $validator
            ->required('username', 'Username is required')
            ->required('email', 'Email is required')
            ->email('email')
            ->required('password', 'Password is required')
            ->minLength('password', 8, 'Password must be at least 8 characters');

        if ($validator->fails()) {
            $this->redirect('/admin/users/new', $validator->firstError(), 'error');
            return;
        }

        $existing = User::findByUsername($input['username']);
        if ($existing) {
            $this->redirect('/admin/users/new', 'Username already exists', 'error');
            return;
        }

        $userId = Auth::createLocalAdmin($input['username'], $input['email'], $input['password']);

        if ($userId) {
            AuditLog::log('create_admin', 'user', $userId, ['username' => $input['username']]);
            $this->redirect('/admin', 'Admin user created successfully');
        } else {
            $this->redirect('/admin/users/new', 'Failed to create user', 'error');
        }
    }

    public function toggleUser(string $id): void
    {
        $user = User::find((int)$id);
        if (!$user) {
            $this->notFound('User not found');
            return;
        }

        if ((int)$id === Auth::id()) {
            $this->redirect('/admin', 'Cannot deactivate your own account', 'error');
            return;
        }

        User::update((int)$id, ['is_active' => !$user['is_active']]);
        AuditLog::log($user['is_active'] ? 'deactivate' : 'activate', 'user', (int)$id);

        $action = $user['is_active'] ? 'deactivated' : 'activated';
        $this->redirect('/admin', "User $action successfully");
    }
}

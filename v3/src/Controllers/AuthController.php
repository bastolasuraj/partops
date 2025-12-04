<?php
declare(strict_types=1);

namespace PartOps\Controllers;

use PartOps\Services\Auth;
use PartOps\Services\CSRF;
use PartOps\Services\Validator;

class AuthController extends BaseController
{
    public function loginForm(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }
        $this->render('auth.login', [], null);
    }

    public function login(): void
    {
        $input = $this->getInput();
        
        $validator = new Validator($input);
        $validator->required('username')->required('password');
        
        if ($validator->fails()) {
            $this->redirect('/login', $validator->firstError(), 'error');
            return;
        }

        if (Auth::attempt($input['username'], $input['password'])) {
            $this->redirect('/', 'Welcome back!');
        } else {
            $this->redirect('/login', 'Invalid credentials', 'error');
        }
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login', 'You have been logged out');
    }
}

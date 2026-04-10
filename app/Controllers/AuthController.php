<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\UserRepository;

final class AuthController extends Controller
{
    public function __construct(
        private readonly UserRepository $users
    ) {
    }

    public function login(): void
    {
        if (Auth::check()) {
            $this->redirect();
        }

        $errors = [];
        $username = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim((string) ($_POST['username'] ?? ''));
            $password = trim((string) ($_POST['password'] ?? ''));

            if ($username === '' || $password === '') {
                $errors[] = 'Preencha usuario e senha.';
            } elseif (Auth::attempt($username, $password, $this->users)) {
                $this->redirect();
            } else {
                $errors[] = 'Usuario ou senha invalidos.';
            }
        }

        $this->render('auth/login', [
            'title' => 'Entrar',
            'errors' => $errors,
            'username' => $username,
        ], 'layouts/guest');
    }

    public function logout(): never
    {
        Auth::logout();
        $this->redirect('login');
    }
}

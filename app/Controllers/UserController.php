<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\UserRepository;
use App\Services\AuditLogger;

final class UserController extends Controller
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AuditLogger $auditLogger
    ) {
    }

    public function index(): void
    {
        $currentUser = Auth::requireUser();
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = (string) ($_POST['action'] ?? '');

            if ($action === 'save') {
                $error = $this->saveUser($currentUser);
            } elseif ($action === 'delete') {
                $username = (string) ($_POST['username_delete'] ?? '');
                $existing = $this->users->findByUsername($username);
                $this->users->delete($username);
                $this->auditLogger->log(
                    $currentUser,
                    'delete',
                    'user',
                    $username,
                    sprintf('Usuario %s removido.', $username),
                    $existing ?? ['username' => $username]
                );
                Session::flash('success', 'Usuario removido com sucesso.');
                $this->redirect('usuarios');
            }
        }

        $this->render('users/index', [
            'title' => 'Usuarios',
            'error' => $error,
            'success' => Session::pullFlash('success'),
            'users' => $this->users->all(),
        ]);
    }

    private function saveUser(array $currentUser): ?string
    {
        $originalUsername = mb_strtolower(trim((string) ($_POST['original_username'] ?? '')), 'UTF-8');
        $username = mb_strtolower(trim((string) ($_POST['username'] ?? '')), 'UTF-8');
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $senha = trim((string) ($_POST['senha'] ?? ''));
        $consumo = max(0, (int) ($_POST['consumo_dia'] ?? 0));
        $before = $originalUsername !== '' ? $this->users->findByUsername($originalUsername) : null;

        if ($username === '' || $nome === '') {
            return 'Usuario e nome sao obrigatorios.';
        }
        if ($originalUsername === '' && $senha === '') {
            return 'Senha obrigatoria para novos usuarios.';
        }
        $existing = $this->users->findByUsername($username);
        if ($existing !== null && $username !== $originalUsername) {
            return 'Ja existe um usuario com esse identificador.';
        }

        try {
            $this->users->save($originalUsername, [
                'username' => $username,
                'nome' => $nome,
                'senha' => $senha,
                'consumo_dia' => $consumo,
            ]);
            $this->auditLogger->log(
                $currentUser,
                $before === null ? 'create' : 'update',
                'user',
                $username,
                $before === null ? sprintf('Usuario %s criado.', $username) : sprintf('Usuario %s atualizado.', $username),
                [
                    'before' => $before ? ['username' => $before['username'], 'nome' => $before['nome'], 'consumo_dia' => $before['consumo_dia']] : null,
                    'after' => ['username' => $username, 'nome' => $nome, 'consumo_dia' => $consumo],
                    'password_changed' => $senha !== '',
                ]
            );
            Session::flash('success', 'Usuario salvo com sucesso.');
            $this->redirect('usuarios');
        } catch (\Throwable $exception) {
            error_log('Erro ao salvar usuario: ' . $exception->getMessage());
            return 'Erro interno ao salvar usuario.';
        }
    }
}

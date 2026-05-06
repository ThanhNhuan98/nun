<?php

namespace App\Controllers;

use App\Core\Response;

abstract class BaseController
{
    protected function requireAuth(Response $response)
    {
        if (!isset($_SESSION['user'])) {
            return $response->redirect('/login');
        }

        return null;
    }

    protected function requireRole(Response $response, $roles)
    {
        if (!\app_has_role($roles)) {
            return $response->redirect('/login');
        }

        return null;
    }

    protected function currentUser(?string $key = null, $default = null)
    {
        return \app_current_user($key, $default);
    }

    protected function userId(): int
    {
        return (int) ($this->currentUser('id', 0));
    }

    protected function notFound(Response $response)
    {
        $response->setStatusCode(404);
        return $response->render('layouts/404');
    }
}

<?php

namespace App\Controllers;

use App\Core\Response;

abstract class BaseController
{
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

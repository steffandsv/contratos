<?php

namespace App\Core;

abstract class Controller
{
    protected function render($view, $data = [])
    {
        View::render($view, $data);
    }

    protected function redirect($url)
    {
        header("Location: $url");
        exit;
    }
}

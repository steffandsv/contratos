<?php

namespace App\Core;

class View
{
    public static function render($view, $data = [])
    {
        extract($data);

        $viewFile = __DIR__ . "/../../views/$view.php";

        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "View not found: $view";
        }
    }
}

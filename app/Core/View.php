<?php
namespace App\Core;

class View
{
    public function render(string $template, array $params = [], string $layout = 'front'): void
    {
        if (!isset($params['csrf']) && isset($GLOBALS['csrf'])) {
            $params['csrf'] = $GLOBALS['csrf'];
        }
        extract($params, EXTR_SKIP);
        $templatePath = dirname(__DIR__) . '/Views/' . $template . '.php';
        $layoutPath = dirname(__DIR__) . '/Views/layouts/' . $layout . '.php';

        ob_start();
        if (file_exists($templatePath)) {
            include $templatePath;
        } else {
            echo 'Şablon bulunamadı';
        }
        $content = ob_get_clean();

        if (file_exists($layoutPath)) {
            include $layoutPath;
        } else {
            echo $content;
        }
    }
}

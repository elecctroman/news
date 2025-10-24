<?php
namespace App\Core;

class Controller
{
    protected View $view;
    protected ?Auth $auth = null;
    protected ?\PDO $pdo = null;
    protected array $config = [];

    public function __construct()
    {
        $this->view = new View();
        $this->auth = $GLOBALS['auth'] ?? null;
        $this->pdo = $GLOBALS['pdo'] ?? null;
        $this->config = $GLOBALS['config'] ?? [];
    }
}

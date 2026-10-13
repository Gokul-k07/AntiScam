<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * Handles the home page route.
 */
class HomeController
{
    public function index(): void
    {
        $title = 'Home';
        $this->render('pages/home', $title, [
            'currentPage' => 'home',
        ]);
    }

    /**
     * Render a view file inside the main layout.
     *
     * @param string               $view   Relative path under app/Views/ (no .php extension)
     * @param string               $title  Page <title> value
     * @param array<string, mixed> $vars   Variables to extract into view scope
     */
    private function render(string $view, string $title = 'ScamShield', array $vars = []): void
    {
        $viewsDir = dirname(__DIR__) . '/Views/';

        $viewFile   = $viewsDir . $view . '.php';
        $layoutFile = $viewsDir . 'layouts/main.php';

        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo 'View not found.';
            return;
        }

        // Make variables available to the view
        extract($vars, EXTR_SKIP);

        // Capture view output into $content.
        ob_start();
        include $viewFile;
        $content = (string)ob_get_clean();

        // Render layout with $title and $content in scope.
        include $layoutFile;
    }
}

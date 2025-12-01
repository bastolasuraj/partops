<?php
/**
 * Home Controller
 * 
 * Handle home page and dashboard
 */

declare(strict_types=1);

namespace PartOps\Controllers;

use PartOps\Core\Controller;

class HomeController extends Controller
{
    /**
     * Display home page / dashboard
     */
    public function index(): void
    {
        $this->requireAuth();
        
        $data = [
            'title' => 'Dashboard',
            'user' => $_SESSION['username'] ?? 'User'
        ];
        
        $this->view('home.index', $data);
    }
}

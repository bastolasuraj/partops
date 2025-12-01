<?php
/**
 * Part Controller
 * 
 * Handle parts CRUD operations
 */

declare(strict_types=1);

namespace PartOps\Controllers;

use PartOps\Core\Controller;
use PartOps\Models\Part;

class PartController extends Controller
{
    private Part $partModel;

    public function __construct()
    {
        $this->partModel = new Part();
    }

    public function index(): void
    {
        $this->requireAuth();
        $parts = $this->partModel->all(['is_active' => 1]);
        $this->view('parts.index', ['title' => 'Parts', 'parts' => $parts]);
    }

    public function show(string $id): void
    {
        $this->requireAuth();
        $part = $this->partModel->find((int)$id);
        
        if (!$part) {
            http_response_code(404);
            echo "Part not found";
            return;
        }
        
        $this->view('parts.show', ['title' => 'Part Details', 'part' => $part]);
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->view('parts.create', [
            'title' => 'Create Part',
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrf()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        // TODO: Implement part creation logic
        $this->json(['message' => 'Part creation not yet implemented'], 501);
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $part = $this->partModel->find((int)$id);
        
        if (!$part) {
            http_response_code(404);
            echo "Part not found";
            return;
        }
        
        $this->view('parts.edit', [
            'title' => 'Edit Part',
            'part' => $part,
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrf()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        // TODO: Implement part update logic
        $this->json(['message' => 'Part update not yet implemented'], 501);
    }

    public function delete(string $id): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrf()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $this->partModel->delete((int)$id);
        $this->redirect('/parts');
    }

    public function search(): void
    {
        $this->requireAuth();
        $query = $this->get('q', '');
        
        // TODO: Implement fulltext search
        $this->json(['message' => 'Search not yet implemented'], 501);
    }
}

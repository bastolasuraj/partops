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

        $name = trim($this->post('name', ''));
        $anchorSlug = trim($this->post('anchor_slug', ''));
        
        if (empty($name) || empty($anchorSlug)) {
            $this->json(['error' => 'Name and Anchor Slug are required'], 400);
        }

        $data = [
            'name' => $name,
            'anchor_slug' => $anchorSlug,
            'description' => trim($this->post('description', '')),
            'notes' => trim($this->post('notes', '')),
            'is_active' => $this->post('is_active') ? 1 : 0
        ];

        try {
            $this->partModel->create($data);
            $this->redirect('/parts');
        } catch (\PDOException $e) {
            // Check for duplicate entry
            if ($e->getCode() == 23000) {
                 $this->json(['error' => 'Anchor Slug already exists'], 400);
            }
            throw $e;
        }
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

        $name = trim($this->post('name', ''));
        $anchorSlug = trim($this->post('anchor_slug', ''));
        
        if (empty($name) || empty($anchorSlug)) {
            $this->json(['error' => 'Name and Anchor Slug are required'], 400);
        }

        $data = [
            'name' => $name,
            'anchor_slug' => $anchorSlug,
            'description' => trim($this->post('description', '')),
            'notes' => trim($this->post('notes', '')),
            'is_active' => $this->post('is_active') ? 1 : 0
        ];

        try {
            $this->partModel->update((int)$id, $data);
            $this->redirect('/parts');
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                 $this->json(['error' => 'Anchor Slug already exists'], 400);
            }
            throw $e;
        }
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
        
        if (empty($query)) {
            $this->redirect('/parts');
        }

        $parts = $this->partModel->search($query);
        
        $this->view('parts.index', [
            'title' => 'Search Results: ' . $query, 
            'parts' => $parts,
            'query' => $query
        ]);
    }
}

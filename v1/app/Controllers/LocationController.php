<?php
/**
 * Location Controller
 * 
 * Handle warehouse locations CRUD operations
 */

declare(strict_types=1);

namespace PartOps\Controllers;

use PartOps\Core\Controller;
use PartOps\Models\Location;

class LocationController extends Controller
{
    private Location $locationModel;

    public function __construct()
    {
        $this->locationModel = new Location();
    }

    public function index(): void
    {
        $this->requireAuth();
        $locations = $this->locationModel->all(['is_active' => 1]);
        $this->view('locations.index', [
            'title' => 'Locations',
            'locations' => $locations
        ]);
    }

    public function show(string $id): void
    {
        $this->requireAuth();
        $location = $this->locationModel->find((int)$id);
        
        if (!$location) {
            http_response_code(404);
            echo "Location not found";
            return;
        }
        
        $this->view('locations.show', [
            'title' => 'Location Details',
            'location' => $location
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->view('locations.create', [
            'title' => 'Create Location',
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrf()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $aisle = trim($this->post('aisle', ''));
        $shelf = trim($this->post('shelf', ''));
        $bay = trim($this->post('bay', ''));
        
        if (empty($aisle) || empty($shelf) || empty($bay)) {
            $this->json(['error' => 'Aisle, Shelf, and Bay are required'], 400);
        }

        $data = [
            'aisle' => $aisle,
            'shelf' => $shelf,
            'bay' => $bay,
            'bin' => trim($this->post('bin', '')),
            'is_active' => $this->post('is_active') ? 1 : 0
        ];

        try {
            $this->locationModel->create($data);
            $this->redirect('/locations');
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                 $this->json(['error' => 'Location already exists'], 400);
            }
            throw $e;
        }
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $location = $this->locationModel->find((int)$id);
        
        if (!$location) {
            http_response_code(404);
            echo "Location not found";
            return;
        }
        
        $this->view('locations.edit', [
            'title' => 'Edit Location',
            'location' => $location,
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrf()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $aisle = trim($this->post('aisle', ''));
        $shelf = trim($this->post('shelf', ''));
        $bay = trim($this->post('bay', ''));
        
        if (empty($aisle) || empty($shelf) || empty($bay)) {
            $this->json(['error' => 'Aisle, Shelf, and Bay are required'], 400);
        }

        $data = [
            'aisle' => $aisle,
            'shelf' => $shelf,
            'bay' => $bay,
            'bin' => trim($this->post('bin', '')),
            'is_active' => $this->post('is_active') ? 1 : 0
        ];

        try {
            $this->locationModel->update((int)$id, $data);
            $this->redirect('/locations');
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                 $this->json(['error' => 'Location already exists'], 400);
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

        $this->locationModel->delete((int)$id);
        $this->redirect('/locations');
    }
}

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

        // TODO: Implement location creation
        $this->json(['message' => 'Location creation not yet implemented'], 501);
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

        // TODO: Implement location update
        $this->json(['message' => 'Location update not yet implemented'], 501);
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

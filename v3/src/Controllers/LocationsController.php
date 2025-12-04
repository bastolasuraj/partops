<?php
declare(strict_types=1);

namespace PartOps\Controllers;

use PartOps\Models\Location;
use PartOps\Models\InventoryLevel;
use PartOps\Services\Validator;
use PartOps\Services\AuditLog;

class LocationsController extends BaseController
{
    public function index(): void
    {
        $locations = Location::getActive();

        $this->render('locations.index', [
            'locations' => $locations,
            'pageTitle' => 'Locations'
        ]);
    }

    public function show(string $id): void
    {
        $location = Location::find((int)$id);
        if (!$location) {
            $this->notFound('Location not found');
            return;
        }

        $inventory = InventoryLevel::getByLocation((int)$id);

        $this->render('locations.show', [
            'location' => $location,
            'inventory' => $inventory,
            'pageTitle' => Location::getFormatted((int)$id)
        ]);
    }

    public function create(): void
    {
        $this->render('locations.form', [
            'location' => null,
            'pageTitle' => 'New Location'
        ]);
    }

    public function store(): void
    {
        $input = $this->getInput();
        
        $validator = new Validator($input);
        $validator
            ->required('aisle', 'Aisle is required')
            ->required('shelf', 'Shelf is required')
            ->required('bay', 'Bay is required');

        if ($validator->fails()) {
            $this->redirect('/locations/new', $validator->firstError(), 'error');
            return;
        }

        $existing = Location::findByPosition($input['aisle'], $input['shelf'], $input['bay']);
        if ($existing) {
            $this->redirect('/locations/new', 'This location already exists', 'error');
            return;
        }

        $locationId = Location::create([
            'aisle' => strtoupper($input['aisle']),
            'shelf' => strtoupper($input['shelf']),
            'bay' => strtoupper($input['bay']),
            'bin' => $input['bin'] ?? null,
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        if ($locationId) {
            AuditLog::log('create', 'location', $locationId);
            $this->redirect("/locations/$locationId", 'Location created successfully');
        } else {
            $this->redirect('/locations/new', 'Failed to create location', 'error');
        }
    }

    public function edit(string $id): void
    {
        $location = Location::find((int)$id);
        if (!$location) {
            $this->notFound('Location not found');
            return;
        }

        $this->render('locations.form', [
            'location' => $location,
            'pageTitle' => 'Edit Location'
        ]);
    }

    public function update(string $id): void
    {
        $location = Location::find((int)$id);
        if (!$location) {
            $this->notFound('Location not found');
            return;
        }

        $input = $this->getInput();
        
        Location::update((int)$id, [
            'aisle' => strtoupper($input['aisle']),
            'shelf' => strtoupper($input['shelf']),
            'bay' => strtoupper($input['bay']),
            'bin' => $input['bin'] ?? null
        ]);

        AuditLog::log('update', 'location', (int)$id);
        $this->redirect("/locations/$id", 'Location updated successfully');
    }

    public function delete(string $id): void
    {
        $location = Location::find((int)$id);
        if (!$location) {
            $this->notFound('Location not found');
            return;
        }

        Location::softDelete((int)$id);
        AuditLog::log('delete', 'location', (int)$id);
        
        $this->redirect('/locations', 'Location deactivated successfully');
    }
}

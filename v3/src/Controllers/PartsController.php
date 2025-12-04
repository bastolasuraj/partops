<?php
declare(strict_types=1);

namespace PartOps\Controllers;

use PartOps\Models\Part;
use PartOps\Models\PartNumber;
use PartOps\Models\PartSupplier;
use PartOps\Models\Supplier;
use PartOps\Models\Location;
use PartOps\Models\InventoryLevel;
use PartOps\Models\InventoryMove;
use PartOps\Services\Validator;
use PartOps\Services\AuditLog;
use PartOps\Services\QRService;

class PartsController extends BaseController
{
    public function index(): void
    {
        $search = $_GET['q'] ?? '';
        $status = $_GET['status'] ?? 'active';
        
        if ($search) {
            $parts = Part::search($search, $status === 'active');
        } else {
            $parts = Part::getAllWithSummary($status === 'active');
        }

        $suppliers = Supplier::getActive();
        $locations = Location::getActive();

        $this->render('parts.index', [
            'parts' => $parts,
            'suppliers' => $suppliers,
            'locations' => $locations,
            'search' => $search,
            'status' => $status,
            'pageTitle' => 'Parts Catalog'
        ]);
    }

    public function show(string $id): void
    {
        $part = Part::getWithDetails((int)$id);
        if (!$part) {
            $this->notFound('Part not found');
            return;
        }

        $moves = InventoryMove::getByPart((int)$id, 20);
        $qrPayload = QRService::generatePayload((int)$id);

        $this->render('parts.show', [
            'part' => $part,
            'moves' => $moves,
            'qrPayload' => $qrPayload,
            'pageTitle' => 'Part: ' . $part['anchor_slug']
        ]);
    }

    public function create(): void
    {
        $suppliers = Supplier::getActive();
        $locations = Location::getActive();

        $this->render('parts.form', [
            'part' => null,
            'suppliers' => $suppliers,
            'locations' => $locations,
            'pageTitle' => 'New Part'
        ]);
    }

    public function store(): void
    {
        $input = $this->getInput();
        
        $validator = new Validator($input);
        $validator
            ->required('anchor_slug', 'Anchor slug is required')
            ->slug('anchor_slug', 'Anchor must be lowercase with hyphens only')
            ->required('name', 'Part name is required')
            ->required('part_number', 'Part number is required');

        if ($validator->fails()) {
            $this->redirect('/parts/new', $validator->firstError(), 'error');
            return;
        }

        $existing = Part::findByAnchor($input['anchor_slug']);
        if ($existing) {
            $this->redirect('/parts/new', 'A part with this anchor already exists', 'error');
            return;
        }

        $partId = Part::create([
            'anchor_slug' => $input['anchor_slug'],
            'name' => $input['name'],
            'description' => $input['description'] ?? null,
            'notes' => $input['notes'] ?? null,
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        if ($partId) {
            PartNumber::create([
                'part_id' => $partId,
                'value' => $input['part_number'],
                'type' => 'active',
                'manufacturer' => $input['manufacturer'] ?? null,
                'is_primary' => true,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            AuditLog::log('create', 'part', $partId, ['anchor_slug' => $input['anchor_slug']]);
            $this->redirect("/parts/$partId", 'Part created successfully');
        } else {
            $this->redirect('/parts/new', 'Failed to create part', 'error');
        }
    }

    public function edit(string $id): void
    {
        $part = Part::getWithDetails((int)$id);
        if (!$part) {
            $this->notFound('Part not found');
            return;
        }

        $suppliers = Supplier::getActive();
        $locations = Location::getActive();

        $this->render('parts.form', [
            'part' => $part,
            'suppliers' => $suppliers,
            'locations' => $locations,
            'pageTitle' => 'Edit: ' . $part['anchor_slug']
        ]);
    }

    public function update(string $id): void
    {
        $part = Part::find((int)$id);
        if (!$part) {
            $this->notFound('Part not found');
            return;
        }

        $input = $this->getInput();
        
        $validator = new Validator($input);
        $validator
            ->required('name', 'Part name is required');

        if ($validator->fails()) {
            $this->redirect("/parts/$id/edit", $validator->firstError(), 'error');
            return;
        }

        Part::update((int)$id, [
            'name' => $input['name'],
            'description' => $input['description'] ?? null,
            'notes' => $input['notes'] ?? null,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        AuditLog::log('update', 'part', (int)$id, ['name' => $input['name']]);
        $this->redirect("/parts/$id", 'Part updated successfully');
    }

    public function delete(string $id): void
    {
        $part = Part::find((int)$id);
        if (!$part) {
            $this->notFound('Part not found');
            return;
        }

        Part::softDelete((int)$id);
        AuditLog::log('delete', 'part', (int)$id, ['anchor_slug' => $part['anchor_slug']]);
        
        $this->redirect('/parts', 'Part deactivated successfully');
    }

    public function apiSearch(): void
    {
        $query = $_GET['q'] ?? '';
        $parts = Part::search($query, true, 20);
        $this->json(['parts' => $parts]);
    }
}

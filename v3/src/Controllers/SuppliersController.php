<?php
declare(strict_types=1);

namespace PartOps\Controllers;

use PartOps\Models\Supplier;
use PartOps\Models\PartSupplier;
use PartOps\Services\Validator;
use PartOps\Services\AuditLog;

class SuppliersController extends BaseController
{
    public function index(): void
    {
        $search = $_GET['q'] ?? '';
        
        if ($search) {
            $suppliers = Supplier::search($search);
        } else {
            $suppliers = Supplier::getActive();
        }

        $this->render('suppliers.index', [
            'suppliers' => $suppliers,
            'search' => $search,
            'pageTitle' => 'Suppliers'
        ]);
    }

    public function show(string $id): void
    {
        $supplier = Supplier::find((int)$id);
        if (!$supplier) {
            $this->notFound('Supplier not found');
            return;
        }

        $parts = PartSupplier::getBySupplier((int)$id);

        $this->render('suppliers.show', [
            'supplier' => $supplier,
            'parts' => $parts,
            'pageTitle' => $supplier['name']
        ]);
    }

    public function create(): void
    {
        $this->render('suppliers.form', [
            'supplier' => null,
            'pageTitle' => 'New Supplier'
        ]);
    }

    public function store(): void
    {
        $input = $this->getInput();
        
        $validator = new Validator($input);
        $validator->required('name', 'Supplier name is required');

        if ($validator->fails()) {
            $this->redirect('/suppliers/new', $validator->firstError(), 'error');
            return;
        }

        $supplierId = Supplier::create([
            'name' => $input['name'],
            'contact_email' => $input['contact_email'] ?? null,
            'contact_phone' => $input['contact_phone'] ?? null,
            'reorder_url' => $input['reorder_url'] ?? null,
            'is_preferred' => isset($input['is_preferred']),
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        if ($supplierId) {
            AuditLog::log('create', 'supplier', $supplierId, ['name' => $input['name']]);
            $this->redirect("/suppliers/$supplierId", 'Supplier created successfully');
        } else {
            $this->redirect('/suppliers/new', 'Failed to create supplier', 'error');
        }
    }

    public function edit(string $id): void
    {
        $supplier = Supplier::find((int)$id);
        if (!$supplier) {
            $this->notFound('Supplier not found');
            return;
        }

        $this->render('suppliers.form', [
            'supplier' => $supplier,
            'pageTitle' => 'Edit: ' . $supplier['name']
        ]);
    }

    public function update(string $id): void
    {
        $supplier = Supplier::find((int)$id);
        if (!$supplier) {
            $this->notFound('Supplier not found');
            return;
        }

        $input = $this->getInput();
        
        $validator = new Validator($input);
        $validator->required('name', 'Supplier name is required');

        if ($validator->fails()) {
            $this->redirect("/suppliers/$id/edit", $validator->firstError(), 'error');
            return;
        }

        Supplier::update((int)$id, [
            'name' => $input['name'],
            'contact_email' => $input['contact_email'] ?? null,
            'contact_phone' => $input['contact_phone'] ?? null,
            'reorder_url' => $input['reorder_url'] ?? null,
            'is_preferred' => isset($input['is_preferred'])
        ]);

        AuditLog::log('update', 'supplier', (int)$id, ['name' => $input['name']]);
        $this->redirect("/suppliers/$id", 'Supplier updated successfully');
    }

    public function delete(string $id): void
    {
        $supplier = Supplier::find((int)$id);
        if (!$supplier) {
            $this->notFound('Supplier not found');
            return;
        }

        Supplier::softDelete((int)$id);
        AuditLog::log('delete', 'supplier', (int)$id, ['name' => $supplier['name']]);
        
        $this->redirect('/suppliers', 'Supplier deactivated successfully');
    }
}

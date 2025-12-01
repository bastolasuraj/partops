<?php
/**
 * Supplier Controller
 * 
 * Handle suppliers CRUD operations
 */

declare(strict_types=1);

namespace PartOps\Controllers;

use PartOps\Core\Controller;
use PartOps\Models\Supplier;

class SupplierController extends Controller
{
    private Supplier $supplierModel;

    public function __construct()
    {
        $this->supplierModel = new Supplier();
    }

    public function index(): void
    {
        $this->requireAuth();
        $suppliers = $this->supplierModel->all(['is_active' => 1]);
        $this->view('suppliers.index', [
            'title' => 'Suppliers',
            'suppliers' => $suppliers
        ]);
    }

    public function show(string $id): void
    {
        $this->requireAuth();
        $supplier = $this->supplierModel->find((int)$id);
        
        if (!$supplier) {
            http_response_code(404);
            echo "Supplier not found";
            return;
        }
        
        $this->view('suppliers.show', [
            'title' => 'Supplier Details',
            'supplier' => $supplier
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->view('suppliers.create', [
            'title' => 'Create Supplier',
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrf()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        // TODO: Implement supplier creation
        $this->json(['message' => 'Supplier creation not yet implemented'], 501);
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $supplier = $this->supplierModel->find((int)$id);
        
        if (!$supplier) {
            http_response_code(404);
            echo "Supplier not found";
            return;
        }
        
        $this->view('suppliers.edit', [
            'title' => 'Edit Supplier',
            'supplier' => $supplier,
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrf()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        // TODO: Implement supplier update
        $this->json(['message' => 'Supplier update not yet implemented'], 501);
    }

    public function delete(string $id): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrf()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $this->supplierModel->delete((int)$id);
        $this->redirect('/suppliers');
    }
}

<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Response;
use App\Models\Supplier;

class SupplierController extends BaseController
{
    private Supplier $supplier;
    
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        $this->supplier = new Supplier();
    }
    
    public function index(): void
    {
        $suppliers = $this->supplier->getWithPartCount();
        Response::success($suppliers);
    }
    
    public function show(int $id): void
    {
        $supplier = $this->supplier->find($id);
        
        if (!$supplier) {
            Response::notFound('Supplier not found');
        }
        
        Response::success($supplier);
    }
    
    public function store(): void
    {
        $data = $this->validate([
            'name' => 'required|string|min:2',
            'contact' => 'string',
            'phone' => 'string',
            'email' => 'email',
        ]);
        
        $supplier = $this->supplier->create($data);
        Response::created($supplier, 'Supplier created successfully');
    }
    
    public function update(int $id): void
    {
        $existing = $this->supplier->find($id);
        if (!$existing) {
            Response::notFound('Supplier not found');
        }
        
        $data = $this->request->all();
        $supplier = $this->supplier->update($id, $data);
        Response::success($supplier, 'Supplier updated successfully');
    }
    
    public function destroy(int $id): void
    {
        $existing = $this->supplier->find($id);
        if (!$existing) {
            Response::notFound('Supplier not found');
        }
        
        $this->supplier->delete($id);
        Response::success(null, 'Supplier deleted successfully');
    }
}

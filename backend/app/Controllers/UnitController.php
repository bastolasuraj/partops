<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Response;
use App\Models\Unit;

class UnitController extends BaseController
{
    private Unit $unit;
    
    public function __construct()
    {
        parent::__construct();
        $this->unit = new Unit();
    }
    
    public function index(): void
    {
        $units = $this->unit->all();
        Response::success($units);
    }
    
    public function show(int $id): void
    {
        $unit = $this->unit->find($id);
        
        if (!$unit) {
            Response::notFound('Unit not found');
        }
        
        Response::success($unit);
    }
    
    public function store(): void
    {
        $data = $this->validate([
            'name' => 'required|string|min:2',
            'make' => 'string',
            'model' => 'string',
            'year' => 'string',
            'vin' => 'string',
        ]);
        
        $unit = $this->unit->create($data);
        Response::created($unit, 'Unit created successfully');
    }
    
    public function update(int $id): void
    {
        $existing = $this->unit->find($id);
        if (!$existing) {
            Response::notFound('Unit not found');
        }
        
        $data = $this->request->all();
        $unit = $this->unit->update($id, $data);
        Response::success($unit, 'Unit updated successfully');
    }
    
    public function destroy(int $id): void
    {
        $existing = $this->unit->find($id);
        if (!$existing) {
            Response::notFound('Unit not found');
        }
        
        $this->unit->delete($id);
        Response::success(null, 'Unit deleted successfully');
    }
}

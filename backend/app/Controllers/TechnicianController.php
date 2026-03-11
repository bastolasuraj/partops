<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Response;
use App\Models\Technician;

class TechnicianController extends BaseController
{
    private Technician $technician;
    
    public function __construct()
    {
        parent::__construct();
        $this->technician = new Technician();
    }
    
    public function index(): void
    {
        $technicians = $this->technician->all();
        Response::success($technicians);
    }
    
    public function show(int $id): void
    {
        $technician = $this->technician->find($id);
        
        if (!$technician) {
            Response::notFound('Technician not found');
        }
        
        Response::success($technician);
    }
    
    public function store(): void
    {
        $data = $this->validate([
            'name' => 'required|string|min:2',
            'emp_id' => 'string',
        ]);

        $employeeId = $this->normalizeEmployeeId($data['emp_id'] ?? null);
        if ($employeeId === null) {
            $employeeId = $this->generateTemporaryEmployeeId();
        }

        $existing = $this->technician->findBy('emp_id', $employeeId);
        if ($existing) {
            Response::error('Employee Number already exists', 422);
        }

        $payload = [
            'name' => trim((string)$data['name']),
            'emp_id' => $employeeId,
        ];

        $technician = $this->technician->create($payload);
        Response::created($technician, 'Technician created successfully');
    }
    
    public function update(int $id): void
    {
        $existing = $this->technician->find($id);
        if (!$existing) {
            Response::notFound('Technician not found');
        }
        $data = $this->validate([
            'name' => 'required|string|min:2',
            'emp_id' => 'string',
        ]);

        $employeeId = $this->normalizeEmployeeId($data['emp_id'] ?? null);
        if ($employeeId === null) {
            $existingEmployeeId = $this->normalizeEmployeeId($existing['emp_id'] ?? null);
            $employeeId = $existingEmployeeId ?? $this->generateTemporaryEmployeeId();
        }

        $duplicate = $this->technician->findBy('emp_id', $employeeId);
        if ($duplicate && (int)$duplicate['id'] !== $id) {
            Response::error('Employee Number already exists', 422);
        }

        $payload = [
            'name' => trim((string)$data['name']),
            'emp_id' => $employeeId,
        ];

        $technician = $this->technician->update($id, $payload);
        Response::success($technician, 'Technician updated successfully');
    }
    
    public function destroy(int $id): void
    {
        $existing = $this->technician->find($id);
        if (!$existing) {
            Response::notFound('Technician not found');
        }
        
        $this->technician->delete($id);
        Response::success(null, 'Technician deleted successfully');
    }

    private function normalizeEmployeeId($value): ?string
    {
        $employeeId = trim((string)($value ?? ''));
        return $employeeId === '' ? null : $employeeId;
    }

    private function generateTemporaryEmployeeId(): string
    {
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $candidate = (string)random_int(1000000000, 9999999999);
            $existing = $this->technician->findBy('emp_id', $candidate);
            if (!$existing) {
                return $candidate;
            }
        }

        Response::error('Unable to generate a unique temporary employee number', 500);
        throw new \RuntimeException('Unable to generate a unique temporary employee number');
    }
}

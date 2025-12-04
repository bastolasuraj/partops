<?php
declare(strict_types=1);

namespace PartOps\Controllers;

use PartOps\Models\Technician;
use PartOps\Services\Validator;
use PartOps\Services\AuditLog;

class TechniciansController extends BaseController
{
    public function index(): void
    {
        $search = $_GET['q'] ?? '';
        
        if ($search) {
            $technicians = Technician::search($search);
        } else {
            $technicians = Technician::getActive();
        }

        $this->render('technicians.index', [
            'technicians' => $technicians,
            'search' => $search,
            'pageTitle' => 'Technicians'
        ]);
    }

    public function create(): void
    {
        $this->render('technicians.form', [
            'technician' => null,
            'pageTitle' => 'New Technician'
        ]);
    }

    public function store(): void
    {
        $input = $this->getInput();
        
        $validator = new Validator($input);
        $validator->required('name', 'Technician name is required');

        if ($validator->fails()) {
            $this->redirect('/technicians/new', $validator->firstError(), 'error');
            return;
        }

        $techId = Technician::create([
            'name' => $input['name'],
            'email' => $input['email'] ?? null,
            'phone' => $input['phone'] ?? null,
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        if ($techId) {
            AuditLog::log('create', 'technician', $techId, ['name' => $input['name']]);
            $this->redirect('/technicians', 'Technician created successfully');
        } else {
            $this->redirect('/technicians/new', 'Failed to create technician', 'error');
        }
    }

    public function edit(string $id): void
    {
        $technician = Technician::find((int)$id);
        if (!$technician) {
            $this->notFound('Technician not found');
            return;
        }

        $this->render('technicians.form', [
            'technician' => $technician,
            'pageTitle' => 'Edit: ' . $technician['name']
        ]);
    }

    public function update(string $id): void
    {
        $technician = Technician::find((int)$id);
        if (!$technician) {
            $this->notFound('Technician not found');
            return;
        }

        $input = $this->getInput();
        
        Technician::update((int)$id, [
            'name' => $input['name'],
            'email' => $input['email'] ?? null,
            'phone' => $input['phone'] ?? null
        ]);

        AuditLog::log('update', 'technician', (int)$id, ['name' => $input['name']]);
        $this->redirect('/technicians', 'Technician updated successfully');
    }

    public function delete(string $id): void
    {
        $technician = Technician::find((int)$id);
        if (!$technician) {
            $this->notFound('Technician not found');
            return;
        }

        Technician::softDelete((int)$id);
        AuditLog::log('delete', 'technician', (int)$id);
        
        $this->redirect('/technicians', 'Technician deactivated successfully');
    }

    public function apiSearch(): void
    {
        $query = $_GET['q'] ?? '';
        $technicians = Technician::search($query);
        $this->json(['technicians' => $technicians]);
    }
}

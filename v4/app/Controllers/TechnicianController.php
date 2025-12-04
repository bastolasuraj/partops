<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\Technician;

class TechnicianController
{
    private Technician $model;

    public function __construct()
    {
        $this->model = new Technician();
    }

    public function index(): void
    {
        $technicians = $this->model->getActive();
        View::render('technicians/index', ['technicians' => $technicians]);
    }

    public function store(): void
    {
        $data = [
            'name' => $_POST['name'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'email' => $_POST['email'] ?? null,
        ];

        $this->model->create($data);
        $_SESSION['success'] = 'Technician created successfully!';
        View::redirect('/technicians');
    }

    public function update(string $id): void
    {
        $data = [
            'name' => $_POST['name'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'email' => $_POST['email'] ?? null,
        ];

        $this->model->update((int)$id, $data);
        $_SESSION['success'] = 'Technician updated successfully!';
        View::redirect('/technicians');
    }

    public function delete(string $id): void
    {
        $this->model->delete((int)$id);
        $_SESSION['success'] = 'Technician deleted successfully!';
        View::redirect('/technicians');
    }
}

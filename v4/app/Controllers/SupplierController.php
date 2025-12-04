<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\Supplier;

class SupplierController
{
    private Supplier $model;

    public function __construct()
    {
        $this->model = new Supplier();
    }

    public function index(): void
    {
        $suppliers = $this->model->getActive();
        $suppliedParts = $this->model->getSuppliedParts();
        View::render('suppliers/index', [
            'suppliers' => $suppliers,
            'suppliedParts' => $suppliedParts
        ]);
    }

    public function store(): void
    {
        $data = [
            'name' => $_POST['name'] ?? '',
            'phone' => $_POST['phone'] ?? null,
            'email' => $_POST['email'] ?? null,
            'address' => $_POST['address'] ?? null,
            'url' => $_POST['url'] ?? null,
        ];

        $this->model->create($data);
        $_SESSION['success'] = 'Supplier created successfully!';
        View::redirect('/suppliers');
    }

    public function update(string $id): void
    {
        $data = [
            'name' => $_POST['name'] ?? '',
            'phone' => $_POST['phone'] ?? null,
            'email' => $_POST['email'] ?? null,
            'address' => $_POST['address'] ?? null,
            'url' => $_POST['url'] ?? null,
        ];

        $this->model->update((int)$id, $data);
        $_SESSION['success'] = 'Supplier updated successfully!';
        View::redirect('/suppliers');
    }

    public function delete(string $id): void
    {
        $this->model->delete((int)$id);
        $_SESSION['success'] = 'Supplier deleted successfully!';
        View::redirect('/suppliers');
    }
}

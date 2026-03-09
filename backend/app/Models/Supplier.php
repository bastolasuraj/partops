<?php
namespace App\Models;

use App\Core\BaseModel;

class Supplier extends BaseModel
{
    protected string $table = 'suppliers';
    protected array $fillable = ['name', 'contact', 'phone', 'email', 'address', 'url'];
    
    public function getWithPartCount(): array
    {
        $sql = "SELECT s.*, COUNT(p.id) as parts_count 
                FROM suppliers s 
                LEFT JOIN parts p ON s.id = p.supplier_id 
                GROUP BY s.id 
                ORDER BY s.name";
        return \App\Core\Database::query($sql)->fetchAll();
    }
}

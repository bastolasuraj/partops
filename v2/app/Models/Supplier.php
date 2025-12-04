<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Supplier Model
 * This file should be moved to: app/Models/Supplier.php
 */
class Supplier extends Model
{
    protected string $table = 'suppliers';
    protected array $fillable = [
        'name',
        'code',
        'contact_name',
        'email',
        'phone',
        'address',
        'website',
        'reorder_url',
        'payment_terms',
        'notes',
        'is_preferred',
        'is_oem',
        'is_active'
    ];

    /**
     * Get all active suppliers
     */
    public function getActive(): array
    {
        return $this->all(['is_active' => 1], 'is_preferred DESC, name');
    }

    /**
     * Get preferred suppliers
     */
    public function getPreferred(): array
    {
        return $this->all(['is_active' => 1, 'is_preferred' => 1], 'name');
    }

    /**
     * Get supplier with parts count
     */
    public function getWithPartsCount(): array
    {
        $sql = "
            SELECT s.*, COUNT(DISTINCT ps.part_id) AS parts_count
            FROM suppliers s
            LEFT JOIN part_suppliers ps ON ps.supplier_id = s.id
            WHERE s.is_active = 1
            GROUP BY s.id
            ORDER BY s.is_preferred DESC, s.name
        ";
        
        return $this->query($sql);
    }

    /**
     * Get parts supplied by this supplier
     */
    public function getParts(int $supplierId): array
    {
        $sql = "
            SELECT p.*, ps.supplier_sku, ps.price, ps.core_charge, ps.expected_rebate,
                   pn.number AS primary_number
            FROM part_suppliers ps
            JOIN parts p ON p.id = ps.part_id
            LEFT JOIN part_numbers pn ON pn.part_id = p.id AND pn.is_primary = 1
            WHERE ps.supplier_id = :supplier_id AND p.is_active = 1
            ORDER BY p.name
        ";
        
        return $this->query($sql, ['supplier_id' => $supplierId]);
    }

    /**
     * Search suppliers
     */
    public function search(string $query): array
    {
        $sql = "
            SELECT * FROM suppliers
            WHERE is_active = 1
              AND (name LIKE :query OR code LIKE :query OR contact_name LIKE :query)
            ORDER BY is_preferred DESC, name
            LIMIT 20
        ";
        
        return $this->query($sql, ['query' => "%{$query}%"]);
    }
}

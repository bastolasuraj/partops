<?php
/**
 * Supplier Model
 * 
 * Handle supplier data operations
 */

declare(strict_types=1);

namespace PartOps\Models;

use PartOps\Core\Model;

class Supplier extends Model
{
    protected string $table = 'suppliers';

    /**
     * Get preferred suppliers
     */
    public function getPreferred(): array
    {
        return $this->all(['is_preferred' => 1, 'is_active' => 1]);
    }
}

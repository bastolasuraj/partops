<?php
/**
 * Technician Model
 */

declare(strict_types=1);

namespace PartOps\Models;

use PartOps\Core\Model;

class Technician extends Model
{
    protected string $table = 'technicians';

    public function getActive(): array
    {
        return $this->all(['is_active' => 1]);
    }
}

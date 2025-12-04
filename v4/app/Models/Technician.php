<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Technician extends Model
{
    protected string $table = 'technicians';

    public function getActive(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY name";
        return $this->fetchAll($sql);
    }
}

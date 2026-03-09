<?php
namespace App\Models;

use App\Core\BaseModel;

class Unit extends BaseModel
{
    protected string $table = 'units';
    protected array $fillable = ['name', 'make', 'model', 'year', 'vin', 'plate', 'status'];
    
    public function getActive(): array
    {
        return $this->where('status', 'active');
    }
}

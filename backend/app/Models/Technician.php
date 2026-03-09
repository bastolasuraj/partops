<?php
namespace App\Models;

use App\Core\BaseModel;

class Technician extends BaseModel
{
    protected string $table = 'technicians';
    protected array $fillable = ['name', 'emp_id'];
    
    public function getActive(): array
    {
        return $this->where('active', 1);
    }
}

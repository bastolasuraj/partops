<?php
/**
 * Location Model
 * 
 * Handle warehouse location data operations
 */

declare(strict_types=1);

namespace PartOps\Models;

use PartOps\Core\Model;

class Location extends Model
{
    protected string $table = 'locations';

    /**
     * Format location as string
     */
    public static function formatLocation(array $location): string
    {
        $parts = [$location['aisle'], $location['shelf'], $location['bay']];
        
        if (!empty($location['bin'])) {
            $parts[] = $location['bin'];
        }

        return implode('-', $parts);
    }
}

<?php
/**
 * Part Model
 * 
 * Handle part data operations
 */

declare(strict_types=1);

namespace PartOps\Models;

use PartOps\Core\Model;

class Part extends Model
{
    protected string $table = 'parts';

    /**
     * Find part by anchor slug
     */
    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE anchor_slug = ? LIMIT 1"
        );
        $stmt->execute([$slug]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Search parts by fulltext
     */
    public function search(string $query): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, 
                    MATCH(p.name, p.description) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
             FROM {$this->table} p
             WHERE MATCH(p.name, p.description) AGAINST(? IN NATURAL LANGUAGE MODE)
                OR p.anchor_slug LIKE ?
             ORDER BY relevance DESC
             LIMIT 50"
        );
        
        $likeQuery = "%{$query}%";
        $stmt->execute([$query, $query, $likeQuery]);
        
        return $stmt->fetchAll();
    }

    /**
     * Get part with numbers
     */
    public function getWithNumbers(int $partId): ?array
    {
        $part = $this->find($partId);
        
        if (!$part) {
            return null;
        }

        // Get part numbers
        $stmt = $this->db->prepare(
            "SELECT * FROM part_numbers WHERE part_id = ? AND is_active = 1"
        );
        $stmt->execute([$partId]);
        $part['numbers'] = $stmt->fetchAll();

        return $part;
    }
}

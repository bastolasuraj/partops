<?php
declare(strict_types=1);

namespace PartOps\Controllers;

use PartOps\Models\Part;
use PartOps\Models\InventoryLevel;
use PartOps\Models\InventoryMove;
use PartOps\Models\Location;
use PartOps\Services\QRService;
use PartOps\Services\Database;
use PartOps\Services\AuditLog;
use PartOps\Services\IdempotencyService;

class QRController extends BaseController
{
    public function index(): void
    {
        $this->render('qr.index', [
            'pageTitle' => 'QR / Scanner'
        ]);
    }

    public function lookup(): void
    {
        $payload = $_GET['token'] ?? '';
        
        if (!$payload) {
            $this->json(['error' => 'No token provided'], 400);
            return;
        }

        $partId = QRService::getPartIdFromPayload($payload);
        if (!$partId) {
            $this->json(['error' => 'Invalid or expired QR code'], 400);
            return;
        }

        $part = Part::getWithDetails($partId);
        if (!$part) {
            $this->json(['error' => 'Part not found'], 404);
            return;
        }

        $this->json([
            'success' => true,
            'part' => $part
        ]);
    }

    public function quickAdd(): void
    {
        $input = $this->getInput();
        
        $partId = (int)($input['part_id'] ?? 0);
        $locationId = (int)($input['location_id'] ?? 0);
        $quantity = (int)($input['quantity'] ?? 1);
        $idempotencyKey = $input['idempotency_key'] ?? '';

        if (!$partId || !$locationId || $quantity < 1) {
            $this->json(['error' => 'Invalid parameters'], 400);
            return;
        }

        if ($idempotencyKey && !IdempotencyService::checkAndMark($idempotencyKey)) {
            $this->json(['error' => 'Duplicate request'], 409);
            return;
        }

        Database::beginTransaction();
        try {
            InventoryLevel::adjustStock($partId, $locationId, $quantity);
            
            $moveId = InventoryMove::record([
                'part_id' => $partId,
                'location_id' => $locationId,
                'qty' => $quantity,
                'direction' => InventoryMove::DIRECTION_IN,
                'reason' => InventoryMove::REASON_ADJUST,
                'notes' => 'Quick add via QR scanner'
            ]);

            AuditLog::log('qr_add_stock', 'inventory', $moveId, [
                'part_id' => $partId,
                'quantity' => $quantity
            ]);

            Database::commit();

            $newTotal = InventoryLevel::getTotalStock($partId);
            $this->json(['success' => true, 'new_total' => $newTotal]);
        } catch (\Exception $e) {
            Database::rollback();
            $this->json(['error' => 'Failed to update stock'], 500);
        }
    }

    public function quickRemove(): void
    {
        $input = $this->getInput();
        
        $partId = (int)($input['part_id'] ?? 0);
        $locationId = (int)($input['location_id'] ?? 0);
        $quantity = (int)($input['quantity'] ?? 1);
        $idempotencyKey = $input['idempotency_key'] ?? '';

        if (!$partId || !$locationId || $quantity < 1) {
            $this->json(['error' => 'Invalid parameters'], 400);
            return;
        }

        if ($idempotencyKey && !IdempotencyService::checkAndMark($idempotencyKey)) {
            $this->json(['error' => 'Duplicate request'], 409);
            return;
        }

        $available = InventoryLevel::getAvailable($partId, $locationId);
        if ($available < $quantity) {
            $this->json(['error' => "Only $available units available"], 400);
            return;
        }

        Database::beginTransaction();
        try {
            InventoryLevel::adjustStock($partId, $locationId, -$quantity);
            
            $moveId = InventoryMove::record([
                'part_id' => $partId,
                'location_id' => $locationId,
                'qty' => $quantity,
                'direction' => InventoryMove::DIRECTION_OUT,
                'reason' => InventoryMove::REASON_ADJUST,
                'notes' => 'Quick remove via QR scanner'
            ]);

            AuditLog::log('qr_remove_stock', 'inventory', $moveId, [
                'part_id' => $partId,
                'quantity' => $quantity
            ]);

            Database::commit();

            $newTotal = InventoryLevel::getTotalStock($partId);
            $this->json(['success' => true, 'new_total' => $newTotal]);
        } catch (\Exception $e) {
            Database::rollback();
            $this->json(['error' => 'Failed to update stock'], 500);
        }
    }

    public function generateCode(string $id): void
    {
        $part = Part::find((int)$id);
        if (!$part) {
            $this->notFound('Part not found');
            return;
        }

        $payload = QRService::generatePayload((int)$id);
        
        $this->json([
            'payload' => $payload,
            'part' => [
                'id' => $part['id'],
                'anchor_slug' => $part['anchor_slug'],
                'name' => $part['name']
            ]
        ]);
    }
}

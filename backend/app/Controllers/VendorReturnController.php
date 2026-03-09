<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Response;
use App\Core\Database;
use App\Core\Logger;
use App\Models\VendorReturn;
use App\Models\Part;
use App\Models\InventoryTransaction;

class VendorReturnController extends BaseController
{
    private VendorReturn $vendorReturn;
    private Part $part;
    private InventoryTransaction $transaction;
    
    public function __construct()
    {
        parent::__construct();
        $this->vendorReturn = new VendorReturn();
        $this->part = new Part();
        $this->transaction = new InventoryTransaction();
    }
    
    public function index(): void
    {
        $returns = $this->vendorReturn->getAllWithDetails();
        Response::success($returns);
    }
    
    public function show(int $id): void
    {
        $return = $this->vendorReturn->find($id);
        
        if (!$return) {
            Response::notFound('Vendor return not found');
        }
        
        Response::success($return);
    }
    
    public function store(): void
    {
        $data = $this->validate([
            'supplier_id' => 'required|integer',
            'part_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);
        
        $part = $this->part->find($data['part_id']);
        if (!$part) {
            Response::notFound('Part not found');
        }
        
        // Auto-populate core info from part
        $data['core_cost'] = $this->request->get('core_cost', $part['core_cost'] ?? 0);
        $data['core_rebate'] = $this->request->get('core_rebate', $part['core_rebate'] ?? 0);
        $data['rma_number'] = $this->request->get('rma_number');
        $data['notes'] = $this->request->get('notes');
        $data['status'] = $this->request->get('status', 'shipped');
        
        // Reduce stock for the return
        $currentStock = $this->part->getStock($data['part_id']);
        if ($currentStock < $data['quantity']) {
            Response::error("Insufficient stock: available {$currentStock}, requested {$data['quantity']}", 422);
        }

        $partId = (int)$data['part_id'];
        $requestedQty = (int)$data['quantity'];
        $issueBreakdown = $this->part->getFifoIssueBreakdown($partId, $requestedQty);

        $createdBy = $_SESSION['user']['display_name'] ?? $_SESSION['user']['username'] ?? null;
        $notes = $data['notes'] ?? null;
        if (!empty($data['rma_number'])) {
            $notes = trim(($notes ? $notes . ' | ' : '') . 'RMA: ' . $data['rma_number']);
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $checkoutAt = date('Y-m-d H:i:s');
            $allocatedQty = 0;

            // Record outgoing transaction layers for visibility in transactions history
            foreach ($issueBreakdown as $layer) {
                $layerQty = (int)($layer['quantity'] ?? 0);
                if ($layerQty <= 0) {
                    continue;
                }

                $layerUnitPrice = round(max(0, (float)($layer['unit_price'] ?? 0)), 2);
                $allocatedQty += $layerQty;

                $this->transaction->recordCheckout(
                    $partId,
                    $layerQty,
                    'vendor',
                    (int)$data['supplier_id'],
                    $notes,
                    $createdBy,
                    $layerUnitPrice,
                    $checkoutAt
                );
            }

            if ($allocatedQty < $requestedQty) {
                $missingQty = $requestedQty - $allocatedQty;
                $fallbackUnitPrice = $this->part->getFifoIssueUnitPrice($partId, $requestedQty);

                $this->transaction->recordCheckout(
                    $partId,
                    $missingQty,
                    'vendor',
                    (int)$data['supplier_id'],
                    $notes,
                    $createdBy,
                    $fallbackUnitPrice,
                    $checkoutAt
                );
            }

            $this->part->adjustStock($partId, -$requestedQty);
            $this->part->syncUnitPriceFromFifo($partId);

            $return = $this->vendorReturn->create($data);

            $db->commit();

            Response::created($return, 'Vendor return created successfully');
        } catch (\Throwable $e) {
            $db->rollBack();
            Logger::error('Vendor return failed', ['error' => $e->getMessage()]);
            Response::error('Failed to process return', 500);
        }
    }
    
    public function update(int $id): void
    {
        $existing = $this->vendorReturn->find($id);
        if (!$existing) {
            Response::notFound('Vendor return not found');
        }
        
        $data = $this->request->all();
        $return = $this->vendorReturn->update($id, $data);
        Response::success($return, 'Vendor return updated successfully');
    }
    
    public function destroy(int $id): void
    {
        $existing = $this->vendorReturn->find($id);
        if (!$existing) {
            Response::notFound('Vendor return not found');
        }
        
        // Restore stock if return was pending
        if ($existing['status'] === 'pending') {
            $this->part->adjustStock($existing['part_id'], $existing['quantity']);
            $this->part->syncUnitPriceFromFifo((int)$existing['part_id']);
        }
        
        $this->vendorReturn->delete($id);
        Response::success(null, 'Vendor return deleted successfully');
    }
}

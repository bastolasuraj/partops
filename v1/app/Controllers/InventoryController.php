<?php
/**
 * Inventory Controller
 * 
 * Handle inventory movements (receive, checkout, return, adjust)
 */

declare(strict_types=1);

namespace PartOps\Controllers;

use PartOps\Core\Controller;

class InventoryController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $this->view('inventory.index', ['title' => 'Inventory']);
    }

    public function receive(): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrf()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        // TODO: Implement receive logic with transactions
        $this->json(['message' => 'Receive not yet implemented'], 501);
    }

    public function checkout(): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrf()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        // TODO: Implement checkout logic
        $this->json(['message' => 'Checkout not yet implemented'], 501);
    }

    public function return(): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrf()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        // TODO: Implement return logic
        $this->json(['message' => 'Return not yet implemented'], 501);
    }

    public function adjust(): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrf()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        // TODO: Implement adjustment logic
        $this->json(['message' => 'Adjust not yet implemented'], 501);
    }
}

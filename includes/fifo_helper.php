<?php
/**
 * FIFO (First-In, First-Out) Cost Pricing System
 *
 * Tracks inventory purchase batches so that when products are sold,
 * the cost is calculated from the oldest batch first, giving accurate
 * profit reporting even when supplier prices change between shipments.
 *
 * Requires: product_batches table (see sql/product_batches.sql)
 */

/**
 * Add a new purchase batch for a product.
 * Increments products.stock_quantity automatically.
 *
 * @param int   $product_id
 * @param int   $quantity    Units received
 * @param float $cost_price  Per-unit cost of this batch
 * @return array ['success' => bool, 'batch_id' => ?int, 'error' => ?string]
 */
function addProductBatch($product_id, $quantity, $cost_price)
{
    global $conn;

    if (!is_numeric($product_id) || $product_id <= 0) {
        return ['success' => false, 'error' => 'Invalid product ID'];
    }
    $quantity   = (int) $quantity;
    $cost_price = (float) $cost_price;
    if ($quantity <= 0) {
        return ['success' => false, 'error' => 'Quantity must be greater than 0'];
    }
    if ($cost_price < 0) {
        return ['success' => false, 'error' => 'Cost price cannot be negative'];
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare(
            "INSERT INTO product_batches (product_id, quantity_added, quantity_remaining, cost_price)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param('iiid', $product_id, $quantity, $quantity, $cost_price);
        $stmt->execute();
        $batch_id = $stmt->insert_id;
        $stmt->close();

        $update = $conn->prepare(
            "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?"
        );
        $update->bind_param('ii', $quantity, $product_id);
        $update->execute();
        $update->close();

        $conn->commit();
        return ['success' => true, 'batch_id' => $batch_id];
    } catch (Exception $e) {
        $conn->rollback();
        error_log('addProductBatch failed: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Consume a quantity from the oldest available batches (FIFO).
 *
 * Deducts from batches with quantity_remaining > 0, oldest first.
 * Returns the weighted-average cost price for the consumed quantity.
 * Does NOT modify products.stock_quantity (that was deducted at order time).
 *
 * @param int $product_id
 * @param int $quantity    Units being sold/delivered
 * @return array [
 *   'success'    => bool,
 *   'total_cost' => ?float,    // sum of (consumed_qty * cost_price) across all batches
 *   'avg_cost'   => ?float,    // weighted average per-unit cost
 *   'consumed'   => ?array,    // breakdown per batch
 *   'error'      => ?string
 * ]
 */
function consumeProductBatches($product_id, $quantity)
{
    global $conn;

    if (!is_numeric($product_id) || $product_id <= 0) {
        return ['success' => false, 'error' => 'Invalid product ID'];
    }
    $quantity = (int) $quantity;
    if ($quantity <= 0) {
        return ['success' => false, 'error' => 'Quantity must be greater than 0'];
    }

    $conn->begin_transaction();
    try {
        $sel = $conn->prepare(
            "SELECT id, quantity_remaining, cost_price
             FROM product_batches
             WHERE product_id = ? AND quantity_remaining > 0
             ORDER BY created_at ASC, id ASC
             FOR UPDATE"
        );
        $sel->bind_param('i', $product_id);
        $sel->execute();
        $batches = $sel->get_result()->fetch_all(MYSQLI_ASSOC);
        $sel->close();

        $needed         = $quantity;
        $total_cost     = 0.0;
        $consumed_parts = [];

        foreach ($batches as $batch) {
            if ($needed <= 0) break;

            $available = (int) $batch['quantity_remaining'];
            $take      = min($needed, $available);
            $cost      = (float) $batch['cost_price'];

            $remaining = $available - $take;
            $upd = $conn->prepare(
                "UPDATE product_batches SET quantity_remaining = ? WHERE id = ?"
            );
            $upd->bind_param('ii', $remaining, $batch['id']);
            $upd->execute();
            $upd->close();

            $total_cost += $take * $cost;
            $needed     -= $take;

            $consumed_parts[] = [
                'batch_id' => (int) $batch['id'],
                'taken'    => $take,
                'cost'     => $cost,
            ];
        }

        if ($needed > 0) {
            $conn->rollback();
            return [
                'success' => false,
                'error'   => "Insufficient batch quantity for product #{$product_id}: still need {$needed} more units",
                'partial' => true,
            ];
        }

        $conn->commit();

        $avg_cost = $quantity > 0 ? round($total_cost / $quantity, 3) : 0;

        return [
            'success'    => true,
            'total_cost' => round($total_cost, 3),
            'avg_cost'   => $avg_cost,
            'consumed'   => $consumed_parts,
        ];
    } catch (Exception $e) {
        $conn->rollback();
        error_log('consumeProductBatches failed: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Restore a quantity back into the batch system (e.g. when a delivered
 * order is cancelled).  Adds the quantity back to an existing batch
 * that has the same cost_price, falling back to the most recent batch,
 * or creates a new batch entry as a last resort.
 *
 * Does NOT modify products.stock_quantity (restored separately by the caller).
 *
 * @param int   $product_id
 * @param int   $quantity     Units to restore
 * @param float $cost_per_item  The cost to use (typically from order_items.cost_per_item)
 * @return array ['success' => bool, 'error' => ?string]
 */
function restoreProductBatches($product_id, $quantity, $cost_per_item)
{
    global $conn;

    if (!is_numeric($product_id) || $product_id <= 0) {
        return ['success' => false, 'error' => 'Invalid product ID'];
    }
    $quantity      = (int) $quantity;
    $cost_per_item = (float) $cost_per_item;

    if ($quantity <= 0) {
        return ['success' => false, 'error' => 'Quantity must be greater than 0'];
    }

    $conn->begin_transaction();
    try {
        // Try to find a batch with matching cost_price to add back to
        $find = $conn->prepare(
            "SELECT id, quantity_remaining
             FROM product_batches
             WHERE product_id = ? AND cost_price = ?
             ORDER BY created_at DESC
             LIMIT 1
             FOR UPDATE"
        );
        $find->bind_param('id', $product_id, $cost_per_item);
        $find->execute();
        $match = $find->get_result()->fetch_assoc();
        $find->close();

        if ($match) {
            $new_remaining = (int) $match['quantity_remaining'] + $quantity;
            $upd = $conn->prepare(
                "UPDATE product_batches SET quantity_remaining = ? WHERE id = ?"
            );
            $upd->bind_param('ii', $new_remaining, $match['id']);
            $upd->execute();
            $upd->close();
        } else {
            // Create a new batch entry with the restored quantity
            $ins = $conn->prepare(
                "INSERT INTO product_batches (product_id, quantity_added, quantity_remaining, cost_price)
                 VALUES (?, ?, ?, ?)"
            );
            $ins->bind_param('iiid', $product_id, $quantity, $quantity, $cost_per_item);
            $ins->execute();
            $ins->close();
        }

        $conn->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $conn->rollback();
        error_log('restoreProductBatches failed: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get a summary of all batches for a product.
 *
 * @param int $product_id
 * @return array ['success' => bool, 'batches' => ?array, 'total_remaining' => ?int, 'error' => ?string]
 */
function getProductBatchSummary($product_id)
{
    global $conn;

    if (!is_numeric($product_id) || $product_id <= 0) {
        return ['success' => false, 'error' => 'Invalid product ID'];
    }

    try {
        $stmt = $conn->prepare(
            "SELECT id, quantity_added, quantity_remaining, cost_price, created_at
             FROM product_batches
             WHERE product_id = ?
             ORDER BY created_at ASC, id ASC"
        );
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $batches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $total_remaining = 0;
        foreach ($batches as &$b) {
            $b['cost_price'] = (float) $b['cost_price'];
            $b['quantity_added'] = (int) $b['quantity_added'];
            $b['quantity_remaining'] = (int) $b['quantity_remaining'];
            $b['id'] = (int) $b['id'];
            $total_remaining += $b['quantity_remaining'];
        }
        unset($b);

        return [
            'success'         => true,
            'batches'         => $batches,
            'total_remaining' => $total_remaining,
        ];
    } catch (Exception $e) {
        error_log('getProductBatchSummary failed: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get the FIFO cost for a given quantity without consuming (peek).
 *
 * @param int $product_id
 * @param int $quantity
 * @return array ['success' => bool, 'avg_cost' => ?float, 'total_cost' => ?float, 'error' => ?string]
 */
function getProductBatchCost($product_id, $quantity)
{
    global $conn;

    if (!is_numeric($product_id) || $product_id <= 0) {
        return ['success' => false, 'error' => 'Invalid product ID'];
    }
    $quantity = (int) $quantity;

    try {
        $stmt = $conn->prepare(
            "SELECT quantity_remaining, cost_price
             FROM product_batches
             WHERE product_id = ? AND quantity_remaining > 0
             ORDER BY created_at ASC, id ASC"
        );
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $batches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $needed     = $quantity;
        $total_cost = 0.0;

        foreach ($batches as $batch) {
            if ($needed <= 0) break;
            $available   = (int) $batch['quantity_remaining'];
            $take        = min($needed, $available);
            $total_cost += $take * (float) $batch['cost_price'];
            $needed     -= $take;
        }

        if ($needed > 0) {
            return [
                'success' => false,
                'avg_cost' => null,
                'total_cost' => null,
                'error'   => "Insufficient batch quantity: still need {$needed} more units",
            ];
        }

        $avg_cost = $quantity > 0 ? round($total_cost / $quantity, 3) : 0;

        return [
            'success'    => true,
            'avg_cost'   => $avg_cost,
            'total_cost' => round($total_cost, 3),
        ];
    } catch (Exception $e) {
        error_log('getProductBatchCost failed: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

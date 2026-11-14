<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use App\Core\ModuleRegistry;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\CatalogServiceInterface;
use Modules\Sales\Contracts\OrderServiceInterface;
use Modules\Sales\Events\OrderPlaced;
use Modules\Sales\Models\Order;
use Modules\Sales\Models\OrderAddress;
use Modules\Sales\Models\OrderItem;
use Modules\Sales\Models\OrderSetting;

class OrderService implements OrderServiceInterface
{
    /**
     * Create a new order with items and shipping address.
     */
    public function createOrder(
        array $items,
        array $addressData,
        ?string $customerNotes = null,
        string $paymentMethod = 'cod'
    ): Order {
        $addressData = \App\Core\HtmlSanitizer::cleanArray($addressData);
        $customerNotes = $customerNotes ? \App\Core\HtmlSanitizer::clean($customerNotes) : null;

        $order = DB::transaction(function () use ($items, $addressData, $customerNotes, $paymentMethod) {
            $validated = $this->validateCartItems($items);

            $orderNumber = Order::generateOrderNumber();

            $subtotal = $validated['subtotal'];
            $shippingFee = 0.00;
            $taxAmount = 0.00;
            $totalAmount = $subtotal + $shippingFee + $taxAmount;

            $order = Order::create([
                'order_number' => $orderNumber,
                'access_token' => \Illuminate\Support\Str::random(64),
                'status' => 'pending',
                'customer_name' => trim((string) $addressData['full_name']),
                'customer_phone' => trim((string) $addressData['phone']),
                'customer_email' => isset($addressData['email']) ? trim((string) $addressData['email']) : null,
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'payment_method' => $paymentMethod,
                'payment_status' => 'pending',
                'customer_notes' => $customerNotes ? trim($customerNotes) : null,
            ]);

            // Save Shipping Address
            OrderAddress::create([
                'order_id' => $order->id,
                'type' => 'shipping',
                'full_name' => trim((string) $addressData['full_name']),
                'phone' => trim((string) $addressData['phone']),
                'alternate_phone' => !empty($addressData['alternate_phone']) ? trim((string) $addressData['alternate_phone']) : null,
                'address_line1' => trim((string) $addressData['address_line1']),
                'landmark' => !empty($addressData['landmark']) ? trim((string) $addressData['landmark']) : null,
                'city' => trim((string) $addressData['city']),
                'district' => trim((string) $addressData['district']),
                'state' => trim((string) $addressData['state']),
                'pincode' => trim((string) $addressData['pincode']),
            ]);

            // Save Snapshot of Order Items
            foreach ($validated['items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'sku' => $item['sku'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'line_total' => $item['line_total'],
                    'image' => $item['image'],
                    'options' => $item['options'],
                ]);
            }

            return $order->load(['items', 'shippingAddress']);
        });

        event(new OrderPlaced($order));

        return $order;
    }

    /**
     * Validate cart items against the catalog service.
     */
    public function validateCartItems(array $items): array
    {
        if (empty($items)) {
            throw new \InvalidArgumentException('Cannot create an order with an empty cart.');
        }

        $catalogService = null;
        if (ModuleRegistry::isEnabled('Catalog') && interface_exists(CatalogServiceInterface::class)) {
            $catalogService = app(CatalogServiceInterface::class);
        }

        $validatedItems = [];
        $subtotal = 0.00;

        foreach ($items as $rawItem) {
            $productId = $rawItem['id'] ?? $rawItem['product_id'] ?? null;
            $quantity = max(1, (int) ($rawItem['quantity'] ?? 1));

            if ($catalogService && $catalogService->isAvailable()) {
                if (! $productId) {
                    throw new \InvalidArgumentException('Each item must have a valid product ID.');
                }

                $product = $catalogService->findProduct($productId);

                if (! $product) {
                    $name = $rawItem['name'] ?? "ID: {$productId}";
                    throw new \InvalidArgumentException("Product '{$name}' is not found in our catalog.");
                }

                if (isset($product->is_active) && ! $product->is_active) {
                    throw new \InvalidArgumentException("Product '{$product->name}' is currently unavailable.");
                }

                // Support variant price / title if variant_id provided
                $variantId = $rawItem['variant_id'] ?? null;
                $variant = null;
                if ($variantId && method_exists($product, 'variants')) {
                    $variant = $product->variants->firstWhere('id', $variantId);
                }

                if ($variant) {
                    $unitPrice = (float) ($variant->sale_price ?? $variant->actual_price ?? 0);
                    $productName = "{$product->name} - {$variant->title}";
                    $sku = $variant->sku ?? $product->sku ?? null;
                    $image = $variant->image ?? $rawItem['image'] ?? null;
                    $options = ['variant_id' => $variant->id, 'variant_title' => $variant->title];
                } else {
                    $unitPrice = (float) ($product->sale_price ?? $product->actual_price ?? 0);
                    $productName = $product->name;
                    $sku = $product->sku ?? null;
                    $image = $rawItem['image'] ?? null;
                    $options = null;
                }
            } else {
                // Standalone fallback if catalog is not active
                $unitPrice = (float) ($rawItem['price'] ?? 0);
                $productName = (string) ($rawItem['name'] ?? 'Product');
                $sku = $rawItem['sku'] ?? null;
                $image = $rawItem['image'] ?? null;
                $options = null;
            }

            $lineTotal = round($unitPrice * $quantity, 2);
            $subtotal += $lineTotal;

            $validatedItems[] = [
                'product_id' => $productId ? (int) $productId : null,
                'product_name' => $productName,
                'sku' => $sku,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'line_total' => $lineTotal,
                'image' => $image,
                'options' => $options,
            ];
        }

        return [
            'items' => $validatedItems,
            'subtotal' => round($subtotal, 2),
        ];
    }

    /**
     * Find an order by its unique order number.
     */
    public function findOrderByNumber(string $orderNumber): ?Order
    {
        return Order::with(['items', 'shippingAddress'])
            ->where('order_number', $orderNumber)
            ->first();
    }

    /**
     * Update order status.
     */
    public function updateOrderStatus(int|string $orderId, string $status, ?string $adminNotes = null): Order
    {
        $order = Order::findOrFail($orderId);
        $order->status = $status;
        if ($adminNotes !== null) {
            $order->admin_notes = $adminNotes;
        }
        $order->save();

        return $order;
    }

    /**
     * Get the active order settings.
     */
    public function getSettings(): OrderSetting
    {
        return OrderSetting::instance();
    }
}


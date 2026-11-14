<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use App\Core\ModuleRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        $addressData = array_map(function ($val) {
            return is_string($val) ? trim(strip_tags($val)) : $val;
        }, $addressData);
        $customerNotes = $customerNotes ? trim(strip_tags($customerNotes)) : null;

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
            // Resolve productId, variantId, and variantTitle from rawItem
            $productId = $rawItem['product_id'] ?? $rawItem['productId'] ?? null;
            $variantId = $rawItem['variant_id'] ?? $rawItem['variantId'] ?? ($rawItem['options']['variant_id'] ?? null);
            $variantTitle = $rawItem['variant_title'] ?? $rawItem['variantTitle'] ?? ($rawItem['options']['variant_title'] ?? null);

            $rawId = $rawItem['id'] ?? null;

            if (! $productId && $rawId !== null) {
                if (is_string($rawId) && str_contains($rawId, '_')) {
                    $parts = explode('_', $rawId);
                    $productId = is_numeric($parts[0]) ? (int) $parts[0] : $parts[0];
                    if (! $variantId && isset($parts[1])) {
                        $variantId = is_numeric($parts[1]) ? (int) $parts[1] : $parts[1];
                    }
                } elseif (is_numeric($rawId)) {
                    $productId = (int) $rawId;
                } else {
                    $productId = $rawId;
                }
            } elseif (is_string($productId) && str_contains($productId, '_')) {
                $parts = explode('_', $productId);
                $productId = is_numeric($parts[0]) ? (int) $parts[0] : $parts[0];
                if (! $variantId && isset($parts[1])) {
                    $variantId = is_numeric($parts[1]) ? (int) $parts[1] : $parts[1];
                }
            }

            $quantity = max(1, (int) ($rawItem['quantity'] ?? 1));

            if ($catalogService && $catalogService->isAvailable()) {
                if (! $productId) {
                    throw new \InvalidArgumentException('Each item must have a valid product ID.');
                }

                $product = $catalogService->findProduct($productId);

                if (! $product) {
                    $name = $rawItem['name'] ?? $rawItem['product_name'] ?? "ID: {$productId}";
                    throw new \InvalidArgumentException("Product '{$name}' is not found in our catalog.");
                }

                if (isset($product->is_active) && ! $product->is_active) {
                    throw new \InvalidArgumentException("Product '{$product->name}' is currently unavailable.");
                }

                // Support variant price / title if variant_id or variant_title provided
                $variant = null;
                if ($variantId && method_exists($product, 'variants')) {
                    $variant = $product->variants->firstWhere('id', '==', $variantId);
                    if (! $variant && $variantTitle) {
                        $variant = $product->variants->firstWhere('title', $variantTitle);
                    }
                } elseif ($variantTitle && method_exists($product, 'variants')) {
                    $variant = $product->variants->firstWhere('title', $variantTitle);
                }

                if ($variant) {
                    if (isset($variant->is_active) && ! $variant->is_active) {
                        throw new \InvalidArgumentException("Variant '{$variant->title}' of '{$product->name}' is currently unavailable.");
                    }

                    $unitPrice = (float) (! empty($variant->sale_price) && (float) $variant->sale_price > 0
                        ? $variant->sale_price
                        : ($variant->actual_price ?? $product->sale_price ?? $product->actual_price ?? 0));

                    $productName = "{$product->name} ({$variant->title})";
                    $sku = $variant->sku ?? $product->sku ?? null;
                    $image = $this->resolveImageUrl($variant->image ?: ($rawItem['image'] ?? null));
                    $options = [
                        'variant_id' => $variant->id,
                        'variant_title' => $variant->title,
                    ];
                } else {
                    $unitPrice = (float) (! empty($product->sale_price) && (float) $product->sale_price > 0
                        ? $product->sale_price
                        : ($product->actual_price ?? 0));
                    $productName = $product->name;
                    $sku = $product->sku ?? null;
                    $image = $this->resolveImageUrl($rawItem['image'] ?? ($product->gallery_images[0] ?? null));
                    $options = null;
                }
            } else {
                // Standalone fallback if catalog is not active
                $unitPrice = (float) ($rawItem['price'] ?? $rawItem['unit_price'] ?? 0);
                $productName = (string) ($rawItem['name'] ?? $rawItem['product_name'] ?? 'Product');
                $sku = $rawItem['sku'] ?? null;
                $image = $this->resolveImageUrl($rawItem['image'] ?? null);
                $options = ! empty($variantId) ? ['variant_id' => $variantId] : null;
            }

            $lineTotal = round($unitPrice * $quantity, 2);
            $subtotal += $lineTotal;

            $validatedItems[] = [
                'id' => $rawItem['id'] ?? ($productId . ($variantId ? "_{$variantId}" : '')),
                'product_id' => is_numeric($productId) ? (int) $productId : $productId,
                'variant_id' => $variant ? $variant->id : ($variantId ? (is_numeric($variantId) ? (int) $variantId : $variantId) : null),
                'product_name' => $productName,
                'name' => $productName,
                'sku' => $sku,
                'unit_price' => $unitPrice,
                'price' => $unitPrice,
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

    /**
     * Resolve a relative media path or public storage path into a fully qualified web URI.
     */
    protected function resolveImageUrl(?string $image): ?string
    {
        if (empty($image)) {
            return null;
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://') || str_starts_with($image, '//')) {
            return $image;
        }

        if (str_starts_with($image, '/storage/')) {
            return $image;
        }

        if (str_starts_with($image, 'storage/')) {
            return '/' . $image;
        }

        return Storage::url($image);
    }
}


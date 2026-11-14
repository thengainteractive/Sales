<?php

declare(strict_types=1);

namespace Modules\Sales\Contracts;

use Modules\Sales\Models\Order;
use Modules\Sales\Models\OrderSetting;

interface OrderServiceInterface
{
    /**
     * Create a new order with items and shipping address.
     *
     * @param array<int|string, array{id: int|string, quantity: int, name?: string, price?: float, image?: string, variant_id?: int|null}> $items
     * @param array{
     *     full_name: string,
     *     phone: string,
     *     alternate_phone?: string|null,
     *     address_line1: string,
     *     landmark?: string|null,
     *     city: string,
     *     district: string,
     *     state: string,
     *     pincode: string,
     *     email?: string|null
     * } $addressData
     * @param string|null $customerNotes
     * @param string $paymentMethod
     * @return Order
     * @throws \Exception
     */
    public function createOrder(
        array $items,
        array $addressData,
        ?string $customerNotes = null,
        string $paymentMethod = 'cod'
    ): Order;

    /**
     * Validate cart items against the catalog service.
     *
     * @param array<int|string, array{id: int|string, quantity: int, name?: string, price?: float, image?: string, variant_id?: int|null}> $items
     * @return array{
     *     items: array<int, array{
     *         product_id: int|null,
     *         product_name: string,
     *         sku: string|null,
     *         unit_price: float,
     *         quantity: int,
     *         line_total: float,
     *         image: string|null,
     *         options: array<string, mixed>|null
     *     }>,
     *     subtotal: float
     * }
     * @throws \Exception
     */
    public function validateCartItems(array $items): array;

    /**
     * Find an order by its unique order number.
     */
    public function findOrderByNumber(string $orderNumber): ?Order;

    /**
     * Update order status.
     */
    public function updateOrderStatus(int|string $orderId, string $status, ?string $adminNotes = null): Order;

    /**
     * Get the active order settings.
     */
    public function getSettings(): OrderSetting;
}

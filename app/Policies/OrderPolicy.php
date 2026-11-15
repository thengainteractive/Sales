<?php

declare(strict_types=1);

namespace Modules\Sales\Policies;

use App\Models\User;
use Modules\Sales\Models\Order;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->can('view_orders');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->hasRole('Super Admin') || $user->can('view_orders');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->can('create_orders');
    }

    public function update(User $user, Order $order): bool
    {
        return $user->hasRole('Super Admin') || $user->can('edit_orders');
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->hasRole('Super Admin') || $user->can('delete_orders');
    }
}

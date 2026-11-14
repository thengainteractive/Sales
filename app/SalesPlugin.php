<?php

declare(strict_types=1);

namespace Modules\Sales;

use Filament\Contracts\Plugin;
use Filament\Panel;

class SalesPlugin implements Plugin
{
    public function getId(): string
    {
        return 'Sales';
    }

    public function register(Panel $panel): void
    {
        $panel->discoverResources(
            in: module_path('Sales', 'app/Filament/Resources'),
            for: "Modules\\Sales\\Filament\\Resources"
        );

        $panel->discoverPages(
            in: module_path('Sales', 'app/Filament/Pages'),
            for: "Modules\\Sales\\Filament\\Pages"
        );

        $panel->discoverWidgets(
            in: module_path('Sales', 'app/Filament/Widgets'),
            for: "Modules\\Sales\\Filament\\Widgets"
        );
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }
}

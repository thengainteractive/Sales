<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components as SchemaComponents;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Sales\Models\OrderSetting;

class ManageOrderSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static \UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Order Settings';

    protected static ?string $title = 'Order Settings';

    protected static ?int $navigationSort = 15;

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        return $user && ($user->hasRole('Super Admin') || $user->hasPermissionTo('manage_order_settings') || $user->hasPermissionTo('view_orders'));
    }

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $setting = OrderSetting::instance();
        $this->getSchema('form')->fill($setting->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->columns(1)
            ->components([
                SchemaComponents\Tabs::make('OrderSettingsTabs')
                    ->tabs([
                        SchemaComponents\Tabs\Tab::make('Checkout & WhatsApp')
                            ->icon(Heroicon::OutlinedShoppingBag)
                            ->columns(2)
                            ->schema([
                                SchemaComponents\Section::make('Checkout Methods')
                                    ->description('Enable or disable website checkout channels for customers.')
                                    ->columns(2)
                                    ->schema([
                                        Components\Toggle::make('enable_standard_checkout')
                                            ->label('Enable Standard Checkout (Website / COD)')
                                            ->default(true),

                                        Components\Toggle::make('enable_whatsapp_checkout')
                                            ->label('Enable WhatsApp Checkout')
                                            ->default(true),

                                        Components\TextInput::make('shipping_text')
                                            ->label('Checkout Shipping Text')
                                            ->placeholder('Enter Shipping Text')
                                            ->default('Shipping charges extra')
                                            ->columnSpanFull(),
                                    ]),

                                SchemaComponents\Section::make('WhatsApp Order Channel')
                                    ->description('Configure the WhatsApp channel for receiving customer orders and inquiries.')
                                    ->columns(2)
                                    ->schema([
                                        Components\TextInput::make('whatsapp_number')
                                            ->label('WhatsApp Number (Orders)')
                                            ->placeholder('Enter WhatsApp Number')
                                            ->tel()
                                            ->prefixIcon(Heroicon::OutlinedPhone),

                                        Components\TextInput::make('whatsapp_order_prefix')
                                            ->label('WhatsApp Message Title')
                                            ->placeholder('Enter WhatsApp Message Title')
                                            ->default('New Order'),

                                        Components\Textarea::make('custom_order_message')
                                            ->label('Custom Order Instructions')
                                            ->rows(3)
                                            ->placeholder('Enter Custom Order Instructions')
                                            ->columnSpanFull(),
                                    ]),

                                SchemaComponents\Section::make('Customer Support & Contact')
                                    ->description('Official contact details for order support, inquiries, and customer communication.')
                                    ->columns(2)
                                    ->schema([
                                        Components\TextInput::make('contact_phone')
                                            ->label('Order Support Phone')
                                            ->placeholder('Enter Order Support Phone')
                                            ->tel()
                                            ->prefixIcon(Heroicon::OutlinedPhone),

                                        Components\TextInput::make('contact_email')
                                            ->label('Order Support Email')
                                            ->placeholder('Enter Order Support Email')
                                            ->email()
                                            ->prefixIcon(Heroicon::OutlinedEnvelope),

                                        Components\TextInput::make('contact_person')
                                            ->label('Contact Person / Desk')
                                            ->placeholder('Enter Contact Person / Desk')
                                            ->columnSpanFull(),

                                        Components\Textarea::make('business_address')
                                            ->label('Fulfillment / Dispatch Address')
                                            ->rows(3)
                                            ->placeholder('Enter Address')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        SchemaComponents\Tabs\Tab::make('Fulfillment & Notifications')
                            ->icon(Heroicon::OutlinedBell)
                            ->columns(2)
                            ->schema([
                                SchemaComponents\Section::make('Admin Order Notifications')
                                    ->description('Configure notifications sent when an order is placed.')
                                    ->schema([
                                        Components\Toggle::make('notify_admin_on_order')
                                            ->label('Notify Admin on New Order')
                                            ->default(true),

                                        Components\TextInput::make('admin_notification_email')
                                            ->label('Admin Alert Email')
                                            ->placeholder('Enter Admin Alert Email')
                                            ->email()
                                            ->prefixIcon(Heroicon::OutlinedEnvelope),

                                        Components\TextInput::make('order_number_prefix')
                                            ->label('Order Number Prefix')
                                            ->placeholder('Enter Order Number Prefix')
                                            ->default('ORD'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                SchemaComponents\Form::make([
                    SchemaComponents\EmbeddedSchema::make('form'),
                ])
                ->livewireSubmitHandler('save')
                ->footer([
                    SchemaComponents\Actions::make($this->getFormActions()),
                ]),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Changes')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    public function save(): void
    {
        $state = $this->getSchema('form')->getState();

        $setting = OrderSetting::instance();
        $setting->update($state);

        Notification::make()
            ->title('Order Settings Saved')
            ->body('Order module settings have been successfully updated.')
            ->success()
            ->send();
    }
}

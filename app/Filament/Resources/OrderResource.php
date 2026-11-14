<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\Resources;

use Filament\Actions;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use Filament\Schemas\Components as SchemaComponents;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Sales\Filament\Resources\OrderResource\Pages;
use Modules\Sales\Models\Order;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static \UnitEnum|string|null $navigationGroup = 'Sales';

    protected static ?string $navigationLabel = 'Orders';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                SchemaComponents\Group::make([
                    SchemaComponents\Section::make('Order Summary')
                        ->columns(2)
                        ->schema([
                            Components\TextInput::make('order_number')
                                ->label('Order #')
                                ->disabled(),

                            Components\TextInput::make('total_amount')
                                ->label('Total Amount')
                                ->prefix('₹')
                                ->disabled(),

                            Components\Select::make('status')
                                ->label('Order Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'confirmed' => 'Confirmed',
                                    'processing' => 'Processing',
                                    'shipped' => 'Shipped',
                                    'delivered' => 'Delivered',
                                    'cancelled' => 'Cancelled',
                                ])
                                ->required(),

                            Components\Select::make('payment_status')
                                ->label('Payment Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'paid' => 'Paid',
                                    'failed' => 'Failed',
                                    'refunded' => 'Refunded',
                                ])
                                ->required(),

                            Components\TextInput::make('payment_method')
                                ->label('Payment Method')
                                ->disabled(),

                            Components\TextInput::make('created_at')
                                ->label('Order Date')
                                ->disabled(),
                        ]),

                    SchemaComponents\Section::make('Shipping Address')
                        ->relationship('shippingAddress')
                        ->columns(2)
                        ->schema([
                            Components\TextInput::make('full_name')
                                ->label('Full Name')
                                ->required(),

                            Components\TextInput::make('phone')
                                ->label('Mobile Number')
                                ->required(),

                            Components\TextInput::make('alternate_phone')
                                ->label('Alternate Number')
                                ->nullable(),

                            Components\TextInput::make('address_line1')
                                ->label('House/Building/Street')
                                ->required()
                                ->columnSpanFull(),

                            Components\TextInput::make('landmark')
                                ->label('Landmark')
                                ->nullable(),

                            Components\TextInput::make('city')
                                ->label('City/Town')
                                ->required(),

                            Components\TextInput::make('district')
                                ->label('District')
                                ->required(),

                            Components\TextInput::make('state')
                                ->label('State')
                                ->required(),

                            Components\TextInput::make('pincode')
                                ->label('Pin Code')
                                ->required(),
                        ]),

                    SchemaComponents\Section::make('Order Items')
                        ->schema([
                            Components\Repeater::make('items')
                                ->relationship('items')
                                ->schema([
                                    Components\TextInput::make('product_name')
                                        ->label('Product')
                                        ->disabled()
                                        ->columnSpan(2),

                                    Components\TextInput::make('unit_price')
                                        ->label('Unit Price')
                                        ->prefix('₹')
                                        ->disabled(),

                                    Components\TextInput::make('quantity')
                                        ->label('Qty')
                                        ->disabled(),

                                    Components\TextInput::make('line_total')
                                        ->label('Line Total')
                                        ->prefix('₹')
                                        ->disabled(),
                                ])
                                ->columns(5)
                                ->addable(false)
                                ->deletable(false)
                                ->reorderable(false),
                        ]),
                ])->columnSpan(2),

                SchemaComponents\Group::make([
                    SchemaComponents\Section::make('Customer & Notes')
                        ->schema([
                            Components\TextInput::make('customer_name')
                                ->label('Customer Name')
                                ->disabled(),

                            Components\TextInput::make('customer_phone')
                                ->label('Customer Phone')
                                ->disabled(),

                            Components\TextInput::make('customer_email')
                                ->label('Customer Email')
                                ->disabled(),

                            Components\Textarea::make('customer_notes')
                                ->label('Customer Note')
                                ->rows(3)
                                ->disabled(),

                            Components\Textarea::make('admin_notes')
                                ->label('Staff / Admin Notes')
                                ->rows(4)
                                ->placeholder('Add internal order fulfillment notes here...'),
                        ]),
                ])->columnSpan(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer_phone')
                    ->label('Phone')
                    ->searchable(),

                Tables\Columns\TextColumn::make('shippingAddress.city')
                    ->label('City')
                    ->searchable(),

                Tables\Columns\TextColumn::make('shippingAddress.state')
                    ->label('State')
                    ->searchable(),

                Tables\Columns\SelectColumn::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ])
                    ->selectablePlaceholder(false)
                    ->sortable(),

                Tables\Columns\SelectColumn::make('payment_status')
                    ->label('Payment')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ])
                    ->selectablePlaceholder(false)
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('INR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ]),
            ])
            ->recordActions([
                Actions\Action::make('updateStatus')
                    ->label('Status')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('primary')
                    ->modalHeading('Update Order Status')
                    ->form([
                        Components\Select::make('status')
                            ->label('Order Status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'processing' => 'Processing',
                                'shipped' => 'Shipped',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default(fn (Order $record): string => $record->status)
                            ->required(),

                        Components\Select::make('payment_status')
                            ->label('Payment Status')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'failed' => 'Failed',
                                'refunded' => 'Refunded',
                            ])
                            ->default(fn (Order $record): string => $record->payment_status)
                            ->required(),

                        Components\Textarea::make('admin_notes')
                            ->label('Internal Notes')
                            ->default(fn (Order $record): ?string => $record->admin_notes)
                            ->rows(3),
                    ])
                    ->action(function (Order $record, array $data): void {
                        $record->update([
                            'status' => $data['status'],
                            'payment_status' => $data['payment_status'],
                            'admin_notes' => $data['admin_notes'] ?? $record->admin_notes,
                        ]);
                    }),
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}

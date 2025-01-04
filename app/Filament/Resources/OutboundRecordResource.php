<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OutboundRecordResource\Pages;
use App\Filament\Resources\OutboundRecordResource\RelationManagers;
use App\Models\OutboundRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;

class OutboundRecordResource extends Resource
{
    protected static ?string $model = OutboundRecord::class;

    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'Barang Keluar';
    protected static ?string $pluralModelLabel = 'Barang Keluar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('lkb_number')
                            ->label('LKB Number')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->regex('/^LKB-\d{6}$/')
                            ->validationMessages([
                                'regex' => 'Format harus LKB-XXXXXX (X=angka)',
                            ]),
                        Forms\Components\TextInput::make('delivery_note_number')
                            ->label('Delivery Note Number')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\DatePicker::make('outbound_date')
                            ->required(),
                        Forms\Components\Select::make('customer_id')
                            ->relationship('customer', 'customer_name')
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('project_id')
                            ->relationship('project', 'project_name')
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('purpose')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Items')
                    ->schema([
                        Forms\Components\Repeater::make('outboundItems')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('item_id')
                                    ->relationship('item', 'serial_number', fn ($query) => $query->where('status', 'available'))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                        if ($state) {
                                            $item = \App\Models\Item::find($state);
                                            if ($item && $item->status !== 'available') {
                                                $set('item_id', null);
                                                Notification::make()
                                                    ->title('Item not available')
                                                    ->danger()
                                                    ->send();
                                            }
                                        }
                                    }),
                                Forms\Components\TextInput::make('quantity')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lkb_number')
                    ->label('LKB Number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivery_note_number')
                    ->label('Delivery Note')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('outbound_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.customer_name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('project.project_name')
                    ->label('Project')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('purpose')
                    ->searchable()
                    ->limit(30),
            ])
            ->filters([
                Filter::make('outbound_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('outbound_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('outbound_date', '<=', $date),
                            );
                    }),
                SelectFilter::make('customer')
                    ->relationship('customer', 'customer_name'),
                SelectFilter::make('project')
                    ->relationship('project', 'project_name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOutboundRecords::route('/'),
            'create' => Pages\CreateOutboundRecord::route('/create'),
            'edit' => Pages\EditOutboundRecord::route('/{record}/edit'),
        ];
    }
}

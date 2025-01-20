<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $modelLabel = 'Project';

    protected static ?string $pluralModelLabel = 'Projects';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Project')
                    ->schema([
                        Forms\Components\TextInput::make('project_id')
                            ->label('Project ID')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('project_name')
                            ->label('Nama Project')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('vendor_id')
                            ->relationship('vendor', 'vendor_name')
                            ->label('Customer')
                            ->required()
                            ->preload()
                            ->searchable(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project_id')
                    ->label('Project ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('project_name')
                    ->label('Nama Project')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendor.vendor_name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
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
            ])
            ->recordUrl(fn($record) => static::getUrl('view', ['record' => $record]));
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
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'view' => Pages\ViewProject::route('/{record}'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Project')
                    ->schema([
                        TextEntry::make('project_id')
                            ->label('Project ID'),
                        TextEntry::make('project_name')
                            ->label('Nama Project'),
                        TextEntry::make('vendor.vendor_name')
                            ->label('Customer'),
                    ])
                    ->columns(2),

                Section::make('Purchase Orders')
                    ->schema([
                        RepeatableEntry::make('purchaseOrders')
                            ->schema([
                                TextEntry::make('po_number')
                                    ->label('Nomor PO'),
                                TextEntry::make('po_date')
                                    ->label('Tanggal PO')
                                    ->date(),
                                TextEntry::make('vendor.vendor_name')
                                    ->label('Supplier'),
                            ])
                            ->columns(3)
                    ]),

                Section::make('Barang Masuk')
                    ->schema([
                        RepeatableEntry::make('inboundRecords')
                            ->schema([
                                TextEntry::make('lpb_number')
                                    ->label('Nomor LPB')
                                    ->url(fn ($record) => url("/admin/inbound-records/{$record->inbound_id}"))
                                    ->openUrlInNewTab(),
                                TextEntry::make('receive_date')
                                    ->label('Tanggal Terima')
                                    ->date(),
                                TextEntry::make('inboundItems_count')
                                    ->label('Jumlah Item')
                                    ->state(function ($record) {
                                        return $record->inboundItems->count();
                                    }),
                            ])
                            ->columns(3),
                    ]),

                Section::make('Barang Keluar')
                    ->schema([
                        RepeatableEntry::make('outboundRecords')
                            ->schema([
                                TextEntry::make('lkb_number')
                                    ->label('Nomor LKB')
                                    ->url(fn ($record) => url("/admin/outbound-records/{$record->outbound_id}"))
                                    ->openUrlInNewTab(),
                                TextEntry::make('delivery_date')
                                    ->label('Tanggal Keluar')
                                    ->date(),
                                TextEntry::make('purpose.name')
                                    ->label('Tujuan'),
                                TextEntry::make('outboundItems_count')
                                    ->label('Jumlah Item')
                                    ->state(function ($record) {
                                        return $record->outboundItems->count();
                                    }),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }

    public static function getRecordRouteKeyName(): string
    {
        return 'project_id';
    }
}

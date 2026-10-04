<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SystemSettingResource\Pages;
use App\Models\SystemSetting;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SystemSettingResource extends Resource
{
    protected static ?string $model = SystemSetting::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'System Administration';

    protected static ?string $navigationLabel = 'Commercial & System Settings';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label('Setting Key')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->extraAttributes(['class' => 'font-mono text-xs font-semibold text-slate-900']),

                TextColumn::make('group')
                    ->label('Setting Domain')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'commercial', 'finance' => 'success',
                        'security' => 'danger',
                        'logistics' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('value')
                    ->label('Configured Value')
                    ->formatStateUsing(function ($state, SystemSetting $record): string {
                        if ($record->key === 'commercial_vat_rate') {
                            return "{$state}% (National Standard)";
                        }

                        return (string) $state;
                    })
                    ->extraAttributes(['class' => 'font-mono text-xs font-bold text-sky-700']),

                TextColumn::make('type')
                    ->label('Data Type')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('description')
                    ->label('Purpose & Impact')
                    ->wrap(),

                IconColumn::make('is_public')
                    ->label('Public Facing')
                    ->boolean()
                    ->trueIcon('heroicon-o-globe-alt')
                    ->falseIcon('heroicon-o-lock-closed')
                    ->alignment('center'),
            ])
            ->defaultSort('group', 'asc')
            ->filters([
                SelectFilter::make('group')
                    ->options([
                        'commercial' => 'Commercial & Tax',
                        'finance' => 'Finance & Commissions',
                        'logistics' => 'Logistics & Warehousing',
                        'system' => 'Core Architecture',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSystemSettings::route('/'),
        ];
    }
}

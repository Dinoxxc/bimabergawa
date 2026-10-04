<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'System Administration';

    protected static ?string $navigationLabel = 'Staff & User Accounts';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Staff Member')
                    ->searchable()
                    ->sortable()
                    ->extraAttributes(['class' => 'font-semibold text-slate-900']),

                TextColumn::make('email')
                    ->label('Corporate Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->extraAttributes(['class' => 'font-mono text-xs text-slate-600']),

                TextColumn::make('roles.name')
                    ->label('Spatie RBAC Role')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => match ($state) {
                        'super_admin' => 'Super Admin',
                        'sales_pic' => 'Sales PIC',
                        'finance' => 'Finance',
                        'logistics' => 'Logistics',
                        'freelance_agent' => 'Mitra Freelance',
                        default => ucfirst((string) $state),
                    })
                    ->color(fn ($state): string => match ($state) {
                        'super_admin' => 'danger',
                        'sales_pic' => 'info',
                        'finance' => 'success',
                        'logistics' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('phone')
                    ->label('Phone Contact')
                    ->placeholder('-')
                    ->extraAttributes(['class' => 'font-mono text-xs text-slate-600']),

                IconColumn::make('is_active')
                    ->label('Access Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-no-symbol')
                    ->alignment('center'),

                TextColumn::make('created_at')
                    ->label('Onboarded')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'asc')
            ->filters([
                SelectFilter::make('role')
                    ->relationship('roles', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
        ];
    }
}

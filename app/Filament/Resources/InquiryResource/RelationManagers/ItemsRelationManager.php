<?php

declare(strict_types=1);

namespace App\Filament\Resources\InquiryResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $recordTitleAttribute = 'item_name';

    protected function getItemFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('item_name')
                ->required()
                ->maxLength(255)
                ->label('Product / Item Name'),
            Forms\Components\TextInput::make('model_number')
                ->label('Part Number / Model')
                ->maxLength(100),
            Forms\Components\TextInput::make('quantity')
                ->numeric()
                ->default(1)
                ->minValue(1)
                ->required(),
            Forms\Components\TextInput::make('uom')
                ->label('Unit (UOM)')
                ->default('PCS')
                ->maxLength(50),
            Forms\Components\Textarea::make('custom_notes')
                ->label('Specification / Notes')
                ->maxLength(500)
                ->columnSpanFull(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item_name')
                    ->label('Item Name')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('model_number')
                    ->label('Model / SKU')
                    ->searchable()
                    ->fontFamily('mono')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->alignRight()
                    ->sortable(),
                Tables\Columns\TextColumn::make('uom')
                    ->label('UOM')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('custom_notes')
                    ->label('Notes')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->form($this->getItemFormSchema()),
            ])
            ->actions([
                EditAction::make()
                    ->form($this->getItemFormSchema()),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultPaginationPageOption(25)
            ->paginationPageOptions([10, 25, 50, 100]);
    }
}

<?php

namespace BacktikCh\LaravelAiUsage\Filament\Resources;

use BacktikCh\LaravelAiUsage\AiUsageLog;
use BacktikCh\LaravelAiUsage\AiUsageStatus;
use BacktikCh\LaravelAiUsage\Filament\Resources\AiUsageResource\Pages\ListAiUsage;
use BacktikCh\LaravelAiUsage\Filament\Resources\AiUsageResource\Pages\ViewAiUsage;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class AiUsageResource extends Resource
{
    protected static ?string $model = AiUsageLog::class;

    protected static ?string $navigationLabel = 'AI Usage';

    protected static ?string $modelLabel = 'AI Usage';

    protected static ?string $pluralModelLabel = 'AI Usage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CpuChip;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 90;

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Request Details')
                    ->schema([
                        TextEntry::make('label'),
                        TextEntry::make('driver'),
                        TextEntry::make('model'),
                        TextEntry::make('agent_class')
                            ->label('Agent'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (AiUsageStatus $state) => match ($state) {
                                AiUsageStatus::Completed => 'success',
                                AiUsageStatus::Failed => 'danger',
                                AiUsageStatus::Processing => 'warning',
                                AiUsageStatus::Pending => 'gray',
                            }),
                        TextEntry::make('duration_ms')
                            ->label('Duration')
                            ->formatStateUsing(fn (?int $state) => $state ? "{$state} ms" : '-'),
                        TextEntry::make('created_at')
                            ->label('Created at')
                            ->dateTime(),
                    ])
                    ->columns(2),
                Section::make('Token Usage')
                    ->schema([
                        TextEntry::make('prompt_tokens'),
                        TextEntry::make('completion_tokens'),
                        TextEntry::make('cache_write_tokens')
                            ->label('Cache Write Tokens'),
                        TextEntry::make('cache_read_tokens')
                            ->label('Cache Read Tokens'),
                        TextEntry::make('reasoning_tokens'),
                        TextEntry::make('estimated_cost')
                            ->label('Estimated Cost (USD)')
                            ->formatStateUsing(function (mixed $state): string {
                                if ($state === null) {
                                    return '— (no price configured)';
                                }
                                return '$' . number_format((float) $state, $state < 0.01 ? 6 : 4);
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->footerActions([])
                    ->description('Prices snapshotted at log time from config. null = price not configured.'),
                Section::make('Prompt')
                    ->schema([
                        TextEntry::make('prompt_text')
                            ->label('')
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
                Section::make('Response')
                    ->schema([
                        TextEntry::make('response_text')
                            ->label('')
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
                Section::make('Metadata')
                    ->schema([
                        TextEntry::make('request_meta')
                            ->label('Request Meta')
                            ->formatStateUsing(function (mixed $state): string {
                                if (is_string($state)) {
                                    $state = json_decode($state, true);
                                }

                                return $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '-';
                            })
                            ->columnSpanFull(),
                        TextEntry::make('response_meta')
                            ->label('Response Meta')
                            ->formatStateUsing(function (mixed $state): string {
                                if (is_string($state)) {
                                    $state = json_decode($state, true);
                                }

                                return $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '-';
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('driver')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('model')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('agent_class')
                    ->label('Agent')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '-')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('prompt_tokens')
                    ->label('Prompt Tok.')
                    ->sortable(),
                TextColumn::make('completion_tokens')
                    ->label('Comp. Tok.')
                    ->sortable(),
                TextColumn::make('duration_ms')
                    ->label('Duration')
                    ->formatStateUsing(fn (?int $state) => $state ? "{$state} ms" : '-')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (AiUsageStatus $state) => match ($state) {
                        AiUsageStatus::Completed => 'success',
                        AiUsageStatus::Failed => 'danger',
                        AiUsageStatus::Processing => 'warning',
                        AiUsageStatus::Pending => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('driver')
                    ->options(fn () => AiUsageLog::query()
                        ->select('driver')
                        ->distinct()
                        ->whereNotNull('driver')
                        ->pluck('driver', 'driver')),
                SelectFilter::make('label')
                    ->options(fn () => AiUsageLog::query()
                        ->select('label')
                        ->distinct()
                        ->whereNotNull('label')
                        ->pluck('label', 'label')),
                SelectFilter::make('status')
                    ->options(AiUsageStatus::class),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiUsage::route('/'),
            'view' => ViewAiUsage::route('/{record}'),
        ];
    }
}

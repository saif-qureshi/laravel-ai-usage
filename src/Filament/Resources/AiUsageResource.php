<?php

namespace BacktikCh\LaravelAiUsage\Filament\Resources;

use BackedEnum;
use BacktikCh\LaravelAiUsage\AiUsageLog;
use BacktikCh\LaravelAiUsage\AiUsageStatus;
use BacktikCh\LaravelAiUsage\Filament\Resources\AiUsageResource\Pages\ListAiUsage;
use BacktikCh\LaravelAiUsage\Filament\Resources\AiUsageResource\Pages\ViewAiUsage;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('owner');

        if (AiUsageLog::accountModelClass()) {
            $query->with('account');
        }

        return $query;
    }

    public static function infolist(Schema $schema): Schema
    {
        $tokenUsage = [
            TextEntry::make('prompt_tokens'),
            TextEntry::make('completion_tokens'),
            TextEntry::make('cache_write_tokens')
                ->label('Cache Write Tokens'),
            TextEntry::make('cache_read_tokens')
                ->label('Cache Read Tokens'),
            TextEntry::make('reasoning_tokens'),
        ];

        if (static::showsCosts()) {
            $tokenUsage[] = TextEntry::make('estimated_cost')
                ->label('Estimated Cost (USD)')
                ->formatStateUsing(function (mixed $state): string {
                    if ($state === null) {
                        return '— (no price configured)';
                    }

                    return '$'.number_format((float) $state, $state < 0.01 ? 6 : 4);
                })
                ->columnSpanFull();
        }

        return $schema
            ->components([
                Section::make('Request Details')
                    ->schema([
                        TextEntry::make('label'),
                        TextEntry::make('driver'),
                        TextEntry::make('model'),
                        TextEntry::make('agent_class')
                            ->label('Agent'),
                        TextEntry::make('account_id')
                            ->label('Account')
                            ->getStateUsing(fn (AiUsageLog $record): string => $record->accountDisplayName()),
                        TextEntry::make('owner_id')
                            ->label('Owner')
                            ->getStateUsing(fn (AiUsageLog $record): string => static::ownerLabel($record)),
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
                            ->formatStateUsing(fn (?int $state) => $state ? number_format($state / 1000, 2).' s' : '-'),
                        TextEntry::make('created_at')
                            ->label('Created at')
                            ->dateTime(),
                    ])
                    ->columns(2),
                Section::make('Token Usage')
                    ->schema($tokenUsage)
                    ->columns(3)
                    ->footerActions([])
                    ->description(static::showsCosts()
                        ? 'Prices snapshotted at log time from config. null = price not configured.'
                        : null),
                Section::make('System Prompt')
                    ->schema([
                        TextEntry::make('prompt_text')
                            ->label('')
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
                Section::make('User Prompt')
                    ->schema([
                        TextEntry::make('user_prompt_text')
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
                TextColumn::make('owner_id')
                    ->label('Owner')
                    ->getStateUsing(fn (AiUsageLog $record): string => static::ownerLabel($record))
                    ->toggleable(),
                TextColumn::make('account_id')
                    ->label('Account')
                    ->getStateUsing(fn (AiUsageLog $record): string => $record->accountDisplayName())
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('prompt_tokens')
                    ->label('Prompt Tok.')
                    ->sortable(),
                TextColumn::make('completion_tokens')
                    ->label('Comp. Tok.')
                    ->sortable(),
                TextColumn::make('duration_ms')
                    ->label('Duration')
                    ->formatStateUsing(fn (?int $state) => $state ? number_format($state / 1000, 2).' s' : '-')
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
                SelectFilter::make('account_id')
                    ->label('Account')
                    ->options(fn () => AiUsageLog::accountLabels(
                        AiUsageLog::query()
                            ->select('account_id')
                            ->distinct()
                            ->whereNotNull('account_id')
                            ->pluck('account_id'),
                    )),
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

    private static function ownerLabel(AiUsageLog $record): string
    {
        $owner = $record->owner;

        if ($owner) {
            $name = $owner->getAttribute('name');
            $email = $owner->getAttribute('email');

            if (filled($name) && filled($email)) {
                return "{$name} <{$email}>";
            }

            if (filled($name) || filled($email)) {
                return (string) ($name ?: $email);
            }

            return class_basename($owner::class).' #'.$owner->getKey();
        }

        if ($record->owner_type && $record->owner_id !== null) {
            return class_basename($record->owner_type).' #'.$record->owner_id;
        }

        return '-';
    }

    private static function showsCosts(): bool
    {
        return config('ai-usage.show_costs', true);
    }
}

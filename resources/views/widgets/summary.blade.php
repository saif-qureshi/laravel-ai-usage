<x-filament::section>
    <x-slot name="heading">AI Usage Summary</x-slot>

    {{-- Period selector --}}
    <div style="display:flex;justify-content:flex-end;margin-bottom:1rem;">
        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="period">
                @foreach ($this->getPeriods() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>
    </div>

    {{-- Compact stats bar --}}
    @php
        $stats = $this->getStatsData();
        $statItems = [
            'Total Calls'     => $stats['totalCalls'],
            'Total Tokens'    => number_format($stats['totalTokens']),
            'Avg Tokens/Call' => number_format($stats['avgTokensPerCall']),
            'Unique Drivers'  => $stats['uniqueDrivers'],
            'Unique Models'   => $stats['uniqueModels'],
            'Est. Cost (USD)' => $this->formatCost($stats['estimatedCost']),
        ];
    @endphp

    <div style="display:flex;flex-wrap:wrap;gap:1px;border-radius:0.5rem;overflow:hidden;margin-bottom:1.5rem;border:1px solid color-mix(in srgb,currentColor 12%,transparent);background:color-mix(in srgb,currentColor 12%,transparent);">
        @foreach ($statItems as $label => $value)
            <div style="flex:1;min-width:130px;padding:0.875rem 1.125rem;background:var(--fi-bg, canvas);">
                <div style="font-size:0.68rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;opacity:0.5;">{{ $label }}</div>
                <div style="font-size:1.25rem;font-weight:700;margin-top:0.125rem;line-height:1.6;">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    {{-- Tabbed tables --}}
    <div x-data="{ activeTab: 'driver' }">
        <x-filament::tabs label="Breakdown">
            <x-filament::tabs.item
                alpine-active="activeTab === 'driver'"
                x-on:click="activeTab = 'driver'"
                icon="heroicon-o-server-stack"
            >
                By Driver
            </x-filament::tabs.item>
            <x-filament::tabs.item
                alpine-active="activeTab === 'model'"
                x-on:click="activeTab = 'model'"
                icon="heroicon-o-cube"
            >
                By Model
            </x-filament::tabs.item>
        </x-filament::tabs>

        <div style="margin-top:1rem;overflow-x:auto;">

            {{-- By Driver --}}
            <div x-show="activeTab === 'driver'">
                <table class="fi-ta-table" style="width:100%;">
                    <thead>
                        <tr>
                            <th class="fi-ta-header-cell px-3 py-2" style="text-align:left;">Driver</th>
                            <th class="fi-ta-header-cell px-3 py-2" style="text-align:right;">Calls</th>
                            <th class="fi-ta-header-cell px-3 py-2" style="text-align:right;">Prompt Tokens</th>
                            <th class="fi-ta-header-cell px-3 py-2" style="text-align:right;">Completion Tokens</th>
                            <th class="fi-ta-header-cell px-3 py-2" style="text-align:right;">Total Tokens</th>
                            <th class="fi-ta-header-cell px-3 py-2" style="text-align:right;">Est. Cost</th>
                            <th class="fi-ta-header-cell px-3 py-2" style="text-align:right;">Avg Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->getTokensByDriver() as $row)
                            <tr class="fi-ta-row">
                                <td class="fi-ta-cell px-3" style="padding-top:0.75rem;padding-bottom:0.75rem;font-weight:600;">{{ $row['driver'] }}</td>
                                <td class="fi-ta-cell px-3" style="padding-top:0.75rem;padding-bottom:0.75rem;text-align:right;">{{ number_format($row['total_calls']) }}</td>
                                <td class="fi-ta-cell px-3" style="padding-top:0.75rem;padding-bottom:0.75rem;text-align:right;">{{ number_format($row['total_prompt_tokens']) }}</td>
                                <td class="fi-ta-cell px-3" style="padding-top:0.75rem;padding-bottom:0.75rem;text-align:right;">{{ number_format($row['total_completion_tokens']) }}</td>
                                <td class="fi-ta-cell px-3" style="padding-top:0.75rem;padding-bottom:0.75rem;text-align:right;font-weight:700;">{{ number_format($row['total_tokens']) }}</td>
                                <td class="fi-ta-cell px-3" style="padding-top:0.75rem;padding-bottom:0.75rem;text-align:right;">{{ $this->formatCost(isset($row['estimated_cost']) ? (float) $row['estimated_cost'] : null) }}</td>
                                <td class="fi-ta-cell px-3" style="padding-top:0.75rem;padding-bottom:0.75rem;text-align:right;">{{ $row['avg_duration_ms'] ? (int) $row['avg_duration_ms'] . ' ms' : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="fi-ta-cell px-3 py-6" style="text-align:center;opacity:0.5;">No data for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div x-show="activeTab === 'model'" x-cloak>
                <table class="fi-ta-table" style="width:100%;">
                    <thead>
                        <tr>
                            <th class="fi-ta-header-cell px-3 py-2" style="text-align:left;">Model</th>
                            <th class="fi-ta-header-cell px-3 py-2" style="text-align:right;">Calls</th>
                            <th class="fi-ta-header-cell px-3 py-2" style="text-align:right;">Prompt Tokens</th>
                            <th class="fi-ta-header-cell px-3 py-2" style="text-align:right;">Completion Tokens</th>
                            <th class="fi-ta-header-cell px-3 py-2" style="text-align:right;">Total Tokens</th>
                            <th class="fi-ta-header-cell px-3 py-2" style="text-align:right;">Est. Cost</th>
                            <th class="fi-ta-header-cell px-3 py-2" style="text-align:right;">Avg Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->getTokensByModel() as $row)
                            <tr class="fi-ta-row">
                                <td class="fi-ta-cell px-3" style="padding-top:0.75rem;padding-bottom:0.75rem;font-weight:600;">{{ $row['model'] }}</td>
                                <td class="fi-ta-cell px-3" style="padding-top:0.75rem;padding-bottom:0.75rem;text-align:right;">{{ number_format($row['total_calls']) }}</td>
                                <td class="fi-ta-cell px-3" style="padding-top:0.75rem;padding-bottom:0.75rem;text-align:right;">{{ number_format($row['total_prompt_tokens']) }}</td>
                                <td class="fi-ta-cell px-3" style="padding-top:0.75rem;padding-bottom:0.75rem;text-align:right;">{{ number_format($row['total_completion_tokens']) }}</td>
                                <td class="fi-ta-cell px-3" style="padding-top:0.75rem;padding-bottom:0.75rem;text-align:right;font-weight:700;">{{ number_format($row['total_tokens']) }}</td>
                                <td class="fi-ta-cell px-3" style="padding-top:0.75rem;padding-bottom:0.75rem;text-align:right;">{{ $this->formatCost(isset($row['estimated_cost']) ? (float) $row['estimated_cost'] : null) }}</td>
                                <td class="fi-ta-cell px-3" style="padding-top:0.75rem;padding-bottom:0.75rem;text-align:right;">{{ $row['avg_duration_ms'] ? (int) $row['avg_duration_ms'] . ' ms' : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="fi-ta-cell px-3 py-6" style="text-align:center;opacity:0.5;">No data for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-filament::section>


<x-filament-panels::page>
    <!-- 1. Executive Operations Header & Telemetry Status -->
    <div class="pbb-dashboard-header">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="pbb-status-pill">
                    <span class="pbb-status-dot"></span>
                    <span>Systems Operational &bull; SLA Guard Active</span>
                </span>
                <span class="text-xs text-zinc-400 font-mono">{{ now()->format('l, d F Y') }}</span>
            </div>
            <h1 class="pbb-dashboard-title">
                Commercial Intelligence & Operations Overview
            </h1>
            <p class="pbb-dashboard-subtitle">
                Real-time RFQ telemetry, sales funnel velocity, live profit margin supervision, and SLA monitoring.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ \App\Filament\Resources\InquiryResource::getUrl('kanban') }}" class="pbb-dash-btn secondary">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="18" rx="1"></rect>
                    <rect x="14" y="3" width="7" height="18" rx="1"></rect>
                </svg>
                <span>Kanban Pipeline</span>
            </a>
            <a href="{{ \App\Filament\Resources\InquiryResource::getUrl('create') }}" class="pbb-dash-btn primary">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>New Inquiry</span>
            </a>
        </div>
    </div>

    <!-- 2. KPI Summary Cards (4-Grid) -->
    @php
        $m = $this->metrics;
    @endphp
    <div class="pbb-kpi-grid">
        <!-- KPI 1: Inquiries -->
        <div class="pbb-kpi-card">
            <div class="pbb-kpi-top">
                <span class="pbb-kpi-label">Total Inquiries (MTD)</span>
                <div class="pbb-kpi-icon-box blue">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </div>
            </div>
            <div class="pbb-kpi-value">{{ $m['total_inquiries'] }}</div>
            <div class="pbb-kpi-footer">
                <span class="pbb-kpi-badge positive">+18.5%</span>
                <span class="pbb-kpi-foot-text">{{ $m['inquiries_this_month'] }} leads logged this month</span>
            </div>
        </div>

        <!-- KPI 2: Pipeline Deal Value -->
        <div class="pbb-kpi-card">
            <div class="pbb-kpi-top">
                <span class="pbb-kpi-label">Active Pipeline Value</span>
                <div class="pbb-kpi-icon-box emerald">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                </div>
            </div>
            <div class="pbb-kpi-value">Rp {{ number_format($m['pipeline_value'], 0, ',', '.') }}</div>
            <div class="pbb-kpi-footer">
                <span class="pbb-kpi-badge neutral">Live Est.</span>
                <span class="pbb-kpi-foot-text">Active Quote & Negotiation stages</span>
            </div>
        </div>

        <!-- KPI 3: Active Quotes & Margin Guard -->
        <div class="pbb-kpi-card">
            <div class="pbb-kpi-top">
                <span class="pbb-kpi-label">Active Quotations</span>
                <div class="pbb-kpi-icon-box purple">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                </div>
            </div>
            <div class="pbb-kpi-value">{{ $m['active_quotes'] }} Quotes</div>
            <div class="pbb-kpi-footer">
                <span class="pbb-kpi-badge positive">&gt; 15%</span>
                <span class="pbb-kpi-foot-text">HPP Margin Guard active</span>
            </div>
        </div>

        <!-- KPI 4: Win Rate -->
        <div class="pbb-kpi-card">
            <div class="pbb-kpi-top">
                <span class="pbb-kpi-label">Conversion Win Rate</span>
                <div class="pbb-kpi-icon-box amber">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                </div>
            </div>
            <div class="pbb-kpi-value">{{ $m['win_rate'] }}%</div>
            <div class="pbb-kpi-footer">
                <span class="pbb-kpi-badge positive">{{ $m['deals_won'] }} Won</span>
                <span class="pbb-kpi-foot-text">Converted deals closed</span>
            </div>
        </div>
    </div>

    <!-- 3. Sales Pipeline Funnel Progression Bar -->
    <div class="pbb-dash-section mt-6">
        <div class="pbb-dash-section-header">
            <div>
                <h3 class="pbb-dash-section-title">Sales Pipeline Funnel Distribution</h3>
                <p class="pbb-dash-section-desc">Active RFQ lead progression through the 6 stages of commercial conversion.</p>
            </div>
            <a href="{{ \App\Filament\Resources\InquiryResource::getUrl('kanban') }}" class="pbb-link-detail">
                <span>View Full Kanban Board</span>
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <!-- Funnel Multi-Segment Bar -->
        <div class="pbb-funnel-bar-wrapper">
            @foreach($this->pipelineFunnel as $f)
                @if($f['count'] > 0)
                    <div 
                        class="pbb-funnel-segment" 
                        style="width: {{ max(10, $f['percentage']) }}%; background-color: {{ $f['color'] }};" 
                        title="{{ $f['label'] }}: {{ $f['count'] }} leads ({{ $f['percentage'] }}%)"
                    ></div>
                @endif
            @endforeach
        </div>

        <!-- Funnel Stage Cards -->
        <div class="pbb-funnel-stages-grid">
            @foreach($this->pipelineFunnel as $stage)
                <div class="pbb-funnel-stage-card" style="border-top: 3px solid {{ $stage['color'] }};">
                    <span class="pbb-stage-label">{{ $stage['label'] }}</span>
                    <div class="flex items-baseline justify-between mt-1">
                        <span class="pbb-stage-count">{{ $stage['count'] }}</span>
                        <span class="pbb-stage-pct">{{ $stage['percentage'] }}%</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- 4. Middle Section: Channel Attribution & Top Industrial Brands -->
    <div class="pbb-dash-two-col">
        <!-- Channel Attribution -->
        <div class="pbb-dash-card">
            <div class="pbb-dash-card-header">
                <h3 class="pbb-dash-card-title">Lead Ingestion Channel Breakdown</h3>
                <span class="pbb-card-tag">Multi-Channel Telemetry</span>
            </div>
            <p class="pbb-dash-card-desc">Attribution telemetry from digital cart, direct WhatsApp, and field referrals.</p>

            <div class="space-y-3.5 mt-4">
                @foreach($this->channelAttribution as $ch)
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $ch['name'] }}</span>
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-bold text-zinc-900 dark:text-zinc-100">{{ $ch['count'] }} RFQs</span>
                                <span class="text-zinc-400">({{ $ch['percentage'] }}%)</span>
                            </div>
                        </div>
                        <div class="w-full h-2 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                            <div class="h-full rounded-full" style="width: {{ $ch['percentage'] }}%; background-color: {{ $ch['color'] }};"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Top Industrial Brands -->
        <div class="pbb-dash-card">
            <div class="pbb-dash-card-header">
                <h3 class="pbb-dash-card-title">Top Inquired Industrial Brands</h3>
                <span class="pbb-card-tag">Official Principles</span>
            </div>
            <p class="pbb-dash-card-desc">Volume of RFQ inquiries by official distributor partner brands.</p>

            <div class="mt-3 divide-y divide-zinc-100 dark:divide-zinc-800/80">
                @foreach($this->topBrands as $b)
                    <div class="py-2.5 flex items-center justify-between">
                        <div>
                            <h4 class="text-xs font-bold text-zinc-900 dark:text-zinc-100">{{ $b['name'] }}</h4>
                            <span class="text-[11px] text-zinc-500">{{ $b['category'] }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-mono font-bold text-zinc-900 dark:text-zinc-100">{{ $b['inquiries'] }} RFQs</span>
                            <span class="block text-[10.5px] font-semibold text-sky-600 dark:text-sky-400">{{ $b['share'] }} demand</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- 5. Priority Inquiries & SLA Alerts Table -->
    <div class="pbb-dash-section mt-6">
        <div class="pbb-dash-section-header">
            <div>
                <h3 class="pbb-dash-section-title">Priority Leads & Stale SLA Watchlist</h3>
                <p class="pbb-dash-section-desc">Active RFQs requiring immediate Sales PIC response to maintain B2B SLA compliance.</p>
            </div>
            <a href="{{ \App\Filament\Resources\InquiryResource::getUrl('index') }}" class="pbb-link-detail">
                <span>View All Inquiries</span>
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <div class="overflow-x-auto mt-4">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 font-mono uppercase text-[10.5px]">
                        <th class="pb-2.5 font-bold">Inquiry #</th>
                        <th class="pb-2.5 font-bold">Client / Company</th>
                        <th class="pb-2.5 font-bold">Contact</th>
                        <th class="pb-2.5 font-bold">Sales PIC</th>
                        <th class="pb-2.5 font-bold">Stage</th>
                        <th class="pb-2.5 font-bold">SLA Aging</th>
                        <th class="pb-2.5 font-bold text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/80">
                    @forelse($this->priorityInquiries as $inq)
                        <tr class="hover:bg-zinc-50/80 dark:hover:bg-zinc-800/40 transition">
                            <td class="py-3 font-mono font-bold text-zinc-900 dark:text-zinc-100">
                                {{ $inq->inquiry_number }}
                            </td>
                            <td class="py-3 font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $inq->company_name }}
                            </td>
                            <td class="py-3 text-zinc-600 dark:text-zinc-400">
                                <div>{{ $inq->customer_name }}</div>
                                <div class="text-[11px] text-zinc-400">{{ $inq->phone_wa }}</div>
                            </td>
                            <td class="py-3">
                                <span class="inline-flex items-center gap-1 rounded-sm bg-zinc-100 px-2 py-0.5 font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                    {{ $inq->assignedUser->name ?? 'Unassigned' }}
                                </span>
                            </td>
                            <td class="py-3">
                                <span class="inline-flex items-center rounded-sm bg-sky-50 px-2 py-0.5 font-bold text-sky-700 border border-sky-200 dark:bg-sky-950/60 dark:text-sky-300 dark:border-sky-900 text-[10.5px]">
                                    {{ ucfirst(str_replace('_', ' ', $inq->status)) }}
                                </span>
                            </td>
                            <td class="py-3">
                                @if($inq->sla_data['level'] === 'danger')
                                    <span class="inline-flex items-center gap-1 rounded-sm bg-rose-50 px-2 py-0.5 font-bold text-rose-700 border border-rose-200 dark:bg-rose-950 dark:text-rose-300">
                                        &gt; 48h Stale
                                    </span>
                                @elseif($inq->sla_data['level'] === 'warning')
                                    <span class="inline-flex items-center gap-1 rounded-sm bg-amber-50 px-2 py-0.5 font-semibold text-amber-800 border border-amber-200 dark:bg-amber-950 dark:text-amber-300">
                                        &gt; 24h Aging
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-sm bg-emerald-50 px-2 py-0.5 font-medium text-emerald-700 border border-emerald-200 dark:bg-emerald-950 dark:text-emerald-300">
                                        Normal SLA
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 text-right">
                                <a 
                                    href="{{ \App\Filament\Resources\InquiryResource::getUrl('view', ['record' => $inq->id]) }}" 
                                    class="font-semibold text-zinc-700 hover:text-zinc-950 dark:text-zinc-300 dark:hover:text-zinc-100 inline-flex items-center gap-1"
                                >
                                    <span>Review</span>
                                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M5 12h14M12 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-zinc-400">
                                All recent inquiries are currently handled within SLA standards.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>

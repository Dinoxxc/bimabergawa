<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Tab Navigation Header -->
        <div class="border-b border-zinc-200 dark:border-zinc-800">
            <nav class="flex space-x-2 sm:space-x-4 overflow-x-auto py-1" aria-label="Reports Tabs">
                <button
                    type="button"
                    wire:click="setActiveTab('sales')"
                    class="px-4 py-2 text-sm font-medium rounded-lg transition-colors whitespace-nowrap {{ $activeTab === 'sales' ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900 shadow-sm' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white' }}"
                >
                    1. Sales &amp; Conversion
                </button>

                @if ($this->canViewMarginTab())
                    <button
                        type="button"
                        wire:click="setActiveTab('margin')"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-colors whitespace-nowrap {{ $activeTab === 'margin' ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900 shadow-sm' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white' }}"
                    >
                        2. Profitability &amp; Margin HPP
                    </button>
                @endif

                @if ($this->canViewArTab())
                    <button
                        type="button"
                        wire:click="setActiveTab('ar')"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-colors whitespace-nowrap {{ $activeTab === 'ar' ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900 shadow-sm' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white' }}"
                    >
                        3. Accounts Receivable (AR Aging)
                    </button>
                @endif

                @if ($this->canViewCommissionTab())
                    <button
                        type="button"
                        wire:click="setActiveTab('commission')"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-colors whitespace-nowrap {{ $activeTab === 'commission' ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900 shadow-sm' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white' }}"
                    >
                        4. Agent Commission Summary
                    </button>
                @endif
            </nav>
        </div>

        <!-- Tab 1: Sales & Conversion Performance -->
        @if ($activeTab === 'sales')
            @php $sales = $this->getSalesPerformanceData(); @endphp
            <div class="space-y-6">
                <!-- Stat Cards Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-zinc-500 font-medium">Total Inquiries Received</div>
                        <div class="text-2xl font-bold font-mono text-zinc-900 dark:text-white mt-1">{{ number_format($sales['totalInquiries']) }}</div>
                        <div class="text-xs text-zinc-500 mt-1">prospect requests</div>
                    </div>

                    <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-zinc-500 font-medium">Win Deal Conversion</div>
                        <div class="text-2xl font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-1">{{ $sales['conversionRate'] }}%</div>
                        <div class="text-xs text-zinc-500 mt-1">{{ $sales['wonInquiries'] }} deals won</div>
                    </div>

                    <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-zinc-500 font-medium">Total Sales Confirmed</div>
                        <div class="text-2xl font-bold font-mono text-zinc-900 dark:text-white mt-1">Rp {{ number_format($sales['totalSalesValue'], 0, ',', '.') }}</div>
                        <div class="text-xs text-zinc-500 mt-1">{{ $sales['soCount'] }} approved sales orders</div>
                    </div>

                    <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-zinc-500 font-medium">Average Order Value (AOV)</div>
                        <div class="text-2xl font-bold font-mono text-zinc-900 dark:text-white mt-1">Rp {{ number_format($sales['averageOrderValue'], 0, ',', '.') }}</div>
                        <div class="text-xs text-zinc-500 mt-1">per confirmed transaction</div>
                    </div>
                </div>

                <!-- Brand Distribution & Sales Rep Productivity -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="p-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-sm">
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-3">Top Brand Principal Demand</h3>
                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse ($sales['brands'] as $brand)
                                <div class="py-2.5 flex items-center justify-between text-sm">
                                    <div>
                                        <span class="font-semibold text-zinc-900 dark:text-white">{{ $brand['name'] }}</span>
                                        <span class="ml-2 font-mono text-xs px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400">
                                            {{ $brand['code'] }}
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <span class="font-mono font-medium text-zinc-900 dark:text-zinc-200">{{ $brand['inquiry_items_count'] }}</span>
                                        <span class="text-xs text-zinc-500">requests</span>
                                    </div>
                                </div>
                            @empty
                                <div class="py-4 text-center text-xs text-zinc-500">No brand demand data recorded.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="p-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-sm">
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-3">Sales PIC Productivity</h3>
                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse ($sales['salesReps'] as $rep)
                                <div class="py-2.5 flex items-center justify-between text-sm">
                                    <div>
                                        <div class="font-semibold text-zinc-900 dark:text-white">{{ $rep['name'] }}</div>
                                        <div class="text-xs text-zinc-500">{{ $rep['assigned_inquiries'] }} inquiries | {{ $rep['won_deals'] }} won</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-mono font-bold text-zinc-900 dark:text-zinc-100">Rp {{ number_format($rep['total_omzet'], 0, ',', '.') }}</div>
                                        <div class="text-xs text-emerald-600 font-mono">{{ $rep['conversion'] }}% win rate</div>
                                    </div>
                                </div>
                            @empty
                                <div class="py-4 text-center text-xs text-zinc-500">No active sales PIC accounts.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Tab 2: Profitability & Margin HPP Analysis -->
        @if ($activeTab === 'margin' && $this->canViewMarginTab())
            @php $margin = $this->getMarginAnalysisData(); @endphp
            <div class="space-y-6">
                <!-- Stat Cards Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-zinc-500 font-medium">Net Commercial Revenue (DPP)</div>
                        <div class="text-2xl font-bold font-mono text-zinc-900 dark:text-white mt-1">Rp {{ number_format($margin['totalRevenue'], 0, ',', '.') }}</div>
                        <div class="text-xs text-zinc-500 mt-1">total transaction subtotal</div>
                    </div>

                    <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-zinc-500 font-medium">Cost of Goods Sold (Modal HPP)</div>
                        <div class="text-2xl font-bold font-mono text-zinc-900 dark:text-white mt-1">Rp {{ number_format($margin['totalCogs'], 0, ',', '.') }}</div>
                        <div class="text-xs text-zinc-500 mt-1">realized purchasing cost</div>
                    </div>

                    <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-zinc-500 font-medium">Total Gross Profit</div>
                        <div class="text-2xl font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-1">Rp {{ number_format($margin['grossProfit'], 0, ',', '.') }}</div>
                        <div class="text-xs text-zinc-500 mt-1">revenue minus base HPP</div>
                    </div>

                    <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-zinc-500 font-medium">Weighted Margin Average</div>
                        <div class="text-2xl font-bold font-mono text-zinc-900 dark:text-white mt-1">{{ $margin['weightedMargin'] }}%</div>
                        <div class="text-xs text-zinc-500 mt-1">weighted gross profitability</div>
                    </div>
                </div>

                <!-- Margin Distribution Tier Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="p-4 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800 rounded-lg">
                        <div class="text-xs font-semibold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">[HEALTHY MARGIN &gt;= 15%]</div>
                        <div class="text-2xl font-bold font-mono text-emerald-700 dark:text-emerald-400 mt-1">{{ $margin['healthyCount'] }}</div>
                        <div class="text-xs text-emerald-600 dark:text-emerald-400 mt-0.5">Commercial standard transactions</div>
                    </div>

                    <div class="p-4 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                        <div class="text-xs font-semibold uppercase tracking-wider text-amber-800 dark:text-amber-300">[THIN MARGIN 8% - 14.99%]</div>
                        <div class="text-2xl font-bold font-mono text-amber-700 dark:text-amber-400 mt-1">{{ $margin['thinCount'] }}</div>
                        <div class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">Competitive project pricing</div>
                    </div>

                    <div class="p-4 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <div class="text-xs font-semibold uppercase tracking-wider text-red-800 dark:text-red-300">[CRITICAL MARGIN &lt; 8%]</div>
                        <div class="text-2xl font-bold font-mono text-red-700 dark:text-red-400 mt-1">{{ $margin['criticalCount'] }}</div>
                        <div class="text-xs text-red-600 dark:text-red-400 mt-0.5">Director escalation approved</div>
                    </div>
                </div>

                <!-- Critical Margin Transactions Table -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-sm p-5">
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-3">Critical Margin Transactions (Director Authorized)</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm divide-y divide-zinc-200 dark:divide-zinc-800">
                            <thead class="text-xs text-zinc-500 uppercase tracking-wider bg-zinc-50 dark:bg-zinc-800/50">
                                <tr>
                                    <th class="py-2.5 px-3">Quotation #</th>
                                    <th class="py-2.5 px-3">Client Entity</th>
                                    <th class="py-2.5 px-3 text-right">Subtotal</th>
                                    <th class="py-2.5 px-3 text-center">Gross Margin</th>
                                    <th class="py-2.5 px-3">Director Authorization</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @forelse ($margin['criticalTransactions'] as $tx)
                                    <tr>
                                        <td class="py-2.5 px-3 font-mono text-xs font-semibold">{{ $tx->quotation_number }}</td>
                                        <td class="py-2.5 px-3">{{ $tx->company_name }}</td>
                                        <td class="py-2.5 px-3 text-right font-mono">Rp {{ number_format((float) $tx->subtotal, 0, ',', '.') }}</td>
                                        <td class="py-2.5 px-3 text-center font-mono font-bold text-red-600">{{ $tx->gross_margin_percentage }}%</td>
                                        <td class="py-2.5 px-3 text-xs text-zinc-500">{{ $tx->director_approved_at ? 'Approved: ' . $tx->director_approved_at->format('d/m/Y H:i') : 'Pending Approval' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-4 text-center text-xs text-zinc-500">No critical margin transactions found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <!-- Tab 3: Accounts Receivable (AR Aging Buckets) -->
        @if ($activeTab === 'ar' && $this->canViewArTab())
            @php $ar = $this->getArAgingData(); @endphp
            <div class="space-y-6">
                <!-- AR Stat Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-zinc-500 font-medium">Total Outstanding AR</div>
                        <div class="text-xl font-bold font-mono text-zinc-900 dark:text-white mt-1">Rp {{ number_format($ar['totalOutstanding'], 0, ',', '.') }}</div>
                        <div class="text-xs text-zinc-500 mt-1">total unpaid invoices</div>
                    </div>

                    <div class="p-4 bg-white dark:bg-zinc-900 border border-emerald-200 dark:border-emerald-800 rounded-lg shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-emerald-600 font-medium">Current (Not Overdue)</div>
                        <div class="text-xl font-bold font-mono text-emerald-600 mt-1">Rp {{ number_format($ar['currentAr'], 0, ',', '.') }}</div>
                        <div class="text-xs text-zinc-500 mt-1">within credit terms</div>
                    </div>

                    <div class="p-4 bg-white dark:bg-zinc-900 border border-amber-200 dark:border-amber-800 rounded-lg shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-amber-600 font-medium">1 - 30 Days Overdue</div>
                        <div class="text-xl font-bold font-mono text-amber-600 mt-1">Rp {{ number_format($ar['overdue1_30'], 0, ',', '.') }}</div>
                        <div class="text-xs text-zinc-500 mt-1">tier 1 collection</div>
                    </div>

                    <div class="p-4 bg-white dark:bg-zinc-900 border border-orange-200 dark:border-orange-800 rounded-lg shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-orange-600 font-medium">31 - 60 Days Overdue</div>
                        <div class="text-xl font-bold font-mono text-orange-600 mt-1">Rp {{ number_format($ar['overdue31_60'], 0, ',', '.') }}</div>
                        <div class="text-xs text-zinc-500 mt-1">tier 2 escalation</div>
                    </div>

                    <div class="p-4 bg-white dark:bg-zinc-900 border border-red-200 dark:border-red-800 rounded-lg shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-red-600 font-medium">&gt; 60 Days Overdue</div>
                        <div class="text-xl font-bold font-mono text-red-600 mt-1">Rp {{ number_format($ar['criticalOverdue'], 0, ',', '.') }}</div>
                        <div class="text-xs text-zinc-500 mt-1">critical debt action</div>
                    </div>
                </div>

                <!-- Unpaid Invoices List with WhatsApp Action -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-sm p-5">
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-3">Outstanding Commercial Invoices</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm divide-y divide-zinc-200 dark:divide-zinc-800">
                            <thead class="text-xs text-zinc-500 uppercase tracking-wider bg-zinc-50 dark:bg-zinc-800/50">
                                <tr>
                                    <th class="py-2.5 px-3">Invoice #</th>
                                    <th class="py-2.5 px-3">Client Entity</th>
                                    <th class="py-2.5 px-3">Due Date</th>
                                    <th class="py-2.5 px-3 text-right">Total</th>
                                    <th class="py-2.5 px-3 text-right">Remaining Balance</th>
                                    <th class="py-2.5 px-3 text-center">Aging Status</th>
                                    <th class="py-2.5 px-3 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @forelse ($ar['invoicesList'] as $inv)
                                    <tr>
                                        <td class="py-2.5 px-3 font-mono text-xs font-semibold">{{ $inv['invoice_number'] }}</td>
                                        <td class="py-2.5 px-3 font-medium">{{ $inv['company_name'] }}</td>
                                        <td class="py-2.5 px-3 text-xs font-mono">{{ $inv['due_date'] }}</td>
                                        <td class="py-2.5 px-3 text-right font-mono text-xs">Rp {{ number_format($inv['grand_total'], 0, ',', '.') }}</td>
                                        <td class="py-2.5 px-3 text-right font-mono text-xs font-bold text-zinc-900 dark:text-white">Rp {{ number_format($inv['remaining'], 0, ',', '.') }}</td>
                                        <td class="py-2.5 px-3 text-center">
                                            <span class="inline-flex px-2 py-0.5 text-xs font-mono font-medium rounded {{ $inv['badgeColor'] === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($inv['badgeColor'] === 'danger' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-amber-50 text-amber-700 border border-amber-200') }}">
                                                {{ $inv['bucket'] }}
                                            </span>
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            <a
                                                href="{{ $inv['waUrl'] }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded border border-emerald-600 text-emerald-700 hover:bg-emerald-50 dark:hover:bg-emerald-950/30 transition-colors"
                                            >
                                                Send WA Reminder
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-4 text-center text-xs text-zinc-500">All invoices are settled. No outstanding accounts receivable.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <!-- Tab 4: Agent Freelance Commission Summary -->
        @if ($activeTab === 'commission' && $this->canViewCommissionTab())
            @php $comm = $this->getCommissionData(); @endphp
            <div class="space-y-6">
                <!-- Commission Stat Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-zinc-500 font-medium">Total Commission Generated</div>
                        <div class="text-2xl font-bold font-mono text-zinc-900 dark:text-white mt-1">Rp {{ number_format($comm['totalIssued'], 0, ',', '.') }}</div>
                        <div class="text-xs text-zinc-500 mt-1">all historical commissions</div>
                    </div>

                    <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-zinc-500 font-medium">Total Commission Paid</div>
                        <div class="text-2xl font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-1">Rp {{ number_format($comm['totalPaid'], 0, ',', '.') }}</div>
                        <div class="text-xs text-zinc-500 mt-1">transferred to partner bank accounts</div>
                    </div>

                    <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-zinc-500 font-medium">Outstanding Payable Balance</div>
                        <div class="text-2xl font-bold font-mono text-amber-600 dark:text-amber-400 mt-1">Rp {{ number_format($comm['payableBalance'], 0, ',', '.') }}</div>
                        <div class="text-xs text-zinc-500 mt-1">approved for payout</div>
                    </div>
                </div>

                <!-- Agent Leaderboard & Performance Table -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-sm p-5">
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-3">Freelance Partner Performance & Balances</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm divide-y divide-zinc-200 dark:divide-zinc-800">
                            <thead class="text-xs text-zinc-500 uppercase tracking-wider bg-zinc-50 dark:bg-zinc-800/50">
                                <tr>
                                    <th class="py-2.5 px-3">Partner Name</th>
                                    <th class="py-2.5 px-3">Email</th>
                                    <th class="py-2.5 px-3 text-right">Generated Sales</th>
                                    <th class="py-2.5 px-3 text-right">Commission Earned</th>
                                    <th class="py-2.5 px-3 text-right">Commission Paid</th>
                                    <th class="py-2.5 px-3 text-right">Current Balance</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @forelse ($comm['agents'] as $agent)
                                    <tr>
                                        <td class="py-2.5 px-3 font-semibold">{{ $agent['name'] }}</td>
                                        <td class="py-2.5 px-3 text-xs text-zinc-500 font-mono">{{ $agent['email'] }}</td>
                                        <td class="py-2.5 px-3 text-right font-mono text-xs">Rp {{ number_format($agent['total_deals'], 0, ',', '.') }}</td>
                                        <td class="py-2.5 px-3 text-right font-mono text-xs font-medium">Rp {{ number_format($agent['commission_earned'], 0, ',', '.') }}</td>
                                        <td class="py-2.5 px-3 text-right font-mono text-xs text-emerald-600">Rp {{ number_format($agent['commission_paid'], 0, ',', '.') }}</td>
                                        <td class="py-2.5 px-3 text-right font-mono text-xs font-bold text-zinc-900 dark:text-white">Rp {{ number_format($agent['balance'], 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-4 text-center text-xs text-zinc-500">No freelance agents registered.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>

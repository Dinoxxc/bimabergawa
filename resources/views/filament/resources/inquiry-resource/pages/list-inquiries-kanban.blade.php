<x-filament-panels::page>
    <!-- View Switcher & SLA Legend Toolbar matching Login Precision Blueprint Aesthetic -->
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-zinc-200 bg-white p-3 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex items-center gap-2.5">
            <span class="text-xs font-bold text-zinc-500 uppercase tracking-wider font-mono">View:</span>
            <div class="crm-view-switcher">
                <a href="{{ \App\Filament\Resources\InquiryResource::getUrl('index') }}" class="crm-view-switcher-btn">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="3" y1="9" x2="21" y2="9"></line>
                        <line x1="3" y1="15" x2="21" y2="15"></line>
                        <line x1="9" y1="3" x2="9" y2="21"></line>
                    </svg>
                    <span>Table View</span>
                </a>
                <span class="crm-view-switcher-btn active">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="18" rx="1"></rect>
                        <rect x="14" y="3" width="7" height="18" rx="1"></rect>
                    </svg>
                    <span>Kanban Board</span>
                </span>
            </div>
        </div>

        <div class="flex items-center gap-3 text-xs text-zinc-500">
            <span class="inline-flex items-center gap-1.5 font-medium">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span>Warning SLA: &gt; 24h</span>
            </span>
            <span>&bull;</span>
            <span class="inline-flex items-center gap-1.5 font-medium">
                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                <span>Danger SLA: &gt; 48h</span>
            </span>
        </div>
    </div>

    <div class="overflow-x-auto pb-4">
        <div class="flex items-start gap-4 min-w-[1240px]">
            @php
                $grouped = $this->inquiriesGrouped;
                $statuses = $this->getStatuses();
                $service = app(\App\Services\InquiryAssignmentService::class);
            @endphp

            @foreach($statuses as $statusKey => $statusConfig)
                @php
                    $cards = $grouped->get($statusKey, collect());
                @endphp
                <div class="flex-1 min-w-[280px] max-w-[340px] rounded-xl border border-zinc-200 bg-zinc-50/70 p-3 dark:border-zinc-800 dark:bg-zinc-900/60">
                    <!-- Column Header -->
                    <div class="flex items-center justify-between pb-3 border-b border-zinc-200 dark:border-zinc-800">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">
                                {{ $statusConfig['title'] }}
                            </span>
                            <span class="inline-flex items-center justify-center rounded-full px-2 py-0.5 text-[11px] font-bold border {{ $statusConfig['badge_color'] }}">
                                {{ $cards->count() }}
                            </span>
                        </div>
                    </div>

                    <!-- Column Cards -->
                    <div class="mt-3 space-y-3 min-h-[480px]">
                        @forelse($cards as $inq)
                            @php
                                $sla = $service->calculateSlaAging($inq);
                            @endphp
                            <div class="rounded-lg border border-zinc-200 bg-white p-3.5 shadow-xs transition hover:border-zinc-400 hover:shadow-sm dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700">
                                <!-- Card Header -->
                                <div class="flex items-center justify-between gap-1 text-[11px]">
                                    <span class="font-mono font-bold text-zinc-900 dark:text-zinc-100">
                                        {{ $inq->inquiry_number }}
                                    </span>
                                    <span class="text-zinc-400">
                                        {{ $inq->created_at->diffForHumans() }}
                                    </span>
                                </div>

                                <!-- Company & Contact -->
                                <div class="mt-2">
                                    <h4 class="text-xs font-bold text-zinc-900 dark:text-zinc-100 line-clamp-1">
                                        {{ $inq->company_name }}
                                    </h4>
                                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5 flex items-center gap-1.5">
                                        <span>{{ $inq->customer_name }}</span>
                                        <span>&bull;</span>
                                        <span>{{ $inq->phone_wa }}</span>
                                    </p>
                                </div>

                                <!-- Items Count & Project -->
                                <div class="mt-2 flex flex-wrap items-center gap-1.5 text-[11px]">
                                    <span class="inline-flex items-center rounded-sm bg-zinc-100 px-1.5 py-0.5 font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                        {{ $inq->items->count() }} SKU Items
                                    </span>
                                    @if($inq->project_name)
                                        <span class="text-zinc-400 truncate max-w-[140px]" title="{{ $inq->project_name }}">
                                            {{ $inq->project_name }}
                                        </span>
                                    @endif
                                </div>

                                <!-- SLA Stale Alert Badges (TC-ERP-CRM-004 & TC-ERP-CRM-005) -->
                                @if($inq->status === 'new' && $sla['is_stale'])
                                    @if($sla['level'] === 'danger')
                                        <div class="mt-2.5 flex items-center gap-1.5 rounded-md bg-rose-50 border border-rose-200 px-2 py-1 text-[11px] font-bold text-rose-700 dark:bg-rose-950/50 dark:border-rose-900 dark:text-rose-300">
                                            <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                            </svg>
                                            <span>SLA Danger: > 48 Jam Belum Diproses</span>
                                        </div>
                                    @elseif($sla['level'] === 'warning')
                                        <div class="mt-2.5 flex items-center gap-1.5 rounded-md bg-amber-50 border border-amber-200 px-2 py-1 text-[11px] font-semibold text-amber-800 dark:bg-amber-950/50 dark:border-amber-900 dark:text-amber-300">
                                            <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <polyline points="12 6 12 12 16 14"></polyline>
                                            </svg>
                                            <span>SLA Warning: {{ $inq->created_at->diffInHours(now()) }} Jam Belum Direspons</span>
                                        </div>
                                    @endif
                                @endif

                                <!-- Assigned PIC Badge -->
                                <div class="mt-2.5 flex items-center justify-between border-t border-zinc-100 pt-2 text-[11px] dark:border-zinc-800/80">
                                    <div class="flex items-center gap-1 text-zinc-500">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                        <span class="font-medium text-zinc-700 dark:text-zinc-300">
                                            {{ $inq->assignedUser->name ?? 'Unassigned' }}
                                        </span>
                                    </div>

                                    @if(auth()->user()->can('assignPic', $inq))
                                        <button
                                            type="button"
                                            wire:click="openAssignModal({{ $inq->id }})"
                                            class="text-[10px] font-semibold text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100"
                                            title="Assign PIC"
                                        >
                                            Assign
                                        </button>
                                    @endif
                                </div>

                                <!-- Card Actions -->
                                <div class="mt-2.5 flex flex-wrap items-center justify-between gap-1 pt-2 border-t border-zinc-100 dark:border-zinc-800/80 text-[10px]">
                                    <a
                                        href="{{ \App\Filament\Resources\InquiryResource::getUrl('view', ['record' => $inq->id]) }}"
                                        class="font-semibold text-zinc-700 hover:text-zinc-950 dark:text-zinc-300 dark:hover:text-zinc-100 inline-flex items-center gap-1"
                                    >
                                        <span>View Details</span>
                                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M5 12h14M12 5l7 7-7 7"/>
                                        </svg>
                                    </a>

                                    <div class="flex items-center gap-1">
                                        <!-- Quick Status Dropdown / Move Button -->
                                        @if($inq->status === 'new')
                                            <button
                                                type="button"
                                                wire:click="moveCard({{ $inq->id }}, 'contacted')"
                                                class="rounded-sm bg-zinc-100 px-1.5 py-0.5 font-medium text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300"
                                                title="Mark as Contacted"
                                            >
                                                &rarr; Contacted
                                            </button>
                                        @elseif($inq->status === 'contacted')
                                            <button
                                                type="button"
                                                wire:click="moveCard({{ $inq->id }}, 'quote_draft')"
                                                class="rounded-sm bg-zinc-100 px-1.5 py-0.5 font-medium text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300"
                                                title="Mark as Quote Draft"
                                            >
                                                &rarr; Quoted
                                            </button>
                                        @elseif($inq->status === 'quote_draft')
                                            <button
                                                type="button"
                                                wire:click="moveCard({{ $inq->id }}, 'quote_sent')"
                                                class="rounded-sm bg-zinc-100 px-1.5 py-0.5 font-medium text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300"
                                                title="Mark as Negotiation"
                                            >
                                                &rarr; Negotiate
                                            </button>
                                        @elseif($inq->status === 'quote_sent')
                                            <button
                                                type="button"
                                                wire:click="moveCard({{ $inq->id }}, 'deal')"
                                                class="rounded-sm bg-emerald-100 text-emerald-800 px-1.5 py-0.5 font-bold hover:bg-emerald-200 dark:bg-emerald-950 dark:text-emerald-300"
                                                title="Mark as Won"
                                            >
                                                &check; Won
                                            </button>
                                        @endif

                                        @if(! in_array($inq->status, ['deal', 'lost'], true))
                                            <button
                                                type="button"
                                                wire:click="openLostModal({{ $inq->id }})"
                                                class="rounded-sm bg-rose-50 text-rose-700 px-1.5 py-0.5 font-medium hover:bg-rose-100 dark:bg-rose-950/60 dark:text-rose-300"
                                                title="Mark as Lost"
                                            >
                                                Lost
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-lg border border-dashed border-zinc-200 p-6 text-center text-xs text-zinc-400 dark:border-zinc-800 dark:text-zinc-500">
                                No inquiries in this stage
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Modal: Mark as Lost with Required Reason (TC-ERP-CRM-009, TC-ERP-CRM-010) -->
    @if($lostInquiryId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
            <div class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-800 dark:bg-zinc-900">
                <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    Close Inquiry as Lost
                </h3>
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    A formal cancellation reason is strictly required to archive this inquiry into the lost pipeline.
                </p>

                <div class="mt-4">
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Lost / Cancellation Reason <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        wire:model="lostReason"
                        rows="3"
                        placeholder="e.g. Competitor offered lower price, client canceled project, or out of stock specifications..."
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-900 placeholder-zinc-400 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                        required
                    ></textarea>
                    @error('lostReason')
                        <span class="mt-1 text-xs text-rose-500 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mt-5 flex items-center justify-end gap-2.5">
                    <button
                        type="button"
                        wire:click="cancelLost"
                        class="rounded-lg border border-zinc-300 px-4 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        wire:click="confirmLost"
                        class="rounded-lg bg-rose-600 px-4 py-2 text-xs font-semibold text-white hover:bg-rose-700"
                    >
                        Confirm Lost
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal: Assign Sales PIC (TC-ERP-CRM-006, TC-ERP-CRM-012) -->
    @if($assigningInquiryId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
            <div class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-800 dark:bg-zinc-900">
                <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                    <svg class="w-5 h-5 text-zinc-700 dark:text-zinc-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    Assign Sales PIC
                </h3>
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    Select an active Sales PIC or Director to take ownership of this lead.
                </p>

                <div class="mt-4">
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Active Sales Personnel <span class="text-rose-500">*</span>
                    </label>
                    <select
                        wire:model="selectedSalesUserId"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                    >
                        <option value="">-- Choose Sales PIC --</option>
                        @foreach($this->activeSalesStaff as $staff)
                            <option value="{{ $staff->id }}">{{ $staff->name }} ({{ $staff->email }})</option>
                        @endforeach
                    </select>
                    @error('selectedSalesUserId')
                        <span class="mt-1 text-xs text-rose-500 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mt-5 flex items-center justify-end gap-2.5">
                    <button
                        type="button"
                        wire:click="cancelAssign"
                        class="rounded-lg border border-zinc-300 px-4 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        wire:click="confirmAssign"
                        class="rounded-lg bg-zinc-900 px-4 py-2 text-xs font-semibold text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200"
                    >
                        Confirm Assignment
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>

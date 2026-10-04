<x-filament-panels::page>
    <!-- View Switcher & SLA Legend Toolbar matching Login Precision Blueprint Aesthetic -->
    <div class="pbb-kanban-toolbar">
        <div class="pbb-kanban-toolbar-left">
            <span class="pbb-kanban-toolbar-label">View:</span>
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

        <div class="pbb-kanban-toolbar-right">
            <span class="pbb-kanban-legend-item">
                <span class="pbb-kanban-legend-dot warning"></span>
                <span>Warning SLA: &gt; 24h</span>
            </span>
            <span class="pbb-kanban-legend-sep">&bull;</span>
            <span class="pbb-kanban-legend-item">
                <span class="pbb-kanban-legend-dot danger"></span>
                <span>Danger SLA: &gt; 48h</span>
            </span>
        </div>
    </div>

    <!-- Kanban Board Container -->
    <div class="pbb-kanban-container">
        <div class="pbb-kanban-board">
            @php
                $grouped = $this->inquiriesGrouped;
                $statuses = $this->getStatuses();
                $service = app(\App\Services\InquiryAssignmentService::class);
            @endphp

            @foreach($statuses as $statusKey => $statusConfig)
                @php
                    $cards = $grouped->get($statusKey, collect());
                @endphp
                <div class="pbb-kanban-col">
                    <!-- Column Header -->
                    <div class="pbb-kanban-col-header">
                        <span class="pbb-kanban-col-title">
                            {{ $statusConfig['title'] }}
                        </span>
                        <span class="pbb-kanban-col-badge {{ $statusConfig['badge_class'] }}">
                            {{ $cards->count() }}
                        </span>
                    </div>

                    <!-- Column Cards -->
                    <div class="pbb-kanban-cards">
                        @forelse($cards as $inq)
                            @php
                                $sla = $service->calculateSlaAging($inq);
                            @endphp
                            <div class="pbb-kanban-card">
                                <!-- Card Header -->
                                <div class="pbb-card-top">
                                    <span class="pbb-card-number">
                                        {{ $inq->inquiry_number }}
                                    </span>
                                    <span class="pbb-card-time">
                                        {{ $inq->created_at->diffForHumans() }}
                                    </span>
                                </div>

                                <!-- Company & Contact -->
                                <div>
                                    <h4 class="pbb-card-company" title="{{ $inq->company_name }}">
                                        {{ $inq->company_name }}
                                    </h4>
                                    <p class="pbb-card-contact">
                                        <span>{{ $inq->customer_name }}</span>
                                        <span>&bull;</span>
                                        <span>{{ $inq->phone_wa }}</span>
                                    </p>
                                </div>

                                <!-- Items Count & Project -->
                                <div class="pbb-card-meta">
                                    <span class="pbb-card-sku-badge">
                                        {{ $inq->items->count() }} SKU Items
                                    </span>
                                    @if($inq->project_name)
                                        <span class="pbb-card-project" title="{{ $inq->project_name }}">
                                            {{ $inq->project_name }}
                                        </span>
                                    @endif
                                </div>

                                <!-- SLA Stale Alert Badges (TC-ERP-CRM-004 & TC-ERP-CRM-005) -->
                                @if($inq->status === 'new' && $sla['is_stale'])
                                    @if($sla['level'] === 'danger')
                                        <div class="pbb-card-sla-danger">
                                            <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                            </svg>
                                            <span>SLA Danger: &gt; 48 Jam Belum Diproses</span>
                                        </div>
                                    @elseif($sla['level'] === 'warning')
                                        <div class="pbb-card-sla-warning">
                                            <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <polyline points="12 6 12 12 16 14"></polyline>
                                            </svg>
                                            <span>SLA Warning: {{ $inq->created_at->diffInHours(now()) }} Jam Belum Direspons</span>
                                        </div>
                                    @endif
                                @endif

                                <!-- Assigned PIC Badge -->
                                <div class="pbb-card-pic-row">
                                    <div class="pbb-card-pic-info">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                        <span class="pbb-card-pic-name">
                                            {{ $inq->assignedUser->name ?? 'Unassigned' }}
                                        </span>
                                    </div>

                                    @if(auth()->user()->can('assignPic', $inq))
                                        <button
                                            type="button"
                                            wire:click="openAssignModal({{ $inq->id }})"
                                            class="pbb-card-btn-assign"
                                            title="Assign PIC"
                                        >
                                            Assign
                                        </button>
                                    @endif
                                </div>

                                <!-- Card Actions -->
                                <div class="pbb-card-actions">
                                    <a
                                        href="{{ \App\Filament\Resources\InquiryResource::getUrl('view', ['record' => $inq->id]) }}"
                                        class="pbb-card-link-view"
                                    >
                                        <span>View Details</span>
                                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M5 12h14M12 5l7 7-7 7"/>
                                        </svg>
                                    </a>

                                    <div class="pbb-card-btns-group">
                                        <!-- Quick Status Move Buttons -->
                                        @if($inq->status === 'new')
                                            <button
                                                type="button"
                                                wire:click="moveCard({{ $inq->id }}, 'contacted')"
                                                class="pbb-btn-move"
                                                title="Mark as Contacted"
                                            >
                                                &rarr; Contacted
                                            </button>
                                        @elseif($inq->status === 'contacted')
                                            <button
                                                type="button"
                                                wire:click="moveCard({{ $inq->id }}, 'quote_draft')"
                                                class="pbb-btn-move"
                                                title="Mark as Quote Draft"
                                            >
                                                &rarr; Quoted
                                            </button>
                                        @elseif($inq->status === 'quote_draft')
                                            <button
                                                type="button"
                                                wire:click="moveCard({{ $inq->id }}, 'quote_sent')"
                                                class="pbb-btn-move"
                                                title="Mark as Negotiation"
                                            >
                                                &rarr; Negotiate
                                            </button>
                                        @elseif($inq->status === 'quote_sent')
                                            <button
                                                type="button"
                                                wire:click="moveCard({{ $inq->id }}, 'deal')"
                                                class="pbb-btn-won"
                                                title="Mark as Won"
                                            >
                                                &check; Won
                                            </button>
                                        @endif

                                        @if(! in_array($inq->status, ['deal', 'lost'], true))
                                            <button
                                                type="button"
                                                wire:click="openLostModal({{ $inq->id }})"
                                                class="pbb-btn-lost"
                                                title="Mark as Lost"
                                            >
                                                Lost
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="pbb-kanban-empty">
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
        <div class="pbb-modal-backdrop">
            <div class="pbb-modal-dialog">
                <h3 class="pbb-modal-title">
                    <svg class="w-5 h-5 text-rose-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    Close Inquiry as Lost
                </h3>
                <p class="pbb-modal-desc">
                    A formal cancellation reason is strictly required to archive this inquiry into the lost pipeline.
                </p>

                <div>
                    <label class="pbb-modal-label">
                        Lost / Cancellation Reason <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        wire:model="lostReason"
                        rows="3"
                        placeholder="e.g. Competitor offered lower price, client canceled project, or out of stock specifications..."
                        class="pbb-modal-input"
                        required
                    ></textarea>
                    @error('lostReason')
                        <span class="mt-1 text-xs text-rose-500 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="pbb-modal-actions">
                    <button
                        type="button"
                        wire:click="cancelLost"
                        class="pbb-modal-btn-cancel"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        wire:click="confirmLost"
                        class="pbb-modal-btn-lost"
                    >
                        Confirm Lost
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal: Assign Sales PIC (TC-ERP-CRM-006, TC-ERP-CRM-012) -->
    @if($assigningInquiryId)
        <div class="pbb-modal-backdrop">
            <div class="pbb-modal-dialog">
                <h3 class="pbb-modal-title">
                    <svg class="w-5 h-5 text-zinc-700 dark:text-zinc-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    Assign Sales PIC
                </h3>
                <p class="pbb-modal-desc">
                    Select an active Sales PIC or Director to take ownership of this lead.
                </p>

                <div>
                    <label class="pbb-modal-label">
                        Active Sales Personnel <span class="text-rose-500">*</span>
                    </label>
                    <select
                        wire:model="selectedSalesUserId"
                        class="pbb-modal-input"
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

                <div class="pbb-modal-actions">
                    <button
                        type="button"
                        wire:click="cancelAssign"
                        class="pbb-modal-btn-cancel"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        wire:click="confirmAssign"
                        class="pbb-modal-btn-confirm"
                    >
                        Confirm Assignment
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>

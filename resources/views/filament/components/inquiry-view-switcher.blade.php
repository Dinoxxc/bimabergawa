<div class="pbb-kanban-toolbar">
    <div class="pbb-kanban-toolbar-left">
        <span class="pbb-kanban-toolbar-label">View:</span>
        <div class="crm-view-switcher">
            <span class="crm-view-switcher-btn active">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="3" y1="9" x2="21" y2="9"></line>
                    <line x1="3" y1="15" x2="21" y2="15"></line>
                    <line x1="9" y1="3" x2="9" y2="21"></line>
                </svg>
                <span>Table View</span>
            </span>
            <a href="{{ \App\Filament\Resources\InquiryResource::getUrl('kanban') }}" class="crm-view-switcher-btn">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="18" rx="1"></rect>
                    <rect x="14" y="3" width="7" height="18" rx="1"></rect>
                </svg>
                <span>Kanban Board</span>
            </a>
        </div>
    </div>
</div>

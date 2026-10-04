<!-- Google Fonts: Outfit (Headings) & Inter (Body) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800;900&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">

<style>
    /* 1. Global Typography matching Login Screen */
    :root {
        --font-heading: 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif;
        --font-body: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        --brand-blue: #46a2da;
        --brand-blue-hover: #3791c7;
        --brand-blue-active: #297fb5;
    }

    body {
        font-family: var(--font-body);
        -webkit-font-smoothing: antialiased;
    }

    /* Top Precision Accent Line (Engineering Blueprint signature) */
    body::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 2.5px;
        background: linear-gradient(90deg, #1e5a99 0%, #46a2da 50%, #153e6b 100%);
        z-index: 99999;
        pointer-events: none;
    }

    /* 2. STRICT BRAND LOGO SIZING: Prevents any visual overflow or giant image rendering */
    .fi-logo,
    .fi-sidebar-header .fi-logo {
        display: flex !important;
        align-items: center !important;
        height: 36px !important;
        max-height: 36px !important;
        max-width: 220px !important;
        overflow: hidden !important;
    }

    .fi-logo img,
    .fi-sidebar-header img,
    .fi-topbar img {
        height: 28px !important;
        max-height: 28px !important;
        width: auto !important;
        max-width: 200px !important;
        object-fit: contain !important;
        display: block !important;
    }

    html.dark .fi-logo img.logo-light,
    .dark .fi-logo img.logo-light {
        display: none !important;
    }

    html.dark .fi-logo img.logo-dark,
    .dark .fi-logo img.logo-dark {
        display: block !important;
    }

    html:not(.dark) .fi-logo img.logo-dark,
    body:not(.dark) .fi-logo img.logo-dark {
        display: none !important;
    }

    /* Headings hierarchy using Outfit */
    h1, h2, h3, h4, h5, h6,
    .fi-header-heading,
    .fi-section-header-heading,
    .fi-ta-header-heading,
    .fi-modal-header-heading,
    .fi-sidebar-group-label {
        font-family: var(--font-heading) !important;
        letter-spacing: -0.02em;
    }

    .fi-header-heading {
        font-weight: 800 !important;
        font-size: 1.5rem !important;
    }

    /* 3. Primary Buttons using exact #46a2da from Login Screen */
    .fi-btn-primary,
    button.fi-btn.fi-color-primary,
    a.fi-btn.fi-color-primary {
        background-color: var(--brand-blue) !important;
        border-color: var(--brand-blue) !important;
        color: #ffffff !important;
        font-family: var(--font-body) !important;
        font-weight: 600 !important;
        transition: all 0.15s ease-in-out !important;
        box-shadow: 0 1px 3px rgba(70, 162, 218, 0.25) !important;
    }

    .fi-btn-primary:hover,
    button.fi-btn.fi-color-primary:hover,
    a.fi-btn.fi-color-primary:hover {
        background-color: var(--brand-blue-hover) !important;
        border-color: var(--brand-blue-hover) !important;
        box-shadow: 0 2px 6px rgba(70, 162, 218, 0.35) !important;
        transform: translateY(-0.5px);
    }

    .fi-btn-primary:active,
    button.fi-btn.fi-color-primary:active,
    a.fi-btn.fi-color-primary:active {
        background-color: var(--brand-blue-active) !important;
        border-color: var(--brand-blue-active) !important;
        transform: translateY(0);
    }

    /* 4. Sidebar Brand Header & Active Items */
    .fi-sidebar-header {
        border-bottom: 1px solid rgba(228, 228, 231, 0.8) !important;
        padding-top: 0.75rem !important;
        padding-bottom: 0.75rem !important;
    }

    .dark .fi-sidebar-header {
        border-bottom: 1px solid rgba(39, 39, 42, 0.8) !important;
    }

    .fi-sidebar-item-active .fi-sidebar-item-btn {
        background-color: rgba(70, 162, 218, 0.08) !important;
        color: #0369a1 !important;
        border-left: 3px solid var(--brand-blue) !important;
        font-weight: 600 !important;
    }

    .dark .fi-sidebar-item-active .fi-sidebar-item-btn {
        background-color: rgba(70, 162, 218, 0.15) !important;
        color: #7dd3fc !important;
    }

    .fi-sidebar-item-active .fi-sidebar-item-icon {
        color: var(--brand-blue) !important;
    }

    /* 5. Custom View Switcher Toolbar */
    .crm-view-switcher {
        display: inline-flex;
        align-items: center;
        background-color: #f4f4f5;
        border: 1px solid #e4e4e7;
        border-radius: 8px;
        padding: 3px;
        gap: 2px;
    }

    .dark .crm-view-switcher {
        background-color: #18181b;
        border-color: #27272a;
    }

    .crm-view-switcher-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        font-size: 12px;
        font-weight: 600;
        border-radius: 6px;
        color: #71717a;
        transition: all 0.15s ease;
        text-decoration: none;
    }

    .crm-view-switcher-btn:hover {
        color: #09090b;
    }

    .dark .crm-view-switcher-btn:hover {
        color: #fafafa;
    }

    .crm-view-switcher-btn.active {
        background-color: #ffffff;
        color: #0c2646;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
        border: 1px solid #e4e4e7;
    }

    .dark .crm-view-switcher-btn.active {
        background-color: #27272a;
        color: #7dd3fc;
        border-color: #3f3f46;
    }

    /* 6. Precision CRM Kanban Board Styling (Bulletproof against Tailwind JIT) */
    .pbb-kanban-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        background-color: #ffffff;
        border: 1px solid #e4e4e7;
        border-radius: 12px;
        padding: 10px 16px;
        margin-bottom: 20px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }

    .dark .pbb-kanban-toolbar {
        background-color: #18181b;
        border-color: #27272a;
    }

    .pbb-kanban-toolbar-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .pbb-kanban-toolbar-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #71717a;
        font-family: 'JetBrains Mono', monospace;
    }

    .pbb-kanban-toolbar-right {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 12px;
        color: #71717a;
    }

    .pbb-kanban-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 500;
    }

    .pbb-kanban-legend-dot {
        width: 8px;
        height: 8px;
        border-radius: 9999px;
    }

    .pbb-kanban-legend-dot.warning {
        background-color: #f59e0b;
    }

    .pbb-kanban-legend-dot.danger {
        background-color: #ef4444;
    }

    .pbb-kanban-legend-sep {
        color: #d4d4d8;
    }

    /* Kanban Horizontal Scrollable Container */
    .pbb-kanban-container {
        width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 24px;
    }

    .pbb-kanban-container::-webkit-scrollbar {
        height: 8px;
    }

    .pbb-kanban-container::-webkit-scrollbar-track {
        background: #f4f4f5;
        border-radius: 4px;
    }

    .dark .pbb-kanban-container::-webkit-scrollbar-track {
        background: #27272a;
    }

    .pbb-kanban-container::-webkit-scrollbar-thumb {
        background: #d4d4d8;
        border-radius: 4px;
    }

    .dark .pbb-kanban-container::-webkit-scrollbar-thumb {
        background: #52525b;
    }

    .pbb-kanban-board {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        min-width: 1880px;
        width: max-content;
    }

    /* Column */
    .pbb-kanban-col {
        width: 298px;
        min-width: 298px;
        max-width: 298px;
        flex: 0 0 298px;
        background-color: #fafafa;
        border: 1px solid #e4e4e7;
        border-radius: 12px;
        padding: 12px;
        box-sizing: border-box;
    }

    .dark .pbb-kanban-col {
        background-color: #121214;
        border-color: #27272a;
    }

    .pbb-kanban-col-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 10px;
        border-bottom: 1px solid #e4e4e7;
        margin-bottom: 12px;
    }

    .dark .pbb-kanban-col-header {
        border-color: #27272a;
    }

    .pbb-kanban-col-title {
        font-family: var(--font-heading);
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #18181b;
    }

    .dark .pbb-kanban-col-title {
        color: #f4f4f5;
    }

    .pbb-kanban-col-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        height: 20px;
        padding: 0 6px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
        font-family: 'JetBrains Mono', monospace;
    }

    /* Column Badge Colors */
    .pbb-badge-sky {
        background-color: #f0f9ff;
        color: #0369a1;
        border: 1px solid #bae6fd;
    }
    .dark .pbb-badge-sky {
        background-color: rgba(3, 105, 161, 0.2);
        color: #7dd3fc;
        border-color: #0369a1;
    }

    .pbb-badge-amber {
        background-color: #fffbeb;
        color: #b45309;
        border: 1px solid #fde68a;
    }
    .dark .pbb-badge-amber {
        background-color: rgba(180, 83, 9, 0.2);
        color: #fcd34d;
        border-color: #b45309;
    }

    .pbb-badge-indigo {
        background-color: #eef2ff;
        color: #4338ca;
        border: 1px solid #c7d2fe;
    }
    .dark .pbb-badge-indigo {
        background-color: rgba(67, 56, 202, 0.2);
        color: #a5b4fc;
        border-color: #4338ca;
    }

    .pbb-badge-purple {
        background-color: #faf5ff;
        color: #7e22ce;
        border: 1px solid #e9d5ff;
    }
    .dark .pbb-badge-purple {
        background-color: rgba(126, 34, 206, 0.2);
        color: #d8b4fe;
        border-color: #7e22ce;
    }

    .pbb-badge-emerald {
        background-color: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }
    .dark .pbb-badge-emerald {
        background-color: rgba(4, 120, 87, 0.2);
        color: #6ee7b7;
        border-color: #047857;
    }

    .pbb-badge-rose {
        background-color: #fff1f2;
        color: #be123c;
        border: 1px solid #fecdd3;
    }
    .dark .pbb-badge-rose {
        background-color: rgba(190, 18, 60, 0.2);
        color: #fda4af;
        border-color: #be123c;
    }

    /* Cards Stack */
    .pbb-kanban-cards {
        display: flex;
        flex-direction: column;
        gap: 12px;
        min-height: 480px;
    }

    /* Empty state */
    .pbb-kanban-empty {
        border: 1px dashed #d4d4d8;
        border-radius: 8px;
        padding: 24px 12px;
        text-align: center;
        font-size: 12px;
        color: #a1a1aa;
    }

    .dark .pbb-kanban-empty {
        border-color: #3f3f46;
        color: #71717a;
    }

    /* Individual Card */
    .pbb-kanban-card {
        background-color: #ffffff;
        border: 1px solid #e4e4e7;
        border-radius: 10px;
        padding: 12px 14px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        transition: all 0.15s ease;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .dark .pbb-kanban-card {
        background-color: #18181b;
        border-color: #27272a;
    }

    .pbb-kanban-card:hover {
        border-color: #a1a1aa;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        transform: translateY(-1px);
    }

    .dark .pbb-kanban-card:hover {
        border-color: #52525b;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    }

    /* Card Elements */
    .pbb-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 11px;
    }

    .pbb-card-number {
        font-family: 'JetBrains Mono', monospace;
        font-weight: 700;
        color: #09090b;
    }

    .dark .pbb-card-number {
        color: #f4f4f5;
    }

    .pbb-card-time {
        color: #a1a1aa;
        font-size: 11px;
    }

    .pbb-card-company {
        font-family: var(--font-heading);
        font-size: 13px;
        font-weight: 700;
        color: #09090b;
        line-height: 1.3;
        margin: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .dark .pbb-card-company {
        color: #ffffff;
    }

    .pbb-card-contact {
        font-size: 11px;
        color: #71717a;
        display: flex;
        align-items: center;
        gap: 6px;
        margin: 0;
    }

    .pbb-card-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
        font-size: 11px;
    }

    .pbb-card-sku-badge {
        display: inline-flex;
        align-items: center;
        background-color: #f4f4f5;
        border: 1px solid #e4e4e7;
        color: #3f3f46;
        padding: 1px 6px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 600;
    }

    .dark .pbb-card-sku-badge {
        background-color: #27272a;
        border-color: #3f3f46;
        color: #d4d4d8;
    }

    .pbb-card-project {
        color: #71717a;
        font-size: 11px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 140px;
    }

    /* SLA Badges */
    .pbb-card-sla-warning {
        display: flex;
        align-items: center;
        gap: 6px;
        background-color: #fffbeb;
        border: 1px solid #fde68a;
        color: #b45309;
        padding: 5px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
    }

    .dark .pbb-card-sla-warning {
        background-color: rgba(180, 83, 9, 0.2);
        border-color: #b45309;
        color: #fcd34d;
    }

    .pbb-card-sla-danger {
        display: flex;
        align-items: center;
        gap: 6px;
        background-color: #fff1f2;
        border: 1px solid #fecdd3;
        color: #be123c;
        padding: 5px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
    }

    .dark .pbb-card-sla-danger {
        background-color: rgba(190, 18, 60, 0.2);
        border-color: #be123c;
        color: #fda4af;
    }

    /* PIC row */
    .pbb-card-pic-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 8px;
        border-top: 1px solid #f4f4f5;
        font-size: 11px;
    }

    .dark .pbb-card-pic-row {
        border-color: #27272a;
    }

    .pbb-card-pic-info {
        display: flex;
        align-items: center;
        gap: 5px;
        color: #52525b;
    }

    .dark .pbb-card-pic-info {
        color: #a1a1aa;
    }

    .pbb-card-pic-name {
        font-weight: 600;
        color: #18181b;
    }

    .dark .pbb-card-pic-name {
        color: #f4f4f5;
    }

    .pbb-card-btn-assign {
        background: transparent;
        border: none;
        color: var(--brand-blue);
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        padding: 0;
    }

    .pbb-card-btn-assign:hover {
        color: var(--brand-blue-hover);
        text-decoration: underline;
    }

    /* Actions row */
    .pbb-card-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 8px;
        border-top: 1px solid #f4f4f5;
        gap: 6px;
    }

    .dark .pbb-card-actions {
        border-color: #27272a;
    }

    .pbb-card-link-view {
        font-size: 11px;
        font-weight: 600;
        color: #52525b;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: color 0.15s;
    }

    .pbb-card-link-view:hover {
        color: #09090b;
    }

    .dark .pbb-card-link-view {
        color: #a1a1aa;
    }

    .dark .pbb-card-link-view:hover {
        color: #ffffff;
    }

    .pbb-card-btns-group {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    /* Button Next / Move */
    .pbb-btn-move {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background-color: #eef7fc;
        color: #0284c7;
        border: 1px solid #bae6fd;
        font-size: 10.5px;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.15s;
    }

    .pbb-btn-move:hover {
        background-color: var(--brand-blue);
        color: #ffffff;
        border-color: var(--brand-blue);
    }

    .dark .pbb-btn-move {
        background-color: rgba(70, 162, 218, 0.15);
        color: #7dd3fc;
        border-color: rgba(70, 162, 218, 0.3);
    }

    .dark .pbb-btn-move:hover {
        background-color: var(--brand-blue);
        color: #ffffff;
    }

    /* Won Button */
    .pbb-btn-won {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        background-color: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
        font-size: 10.5px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.15s;
    }

    .pbb-btn-won:hover {
        background-color: #10b981;
        color: #ffffff;
        border-color: #10b981;
    }

    /* Lost Button */
    .pbb-btn-lost {
        display: inline-flex;
        align-items: center;
        background-color: #fff1f2;
        color: #be123c;
        border: 1px solid #fecdd3;
        font-size: 10.5px;
        font-weight: 600;
        padding: 3px 7px;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.15s;
    }

    .pbb-btn-lost:hover {
        background-color: #e11d48;
        color: #ffffff;
        border-color: #e11d48;
    }

    .dark .pbb-btn-lost {
        background-color: rgba(190, 18, 60, 0.2);
        color: #fda4af;
        border-color: rgba(190, 18, 60, 0.4);
    }

    .dark .pbb-btn-lost:hover {
        background-color: #e11d48;
        color: #ffffff;
    }

    /* Modal Backdrop & Dialog */
    .pbb-modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: rgba(0, 0, 0, 0.55);
        backdrop-filter: blur(2px);
        padding: 16px;
    }

    .pbb-modal-dialog {
        width: 100%;
        max-width: 440px;
        background-color: #ffffff;
        border: 1px solid #e4e4e7;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
        box-sizing: border-box;
    }

    .dark .pbb-modal-dialog {
        background-color: #18181b;
        border-color: #27272a;
    }

    .pbb-modal-title {
        font-family: var(--font-heading);
        font-size: 16px;
        font-weight: 700;
        color: #09090b;
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
    }

    .dark .pbb-modal-title {
        color: #f4f4f5;
    }

    .pbb-modal-desc {
        font-size: 12px;
        color: #71717a;
        margin-top: 6px;
        margin-bottom: 16px;
    }

    .pbb-modal-label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #3f3f46;
        margin-bottom: 6px;
    }

    .dark .pbb-modal-label {
        color: #d4d4d8;
    }

    .pbb-modal-input {
        width: 100%;
        border-radius: 8px;
        border: 1px solid #d4d4d8;
        background-color: #ffffff;
        padding: 8px 12px;
        font-size: 12px;
        color: #09090b;
        box-sizing: border-box;
        outline: none;
    }

    .pbb-modal-input:focus {
        border-color: var(--brand-blue);
        box-shadow: 0 0 0 2px rgba(70, 162, 218, 0.2);
    }

    .dark .pbb-modal-input {
        background-color: #27272a;
        border-color: #3f3f46;
        color: #f4f4f5;
    }

    .pbb-modal-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }

    .pbb-modal-btn-cancel {
        padding: 8px 16px;
        border-radius: 8px;
        border: 1px solid #d4d4d8;
        background-color: #ffffff;
        color: #52525b;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
    }

    .pbb-modal-btn-cancel:hover {
        background-color: #f4f4f5;
        color: #09090b;
    }

    .dark .pbb-modal-btn-cancel {
        background-color: #27272a;
        border-color: #3f3f46;
        color: #d4d4d8;
    }

    .pbb-modal-btn-confirm {
        padding: 8px 16px;
        border-radius: 8px;
        border: none;
        background-color: #09090b;
        color: #ffffff;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
    }

    .pbb-modal-btn-confirm:hover {
        background-color: #27272a;
    }

    .dark .pbb-modal-btn-confirm {
        background-color: #f4f4f5;
        color: #09090b;
    }

    .pbb-modal-btn-lost {
        padding: 8px 16px;
        border-radius: 8px;
        border: none;
        background-color: #e11d48;
        color: #ffffff;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
    }

    .pbb-modal-btn-lost:hover {
        background-color: #be123c;
    }
</style>

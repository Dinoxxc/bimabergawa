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
        color: #09090b;
    }

    .dark .fi-header-heading {
        color: #f4f4f5 !important;
    }

    /* 2. Primary Buttons using exact #46a2da from Login Screen */
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

    /* 3. Sidebar Brand Header & Active Items */
    .fi-sidebar-header {
        border-bottom: 1px solid rgba(228, 228, 231, 0.8) !important;
        padding-top: 1rem !important;
        padding-bottom: 1rem !important;
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

    /* 4. Tabs & Active Badges */
    .fi-tabs-item-active {
        color: var(--brand-blue) !important;
        border-color: var(--brand-blue) !important;
        font-weight: 600 !important;
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
</style>

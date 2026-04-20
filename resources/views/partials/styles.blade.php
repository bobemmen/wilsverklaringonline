<style>
    :root {
        --wv-purple-dark:   #26215C;
        --wv-purple-mid:    #3C3489;
        --wv-purple-accent: #534AB7;
        --wv-purple-light:  #AFA9EC;
        --wv-purple-pale:   #EEEDFE;

        --wv-bg-page:       #F8F7F4;
        --wv-bg-sidebar:    #26215C;
        --wv-bg-topbar:     #F1EFE8;
        --wv-bg-card:       #ffffff;

        --wv-text-primary:  #2C2C2A;
        --wv-text-muted:    #888780;
        --wv-text-subtle:   #B4B2A9;

        --wv-border:        #D3D1C7;
        --wv-green-check:   #5DCAA5;
    }

    * { box-sizing: border-box; }

    html, body {
        margin: 0;
        padding: 0;
        font-family: 'Jost', -apple-system, BlinkMacSystemFont, sans-serif;
        font-weight: 400;
        color: var(--wv-text-primary);
        background: var(--wv-bg-page);
        font-size: 13px;
        line-height: 1.55;
    }

    a { color: var(--wv-purple-mid); text-decoration: none; }
    a:hover { color: var(--wv-purple-dark); }

    h1, h2, h3, h4 {
        font-family: 'EB Garamond', serif;
        font-weight: 400;
        color: var(--wv-text-primary);
        margin: 0 0 0.5em;
    }
    h1 { font-size: 24px; }
    h2 { font-size: 19px; }
    h3 { font-size: 15px; }

    .app-shell {
        display: grid;
        grid-template-columns: 190px 1fr;
        min-height: 100vh;
    }

    .app-sidebar {
        background: var(--wv-bg-sidebar);
        color: white;
        padding: 24px 14px;
    }

    .sidebar-logo {
        font-family: 'EB Garamond', serif;
        font-size: 20px;
        color: white;
        padding: 4px 10px 22px;
        letter-spacing: 0.01em;
    }

    .sidebar-section {
        text-transform: uppercase;
        font-size: 9px;
        letter-spacing: 0.15em;
        color: rgba(255,255,255,0.45);
        padding: 14px 10px 6px;
    }

    .sidebar-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 10px;
        border-radius: 6px;
        font-size: 12px;
        opacity: 0.5;
        color: white;
        margin-bottom: 2px;
    }
    .sidebar-item:hover { opacity: 0.8; color: white; }
    .sidebar-item.active {
        background: rgba(255,255,255,0.08);
        opacity: 1;
    }

    .sidebar-pip {
        width: 7px; height: 7px; border-radius: 999px;
        background: rgba(255,255,255,0.22);
        flex-shrink: 0;
    }
    .sidebar-pip.done { background: var(--wv-green-check); }
    .sidebar-pip.active { background: var(--wv-purple-light); }

    .app-main { display: flex; flex-direction: column; }

    .app-topbar {
        background: var(--wv-bg-topbar);
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 28px;
        border-bottom: 0.5px solid var(--wv-border);
        font-size: 11px;
        color: var(--wv-text-muted);
    }

    .breadcrumb {
        display: flex; gap: 6px; align-items: center;
    }
    .breadcrumb .sep { color: var(--wv-text-subtle); }

    .topbar-right { display: flex; align-items: center; gap: 14px; }

    .avatar {
        width: 26px; height: 26px; border-radius: 999px;
        background: var(--wv-purple-pale);
        color: var(--wv-purple-mid);
        display: flex; align-items: center; justify-content: center;
        font-size: 11px;
    }

    .app-content {
        padding: 24px 28px;
        max-width: 960px;
    }

    .card {
        background: var(--wv-bg-card);
        border: 0.5px solid var(--wv-border);
        border-radius: 10px;
        padding: 18px 20px;
        margin-bottom: 16px;
    }
    .card-title {
        font-family: 'EB Garamond', serif;
        font-size: 15px;
        margin-bottom: 6px;
    }

    .card-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }

    .field { margin-bottom: 14px; }
    .label {
        display: block;
        font-size: 9.5px;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--wv-text-muted);
        margin-bottom: 6px;
    }

    input[type=text], input[type=email], input[type=password], input[type=date], textarea, select {
        width: 100%;
        background: var(--wv-bg-page);
        border: 0.5px solid var(--wv-border);
        border-radius: 7px;
        padding: 10px 12px;
        font-family: 'Jost', sans-serif;
        font-size: 13px;
        color: var(--wv-text-primary);
        transition: border-color 0.15s ease;
    }
    textarea { min-height: 110px; resize: vertical; line-height: 1.55; }

    input:focus, textarea:focus, select:focus {
        outline: none;
        border-color: var(--wv-purple-light);
    }

    .radio-group { display: flex; flex-direction: column; gap: 8px; }

    .radio-option {
        border: 0.5px solid var(--wv-border);
        border-radius: 8px;
        padding: 11px 14px;
        cursor: pointer;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        background: white;
        transition: all 0.12s ease;
    }
    .radio-option:hover { border-color: var(--wv-purple-light); }
    .radio-option.selected {
        border-color: var(--wv-purple-light);
        background: #EEEDFE44;
    }
    .radio-dot {
        width: 14px; height: 14px; border-radius: 999px;
        border: 0.5px solid var(--wv-border);
        flex-shrink: 0; margin-top: 1px;
        background: white;
    }
    .radio-option.selected .radio-dot {
        border-color: var(--wv-purple-mid);
        background: radial-gradient(circle, var(--wv-purple-mid) 40%, white 45%);
    }
    .radio-label { font-size: 13px; }
    .radio-help { font-size: 11px; color: var(--wv-text-muted); margin-top: 3px; }

    .info-block {
        background: #EEEDFE55;
        border: 0.5px solid var(--wv-purple-light);
        border-radius: 7px;
        padding: 10px 12px;
        font-size: 11px;
        color: var(--wv-text-muted);
        margin-bottom: 14px;
    }

    .btn {
        font-family: 'Jost', sans-serif;
        font-weight: 400;
        font-size: 12px;
        padding: 8px 18px;
        border-radius: 7px;
        border: 0;
        cursor: pointer;
        transition: background 0.12s ease, border-color 0.12s ease;
    }
    .btn-primary {
        background: var(--wv-purple-mid);
        color: white;
    }
    .btn-primary:hover { background: var(--wv-purple-dark); }

    .btn-ghost {
        background: transparent;
        border: 0.5px solid var(--wv-border);
        color: var(--wv-text-muted);
    }
    .btn-ghost:hover { border-color: var(--wv-purple-light); color: var(--wv-purple-mid); }

    .btn-danger {
        background: transparent;
        border: 0.5px solid #D5A2A2;
        color: #9B4A4A;
    }
    .btn-danger:hover { background: #F5E7E7; }

    .action-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 24px;
        padding-top: 16px;
        border-top: 0.5px solid var(--wv-border);
    }
    .action-bar .left, .action-bar .right { display: flex; gap: 8px; }

    .progress-bar {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 6px;
        margin: 14px 0 24px;
    }
    .progress-seg {
        height: 2.5px;
        border-radius: 2px;
        background: var(--wv-border);
    }
    .progress-seg.done { background: var(--wv-purple-mid); }
    .progress-seg.active { background: var(--wv-purple-light); }

    .pill {
        display: inline-block;
        background: var(--wv-purple-pale);
        color: var(--wv-purple-mid);
        font-size: 9px;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        padding: 3px 10px;
        border-radius: 999px;
    }
    .pill-muted { background: #F1EFE8; color: var(--wv-text-muted); }
    .pill-green { background: #E5F6EE; color: #2E8A67; }
    .pill-red   { background: #F5E7E7; color: #9B4A4A; }

    .flash-banner {
        background: var(--wv-purple-pale);
        border: 0.5px solid var(--wv-purple-light);
        color: var(--wv-purple-mid);
        padding: 10px 14px;
        border-radius: 7px;
        margin-bottom: 16px;
        font-size: 12px;
    }

    .muted { color: var(--wv-text-muted); }
    .subtle { color: var(--wv-text-subtle); }
    .small { font-size: 11px; }

    .divider { border-top: 0.5px solid var(--wv-border); margin: 14px 0; }

    table { width: 100%; border-collapse: collapse; font-size: 12px; }
    th { text-align: left; padding: 10px 8px; font-size: 9.5px; letter-spacing: 0.12em; text-transform: uppercase; color: var(--wv-text-muted); border-bottom: 0.5px solid var(--wv-border); font-weight: 400; }
    td { padding: 12px 8px; border-bottom: 0.5px solid var(--wv-border); }
    tr:last-child td { border-bottom: 0; }

    .summary-block {
        background: var(--wv-bg-page);
        border: 0.5px solid var(--wv-border);
        border-radius: 8px;
        padding: 12px 14px;
        margin-bottom: 10px;
    }
    .summary-label {
        font-size: 9.5px;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--wv-text-muted);
        margin-bottom: 4px;
    }
    .summary-value { font-size: 13px; }
    .summary-value em { color: var(--wv-text-subtle); font-style: italic; }

    /* Guest layout */
    .guest-body {
        background: var(--wv-bg-page);
    }
    .guest-wrap {
        max-width: 420px;
        margin: 0 auto;
        padding: 90px 24px 40px;
    }
    .guest-brand {
        font-family: 'EB Garamond', serif;
        font-size: 28px;
        color: var(--wv-purple-dark);
        text-align: center;
        margin-bottom: 24px;
    }
    .guest-card {
        background: white;
        border: 0.5px solid var(--wv-border);
        border-radius: 10px;
        padding: 28px 26px;
    }
    .guest-links {
        text-align: center;
        padding-top: 14px;
        font-size: 12px;
        color: var(--wv-text-muted);
    }

    .error {
        color: #9B4A4A;
        font-size: 11px;
        margin-top: 4px;
    }

    .public-header {
        text-align: center;
        padding: 32px 20px 12px;
        border-bottom: 0.5px solid var(--wv-border);
        margin-bottom: 28px;
    }
    .public-wrap { max-width: 720px; margin: 0 auto; padding: 0 24px 60px; }

    .block-header {
        font-family: 'EB Garamond', serif;
        font-size: 18px;
        color: var(--wv-purple-dark);
        margin: 28px 0 12px;
        padding-bottom: 6px;
        border-bottom: 0.5px solid var(--wv-border);
    }
    .block-section {
        background: white;
        border: 0.5px solid var(--wv-border);
        border-radius: 10px;
        padding: 18px 22px;
        margin-bottom: 12px;
    }

    .status-row { display: flex; justify-content: space-between; align-items: center; }

    .token-display {
        font-family: 'Jost', monospace;
        font-size: 12px;
        background: var(--wv-purple-pale);
        color: var(--wv-purple-dark);
        padding: 10px 12px;
        border-radius: 7px;
        word-break: break-all;
        border: 0.5px solid var(--wv-purple-light);
    }
</style>

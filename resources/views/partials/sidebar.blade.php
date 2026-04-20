<aside class="app-sidebar">
    <div class="sidebar-logo">WilsVerklaring</div>

    <div class="sidebar-section">Uw verklaring</div>
    <a href="{{ route('dashboard') }}" class="sidebar-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <span class="sidebar-pip {{ request()->routeIs('dashboard') ? 'active' : '' }}"></span>
        Dashboard
    </a>
    <a href="{{ route('wizard.show') }}" class="sidebar-item {{ request()->routeIs('wizard.show') ? 'active' : '' }}">
        <span class="sidebar-pip {{ request()->routeIs('wizard.show') ? 'active' : '' }}"></span>
        Wizard
    </a>
    <a href="{{ route('wizard.versions') }}" class="sidebar-item {{ request()->routeIs('wizard.versions') ? 'active' : '' }}">
        <span class="sidebar-pip {{ request()->routeIs('wizard.versions') ? 'active' : '' }}"></span>
        Versies
    </a>

    <div class="sidebar-section">Delen</div>
    <a href="{{ route('token.index') }}" class="sidebar-item {{ request()->routeIs('token.*') ? 'active' : '' }}">
        <span class="sidebar-pip {{ request()->routeIs('token.*') ? 'active' : '' }}"></span>
        Arts-toegang
    </a>
    <a href="{{ route('naasten.index') }}" class="sidebar-item {{ request()->routeIs('naasten.*') ? 'active' : '' }}">
        <span class="sidebar-pip {{ request()->routeIs('naasten.*') ? 'active' : '' }}"></span>
        Naasten
    </a>

    <div class="sidebar-section">Account</div>
    <a href="{{ route('2fa.setup') }}" class="sidebar-item {{ request()->routeIs('2fa.*') ? 'active' : '' }}">
        <span class="sidebar-pip"></span>
        Tweestapsverificatie
    </a>
    <a href="{{ route('account.export') }}" class="sidebar-item">
        <span class="sidebar-pip"></span>
        Exporteer gegevens
    </a>
    <form method="POST" action="{{ route('logout') }}" style="margin-top: 8px;">
        @csrf
        <button type="submit" class="sidebar-item" style="background: transparent; border: 0; width: 100%; text-align: left; cursor: pointer; font-family: inherit;">
            <span class="sidebar-pip"></span>
            Uitloggen
        </button>
    </form>
</aside>

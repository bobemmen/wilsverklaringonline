<header class="app-topbar">
    <div class="breadcrumb">
        <span class="subtle">WilsVerklaring</span>
        <span class="sep">/</span>
        <span>
            @if(request()->routeIs('dashboard')) Dashboard
            @elseif(request()->routeIs('wizard.show')) Wizard
            @elseif(request()->routeIs('wizard.versions')) Versies
            @elseif(request()->routeIs('token.*')) Arts-toegang
            @elseif(request()->routeIs('naasten.*')) Naasten
            @elseif(request()->routeIs('2fa.*')) Tweestapsverificatie
            @elseif(request()->routeIs('admin.*')) Beheer
            @else {{ config('app.name') }}
            @endif
        </span>
    </div>
    <div class="topbar-right">
        @php
            $user = auth()->user();
            $declaration = $user ? \App\Models\Declaration::where('user_id', $user->id)->first() : null;
        @endphp
        @if($declaration)
            @if($declaration->is_published)
                <span class="pill pill-green">Definitief</span>
            @else
                <span class="pill pill-muted">Concept</span>
            @endif
        @endif
        @if($user)
            <div class="avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
        @endif
    </div>
</header>

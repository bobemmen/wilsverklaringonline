<div>
    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px;">
        <div>
            <div class="small muted" style="letter-spacing: 0.12em; text-transform: uppercase;">Hoofdstuk {{ $chapter }} van 5 &middot; {{ $chapterTitle }}</div>
            <h1 style="margin-top: 4px;">@yield('step-title') {{ match($step) {
                1 => 'Wie bent u',
                2 => 'Wanneer is het genoeg',
                3 => 'Reanimatie',
                4 => 'Beademing en intensive care',
                5 => 'Antibiotica en voeding',
                6 => 'Palliatieve sedatie',
                7 => 'Lijden omschrijven',
                8 => 'Euthanasieverzoek opnemen',
                9 => 'Lijden en situaties',
                10 => 'Wilsonbekwaamheid',
                11 => 'Persoonlijke brief',
                12 => 'Orgaandonatie en uitvaart',
                13 => 'Arts en ondertekening',
                14 => 'Overzicht en publicatie',
                default => '',
            } }}</h1>
        </div>
        <div class="small muted">Stap {{ $step }} van {{ $totalSteps }}</div>
    </div>

    @php
        $progressChapters = [1,2,3,4,5];
    @endphp
    <div class="progress-bar">
        @foreach($progressChapters as $c)
            @php
                $currentChapter = $chapter;
                $class = 'progress-seg';
                if ($c < $currentChapter) $class .= ' done';
                if ($c === $currentChapter) $class .= ' active';
            @endphp
            <div class="{{ $class }}"></div>
        @endforeach
    </div>

    @if($statusMessage)
        <div class="flash-banner">{{ $statusMessage }}</div>
    @endif

    <div class="card">
        @include('verklaring.partials.step-' . $step)

        <div class="action-bar">
            <div class="left">
                @if($step > 1)
                    <button type="button" class="btn btn-ghost" wire:click="previous">Terug</button>
                @endif
            </div>
            <div class="right">
                @if($step === 8)
                    <button type="button" class="btn btn-ghost" wire:click="skip">Ik sla dit over</button>
                @endif
                <button type="button" class="btn btn-ghost" wire:click="saveDraft">Opslaan als concept</button>
                @if($step < $totalSteps)
                    <button type="button" class="btn btn-primary" wire:click="next">Volgende</button>
                @else
                    <button type="button" class="btn btn-primary" wire:click="publish" wire:confirm="Weet u zeker dat u uw wilsverklaring wilt publiceren? Eerdere versies blijven bewaard.">Publiceer verklaring</button>
                @endif
            </div>
        </div>
    </div>
</div>

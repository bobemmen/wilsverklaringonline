<div class="info-block">
    Uw arts kennen helpt de behandelende arts. Heeft u dit besproken met uw huisarts? Als dat niet zo is, bespreek het dan zo snel mogelijk &mdash; uw verklaring wint aan waarde als uw arts weet wat er in staat.
</div>

<div class="field">
    <label class="label" for="arts">Naam en praktijk van mijn huisarts</label>
    <input type="text" id="arts" wire:model="data.arts" placeholder="Dr. &hellip; &mdash; Huisartsenpraktijk &hellip;">
</div>

<div class="field">
    <label class="label">Heeft u deze verklaring met uw arts besproken?</label>
    <div class="radio-group">
        @include('partials.radio', ['name' => 'besproken', 'value' => 'ja', 'label' => 'Ja', 'model' => $data['besproken']])
        @include('partials.radio', ['name' => 'besproken', 'value' => 'binnenkort', 'label' => 'Nog niet &mdash; ik plan een afspraak', 'model' => $data['besproken']])
        @include('partials.radio', ['name' => 'besproken', 'value' => 'nee', 'label' => 'Nee', 'model' => $data['besproken']])
    </div>
</div>

<div class="field">
    <label class="label" for="datum">Datum van ondertekening</label>
    <input type="date" id="datum" wire:model="data.datum">
</div>

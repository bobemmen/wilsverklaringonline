<div class="callout"><p>Een arts mag alleen op uw verzoek ingaan als er sprake is van ondraaglijk en uitzichtloos lijden. Omschrijf zo concreet mogelijk wat voor u ondraaglijk is en in welke situatie u om euthanasie zou vragen.</p></div>

<details class="more">
    <summary>Voorbeelden van ondraaglijk lijden</summary>
    <div class="more-body">
<p>Een arts kan alleen euthanasie verlenen bij lijden met een medische oorzaak. Hieronder staan situaties die vaak voorkomen. Het zijn voorbeelden, geen checklist: alleen u weet wat voor u ondraaglijk is.</p>
<h2>Chronische ziekte (bijvoorbeeld MS, Parkinson, ALS, COPD)</h2>
<p>De ziekte is niet te genezen en wordt steeds erger. Mogelijk lijden: steeds afhankelijker worden van anderen, minder makkelijk gaan waar u wilt, minder goed beslissingen kunnen nemen, vaker verward zijn, pijn, stijfheid, benauwdheid, uitputting, incontinentie of verstopping. U kunt om euthanasie vragen, ook als u nog lang zou kunnen leven.</p>
<h2>Meerdere ouderdomsklachten tegelijk</h2>
<p>Alleen het gevoel dat uw leven voltooid is, is voor een arts niet genoeg. Is er een stapeling van ouderdomsaandoeningen, zoals slechter horen of zien, vermoeidheid, pijn of incontinentie? Dan is euthanasie wel mogelijk. Mogelijk lijden: afhankelijk zijn van zorg, verlies van zelfstandigheid en waardigheid, niet meer kunnen doen wat uw leven waardevol maakt, geen sociale contacten meer kunnen onderhouden.</p>
<h2>Psychiatrische ziekte</h2>
<p>Ook psychiatrische ziekten kunnen ondraaglijk en uitzichtloos lijden geven. Euthanasie is dan mogelijk, maar een traject dat intensief is en lang kan duren, met extra onderzoek door een onafhankelijk psychiater. De arts onderzoekt ook of u het echt voor uzelf vraagt, en niet uit schaamte, schuldgevoel of omdat u anderen niet tot last wilt zijn.</p>
<p><strong>Tip:</strong> hoe concreter u beschrijft wat voor u ondraaglijk is, hoe beter uw arts u begrijpt.</p>
    </div>
</details>


<div class="field">
    <label for="euthanasie_lijden">Wat ik ondraaglijk vind</label>
    <p class="hint" id="euthanasie_lijden-hint">Beschrijf het zo concreet mogelijk. Uw arts moet kunnen begrijpen waarom het voor u ondraaglijk is.</p>
    <?= $this->textarea('euthanasie_lijden', $data['euthanasie_lijden'], 'Ik lijd ondraaglijk wanneer …', '', 'euthanasie_lijden-hint') ?>
</div>

<div class="field">
    <label for="euthanasie_situatie">Concrete situatie waarin ik om euthanasie zou vragen</label>
    <?= $this->textarea('euthanasie_situatie', $data['euthanasie_situatie'], 'Als ik niet meer … en geen uitzicht heb op …') ?>
</div>

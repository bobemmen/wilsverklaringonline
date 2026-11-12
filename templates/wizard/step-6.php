<div class="callout"><p>Bij palliatieve sedatie wordt u — meestal in de laatste levensfase — in slaap gebracht om ondraaglijk lijden te verzachten. Dit versnelt het sterven niet, maar haalt de scherpe randen eraf.</p></div>

<details class="more">
    <summary>Wat is palliatieve sedatie, en wanneer kan het?</summary>
    <div class="more-body">
<p>U krijgt medicijnen die u rustig en minder bewust maken, zodat u geen last meer heeft van uw klachten. Het overlijden maakt u niet meer bewust mee. Meestal overlijdt u binnen enkele uren tot dagen.</p>
<h2>Twee voorwaarden</h2>
<ul>
    <li>U heeft klachten die niet te behandelen zijn en u lijdt ondraaglijk.</li>
    <li>U bent stervende: uw arts verwacht dat u binnen twee weken overlijdt.</li>
</ul>
<p>Uw arts beslist of er wordt begonnen en bespreekt dat met u. Kan dat niet meer, dan overlegt de arts met uw familie of uw vertegenwoordiger.</p>
<h2>Sedatie of euthanasie?</h2>
<p>Soms stelt een arts palliatieve sedatie voor als u om euthanasie vraagt. Het verschil: na het begin van sedatie kunt u al snel niet meer praten met de mensen om u heen en sterft u slapend, in uren tot dagen. Bij euthanasie overlijdt u vrijwel direct na de medicatie. Vragen om over na te denken:</p>
<ul>
    <li>Heeft u zelf een voorkeur, en waarom?</li>
    <li>Weet u genoeg over beide mogelijkheden?</li>
    <li>Is er nog tijd voor een euthanasieverzoek, of gaat het al zo slecht dat sedatie beter past?</li>
    <li>Wil uw arts euthanasie uitvoeren, en kan het volgens de wet?</li>
    <li>Hebben uw naasten hier een mening over, en hoe belangrijk is die voor u?</li>
</ul>
    </div>
</details>


<fieldset class="field">
    <legend>Wens palliatieve sedatie</legend>
    <?= $this->radio('palliatief_wens', 'ja', 'Ja, als ik ondraaglijk lijd', 'Ook wanneer ik dat zelf niet meer kan aangeven.', $data['palliatief_wens']) ?>
    <?= $this->radio('palliatief_wens', 'overleg', 'Alleen na overleg met mijn naasten en arts', null, $data['palliatief_wens']) ?>
    <?= $this->radio('palliatief_wens', 'nee', 'Nee, ik wil bewust blijven', null, $data['palliatief_wens']) ?>
</fieldset>

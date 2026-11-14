<?php

declare(strict_types=1);

namespace WV;

/** De inhoud van een wilsverklaring: velden, standaardwaarden en het wizardverloop. */
final class Content
{
    public const TOTAL_STEPS = 17;
    public const MAX_FIELD_LENGTH = 20000;

    /** Hoofdstukken (nummer => titel). */
    public const CHAPTER_NAMES = [
        1 => 'Introductie',
        2 => 'Behandelverbod',
        3 => 'Palliatieve sedatie',
        4 => 'Euthanasieverzoek algemeen',
        5 => 'Euthanasieverzoek dementie',
        6 => 'Afsluiting',
    ];

    /** stap => hoofdstuknummer */
    public const STEP_CHAPTER = [
        1 => 1, 2 => 1,
        3 => 2, 4 => 2, 5 => 2,
        6 => 3, 7 => 3,
        8 => 4, 9 => 4, 10 => 4, 11 => 4,
        12 => 5, 13 => 5, 14 => 5,
        15 => 6, 16 => 6, 17 => 6,
    ];

    /** Stappen waarmee een optioneel hoofdstuk wordt in- of uitgeschakeld: stap => [veld, eerstvolgende stap bij overslaan] */
    public const GATES = [
        8 => ['euthanasie_opgenomen', 12],
        12 => ['dementie_opgenomen', 15],
    ];

    public const TITLES = [
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
        12 => 'Euthanasie bij dementie',
        13 => 'Mijn grens bij dementie',
        14 => 'Mijn naasten en mijn huisarts',
        15 => 'Orgaandonatie en uitvaart',
        16 => 'Arts, volmacht en ondertekening',
        17 => 'Overzicht en publicatie',
    ];

    /** Korte omschrijving per stap, voor het uitlegscherm vóór een onderwerp. */
    public const STEP_SUMMARIES = [
        1 => 'Uw gegevens, en in uw eigen woorden wie u bent.',
        2 => 'Uw eigen grens: wanneer het leven voor u niet meer de moeite waard is.',
        3 => 'Wat er moet gebeuren als uw hart stopt.',
        4 => 'Als u niet meer zelf kunt ademen of zeer ernstig ziek bent.',
        5 => 'Behandeling van infecties, en voeding via een slangetje.',
        6 => 'Of u palliatieve sedatie wilt.',
        7 => 'Wat u ondraaglijk vindt, en hoe diep de sedatie mag zijn.',
        8 => 'Of u een euthanasieverzoek wilt opnemen.',
        9 => 'Wat u ondraaglijk vindt, en in welke situatie.',
        10 => 'Of uw verzoek ook geldt als u zelf niet meer kunt vragen.',
        11 => 'Een persoonlijke brief aan uw arts.',
        12 => 'Of u een apart verzoek bij dementie wilt opnemen.',
        13 => 'Wanneer u bij dementie uw leven niet meer wilt verlengen.',
        14 => 'Wat uw naasten en uw huisarts moeten weten.',
        15 => 'Uw wensen na uw overlijden.',
        16 => 'Wie namens u spreekt, en de ondertekening.',
        17 => 'Alles nakijken en publiceren.',
    ];

    /**
     * Uitlegscherm vóór elk onderwerp: titel, inleiding en vier korte uitlegblokken [kop, tekst].
     * In eigen woorden samengevat uit de toelichting bij de stappen (NVVE-brochure).
     */
    public const CHAPTER_INTROS = [
        1 => [
            'title' => 'Over u',
            'lead' => 'In dit deel stelt u zich voor: wie u bent, wat uw leven waarde geeft en wanneer het voor u genoeg is. Dat is de basis van uw verklaring.',
            'cards' => [
                ['Waarom over uzelf vertellen?', 'Uw arts en naasten begrijpen uw keuzes beter als ze weten wie u bent en wat u belangrijk vindt.'],
                ['Uw gegevens', 'Met uw naam, geboortedatum en geboorteplaats is de verklaring eenduidig van u. Voor een euthanasieverzoek zijn ze verplicht.'],
                ['In uw eigen woorden', 'Er zijn geen goede of foute antwoorden. Schrijf zoals u praat: uw eigen woorden wegen het zwaarst.'],
                ['Is het rechtsgeldig?', 'Ja. U hoeft niet naar de notaris. Een verklaring met datum en uw handtekening is geldig.'],
            ],
        ],
        2 => [
            'title' => 'Behandelingen die u niet meer wilt',
            'lead' => 'In dit deel legt u vast welke behandelingen u niet meer wilt krijgen, ook als ze uw leven kunnen verlengen. Dit heet een behandelverbod.',
            'cards' => [
                ['Wanneer geldt het?', 'Op het moment dat u zelf niet meer kunt zeggen wat u wilt. Bijvoorbeeld als u bewusteloos bent of ernstig in de war.'],
                ['Waarom is het belangrijk?', 'Zonder verklaring doen artsen in een noodsituatie meestal alles om uw leven te redden. Met een behandelverbod weten zij wat u wilt.'],
                ['Moet een arts zich eraan houden?', 'Ja. Een arts heeft uw toestemming nodig voor een behandeling. Alleen als daar gegronde redenen voor zijn, mag een arts van uw behandelverbod afwijken.'],
                ['U blijft goede zorg krijgen', 'Ook als u een behandeling weigert, krijgt u palliatieve zorg: behandelingen die pijn en andere klachten verlichten.'],
            ],
        ],
        3 => [
            'title' => 'Palliatieve sedatie',
            'lead' => 'In dit deel legt u vast wat u wilt als u in uw laatste levensfase ondraaglijk lijdt en uw klachten niet meer op een andere manier te verlichten zijn.',
            'cards' => [
                ['Wat is het?', 'U krijgt medicijnen die u rustig en minder bewust maken, zodat u geen last meer heeft van uw klachten. Het overlijden maakt u niet meer bewust mee.'],
                ['Wanneer kan het?', 'Als u klachten heeft die niet te behandelen zijn, u ondraaglijk lijdt en uw arts verwacht dat u binnen twee weken overlijdt.'],
                ['Wie beslist?', 'Uw arts beslist of sedatie medisch past en bespreekt dat met u. Kan dat niet meer, dan met uw naasten of uw vertegenwoordiger.'],
                ['Is het hetzelfde als euthanasie?', 'Nee. Bij sedatie slaapt u en overlijdt u aan uw ziekte, meestal binnen uren tot dagen. Bij euthanasie beëindigt een arts op uw verzoek uw leven.'],
            ],
        ],
        4 => [
            'title' => 'Een euthanasieverzoek',
            'lead' => 'In dit deel kunt u een schriftelijk euthanasieverzoek opnemen. Dat is een persoonlijke keuze die niet bij iedereen past: u kunt dit deel ook overslaan.',
            'cards' => [
                ['Wat is euthanasie?', 'Een zelfgekozen levenseinde met hulp van een arts, op uw eigen verzoek. Alleen een arts mag het uitvoeren, volgens strikte regels in de wet.'],
                ['Wanneer kan het?', 'Als u ondraaglijk en uitzichtloos lijdt door een medische oorzaak, en u en uw arts geen redelijke andere oplossing zien.'],
                ['Is het een recht?', 'Nee. Uw arts is niet verplicht om euthanasie te verlenen, ook niet als u het schriftelijk heeft gevraagd. Bespreek het daarom op tijd.'],
                ['Waarom op papier?', 'Kunt u later zelf niet meer vragen, dan kan uw schriftelijke verzoek in de plaats komen van een mondeling verzoek.'],
            ],
        ],
        5 => [
            'title' => 'Euthanasie bij dementie',
            'lead' => 'Dementie heeft een eigen verloop. Daarom kunt u hier een apart euthanasieverzoek bij dementie opnemen. Ook dit deel kunt u overslaan.',
            'cards' => [
                ['Waarom apart?', 'Bij dementie komt er een moment dat u niet meer zelf om euthanasie kunt vragen. Dan is uw schriftelijke verzoek extra belangrijk.'],
                ['Geen garantie', 'Ook met een verklaring moet de arts ervan overtuigd zijn dat u ondraaglijk lijdt. Bij gevorderde dementie komt euthanasie maar zelden voor.'],
                ['Op tijd beginnen', 'Bespreek uw wens zo vroeg mogelijk met uw huisarts, zolang u zelf nog goed kunt beslissen. Bevestig hem daarna regelmatig.'],
                ['Betrek uw naasten', 'Zij zien als eerste dat het slechter gaat, en uw arts betrekt hen bij de beoordeling. Zij moeten dus weten wat u wilt.'],
            ],
        ],
        6 => [
            'title' => 'Afronden',
            'lead' => 'U bent bijna klaar. U regelt nog een paar praktische zaken en kijkt alles na voordat u uw verklaring publiceert.',
            'cards' => [
                ['Orgaandonatie en uitvaart', 'U kunt hier uw wensen vastleggen. Voor orgaandonatie telt uw keuze in het Donorregister.'],
                ['Een gevolmachtigde', 'Iemand die u vertrouwt en die namens u met artsen praat als u dat zelf niet meer kunt.'],
                ['Ondertekenen', 'Print uw verklaring na het publiceren en zet er de datum en uw handtekening onder.'],
                ['Delen en bijhouden', 'Uw huisarts en naasten lezen altijd de nieuwste versie. Kijk uw verklaring af en toe na en pas hem aan als uw wensen veranderen.'],
            ],
        ],
    ];

    /** "Wat betekent deze keuze?": korte uitleg per antwoord, sleutel "veld.waarde". */
    public const MEANINGS = [
        'reanimatie.ja' => 'Hulpverleners proberen u te reanimeren als uw hart stopt. Dat gebeurt ook als u niets vastlegt.',
        'reanimatie.nee' => 'Hulpverleners reanimeren u niet. Draag ook een niet-reanimerenpenning en vertel het uw huisarts en naasten: bij een hartstilstand is er geen tijd om deze verklaring op te zoeken.',
        'reanimatie.voorwaarden' => 'Beschrijf in de toelichting wanneer wel en wanneer niet. Bij een hartstilstand moeten hulpverleners binnen enkele minuten beslissen. Bespreek uw voorwaarden daarom ook met uw huisarts.',
        'beademing.ja' => 'Als u niet meer zelf kunt ademen, mag een machine uw ademhaling overnemen, zo nodig op de intensive care.',
        'beademing.nee' => 'Artsen beademen u niet kunstmatig, ook niet als u daardoor kunt overlijden. Benauwdheid kan wel met medicijnen worden verlicht.',
        'beademing.kort' => 'Artsen mogen u tijdelijk beademen als zij verwachten dat u herstelt. Is herstel niet meer te verwachten, dan stoppen ze ermee.',
        'ic.ja' => 'U kunt op de intensive care worden opgenomen als artsen denken dat het u kan helpen.',
        'ic.nee' => 'U wordt niet op de intensive care opgenomen. U krijgt wel zorg die pijn en andere klachten verlicht.',
        'ic.twijfel' => 'Artsen wegen af hoe groot de kans is dat u herstelt tot een leven dat voor u de moeite waard is. Wat u bij "Wanneer is het genoeg" schreef, helpt hen daarbij.',
        'antibiotica.ja' => 'Een ernstige infectie wordt altijd met antibiotica behandeld, ook als u al ernstig ziek bent.',
        'antibiotica.nee' => 'Bent u al ernstig ziek, dan krijgt u geen antibiotica tegen bijvoorbeeld een longontsteking. U kunt daaraan overlijden. Klachten zoals koorts en benauwdheid worden wel verlicht.',
        'antibiotica.afwegen' => 'Uw arts beslist samen met uw naasten of antibiotica op dat moment zinvol zijn. Het helpt als u in de toelichting beschrijft wat voor u belangrijk is.',
        'sonde.ja' => 'Kunt u niet meer eten of drinken, dan krijgt u voeding via een slangetje, zolang artsen dat zinvol vinden.',
        'sonde.nee' => 'U krijgt geen voeding via een slangetje. Kunt u niet meer eten en drinken, dan overlijdt u na verloop van tijd. U krijgt wel goede verzorging, zoals mondverzorging.',
        'sonde.kort' => 'Sondevoeding mag tijdelijk, bijvoorbeeld om te herstellen van een ziekte. Is herstel niet meer te verwachten, dan wordt ermee gestopt.',
        'palliatief_wens.ja' => 'Als u stervende bent en ondraaglijk lijdt, mag uw arts sedatie starten, ook als u daar zelf niet meer om kunt vragen. Uw arts beoordeelt of het medisch past.',
        'palliatief_wens.overleg' => 'Uw arts start sedatie pas na overleg met uw naasten. Leg in stap 16 vast wie namens u mag spreken.',
        'palliatief_wens.nee' => 'U krijgt geen palliatieve sedatie, ook niet als uw klachten niet op een andere manier te verlichten zijn. Uw arts blijft uw klachten wel zo goed mogelijk behandelen.',
        'palliatief_diepte.licht' => 'U wordt rustiger en suffer, maar u kunt nog reageren op de mensen om u heen. Het kan zijn dat u dan nog iets van uw klachten merkt.',
        'palliatief_diepte.diep' => 'U slaapt diep en merkt niets meer van uw klachten. U kunt dan niet meer praten met de mensen om u heen.',
        'palliatief_diepte.arts' => 'Uw arts kiest de diepte die nodig is om uw klachten te verlichten.',
        'euthanasie_opgenomen.ja' => 'U vult in de volgende stappen uw euthanasieverzoek in. U kunt het later altijd aanpassen of weer weghalen.',
        'euthanasie_opgenomen.nee' => 'Dit deel komt niet in uw verklaring. U kunt er later altijd nog op terugkomen.',
        'euthanasie_wilsonbekwaam.ja' => 'Uw schriftelijke verzoek kan dan in de plaats komen van een mondeling verzoek. Uw arts moet er wel van overtuigd zijn dat u ondraaglijk lijdt, en is niet verplicht het uit te voeren.',
        'euthanasie_wilsonbekwaam.nee' => 'Uw verzoek geldt alleen zolang u het zelf kunt bevestigen. Kunt u dat niet meer, dan wordt er op grond van deze verklaring geen euthanasie verleend.',
        'euthanasie_wilsonbekwaam.voorwaarden' => 'Beschrijf in de toelichting zo concreet mogelijk wanneer uw verzoek wel en niet moet gelden.',
        'dementie_opgenomen.ja' => 'U vult in de volgende stappen uw verzoek bij dementie in. U kunt het later altijd aanpassen of weer weghalen.',
        'dementie_opgenomen.nee' => 'Dit deel komt niet in uw verklaring. U kunt er later altijd nog op terugkomen.',
        'dementie_moment.vroeg' => 'U kunt het dan zelf nog vragen en bevestigen. In deze fase is euthanasie bij dementie het best mogelijk.',
        'dementie_moment.omslag' => 'Wacht niet te lang: kunt u uw wens niet meer zelf bevestigen, dan moet uw arts op deze verklaring afgaan. Vraag uw naasten u te waarschuwen als dat moment dichtbij komt.',
        'dementie_moment.later' => 'Uw arts kan alleen handelen als die ervan overtuigd is dat u op dat moment ondraaglijk lijdt. Dat is moeilijk vast te stellen, en daarom komt het maar zelden voor.',
        'dementie_moment.bespreken' => 'Maak een afspraak met uw huisarts. Daarna kunt u deze keuze hier aanpassen.',
        'dementie_huisarts.ja' => 'Bespreek uw wens regelmatig opnieuw met uw huisarts, vooral als uw gezondheid verandert.',
        'orgaandonatie.ja' => 'Wat telt, is uw registratie in het Donorregister. Controleer dat die hetzelfde zegt als wat u hier invult.',
        'orgaandonatie.nee' => 'Wat telt, is uw registratie in het Donorregister. Controleer dat daar ook "nee" staat.',
        'orgaandonatie.register' => 'Uw keuze in het Donorregister geldt. Heeft u daar niets ingevuld, dan staat u geregistreerd met "geen bezwaar" en kunt u donor worden.',
        'besproken.ja' => 'Geef uw huisarts na het publiceren ook toegang, zodat die altijd de nieuwste versie leest.',
        'besproken.binnenkort' => 'Bespreek het als het kan voordat u publiceert. Zo weet uw huisarts wat u bedoelt en waarom.',
        'besproken.nee' => 'Uw verklaring geldt ook zonder gesprek, maar een arts kan uw wensen beter volgen als u ze samen heeft besproken.',
    ];

    /** Eerste stap van een hoofdstuk. */
    public static function firstStepOfChapter(int $chapter): int
    {
        foreach (self::STEP_CHAPTER as $step => $ch) {
            if ($ch === $chapter) {
                return $step;
            }
        }
        return 1;
    }

    /** @return list<int> de stappen van een hoofdstuk */
    public static function chapterSteps(int $chapter): array
    {
        return array_keys(array_filter(self::STEP_CHAPTER, fn($ch) => $ch === $chapter));
    }

    /** Is in deze stap al iets ingevuld? (Stappen zonder velden tellen als ingevuld.) */
    public static function stepTouched(int $step, array $data): bool
    {
        $fields = self::STEP_FIELDS[$step] ?? [];
        if (!$fields) {
            return true;
        }
        foreach ($fields as $f) {
            $v = $data[$f] ?? '';
            if ($v === true || (is_string($v) && trim($v) !== '')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Voortgang per hoofdstuk voor het pad op het dashboard.
     * @return array<int, array{status: string, next: int}> status: klaar|bezig|nieuw|overgeslagen; next: stap om verder te gaan
     */
    public static function progress(array $data): array
    {
        $out = [];
        foreach (self::CHAPTER_NAMES as $ch => $name) {
            $steps = self::chapterSteps($ch);
            $gate = $steps[0];
            if (isset(self::GATES[$gate]) && ($data[self::GATES[$gate][0]] ?? '') === 'nee') {
                $out[$ch] = ['status' => 'overgeslagen', 'next' => $gate];
                continue;
            }
            $touched = array_values(array_filter($steps, fn($s) => ($s !== self::TOTAL_STEPS) && self::stepTouched($s, $data)));
            $open = array_values(array_filter($steps, fn($s) => $s !== self::TOTAL_STEPS && !self::stepTouched($s, $data)));
            if (isset(self::GATES[$gate]) && !self::gateOpen($gate, $data)) {
                $out[$ch] = ['status' => 'nieuw', 'next' => $gate];
            } elseif (!$open) {
                $out[$ch] = ['status' => 'klaar', 'next' => $steps[0]];
            } elseif (!$touched) {
                $out[$ch] = ['status' => 'nieuw', 'next' => $steps[0]];
            } else {
                $out[$ch] = ['status' => 'bezig', 'next' => $open[0]];
            }
        }
        return $out;
    }

    /** Welke velden op welke stap worden ingevuld. */
    public const STEP_FIELDS = [
        1 => ['voornamen', 'achternaam', 'geboortenaam', 'geboortedatum', 'geboorteplaats', 'intro', 'waarde'],
        2 => ['grens', 'ervaring'],
        3 => ['reanimatie', 'reanimatie_toelichting'],
        4 => ['beademing', 'ic', 'beademing_toelichting'],
        5 => ['antibiotica', 'sonde', 'behandel_toelichting'],
        6 => ['palliatief_wens'],
        7 => ['palliatief_fysiek', 'palliatief_geestelijk', 'palliatief_diepte'],
        8 => ['euthanasie_opgenomen'],
        9 => ['euthanasie_lijden', 'euthanasie_situatie'],
        10 => ['euthanasie_wilsonbekwaam', 'euthanasie_wilsonbekwaam_toelichting'],
        11 => ['euthanasie_brief'],
        12 => ['dementie_opgenomen'],
        13 => ['dementie_lijden', 'dementie_moment'],
        14 => ['dementie_naasten', 'dementie_huisarts'],
        15 => ['orgaandonatie', 'uitvaart', 'overig'],
        16 => ['volmacht', 'volmacht_contact', 'arts', 'besproken', 'datum', 'plaats'],
        17 => [],
    ];

    /** Velden met een vaste lijst toegestane waarden (keuzerondjes). */
    public const CHOICES = [
        'reanimatie' => ['ja', 'nee', 'voorwaarden'],
        'beademing' => ['ja', 'nee', 'kort'],
        'ic' => ['ja', 'nee', 'twijfel'],
        'antibiotica' => ['ja', 'nee', 'afwegen'],
        'sonde' => ['ja', 'nee', 'kort'],
        'palliatief_wens' => ['ja', 'overleg', 'nee'],
        'palliatief_diepte' => ['licht', 'diep', 'arts'],
        'euthanasie_opgenomen' => ['ja', 'nee'],
        'dementie_opgenomen' => ['ja', 'nee'],
        'dementie_moment' => ['vroeg', 'omslag', 'later', 'bespreken'],
        'dementie_huisarts' => ['ja', 'nee', 'onbekend'],
        'euthanasie_wilsonbekwaam' => ['ja', 'nee', 'voorwaarden'],
        'orgaandonatie' => ['ja', 'nee', 'register'],
        'besproken' => ['ja', 'binnenkort', 'nee'],
    ];

    /** Leesbare tekst bij een gekozen waarde (voor overzicht en publieke weergave). */
    public static function choiceLabel(string $value): string
    {
        return [
            'ja' => 'Ja',
            'nee' => 'Nee',
            'voorwaarden' => 'Alleen onder voorwaarden',
            'kort' => 'Alleen kortdurend',
            'twijfel' => 'Alleen bij goede kans op herstel',
            'afwegen' => 'Na afweging met arts en naasten',
            'overleg' => 'Na overleg met naasten en arts',
            'licht' => 'Lichte sedatie',
            'diep' => 'Diepe sedatie',
            'arts' => 'Laat ik aan de arts over',
            'register' => 'Zie het Donorregister',
            'vroeg' => 'Zodra ik duidelijk ondraaglijk lijd',
            'omslag' => 'Uiterlijk in de omslagzone',
            'later' => 'Ook bij gevorderde dementie, op basis van deze verklaring',
            'bespreken' => 'Eerst te bespreken met mijn huisarts',
            'onbekend' => 'Weet ik nog niet',
            'binnenkort' => 'Nog niet; afspraak wordt gepland',
        ][$value] ?? ucfirst_mb($value);
    }

    public static function defaults(): array
    {
        return [
            'voornamen' => '',
            'achternaam' => '',
            'geboortenaam' => '',
            'geboortedatum' => '',
            'geboorteplaats' => '',
            'intro' => '',
            'waarde' => '',
            'grens' => '',
            'ervaring' => '',
            'reanimatie' => '',
            'reanimatie_toelichting' => '',
            'beademing' => '',
            'ic' => '',
            'beademing_toelichting' => '',
            'antibiotica' => '',
            'sonde' => '',
            'behandel_toelichting' => '',
            'palliatief_wens' => '',
            'palliatief_fysiek' => '',
            'palliatief_geestelijk' => '',
            'palliatief_diepte' => '',
            'euthanasie_opgenomen' => false,
            'euthanasie_lijden' => '',
            'euthanasie_situatie' => '',
            'euthanasie_wilsonbekwaam' => '',
            'euthanasie_wilsonbekwaam_toelichting' => '',
            'euthanasie_brief' => '',
            'dementie_opgenomen' => false,
            'dementie_lijden' => '',
            'dementie_moment' => '',
            'dementie_naasten' => '',
            'dementie_huisarts' => '',
            'orgaandonatie' => '',
            'uitvaart' => '',
            'overig' => '',
            'volmacht' => '',
            'volmacht_contact' => '',
            'arts' => '',
            'besproken' => '',
            'datum' => '',
            'plaats' => '',
        ];
    }

    /** Vult ontbrekende velden aan en gooit onbekende sleutels weg. */
    public static function normalize(array $content): array
    {
        $defaults = self::defaults();
        return array_replace($defaults, array_intersect_key($content, $defaults));
    }

    /** Volledige naam uit de verklaring, met de accountnaam als terugval. */
    public static function fullName(array $doc, string $fallback = ''): string
    {
        $name = trim(trim((string) ($doc['voornamen'] ?? '')) . ' ' . trim((string) ($doc['achternaam'] ?? '')));
        return $name !== '' ? $name : $fallback;
    }

    /** "Geboren op 07-03-1950 te Utrecht (geboortenaam: Jansen)" of een lege tekst. */
    public static function birthLine(array $doc): string
    {
        $parts = [];
        $date = trim((string) ($doc['geboortedatum'] ?? ''));
        $place = trim((string) ($doc['geboorteplaats'] ?? ''));
        if ($date !== '') {
            $parts[] = 'Geboren op ' . fmt_iso_date($date) . ($place !== '' ? ' te ' . $place : '');
        } elseif ($place !== '') {
            $parts[] = 'Geboren te ' . $place;
        }
        $birthName = trim((string) ($doc['geboortenaam'] ?? ''));
        if ($birthName !== '') {
            $parts[] = 'geboortenaam: ' . $birthName;
        }
        return implode(' · ', $parts);
    }

    /**
     * Wat er nog ontbreekt om te mogen publiceren. Bevat de verklaring een euthanasiehoofdstuk, dan zijn
     * naam, geboortedatum, geboorteplaats, datum en plaats van ondertekening verplicht (identificatie en dagtekening).
     * @return list<string>
     */
    public static function missingForPublish(array $doc): array
    {
        if (!self::euthanasieIncluded($doc) && !self::dementieIncluded($doc)) {
            return [];
        }
        $need = [
            'voornamen' => 'uw voornamen (stap 1)',
            'achternaam' => 'uw achternaam (stap 1)',
            'geboortedatum' => 'uw geboortedatum (stap 1)',
            'geboorteplaats' => 'uw geboorteplaats (stap 1)',
            'datum' => 'de datum van ondertekening (stap 16)',
            'plaats' => 'de plaats van ondertekening (stap 16)',
        ];
        $missing = [];
        foreach ($need as $key => $label) {
            if (trim((string) ($doc[$key] ?? '')) === '') {
                $missing[] = 'Vul ' . $label . ' in. Dat is nodig voor een euthanasieverzoek.';
            }
        }
        return $missing;
    }

    public static function euthanasieIncluded(array $data): bool
    {
        $v = $data['euthanasie_opgenomen'] ?? false;
        return $v === 'ja' || $v === true;
    }

    public static function dementieIncluded(array $data): bool
    {
        $v = $data['dementie_opgenomen'] ?? false;
        return $v === 'ja' || $v === true;
    }

    /** Is het optionele hoofdstuk achter deze poortstap ingeschakeld? */
    private static function gateOpen(int $step, array $data): bool
    {
        return match ($step) {
            8 => self::euthanasieIncluded($data),
            12 => self::dementieIncluded($data),
            default => true,
        };
    }

    public static function next(int $step, array $data): int
    {
        if (isset(self::GATES[$step]) && !self::gateOpen($step, $data)) {
            return self::GATES[$step][1];
        }
        return min($step + 1, self::TOTAL_STEPS);
    }

    public static function previous(int $step, array $data): int
    {
        // terug vanaf de eerste stap na een uitgeschakeld hoofdstuk: naar de poortstap ervoor
        foreach (self::GATES as $gate => [, $after]) {
            if ($step === $after && !self::gateOpen($gate, $data)) {
                return $gate;
            }
        }
        return max($step - 1, 1);
    }

    /**
     * Voegt de ingezonden velden van één stap samen met de bestaande gegevens.
     * Alleen velden van die stap worden aangepast; keuzevelden worden gecontroleerd.
     */
    public static function mergeStep(int $step, array $posted, array $data): array
    {
        foreach (self::STEP_FIELDS[$step] ?? [] as $field) {
            if (!array_key_exists($field, $posted) || !is_string($posted[$field])) {
                continue;
            }
            $value = str_replace(["\r\n", "\r", "\0"], ["\n", "\n", ''], $posted[$field]);
            $value = mb_substr($value, 0, self::MAX_FIELD_LENGTH);

            if (isset(self::CHOICES[$field])) {
                if (!in_array($value, self::CHOICES[$field], true)) {
                    continue;
                }
            } elseif ($field === 'datum' || $field === 'geboortedatum') {
                $value = trim($value);
                if ($value !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    continue;
                }
            }
            $data[$field] = $value;
        }
        return $data;
    }
}

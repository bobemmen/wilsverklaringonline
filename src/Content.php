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

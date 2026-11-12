<?php

declare(strict_types=1);

namespace WV;

/** De inhoud van een wilsverklaring: velden, standaardwaarden en het wizardverloop. */
final class Content
{
    public const TOTAL_STEPS = 14;
    public const MAX_FIELD_LENGTH = 20000;

    /** stap => [hoofdstuktitel, hoofdstuknummer] */
    public const CHAPTERS = [
        1 => ['Introductie', 1],
        2 => ['Introductie', 1],
        3 => ['Behandelverbod', 2],
        4 => ['Behandelverbod', 2],
        5 => ['Behandelverbod', 2],
        6 => ['Palliatieve sedatie', 3],
        7 => ['Palliatieve sedatie', 3],
        8 => ['Euthanasieverzoek', 4],
        9 => ['Euthanasieverzoek', 4],
        10 => ['Euthanasieverzoek', 4],
        11 => ['Euthanasieverzoek', 4],
        12 => ['Afsluiting', 5],
        13 => ['Afsluiting', 5],
        14 => ['Afsluiting', 5],
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
        12 => 'Orgaandonatie en uitvaart',
        13 => 'Arts en ondertekening',
        14 => 'Overzicht en publicatie',
    ];

    /** Welke velden op welke stap worden ingevuld. */
    public const STEP_FIELDS = [
        1 => ['intro', 'waarde'],
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
        12 => ['orgaandonatie', 'uitvaart', 'overig'],
        13 => ['arts', 'besproken', 'datum'],
        14 => [],
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
            'binnenkort' => 'Nog niet; afspraak wordt gepland',
        ][$value] ?? ucfirst_mb($value);
    }

    public static function defaults(): array
    {
        return [
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
            'orgaandonatie' => '',
            'uitvaart' => '',
            'overig' => '',
            'arts' => '',
            'besproken' => '',
            'datum' => '',
        ];
    }

    /** Vult ontbrekende velden aan en gooit onbekende sleutels weg. */
    public static function normalize(array $content): array
    {
        $defaults = self::defaults();
        return array_replace($defaults, array_intersect_key($content, $defaults));
    }

    public static function euthanasieIncluded(array $data): bool
    {
        $v = $data['euthanasie_opgenomen'] ?? false;
        return $v === 'ja' || $v === true;
    }

    public static function next(int $step, array $data): int
    {
        if ($step === 8 && !self::euthanasieIncluded($data)) {
            return 12;
        }
        return min($step + 1, self::TOTAL_STEPS);
    }

    public static function previous(int $step, array $data): int
    {
        if ($step === 12 && !self::euthanasieIncluded($data)) {
            return 8;
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
            } elseif ($field === 'datum') {
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

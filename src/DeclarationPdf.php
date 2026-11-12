<?php

declare(strict_types=1);

namespace WV;

/** Zet een verklaring om in een afdrukbare PDF (zelfde indeling als de online weergave). */
final class DeclarationPdf
{
    public static function build(string $name, ?string $versionDate, array $c, bool $tokenActive, float $scale = 1.0, ?array $verification = null): string
    {
        $pdf = new Pdf('Wilsverklaring ' . $name);
        $pdf->scale = $scale;
        $filled = fn(string ...$keys) => array_filter($keys, fn($k) => trim((string) ($c[$k] ?? '')) !== '');

        $pdf->title('Wilsverklaring');
        $pdf->subtitle($name);
        $pdf->subtitle($versionDate ? 'Versie van ' . $versionDate : 'Concept');
        $pdf->space(6);
        $pdf->hr();

        if ($filled('intro', 'waarde')) {
            $pdf->heading('Over mij');
            if ($filled('intro')) {
                $pdf->field('Wie ik ben', $c['intro']);
            }
            if ($filled('waarde')) {
                $pdf->field('Wat mijn leven waarde geeft', $c['waarde']);
            }
        }

        if ($filled('grens', 'ervaring')) {
            $pdf->heading('Mijn grens');
            if ($filled('grens')) {
                $pdf->field('Grens', $c['grens']);
            }
            if ($filled('ervaring')) {
                $pdf->field('Ervaringen', $c['ervaring']);
            }
        }

        $pdf->heading('Behandelverbod');
        foreach (['reanimatie' => 'Reanimatie', 'beademing' => 'Beademing', 'ic' => 'Intensive care', 'antibiotica' => 'Antibiotica', 'sonde' => 'Sondevoeding'] as $k => $l) {
            if ($filled($k)) {
                $pdf->choice($l, $c[$k]);
            }
        }
        foreach (['reanimatie_toelichting', 'beademing_toelichting', 'behandel_toelichting'] as $k) {
            if ($filled($k)) {
                $pdf->quote($c[$k]);
            }
        }

        if ($filled('palliatief_wens')) {
            $pdf->heading('Palliatieve sedatie');
            $pdf->choice('Wens', $c['palliatief_wens']);
            if ($filled('palliatief_fysiek')) {
                $pdf->field('Fysiek lijden', $c['palliatief_fysiek']);
            }
            if ($filled('palliatief_geestelijk')) {
                $pdf->field('Geestelijk lijden', $c['palliatief_geestelijk']);
            }
            if ($filled('palliatief_diepte')) {
                $pdf->choice('Diepte', $c['palliatief_diepte']);
            }
        }

        if (Content::euthanasieIncluded($c)) {
            $pdf->heading('Euthanasieverzoek');
            if ($filled('euthanasie_lijden')) {
                $pdf->field('Ondraaglijk lijden', $c['euthanasie_lijden']);
            }
            if ($filled('euthanasie_situatie')) {
                $pdf->field('Situatie', $c['euthanasie_situatie']);
            }
            if ($filled('euthanasie_wilsonbekwaam')) {
                $pdf->choice('Als ik zelf niet meer kan vragen', $c['euthanasie_wilsonbekwaam']);
            }
            if ($filled('euthanasie_wilsonbekwaam_toelichting')) {
                $pdf->quote($c['euthanasie_wilsonbekwaam_toelichting']);
            }
            if ($filled('euthanasie_brief')) {
                $pdf->subheading('Brief aan de arts');
                $pdf->quote($c['euthanasie_brief']);
            }
        }

        if (Content::dementieIncluded($c)) {
            $pdf->heading('Euthanasieverzoek bij dementie');
            if ($filled('dementie_lijden')) {
                $pdf->field('Situatie waarin ik mijn leven niet meer wil verlengen', $c['dementie_lijden']);
            }
            if ($filled('dementie_moment')) {
                $pdf->choice('Vanaf welk moment', $c['dementie_moment']);
            }
            if ($filled('dementie_naasten')) {
                $pdf->field('Wat mijn naasten moeten weten of doen', $c['dementie_naasten']);
            }
            if ($filled('dementie_huisarts')) {
                $pdf->choice('Huisarts open voor euthanasie bij dementie', $c['dementie_huisarts']);
            }
        }

        if ($filled('volmacht')) {
            $pdf->heading('Medische volmacht');
            $pdf->field('Mag namens mij medische beslissingen nemen', trim($c['volmacht'] . ($filled('volmacht_contact') ? ' — ' . $c['volmacht_contact'] : '')));
        }

        if ($filled('orgaandonatie', 'uitvaart', 'overig')) {
            $pdf->heading('Na het overlijden');
            if ($filled('orgaandonatie')) {
                $pdf->choice('Orgaandonatie', $c['orgaandonatie']);
            }
            if ($filled('uitvaart')) {
                $pdf->field('Uitvaart', $c['uitvaart']);
            }
            if ($filled('overig')) {
                $pdf->field('Overig', $c['overig']);
            }
        }

        $pdf->signature($name, fmt_iso_date((string) $c['datum']));

        if ($verification !== null) {
            $who = $verification['arts_name'] . (!empty($verification['practice']) ? ', ' . $verification['practice'] : '');
            $pdf->noteBox(
                'Bevestigd door huisarts',
                "Op " . fmt_date($verification['verified_at']) . " heeft $who bevestigd dat deze patiënt in persoon is gezien en dat de identiteit is gecontroleerd. "
                . ($verification['his_masked'] !== '' ? 'Patiënt-ID in het HIS: ' . $verification['his_masked'] . '. ' : '')
                . ($verification['current'] ? '' : 'Let op: de verklaring is na die bevestiging gewijzigd.')
            );
        }

        if ($tokenActive) {
            $pdf->noteBox(
                'Online raadplegen door arts',
                'Vraag de patiënt om de persoonlijke link. De actuele versie is altijd online beschikbaar.'
            );
        }

        return $pdf->output();
    }
}

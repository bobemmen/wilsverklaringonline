<?php

declare(strict_types=1);

namespace WV;

/** Zet een verklaring om in een afdrukbare PDF: zelfde kleuren, letter, teksten en volgorde als de online weergave (templates/public/_body.php). */
final class DeclarationPdf
{
    public static function build(string $name, ?string $versionDate, array $c, bool $tokenActive, float $scale = 1.0, ?array $verification = null, ?int $versionNumber = null): string
    {
        $pdf = new Pdf('Wilsverklaring ' . $name);
        $pdf->scale = $scale;
        $filled = fn(string ...$keys) => array_filter($keys, fn($k) => trim((string) ($c[$k] ?? '')) !== '');

        $name = Content::fullName($c, $name);
        $pdf->eyebrow('Wilsverklaring van');
        $pdf->title($name);
        $pdf->space(2);
        if (($birth = Content::birthLine($c)) !== '') {
            $pdf->subtitle($birth);
        }
        $meta = $versionNumber !== null ? 'Versie ' . $versionNumber : 'Concept';
        if ($versionDate) {
            $meta .= ' · ' . $versionDate;
        }
        if ($filled('datum')) {
            $meta .= ' · ondertekend op ' . fmt_iso_date((string) $c['datum']);
        }
        $pdf->subtitle($meta);
        $pdf->space(10);

        if ($verification !== null) {
            $who = $verification['arts_name'] . (!empty($verification['practice']) ? ', ' . $verification['practice'] : '');
            $pdf->noteBox(
                'Identiteit bevestigd door de huisarts',
                $who . ' heeft op ' . fmt_date($verification['verified_at']) . ' bevestigd dat dit account bij deze patiënt hoort en dat de patiënt in persoon is gezien en geïdentificeerd.'
                . ($verification['his_masked'] !== '' ? ' Patiënt-ID in het HIS: ' . $verification['his_masked'] . '.' : ''),
                true
            );
        }

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
                $pdf->field('Wat ik ondraaglijk vind', $c['euthanasie_lijden']);
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
            $pdf->text('Namens mij mag beslissen over mijn medische behandeling, als ik dat zelf niet meer kan:', 'L', 10, Pdf::TEXT, 0, 'L', 0.0, 1.5);
            $pdf->text($c['volmacht'] . ($filled('volmacht_contact') ? "\n" . $c['volmacht_contact'] : ''), 'LB', 10, Pdf::TEXT, 0, 'L', 0.0, 1.5);
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

        $pdf->noteBox(
            'Verklaring van de ondertekenaar',
            'Ik heb deze verklaring zelf en uit vrije wil opgesteld. Ik bevestig haar regelmatig met een nieuwe datum en handtekening.'
        );
        $pdf->signature($name, fmt_iso_date((string) $c['datum']), (string) ($c['plaats'] ?? ''));

        if ($tokenActive) {
            $pdf->noteBox(
                'Online raadplegen door arts',
                'Vraag de patiënt om de persoonlijke link. De actuele versie is altijd online beschikbaar.'
            );
        }

        return $pdf->output();
    }
}

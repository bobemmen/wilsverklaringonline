<?php

namespace App\Support;

class DeclarationContent
{
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

    public static function normalize(array $content): array
    {
        return array_merge(self::defaults(), $content);
    }
}

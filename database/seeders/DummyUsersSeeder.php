<?php

namespace Database\Seeders;

use App\Models\AccessLog;
use App\Models\Declaration;
use App\Models\DeclarationShare;
use App\Models\DeclarationVersion;
use App\Models\User;
use App\Support\DeclarationContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DummyUsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Anna de Vries',
                'email' => 'anna.devries@voorbeeld.nl',
                'profile' => 'published-active',
            ],
            [
                'name' => 'Jan Bakker',
                'email' => 'jan.bakker@voorbeeld.nl',
                'profile' => 'concept',
            ],
            [
                'name' => 'Fatima El Amrani',
                'email' => 'fatima.elamrani@voorbeeld.nl',
                'profile' => 'published-multi',
            ],
            [
                'name' => 'Pieter van den Berg',
                'email' => 'pieter.vandenberg@voorbeeld.nl',
                'profile' => 'published-expired',
            ],
            [
                'name' => 'Sanne Jansen',
                'email' => 'sanne.jansen@voorbeeld.nl',
                'profile' => 'concept-revoked',
            ],
        ];

        foreach ($users as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('demo-wachtwoord'),
                    'email_verified_at' => now()->subDays(rand(30, 400)),
                ]
            );

            $this->seedProfile($user, $data['profile']);
        }

        $this->command?->info('5 dummy-gebruikers aangemaakt of bijgewerkt.');
    }

    private function seedProfile(User $user, string $profile): void
    {
        $user->declarations()->delete();

        $declaration = Declaration::create([
            'user_id' => $user->id,
            'is_published' => in_array($profile, ['published-active', 'published-multi', 'published-expired']),
        ]);

        match ($profile) {
            'published-active' => $this->publishedActive($declaration),
            'concept' => $this->concept($declaration),
            'published-multi' => $this->publishedMulti($declaration),
            'published-expired' => $this->publishedExpired($declaration),
            'concept-revoked' => $this->conceptRevoked($declaration),
        };
    }

    private function publishedActive(Declaration $declaration): void
    {
        $version = $this->createVersion($declaration, $this->sampleContent('volledig'), now()->subDays(12));
        $declaration->update([
            'current_version_id' => $version->id,
            'access_token' => hash('sha256', 'demo-token-anna'),
            'access_token_expires_at' => now()->addDays(18),
            'notification_email' => $declaration->user->email,
        ]);

        DeclarationShare::create([
            'declaration_id' => $declaration->id,
            'email' => 'dochter@voorbeeld.nl',
            'name' => 'Lisa de Vries',
            'role' => 'gemachtigde',
            'share_token' => hash('sha256', Str::random(40)),
            'accepted_at' => now()->subDays(8),
        ]);

        AccessLog::create([
            'declaration_id' => $declaration->id,
            'accessor_type' => 'arts',
            'accessor_identifier' => hash('sha256', 'arts@ziekenhuis.nl'),
            'ip_address' => '145.53.12.44',
            'user_agent' => 'Mozilla/5.0',
            'accessed_at' => now()->subDays(5),
        ]);
    }

    private function concept(Declaration $declaration): void
    {
        $this->createVersion($declaration, $this->sampleContent('beginfase'), now()->subDays(2));
    }

    private function publishedMulti(Declaration $declaration): void
    {
        $this->createVersion($declaration, $this->sampleContent('beginfase'), now()->subMonths(6));
        $this->createVersion($declaration, $this->sampleContent('midden'), now()->subMonths(3));
        $current = $this->createVersion($declaration, $this->sampleContent('volledig'), now()->subWeeks(2));

        $declaration->update([
            'current_version_id' => $current->id,
            'access_token' => hash('sha256', 'demo-token-fatima'),
            'access_token_expires_at' => now()->addDays(25),
            'notification_email' => $declaration->user->email,
        ]);

        DeclarationShare::create([
            'declaration_id' => $declaration->id,
            'email' => 'broer@voorbeeld.nl',
            'name' => 'Youssef El Amrani',
            'role' => 'gemachtigde',
            'share_token' => hash('sha256', Str::random(40)),
            'accepted_at' => now()->subMonths(2),
        ]);

        DeclarationShare::create([
            'declaration_id' => $declaration->id,
            'email' => 'huisarts@praktijk.nl',
            'name' => 'Dr. Meijer',
            'role' => 'naaste',
            'share_token' => hash('sha256', Str::random(40)),
            'accepted_at' => now()->subMonths(1),
        ]);

        foreach ([10, 14, 21] as $daysAgo) {
            AccessLog::create([
                'declaration_id' => $declaration->id,
                'accessor_type' => $daysAgo === 21 ? 'naaste' : 'arts',
                'accessor_identifier' => hash('sha256', 'viewer-'.$daysAgo),
                'ip_address' => '82.173.'.rand(1, 255).'.'.rand(1, 255),
                'user_agent' => 'Mozilla/5.0',
                'accessed_at' => now()->subDays($daysAgo),
            ]);
        }
    }

    private function publishedExpired(Declaration $declaration): void
    {
        $version = $this->createVersion($declaration, $this->sampleContent('midden'), now()->subMonths(8));
        $declaration->update([
            'current_version_id' => $version->id,
            'access_token' => hash('sha256', 'demo-token-pieter'),
            'access_token_expires_at' => now()->subDays(30),
            'notification_email' => $declaration->user->email,
        ]);
    }

    private function conceptRevoked(Declaration $declaration): void
    {
        $version = $this->createVersion($declaration, $this->sampleContent('beginfase'), now()->subDays(20));
        $declaration->update(['current_version_id' => $version->id]);

        DeclarationShare::create([
            'declaration_id' => $declaration->id,
            'email' => 'expartner@voorbeeld.nl',
            'name' => 'Tom Jansen',
            'role' => 'gemachtigde',
            'share_token' => hash('sha256', Str::random(40)),
            'accepted_at' => now()->subDays(15),
            'revoked_at' => now()->subDays(3),
        ]);
    }

    private function createVersion(Declaration $declaration, array $content, \DateTimeInterface $createdAt): DeclarationVersion
    {
        $version = new DeclarationVersion([
            'declaration_id' => $declaration->id,
            'content' => $content,
        ]);
        $version->created_at = $createdAt;
        $version->save();

        return $version;
    }

    private function sampleContent(string $variant): array
    {
        $base = DeclarationContent::defaults();

        if ($variant === 'beginfase') {
            return array_merge($base, [
                'intro' => 'Ik stel deze wilsverklaring op terwijl ik bij volle verstand ben.',
                'waarde' => 'Zelfstandigheid en contact met mijn familie zijn voor mij het allerbelangrijkst.',
                'grens' => 'Ik wil niet in een situatie komen waarin ik mijn naasten niet meer herken.',
            ]);
        }

        if ($variant === 'midden') {
            return array_merge($base, [
                'intro' => 'Ik stel deze wilsverklaring op terwijl ik bij volle verstand ben.',
                'waarde' => 'Ik hecht veel waarde aan autonomie, rust en de nabijheid van mijn gezin.',
                'grens' => 'Volledige afhankelijkheid van verpleging ervaar ik als ondraaglijk.',
                'ervaring' => 'Ik heb mijn moeder zien lijden en wil dit zelf niet meemaken.',
                'reanimatie' => 'niet-reanimeren',
                'reanimatie_toelichting' => 'Ik wil geen reanimatie bij een hartstilstand.',
                'beademing' => 'niet-beademen',
                'ic' => 'geen-ic',
            ]);
        }

        return array_merge($base, [
            'intro' => 'Ik stel deze wilsverklaring op terwijl ik bij volle verstand ben en na overleg met mijn huisarts.',
            'waarde' => 'Kwaliteit van leven, helderheid van geest en contact met mijn naasten zijn voor mij essentieel.',
            'grens' => 'Ik wil niet lang in een uitzichtloze situatie verkeren zonder mogelijkheid tot contact.',
            'ervaring' => 'Na het verlies van mijn partner wil ik duidelijkheid scheppen voor mijn kinderen.',
            'reanimatie' => 'niet-reanimeren',
            'reanimatie_toelichting' => 'Ik draag al enkele jaren een niet-reanimerenpenning.',
            'beademing' => 'niet-beademen',
            'ic' => 'geen-ic',
            'beademing_toelichting' => 'Kortdurende beademing in uitzonderlijke gevallen is bespreekbaar met mijn gemachtigde.',
            'antibiotica' => 'alleen-comfort',
            'sonde' => 'niet-toedienen',
            'behandel_toelichting' => 'Behandelingen alleen indien zij bijdragen aan comfort, niet aan levensverlenging.',
            'palliatief_wens' => 'Thuis sterven, omringd door familie.',
            'palliatief_fysiek' => 'Voldoende pijnstilling, ook als dit bewustzijnsverlaging betekent.',
            'palliatief_geestelijk' => 'Aanwezigheid van mijn geestelijk verzorger.',
            'palliatief_diepte' => 'Diepe palliatieve sedatie bij ondraaglijk lijden akkoord.',
            'euthanasie_opgenomen' => true,
            'euthanasie_lijden' => 'Ondraaglijk en uitzichtloos lijden zonder kans op herstel.',
            'euthanasie_situatie' => 'Bij vergevorderde dementie waarin ik mijn naasten niet meer herken.',
            'euthanasie_wilsonbekwaam' => 'ja',
            'euthanasie_wilsonbekwaam_toelichting' => 'Deze wens geldt ook wanneer ik deze niet meer kan bevestigen.',
            'euthanasie_brief' => 'Zie bijgevoegde uitgebreide euthanasieverklaring.',
            'orgaandonatie' => 'ja',
            'uitvaart' => 'Crematie in besloten kring, zonder dienst.',
            'overig' => 'Mijn gemachtigde mag beslissen over zaken die hier niet genoemd staan.',
            'arts' => 'Dr. Meijer, huisartsenpraktijk Parkweg',
            'besproken' => 'ja',
            'datum' => now()->format('d-m-Y'),
        ]);
    }
}

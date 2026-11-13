<?php

declare(strict_types=1);

namespace WV\Controllers;

use WV\Content;
use WV\HttpException;
use WV\Response;

/**
 * Omgeving voor de huisarts. De arts nodigt een patiënt uit na persoonlijk contact en geeft hem een code op papier.
 * Met die code (plus e-mailadres) registreert de patiënt zich; daarmee is zijn identiteit meteen bevestigd.
 *
 * Patiënten horen bij de praktijk: alle artsen van de praktijk zien elkaars patiënten en gepubliceerde verklaringen.
 * Per patiënt staat vast welke arts hem begeleidt (of dat hij op dit moment alleen bij de praktijk staat).
 */
final class ArtsController extends Controller
{
    public function index(): Response
    {
        return $this->overview(false);
    }

    /** Zoeken gaat via POST, zodat zoektermen (namen, e-mailadressen) niet in een URL, browsergeschiedenis of serverlog terechtkomen. */
    public function search(): Response
    {
        return $this->overview(true);
    }

    /** De praktijk van de ingelogde arts. */
    private function practice(): array
    {
        $user = $this->user();
        return $this->app->store->practiceOf($user);
    }

    private function overview(bool $searching): Response
    {
        $store = $this->app->store;
        $practice = $this->practice();
        $team = [];
        foreach ($store->artsInPractice($practice['id']) as $a) {
            $team[$a['id']] = $a['name'];
        }
        $me = $this->user()['id'];
        $artsFilter = $searching ? $this->input('arts') : '';
        if ($artsFilter !== '' && $artsFilter !== 'mine' && $artsFilter !== 'none' && !isset($team[$artsFilter])) {
            $artsFilter = '';
        }
        $q = $searching ? mb_strtolower(trim($this->input('q'))) : '';
        $qDate = $q !== '' ? parse_dob($q) : null;   // 7-3-1985 en 1985-03-07 vinden dezelfde patiënt
        $status = $searching ? $this->input('status') : '';
        $statuses = ['geverifieerd', 'uitgenodigd', 'ingetrokken', 'verlopen'];

        $total = 0;
        $items = [];
        foreach ($store->verificationsForPractice($practice['id']) as $v) {
            $total++;
            $v['effective'] = $this->effectiveStatus($v);
            $v['owner_name'] = $v['arts_id'] ? ($team[$v['arts_id']] ?? null) : null;
            if ($artsFilter === 'mine' && $v['arts_id'] !== $me) {
                continue;
            }
            if ($artsFilter === 'none' && $v['arts_id'] !== null) {
                continue;
            }
            if ($artsFilter !== '' && $artsFilter !== 'mine' && $artsFilter !== 'none' && $v['arts_id'] !== $artsFilter) {
                continue;
            }
            if ($status !== '' && in_array($status, $statuses, true) && $v['effective'] !== $status) {
                continue;
            }
            if ($q !== '' && !str_contains(mb_strtolower($v['patient_name'] . ' ' . $v['patient_email'] . ' ' . $v['his_id'] . ' ' . ($v['owner_name'] ?? '') . ' ' . fmt_iso_date((string) ($v['birthdate'] ?? ''))), $q)
                && !($qDate !== null && ($v['birthdate'] ?? null) === $qDate)) {
                continue;
            }
            $items[] = $v;
        }

        $perPage = $searching ? PHP_INT_MAX : 20;
        $matched = count($items);
        $pages = max(1, (int) ceil($matched / $perPage));
        $page = $searching ? 1 : min(max(1, (int) $this->input('pagina')), $pages);
        $items = array_slice($items, ($page - 1) * $perPage, $perPage);

        return $this->view('arts/index', [
            'items' => $items,
            'total' => $total,
            'matched' => $matched,
            'page' => $page,
            'pages' => $pages,
            'filter' => ['q' => $searching ? trim($this->input('q')) : '', 'status' => in_array($status, $statuses, true) ? $status : '', 'arts' => $artsFilter],
            'team' => $team,
            'me' => $me,
            'practice' => $practice,
            'paperCode' => $this->app->session->flashed('paper_code'),
        ]);
    }

    public function invite(): Response
    {
        $arts = $this->user();
        $store = $this->app->store;
        $name = trim($this->input('name'));
        $email = mb_strtolower(trim($this->input('email')));
        $his = strtoupper(trim($this->input('his_id')));
        $birth = parse_dob($this->input('birthdate'));
        $old = ['name' => $name, 'email' => $email, 'his_id' => $his, 'birthdate' => trim($this->input('birthdate'))];

        $errors = [];
        if ($name === '' || mb_strlen($name) > 120) {
            $errors['name'] = 'Vul de naam van de patiënt in (maximaal 120 tekens).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $errors['email'] = 'Vul een geldig e-mailadres in.';
        }
        if ($birth === null) {
            $errors['birthdate'] = 'Vul de geboortedatum van de patiënt in (bijvoorbeeld 07-03-1985).';
        }
        if (!preg_match('/^[A-Z0-9-]{8,64}$/', $his)) {
            $errors['his_id'] = 'Vul het patiënt-ID uit het HIS in (8–64 tekens: letters, cijfers en streepjes).';
        }
        if ($this->input('seen') !== '1' || $this->input('identity') !== '1' || $this->input('toestemming') !== '1') {
            $errors['seen'] = 'Vink alle drie de verklaringen aan: u heeft de patiënt gezien, zijn identiteit gecontroleerd en de patiënt weet van deze uitnodiging.';
        }
        if (!$errors && $store->userByEmail($email) !== null) {
            $errors['email'] = 'Met dit e-mailadres bestaat al een account.';
        }
        if (!$errors && !$this->app->limiter->attempt('invite-arts|' . $arts['id'], 30, 3600)) {
            $errors['email'] = 'U heeft het uurlimiet van 30 uitnodigingen bereikt. Probeer het later opnieuw.';
        }
        if ($errors) {
            return $this->fail('/arts', $errors, $old);
        }

        // een nieuwe uitnodiging voor dezelfde patiënt vervangt openstaande eerdere (met een andere code)
        foreach ($store->openVerifications($email) as $prev) {
            if ($prev['practice_id'] !== $this->practice()['id']) {
                continue; // een uitnodiging van een andere praktijk blijft staan
            }
            $prev['status'] = 'ingetrokken';
            $prev['revoked_at'] = time();
            $prev['revoked_by'] = 'vervangen';
            $store->saveVerification($prev);
        }

        $practiceRow = $this->practice();
        $result = $store->addVerification($arts['id'], $practiceRow['id'], $email, [
            'patient_name' => $name,
            'his_id' => $his,
            'birthdate' => $birth,
            'arts_name' => $arts['name'],   // de arts die de patiënt heeft gezien en geïdentificeerd (blijft zo staan)
            'practice' => $practiceRow['name'],
        ]);

        $practice = ' (' . $practiceRow['name'] . ')';
        $sent = $this->app->mailer->send(
            $email,
            'Uitnodiging van uw huisarts voor uw wilsverklaring',
            "Beste $name,\n\n"
            . "Uw huisarts, {$arts['name']}$practice, nodigt u uit om uw wilsverklaring online te maken.\n\n"
            . "U registreert u op deze pagina (veertien dagen geldig):\n" . $this->app->url('/registreren') . "\n\n"
            . "Vul daar uw naam, dit e-mailadres, uw geboortedatum en een wachtwoord in, samen met de code van 6 cijfers die uw huisarts u op papier heeft gegeven. "
            . "Daarmee is meteen uw identiteit bevestigd.\n\n"
            . "Deel de code met niemand. Verwachtte u deze e-mail niet? Dan kunt u hem negeren."
        );

        $this->app->session->flash('paper_code', $result['code']);
        $this->flash($sent
            ? "Uitnodiging verstuurd naar $email. Geef de patiënt de code hieronder op papier mee."
            : 'De e-mail kon niet worden verstuurd. Geef de patiënt de code op papier mee en wijs hem op de registratiepagina.');
        return $this->redirect('/arts');
    }

    /** Nieuwe code (bijvoorbeeld kwijtgeraakt); de oude code werkt dan niet meer. */
    public function paperCode(string $id): Response
    {
        $v = $this->mine($id);
        if ($v['status'] !== 'uitgenodigd' || $v['expires_at'] < time()) {
            throw new HttpException(404);
        }
        $code = $this->app->store->newCode();
        $v['code_mac'] = $this->app->store->codeMac($v['id'], $code);
        $v['attempts'] = 0;
        $this->app->store->saveVerification($v);

        $this->app->session->flash('paper_code', $code);
        $this->flash('Nieuwe code aangemaakt voor ' . $v['patient_name'] . '. Eerdere codes werken niet meer.');
        return $this->redirect('/arts');
    }

    public function patient(string $id): Response
    {
        $v = $this->mine($id);
        $store = $this->app->store;
        $owner = $v['arts_id'] ? $store->userById($v['arts_id']) : null;
        $data = ['v' => $v, 'version' => null, 'doc' => null, 'owner' => $owner['name'] ?? null, 'me' => $this->user()['id'], 'practice' => $this->practice()];

        if ($v['status'] === 'geverifieerd' && $v['patient_user_id']) {
            $declaration = $store->declarationForUser($v['patient_user_id']);
            $version = $store->version($declaration['current_version_id'], $declaration['id']);
            if ($declaration['is_published'] && $version) {
                $store->addLog(
                    $declaration['id'],
                    'huisarts',
                    $this->user()['name'], // de patiënt ziet in zijn overzicht welke arts de verklaring las
                    $this->app->request->ip(),
                    $this->app->request->userAgent()
                );
                $data['version'] = $version;
                $data['doc'] = Content::normalize($version['content']);
            }
        }
        return $this->view('arts/patient', $data);
    }

    public function revoke(string $id): Response
    {
        $v = $this->mine($id);
        if ($v['status'] !== 'ingetrokken') {
            $v['status'] = 'ingetrokken';
            $v['revoked_at'] = time();
            $v['revoked_by'] = 'arts';
            $this->app->store->saveVerification($v);
            if ($v['patient_user_id']) {
                $this->app->mailer->send(
                    $v['patient_email'],
                    'Uw huisarts heeft de bevestiging van uw identiteit ingetrokken',
                    "Beste {$v['patient_name']},\n\nDe huisartsenpraktijk heeft de bevestiging van uw identiteit voor uw online wilsverklaring ingetrokken. "
                    . "Uw verklaring blijft van u, maar toont niet meer dat uw huisarts uw identiteit heeft bevestigd en de artsen van de praktijk kunnen hem niet meer inzien.\n\n"
                    . "Heeft u hier vragen over? Neem dan contact op met uw huisartsenpraktijk."
                );
            }
        }
        $this->flash('Ingetrokken.');
        return $this->redirect('/arts');
    }

    /** Neemt de patiënt over: de ingelogde arts wordt de begeleidende arts. Werkt ook voor patiënten zonder arts. */
    public function takeOver(string $id): Response
    {
        $v = $this->mine($id);
        $me = $this->user();
        if ($v['status'] === 'ingetrokken' || ($v['arts_id'] === $me['id'])) {
            return $this->redirect('/arts/patienten/' . $id);
        }
        $this->app->store->assignVerification($v, $me, $me['name']);
        $this->flash($v['patient_name'] . ' staat nu bij u.');
        return $this->redirect('/arts/patienten/' . $id);
    }

    /** Een verificatie van de eigen praktijk; anders 404 (ook voor patiënten van andere praktijken). */
    private function mine(string $id): array
    {
        $v = $this->app->store->verificationById($id);
        if (!$v || $v['practice_id'] !== $this->practice()['id']) {
            throw new HttpException(404);
        }
        return $v;
    }

    private function effectiveStatus(array $v): string
    {
        return ($v['status'] === 'uitgenodigd' && $v['expires_at'] < time()) ? 'verlopen' : $v['status'];
    }
}

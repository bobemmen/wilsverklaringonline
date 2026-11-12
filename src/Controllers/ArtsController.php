<?php

declare(strict_types=1);

namespace WV\Controllers;

use WV\Content;
use WV\HttpException;
use WV\Response;

/**
 * Omgeving voor de huisarts. De arts nodigt een patiënt uit na persoonlijk contact en geeft hem een code op papier.
 * Met die code (plus e-mailadres en BSN) registreert de patiënt zich; daarmee is zijn identiteit meteen bevestigd.
 */
final class ArtsController extends Controller
{
    public function index(): Response
    {
        return $this->overview(false);
    }

    /** Zoeken gaat via POST, zodat een BSN nooit in een URL, browsergeschiedenis of serverlog terechtkomt. */
    public function search(): Response
    {
        return $this->overview(true);
    }

    private function overview(bool $searching): Response
    {
        $store = $this->app->store;
        $q = $searching ? mb_strtolower(trim($this->input('q'))) : '';
        $bsn = $searching ? (preg_replace('/\D/', '', $this->input('bsn')) ?? '') : '';
        $status = $searching ? $this->input('status') : '';
        $statuses = ['geverifieerd', 'uitgenodigd', 'ingetrokken', 'verlopen'];
        $bsnProof = $bsn !== '' ? $store->bsnProof($bsn) : null;

        $total = 0;
        $items = [];
        foreach ($store->verificationsForArts($this->user()['id']) as $v) {
            $total++;
            $v['effective'] = $this->effectiveStatus($v);
            if ($status !== '' && in_array($status, $statuses, true) && $v['effective'] !== $status) {
                continue;
            }
            if ($q !== '' && !str_contains(mb_strtolower($v['patient_name'] . ' ' . $v['patient_email'] . ' ' . $v['his_id']), $q)) {
                continue;
            }
            // BSN: alleen exacte treffers, vergeleken met de bewaarde HMAC; het BSN zelf staat nergens
            if ($bsnProof !== null && !hash_equals($v['bsn_proof'], $bsnProof)) {
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
            'filter' => ['q' => $searching ? trim($this->input('q')) : '', 'bsn' => $searching ? $this->input('bsn') : '', 'status' => in_array($status, $statuses, true) ? $status : ''],
            'paperCode' => $this->app->session->flashed('paper_code'),
        ]);
    }

    public function invite(): Response
    {
        $arts = $this->user();
        $store = $this->app->store;
        $name = trim($this->input('name'));
        $email = mb_strtolower(trim($this->input('email')));
        $bsn = trim($this->input('bsn'));
        $his = strtoupper(trim($this->input('his_id')));
        $old = ['name' => $name, 'email' => $email, 'his_id' => $his];

        $errors = [];
        if ($name === '' || mb_strlen($name) > 120) {
            $errors['name'] = 'Vul de naam van de patiënt in (maximaal 120 tekens).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $errors['email'] = 'Vul een geldig e-mailadres in.';
        }
        if (!valid_bsn($bsn)) {
            $errors['bsn'] = 'Dit is geen geldig BSN (de elfproef klopt niet).';
        }
        if (!preg_match('/^[A-Z0-9-]{8,64}$/', $his)) {
            $errors['his_id'] = 'Vul het patiënt-ID uit het HIS in (8–64 tekens: letters, cijfers en streepjes).';
        }
        if ($this->input('seen') !== '1' || $this->input('identity') !== '1') {
            $errors['seen'] = 'Vink beide verklaringen aan: u heeft de patiënt gezien en zijn identiteit gecontroleerd.';
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
        foreach ($store->openVerifications($email, $bsn) as $prev) {
            $prev['status'] = 'ingetrokken';
            $prev['revoked_at'] = time();
            $prev['revoked_by'] = 'vervangen';
            $store->saveVerification($prev);
        }

        $result = $store->addVerification($arts['id'], $email, $bsn, [
            'patient_name' => $name,
            'his_id' => $his,
            'arts_name' => $arts['name'],
            'practice' => $arts['practice'] ?? null,
        ]);

        $practice = ($arts['practice'] ?? '') !== '' ? ' (' . $arts['practice'] . ')' : '';
        $sent = $this->app->mailer->send(
            $email,
            'Uitnodiging van uw huisarts voor uw wilsverklaring',
            "Beste $name,\n\n"
            . "Uw huisarts, {$arts['name']}$practice, nodigt u uit om uw wilsverklaring online te maken.\n\n"
            . "U registreert u op deze pagina (veertien dagen geldig):\n" . $this->app->url('/registreren') . "\n\n"
            . "Vul daar uw naam, dit e-mailadres, uw BSN en een wachtwoord in, samen met de code van 6 cijfers die uw huisarts u op papier heeft gegeven. "
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
        $data = ['v' => $v, 'version' => null, 'doc' => null];

        if ($v['status'] === 'geverifieerd' && $v['patient_user_id']) {
            $declaration = $store->declarationForUser($v['patient_user_id']);
            $version = $store->version($declaration['current_version_id'], $declaration['id']);
            if ($declaration['is_published'] && $version) {
                $store->addLog(
                    $declaration['id'],
                    'huisarts',
                    $this->app->crypto->index('accessor', $this->user()['email']),
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
        }
        $this->flash('Ingetrokken.');
        return $this->redirect('/arts');
    }

    private function mine(string $id): array
    {
        $v = $this->app->store->verificationById($id);
        if (!$v || $v['arts_id'] !== $this->user()['id']) {
            throw new HttpException(404);
        }
        return $v;
    }

    private function effectiveStatus(array $v): string
    {
        return ($v['status'] === 'uitgenodigd' && $v['expires_at'] < time()) ? 'verlopen' : $v['status'];
    }
}

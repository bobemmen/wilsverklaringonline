<?php

declare(strict_types=1);

namespace WV\Controllers;

use WV\Content;
use WV\HttpException;
use WV\Response;

/** Omgeving voor de huisarts: patiënten uitnodigen en hun verklaring als "echt deze patiënt" bevestigen. */
final class ArtsController extends Controller
{
    public function index(): Response
    {
        $store = $this->app->store;
        $items = [];
        foreach ($store->verificationsForArts($this->user()['id']) as $v) {
            $items[] = $v + ['effective' => $this->effectiveStatus($v)];
        }
        $token = $this->app->session->flashed('invite_plaintext_token');

        return $this->view('arts/index', [
            'items' => $items,
            'smsEnabled' => \WV\Config::bool('SMS_ENABLED'),
            'paperCode' => $this->app->session->flashed('paper_code'),
            'inviteUrl' => $token ? $this->app->url('/uitnodiging/' . $token) : null,
        ]);
    }

    public function invite(): Response
    {
        $arts = $this->user();
        $name = trim($this->input('name'));
        $email = mb_strtolower(trim($this->input('email')));
        $dobRaw = trim($this->input('dob'));
        $bsn = trim($this->input('bsn'));
        $his = strtoupper(trim($this->input('his_id')));
        $phoneRaw = trim($this->input('phone'));
        $phone = normalize_phone($phoneRaw);
        $smsOn = \WV\Config::bool('SMS_ENABLED');
        $method = ($smsOn && $this->input('method') === 'sms') ? 'sms' : 'papier';
        $old = ['name' => $name, 'email' => $email, 'dob' => $dobRaw, 'his_id' => $his, 'phone' => $phoneRaw, 'method' => $method];

        $errors = [];
        if ($name === '' || mb_strlen($name) > 120) {
            $errors['name'] = 'Vul de naam van de patiënt in (maximaal 120 tekens).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $errors['email'] = 'Vul een geldig e-mailadres in.';
        }
        $dob = parse_birthdate($dobRaw);
        if ($dob === null) {
            $errors['dob'] = 'Vul een geldige geboortedatum in.';
        }
        if (!valid_bsn($bsn)) {
            $errors['bsn'] = 'Dit is geen geldig BSN (de elfproef klopt niet).';
        }
        if (!preg_match('/^[A-Z0-9-]{8,64}$/', $his)) {
            $errors['his_id'] = 'Vul het patiënt-ID uit het HIS in (8–64 tekens: letters, cijfers en streepjes).';
        }
        if ($method === 'sms' && $phone === null) {
            $errors['phone'] = 'Vul een geldig mobiel nummer in, bijvoorbeeld 06 12345678 of +31 6 12345678.';
        }
        if (!$errors && !$this->app->limiter->attempt('invite-arts|' . $arts['id'], 30, 3600)) {
            $errors['email'] = 'U heeft het uurlimiet van 30 uitnodigingen bereikt. Probeer het later opnieuw.';
        }
        if ($errors) {
            return $this->fail('/arts', $errors, $old);
        }

        $store = $this->app->store;
        $result = $store->addVerification($arts['id'], [
            'patient_name' => $name,
            'patient_email' => $email,
            'his_id' => $his,
            'phone' => $phone,
            'code_method' => $method,
            'dob_proof' => $store->dobProof($dob->format('Y-m-d')),
            'bsn_proof' => $store->bsnProof($bsn),
            'arts_name' => $arts['name'],
            'practice' => $arts['practice'] ?? null,
        ]);

        if ($method === 'papier') {
            $paper = $this->issuePaperCode($result['verification']);
            $this->app->session->flash('paper_code', $paper);
        }
        $url = $this->app->url('/uitnodiging/' . $result['token']);
        $practice = ($arts['practice'] ?? '') !== '' ? ' (' . $arts['practice'] . ')' : '';
        $sent = $this->app->mailer->send(
            $email,
            'Uitnodiging van uw huisarts voor uw wilsverklaring',
            "Beste $name,\n\n"
            . "Uw huisarts, {$arts['name']}$practice, nodigt u uit om uw wilsverklaring online te maken en door uw huisarts te laten bevestigen.\n\n"
            . "Met die bevestiging kan uw huisarts vastleggen dat de verklaring echt van u is.\n\n"
            . "Open deze link (veertien dagen geldig):\n$url\n\n"
            . "U maakt een account aan of logt in, bevestigt uw geboortedatum en BSN en voert een code in. " . ($method === 'papier' ? "Die code heeft uw huisarts u op papier meegegeven. " : "Die code sturen we per sms naar uw mobiele nummer. ")
            . "Uw huisarts heeft die gegevens al; ze worden alleen gebruikt om te controleren dat u de juiste persoon bent.\n\n"
            . "Deel deze link met niemand. Verwachtte u deze e-mail niet? Dan kunt u hem negeren."
        );

        $this->app->session->flash('invite_plaintext_token', $result['token']);
        $this->flash($sent
            ? "Uitnodiging verstuurd naar $email."
            : 'De e-mail kon niet worden verstuurd. Geef de onderstaande link zelf door aan de patiënt.');
        return $this->redirect('/arts');
    }

    public function patient(string $id): Response
    {
        $v = $this->mine($id);
        $store = $this->app->store;
        $effective = $this->effectiveStatus($v);

        $data = [
            'v' => $v,
            'effective' => $effective,
            'declaration' => null,
            'version' => null,
            'doc' => null,
        ];

        if (in_array($v['status'], ['gekoppeld', 'geverifieerd'], true) && $v['patient_user_id']) {
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
                $data['declaration'] = $declaration;
                $data['version'] = $version;
                $data['doc'] = Content::normalize($version['content']);
            }
        }
        return $this->view('arts/patient', $data);
    }

    public function confirm(string $id): Response
    {
        $v = $this->mine($id);
        $store = $this->app->store;
        if (!in_array($v['status'], ['gekoppeld', 'geverifieerd'], true) || !$v['patient_user_id']) {
            throw new HttpException(404);
        }
        $back = '/arts/patienten/' . $v['id'];

        if ($v['status'] === 'geverifieerd') {
            return $this->redirect($back); // eenmalig: opnieuw bevestigen is niet nodig
        }
        if ($this->input('seen') !== '1' || $this->input('identity') !== '1') {
            return $this->fail($back, ['verify' => 'Vink beide verklaringen aan om te bevestigen.']);
        }

        $arts = $this->user();
        $v['status'] = 'geverifieerd';
        $v['verified_at'] = time();
        $v['arts_name'] = $arts['name'];
        $v['practice'] = $arts['practice'] ?? null;
        $store->saveVerification($v);

        $this->flash('Bevestigd. Dit hoeft u niet te herhalen: ook latere wijzigingen van de patiënt blijven bij dit account horen.');
        return $this->redirect($back);
    }

    /** Nieuwe code om op papier mee te geven (vervangt een eerdere code; patiënt begint de koppelstappen opnieuw). */
    public function paperCode(string $id): Response
    {
        $v = $this->mine($id);
        if ($v['status'] !== 'uitgenodigd' || $v['expires_at'] < time()) {
            throw new HttpException(404);
        }
        $this->app->session->flash('paper_code', $this->issuePaperCode($v));
        $this->flash('Nieuwe code aangemaakt voor ' . $v['patient_name'] . '. Eerdere codes werken niet meer.');
        return $this->redirect('/arts');
    }

    private function issuePaperCode(array $v): string
    {
        $store = $this->app->store;
        $code = $store->newCode();
        $v['code_method'] = 'papier';
        $v['paper_code_mac'] = $store->codeMac($v['id'], $code);
        $v['pending_user_id'] = null;
        $v['sms_code_mac'] = null;
        $v['sms_expires_at'] = null;
        $v['sms_attempts'] = 0;
        $store->saveVerification($v);
        return $code;
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

    // ---------------------------------------------------------------- helpers

    private function mine(string $id): array
    {
        $v = $this->app->store->verificationById($id);
        if (!$v || $v['arts_id'] !== $this->user()['id']) {
            throw new HttpException(404);
        }
        return $v;
    }

    /** Status zoals de arts hem ziet, inclusief "verlopen". */
    private function effectiveStatus(array $v): string
    {
        if ($v['status'] === 'uitgenodigd' && $v['expires_at'] < time()) {
            return 'verlopen';
        }
        return $v['status'];
    }
}

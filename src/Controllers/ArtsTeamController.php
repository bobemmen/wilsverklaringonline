<?php

declare(strict_types=1);

namespace WV\Controllers;

use WV\HttpException;
use WV\Response;

/**
 * Artsen van de eigen praktijk beheren. Alleen praktijkbeheerders (is_admin) mogen artsen toevoegen of verwijderen.
 * Een nieuwe arts krijgt een tijdelijk wachtwoord dat de beheerder eenmaal te zien krijgt; bij de eerste login volgen
 * een verplichte wachtwoordwijziging en het instellen van tweestapsverificatie (met herstelcodes).
 */
final class ArtsTeamController extends Controller
{
    public function index(): Response
    {
        $store = $this->app->store;
        $me = $this->user();
        $practice = $store->practiceOf($me);

        $counts = [];
        $unassigned = 0;
        foreach ($store->verificationsForPractice($practice['id']) as $v) {
            if ($v['status'] === 'ingetrokken' || ($v['status'] === 'uitgenodigd' && $v['expires_at'] < time())) {
                continue;
            }
            if ($v['arts_id']) {
                $counts[$v['arts_id']] = ($counts[$v['arts_id']] ?? 0) + 1;
            } else {
                $unassigned++;
            }
        }

        return $this->view('arts/team', [
            'practice' => $practice,
            'team' => $store->artsInPractice($practice['id']),
            'counts' => $counts,
            'unassigned' => $unassigned,
            'isAdmin' => !empty($me['is_admin']),
            'meId' => $me['id'],
            'temp' => $this->app->session->flashed('temp_password'),
        ]);
    }

    public function create(): Response
    {
        $this->admin();
        $store = $this->app->store;
        $me = $this->user();
        $practice = $store->practiceOf($me);
        $name = trim($this->input('name'));
        $email = mb_strtolower(trim($this->input('email')));
        $old = ['name' => $name, 'email' => $email];

        $errors = [];
        if ($name === '' || mb_strlen($name) > 120) {
            $errors['name'] = 'Vul de naam van de arts in (maximaal 120 tekens).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $errors['email'] = 'Vul een geldig e-mailadres in.';
        } elseif ($store->userByEmail($email) !== null) {
            $errors['email'] = 'Met dit e-mailadres bestaat al een account.';
        }
        if (!$errors && !$this->app->limiter->attempt('arts-create|' . $me['id'], 20, 3600)) {
            $errors['email'] = 'U heeft het uurlimiet bereikt. Probeer het later opnieuw.';
        }
        if ($errors) {
            return $this->fail('/arts/team', $errors, $old);
        }

        $password = $this->app->auth->temporaryPassword();
        $arts = $store->createArts($practice['id'], $name, $email, $this->app->auth->hashPassword($password), $this->input('beheerder') === '1', true);
        if ($arts === null) {
            return $this->fail('/arts/team', ['email' => 'Met dit e-mailadres bestaat al een account.'], $old);
        }
        $this->app->mailer->send(
            $email,
            'Uw account voor WilsVerklaring',
            "Beste $name,\n\n{$me['name']} heeft een huisartsaccount voor u aangemaakt bij {$practice['name']}.\n\n"
            . 'Inloggen: ' . $this->app->url('/inloggen') . "\nGebruikersnaam: dit e-mailadres\n\n"
            . "Het tijdelijke wachtwoord krijgt u persoonlijk van {$me['name']}. Na het inloggen kiest u een eigen wachtwoord en stelt u tweestapsverificatie in; "
            . 'bewaar de herstelcodes die u dan krijgt op een veilige plek.'
        );
        $this->app->session->flash('temp_password', ['name' => $name, 'email' => $email, 'password' => $password]);
        $this->flash("Account aangemaakt voor $name.");
        return $this->redirect('/arts/team');
    }

    public function remove(string $id): Response
    {
        $this->admin();
        $target = $this->member($id);
        $me = $this->user();
        if ($target['id'] === $me['id']) {
            return $this->fail('/arts/team', ['team' => 'U kunt uw eigen account niet verwijderen. Vraag een andere beheerder dat te doen.']);
        }
        if (!empty($target['is_admin']) && $this->adminCount() < 2) {
            return $this->fail('/arts/team', ['team' => 'Dit is de enige beheerder van de praktijk.']);
        }

        $n = 0;
        foreach ($this->app->store->verificationsForPractice($target['practice_id']) as $v) {
            $n += $v['arts_id'] === $target['id'] ? 1 : 0;
        }
        $this->app->store->removeArts($target['id'], $me['name']);
        $this->flash($target['name'] . ' is verwijderd.' . ($n > 0 ? " $n patiënt(en) staan nu bij de praktijk; een arts kan ze overnemen." : ''));
        return $this->redirect('/arts/team');
    }

    public function toggleAdmin(string $id): Response
    {
        $this->admin();
        $target = $this->member($id);
        if (!empty($target['is_admin'])) {
            if ($this->adminCount() < 2) {
                return $this->fail('/arts/team', ['team' => 'Er moet minstens één beheerder overblijven.']);
            }
            $target['is_admin'] = false;
        } else {
            $target['is_admin'] = true;
        }
        $this->app->store->saveUser($target);
        $this->flash($target['name'] . (!empty($target['is_admin']) ? ' is nu beheerder.' : ' is geen beheerder meer.'));
        return $this->redirect('/arts/team');
    }

    public function resetPassword(string $id): Response
    {
        $this->admin();
        $target = $this->member($id);
        if ($target['id'] === $this->user()['id']) {
            return $this->redirect('/arts/wachtwoord');
        }
        $password = $this->app->auth->temporaryPassword();
        $target['password'] = $this->app->auth->hashPassword($password);
        $target['must_change_password'] = true;
        $this->app->store->saveUser($target);
        $this->app->store->endSessions($target['id']);
        $this->app->session->flash('temp_password', ['name' => $target['name'], 'email' => $target['email'], 'password' => $password]);
        $this->flash('Nieuw tijdelijk wachtwoord aangemaakt voor ' . $target['name'] . '.');
        return $this->redirect('/arts/team');
    }

    /** Collega kwijt telefoon én herstelcodes: tweestapsverificatie opnieuw laten instellen. */
    public function reset2fa(string $id): Response
    {
        $this->admin();
        $target = $this->member($id);
        if ($target['id'] === $this->user()['id']) {
            return $this->redirect('/2fa/beheer');
        }
        $target['two_factor_secret'] = null;
        $target['two_factor_confirmed_at'] = null;
        $target['two_factor_last_step'] = 0;
        $target['recovery_codes'] = [];
        $this->app->store->saveUser($target);
        $this->app->store->endSessions($target['id']);
        $this->app->mailer->send(
            $target['email'],
            'Tweestapsverificatie is opnieuw ingesteld',
            "Beste {$target['name']},\n\nDe beheerder van uw praktijk heeft uw tweestapsverificatie gereset. Bij uw volgende login stelt u deze opnieuw in en krijgt u nieuwe herstelcodes. "
            . 'Verwachtte u dit niet? Neem dan direct contact op met uw praktijk.'
        );
        $this->flash('Tweestapsverificatie van ' . $target['name'] . ' is gereset.');
        return $this->redirect('/arts/team');
    }

    private function admin(): void
    {
        if (empty($this->user()['is_admin'])) {
            throw new HttpException(403);
        }
    }

    /** Een arts uit de eigen praktijk; anders 404. */
    private function member(string $id): array
    {
        $target = $this->app->store->userById($id);
        $me = $this->user();
        $practice = $this->app->store->practiceOf($me);
        if (!$this->app->store->isArts($target) || $target['practice_id'] !== $practice['id']) {
            throw new HttpException(404);
        }
        return $target;
    }

    private function adminCount(): int
    {
        $me = $this->user();
        $n = 0;
        foreach ($this->app->store->artsInPractice($this->app->store->practiceOf($me)['id']) as $a) {
            $n += !empty($a['is_admin']) ? 1 : 0;
        }
        return $n;
    }
}

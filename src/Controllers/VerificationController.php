<?php

declare(strict_types=1);

namespace WV\Controllers;

use WV\HttpException;
use WV\Response;

/** Patiëntkant van de huisartsverificatie: de koppeling met de huisarts intrekken. */
final class VerificationController extends Controller
{
    public function patientRevoke(string $id): Response
    {
        $v = $this->app->store->verificationById($id);
        if (!$v || $v['patient_user_id'] !== $this->user()['id']) {
            throw new HttpException(404);
        }
        $v['status'] = 'ingetrokken';
        $v['revoked_at'] = time();
        $v['revoked_by'] = 'patient';
        $this->app->store->saveVerification($v);

        $this->flash('De koppeling met uw huisarts is ingetrokken. Uw huisarts kan uw verklaring niet meer inzien.');
        return $this->redirect('/dashboard');
    }
}

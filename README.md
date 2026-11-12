# WilsVerklaring

Een online wizard waarmee iemand stap voor stap een persoonlijke wilsverklaring opstelt (behandelverbod,
palliatieve sedatie, euthanasieverzoek, orgaandonatie, uitvaart), die publiceert en deelt met de huisarts
(via een persoonlijke link, met optionele e-mailbevestiging) en met naasten. Inclusief PDF om te ondertekenen.

Dit is een **framework-vrije** PHP-applicatie: geen Laravel, geen Composer-pakketten, geen externe diensten.
Alle gegevens staan in **één SQLite-bestand** waarin elke waarde **versleuteld** is.

- PHP 8.2+ met de extensies `pdo_sqlite`, `sodium` en `mbstring` (standaard aanwezig in PHP)
- Zelf te hosten op elke Linux-server met nginx/Caddy/Apache + PHP-FPM
- Lettertypen worden lokaal geserveerd; de applicatie doet geen verzoeken naar externe partijen

## Snel proberen (lokaal)

```sh
php bin/console install http://127.0.0.1:8000   # maakt .env met een nieuwe APP_KEY en de database
php bin/console serve                            # ontwikkelserver op http://127.0.0.1:8000
```

Zonder e-mailserver schrijft `MAIL_DRIVER=log` (alleen bij `APP_ENV=local`) de bevestigingsmail naar `storage/logs/mail.log`.

## Op een eigen server zetten

1. Zet de bestanden in bijv. `/var/www/wilsverklaring` en laat de webserver **alleen `public/`** als documentroot gebruiken
   (voorbeelden: `deploy/nginx.conf.example`, `deploy/Caddyfile.example`). HTTPS is verplicht.
2. `php bin/console install https://wilsverklaring.example.nl` — maakt `.env` (rechten 0600) met een nieuwe `APP_KEY` en de database.
3. Vul in `.env` de mailinstellingen in (`MAIL_HOST`, `MAIL_USERNAME`, …) zodat de arts een bevestigingsmail kan krijgen.
4. Zorg dat de gebruiker van PHP-FPM schrijfrechten heeft op `storage/` en `.env` kan lezen, en dat niemand anders dat kan.
5. **Bewaar een kopie van `APP_KEY` op een veilige plek, los van de database-back-ups.** Zonder die sleutel zijn alle gegevens
   onherstelbaar onleesbaar; dat is ook precies de bedoeling.
6. Plan een back-up (`deploy/backup.sh.example`) en test eens of je die kunt terugzetten.

Aanbevolen in `php.ini`: `expose_php=Off`, OPcache aan.

### Configuratie (`.env`)

| Variabele | Betekenis |
|---|---|
| `APP_URL` | Publiek adres. Verplicht in productie; wordt gebruikt voor links in e-mails (nooit de Host-header). |
| `APP_KEY` | Hoofdsleutel voor alle versleuteling (32 bytes, base64). |
| `APP_ENV` | `production` of `local`. |
| `DB_PATH` | Pad van het SQLite-bestand (standaard `storage/database/wilsverklaring.sqlite`). |
| `SESSION_LIFETIME` | Minuten inactiviteit voor uitloggen (standaard 120). |
| `TRUSTED_PROXIES` | Alleen nodig als de app achter nóg een proxy draait; dan wordt `X-Forwarded-For` vertrouwd vanaf deze IP's. |
| `MAIL_*` | SMTP (`tls`=STARTTLS, `ssl`, `none`), of `MAIL_DRIVER=mail` voor lokale sendmail. |

## Beheer

```sh
php bin/console migrate                 # databaseschema bijwerken
php bin/console user:reset-2fa <email>  # gebruiker heeft zijn authenticator kwijt
php bin/console backup <bestand>        # consistente back-up (VACUUM INTO); bestand is 0600
php bin/console purge                   # verlopen sessies en tellers opruimen (gebeurt ook automatisch)
php bin/console key:generate            # toont een nieuwe sleutel (zonder iets te wijzigen)
```

## Beveiligingsmodel

**Versleuteling.** Elke rij heeft één kolom `data` met een JSON-document, versleuteld met XChaCha20-Poly1305 (libsodium).
Het rij-adres (`tabel:id`) is geauthenticeerde context, dus een versleutelde waarde kan niet ongemerkt naar een andere rij
worden gekopieerd. Waarden worden opgevuld tot blokken van 256 bytes zodat de lengte weinig verraadt.
Versleuteld zijn onder meer: naam, e-mail, wachtwoordhash, 2FA-geheim, alle inhoud van verklaringen en versies,
concepten, e-mailadres van de arts, uitnodigingen, toegangslog (IP, user-agent) en de sessie-inhoud.

**Zoeken zonder te ontsleutelen.** E-mailadressen en toegangscodes worden opgezocht via een HMAC-index (met een aparte
afgeleide sleutel). De codes zelf worden nergens bewaard; alleen de HMAC. Een databaselek zonder `APP_KEY` levert dus
geen werkende links of e-mailadressen op.

**In platte tekst in de database:** willekeurige id's, de HMAC-indexen, de volgorde van invoegen (rij-nummers) en de
verlooptijd van sessies/tellers. Dus wél zichtbaar: hoeveel rijen er zijn en in welke volgorde ze zijn aangemaakt, maar niet van wie of wanneer.

**Wat dit wel en niet beschermt.** Het beschermt tegen diefstal of lekken van het databasebestand, een back-up of de schijf.
Het beschermt *niet* tegen iemand die de draaiende server (en dus `.env`) overneemt: de applicatie moet de sleutel immers
kunnen gebruiken. Bescherm de server daarom goed (updates, SSH-sleutels, firewall) en houd `.env` en back-ups gescheiden.
Sleutelrotatie is niet ingebouwd.

**Overig.**
- Wachtwoorden: Argon2id, minimaal 10 tekens. Inloggen heeft rate limiting per e-mail+IP en per IP.
- Tweestapsverificatie (TOTP) is verplicht voor alle pagina's met gegevens; een code kan niet twee keer worden gebruikt;
  het 2FA-geheim wordt na het instellen niet meer getoond.
- Sessie-ID's zijn willekeurig en worden bij inloggen en na 2FA vernieuwd; cookie `HttpOnly`, `SameSite=Lax`, `Secure` onder HTTPS.
  In de database staat alleen een HMAC van het sessie-ID.
- CSRF-token op elk formulier; strikte Content-Security-Policy (geen inline scripts, geen externe bronnen),
  `Referrer-Policy: no-referrer` (de geheime links lekken dus niet via de Referer), `no-store`, HSTS, `X-Frame-Options: DENY`.
- Arts-link: optioneel bevestigingsmail naar de arts (link 1 uur geldig, gebonden aan de huidige code), maximaal 1 mail per 5 minuten.
- Account en alle gegevens kunnen door de gebruiker worden geëxporteerd (JSON) en definitief verwijderd (`/account`).

> Dit is geen medisch of juridisch hulpmiddel en er is geen onafhankelijke beveiligingsaudit uitgevoerd.
> Laat de authenticatie- en versleutelingscode (`src/Crypto.php`, `Session.php`, `Auth.php`, `Store.php`) beoordelen
> voordat er echte patiëntgegevens in komen. Over verwerking van gezondheidsgegevens gelden AVG-verplichtingen
> (o.a. verwerkingsregister, verwerkersafspraken met de hostingpartij, datalekprocedure).

## Wizard

Zes hoofdstukken, 17 stappen. Het euthanasieverzoek bestaat uit twee losse onderdelen, elk met een eigen "opnemen ja/nee"-keuze: **algemeen** (stap 8–11) en **dementie** (stap 12–14, met schema en praktijktabel). Overslaan, terug en volgende volgen `Content::GATES`.

## Toegankelijkheid

Ontworpen voor ook oudere gebruikers (70+), getoetst met axe-core (WCAG 2.2 AA) op alle pagina's in drie thema's en vier tekstgroottes:

- Lettertypen: Source Serif 4 (koppen) en Atkinson Hyperlegible (tekst, ontworpen voor slechtzienden), lokaal geserveerd; basisgrootte ruim 18 punten.
- Weergave-instellingen (kop: **A−/A+**, en pagina `/weergave`): tekstgrootte in vier stappen (tot 200%), contrast standaard / hoog / donker (volgt ook `prefers-contrast`), ruime regel- en letterafstand. Opgeslagen in een cookie `wv_prefs` zonder persoonsgegevens; werkt ook zonder JavaScript.
- **Lees voor**: voorlezen via de spraaksynthese van de browser (hele pagina of selectie).
- Grote keuzekaarten met echte keuzerondjes in `fieldset`/`legend`, doelgebieden van minimaal 44 px, zichtbare focus, sla-over-link, landmarks, foutsamenvatting met `role="alert"`, voortgang met `aria-current`.
- Respecteert `prefers-reduced-motion` en forced-colors; bruikbaar tot 400% zoom zonder horizontaal scrollen.
- PDF ook met grote letters (`/verklaring/pdf?groot=1`).

## Tests

```sh
tests/run.sh     # unit-tests + end-to-end tests (start zelf een tijdelijke server en database)
```

De end-to-end test doorloopt registreren, 2FA, wizard, publiceren, arts- en naastenlinks, PDF, export en verwijderen,
en controleert dat het databasebestand geen leesbare persoonsgegevens bevat. (Voor de PDF-inhoudscontrole zijn
`pdftotext` en `qpdf` handig, maar niet verplicht.)

## Indeling

```
public/          webroot: index.php, assets (css, js, lettertypen)
src/             applicatiecode (namespace WV): Crypto, Db, Store, Session, Auth, Router, Pdf, Mailer, …
src/Controllers/ de pagina's
templates/       PHP-templates (alles wat de gebruiker invoert gaat door e())
bin/console      beheercommando's
deploy/          voorbeeldconfiguraties (nginx, Caddy, back-up)
storage/         database en logs (buiten de webroot, 0700)
tests/           unit- en end-to-end tests
```

## Huisarts-verificatie

Een huisarts bevestigt **eenmalig** dat een account bij een patiënt hoort. Latere wijzigingen van de verklaring hoeven niet opnieuw te worden bevestigd.

- Huisartsaccounts worden alleen door de beheerder aangemaakt: `php bin/console arts:aanmaken "Dr. Jansen" jansen@praktijk.nl "Praktijknaam"`. Het tijdelijke wachtwoord wordt eenmalig getoond; tweestapsverificatie is verplicht.
- In `/arts` vult de arts naam, e-mail, geboortedatum, BSN (elfproef), mobiel nummer en het HIS-patiënt-ID in. De patiënt krijgt een e-mail met een persoonlijke link (14 dagen geldig).
- Tweede controle naar keuze: een sms-code, of een **code op papier**: de arts krijgt een 6-cijferige code (eenmalig getoond, alleen als HMAC bewaard) die hij de patiënt meegeeft. In `/arts` kan per openstaande uitnodiging een nieuwe papieren code worden aangemaakt (de oude vervalt).
- De patiënt maakt een account of logt in, bevestigt geboortedatum en BSN (vergeleken via HMAC; nooit leesbaar opgeslagen) en voert daarna een 6-cijferige sms-code in die naar het door de arts opgegeven nummer gaat (10 minuten geldig, maximaal 3 keer versturen, 5 pogingen).
- Daarna bevestigt de arts met twee vinkjes. Lezers zien arts, praktijk, datum en de laatste 6 tekens van het HIS-ID (ook in de PDF). Patiënt en arts kunnen de koppeling intrekken.
- SMS: `SMS_DRIVER=log` (ontwikkeling, schrijft naar `storage/logs/sms.log`) of `SMS_DRIVER=bird`. De Bird-koppeling (bird.com) is nog niet gebouwd: zie `src/Sms.php`, methode `bird()`. Zonder werkende sms-dienst kan een patiënt in productie niet koppelen (er wordt nooit stilzwijgend "verstuurd").

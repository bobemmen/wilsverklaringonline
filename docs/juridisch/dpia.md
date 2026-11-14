# Gegevensbeschermingseffectbeoordeling (DPIA) – WilsVerklaring pilot

**Status:** concept, versie 0.1 · **Datum:** *[invullen]* · **Opgesteld door:** *[naam]* · **Getoetst door FG/privacyadviseur:** *[naam, datum]*

## 0. Waarom een DPIA

De verwerking betreft gezondheidsgegevens, wensen rond levenseinde en levensovertuiging (bijzondere categorieën, art. 9 AVG) van mogelijk kwetsbare personen (ouderen, mensen met beginnende dementie), met een nieuwe online dienst. De Autoriteit Persoonsgegevens vraagt een DPIA voor grootschalige verwerking van gezondheidsgegevens. Of een kleine pilot formeel “grootschalig” is, is twijfelachtig; wij voeren de DPIA uit omdat de risico's voor betrokkenen hoog kunnen zijn en omdat de dienst kan groeien.

## 1. Beschrijving van de verwerking

**Doel.** Patiënten stellen online een wilsverklaring op (behandeling, euthanasieverzoek, dementie, volmacht), bewaren en delen die. De huisarts bevestigt in persoon de identiteit van de patiënt, zodat dit bij de verklaring zichtbaar is.

**Stromen.**

1. De huisarts ziet de patiënt in een consult, controleert de identiteit, en voert naam, e-mailadres, geboortedatum en HIS-patiënt-ID in. De applicatie maakt een code van 6 cijfers; de arts geeft die op papier mee. De patiënt krijgt een e-mail met de registratielink (zonder code).
2. De patiënt registreert met naam, e-mailadres, geboortedatum, wachtwoord en code (code én geboortedatum moeten bij de uitnodiging passen), en geeft toestemming (pilotinformatie en privacyverklaring). De identiteit is daarmee bevestigd.
3. De patiënt stelt de verklaring op en publiceert die. De artsen van de praktijk kunnen de gepubliceerde verklaring inzien; elke inzage wordt gelogd en is zichtbaar voor de patiënt.
4. De patiënt kan de verklaring delen via een geheime link (arts) of uitnodiging (naaste), de huisartsbevestiging intrekken, een PDF downloaden en het account verwijderen.

**Categorieën betrokkenen.** Patiënten (volwassenen); huisartsen van de pilotpraktijk; naasten die een patiënt uitnodigt.

**Categorieën gegevens.** Zie `rollen-en-grondslagen.md` §2. Bijzonder: inhoud van de wilsverklaring. Identificerend: geboortedatum (versleuteld bewaard, alleen zichtbaar voor artsen van de praktijk) en HIS-ID. De geboortedatum is een extra controlefactor, geen sterke: hij is makkelijk te raden. De kracht van de identificatie zit in de persoonlijke controle door de arts en de papieren code. Er wordt geen BSN verwerkt.

**Ontvangers.** Artsen van de praktijk; door de patiënt gekozen personen; hoster en mailprovider (versleutelde gegevens, resp. e-mails zonder verklaring-inhoud).

**Bewaartermijnen.** Account en verklaring tot de patiënt verwijdert; ongebruikte uitnodigingen 14 dagen geldig + 30 dagen bewaard (`bin/console purge`); sessies 2 uur inactiviteit; back-ups *[termijn vaststellen]*.

**Doorgifte buiten de EER.** Nee (eis aan hosting en mail).

## 2. Noodzaak en proportionaliteit

- **Grondslag:** uitdrukkelijke toestemming (zie `rollen-en-grondslagen.md`). Vrij en ingetrokken: de patiënt kan zonder gevolgen voor zijn zorg afzien; intrekken kan via dashboard of account verwijderen.
- **Dataminimalisatie:** de applicatie vraagt geen medisch dossier, alleen wat voor de verklaring en identificatie nodig is. Geen BSN. HIS-ID alleen volledig zichtbaar voor artsen van de praktijk; lezers van de verklaring zien een afgeschermde variant.
- **Doelbinding:** gegevens worden niet voor andere doelen gebruikt; geen analyse of tracking.
- **Transparantie:** pilotinformatie, privacyverklaring, inzagelog voor de patiënt, e-mails bij registratie en intrekking.
- **Rechten van betrokkenen:** inzage/export (JSON), correctie (nieuwe versie), verwijdering (account), intrekken (dashboard). Overige verzoeken via privacycontact, binnen een maand.
- **BSN:** besloten om het BSN niet te verwerken (`rollen-en-grondslagen.md` §4); daarmee vervalt de vraag naar een rechtsgrond voor het BSN.

## 3. Risico's en maatregelen

Schaal: kans/impact laag (L), midden (M), hoog (H) vóór maatregelen → restrisico.

| # | Risico | K | I | Maatregelen (aanwezig = **A**, nog te doen = **T**) | Rest |
|---|---|---|---|---|---|
| 1 | Overname van een artsaccount (phishing, wachtwoord hergebruik) leidt tot valse identiteitsbevestigingen en inzage in verklaringen | M | H | **A** verplichte 2FA (TOTP) bij eerste login; **A** herstelcodes, eenmalig, alleen als HMAC bewaard; **A** wachtwoord ≥12 tekens bij artsen, verplichte wijziging na tijdelijk wachtwoord; **A** limiet van 30 uitnodigingen per uur; **A** inzagelog zichtbaar voor patiënt; **T** passkeys/WebAuthn; **T** melding aan beheerder bij ongebruikelijk veel uitnodigingen | L–M |
| 2 | Kwaadwillende of nieuwsgierige arts leest verklaringen zonder zorgreden (binnen de praktijk kunnen alle artsen alle patiënten inzien) | M | M | **A** elke inzage gelogd met naam arts, zichtbaar voor patiënt; **A** alleen gepubliceerde verklaringen; **A** gebruiksvoorwaarden (inzage alleen met zorgreden); **A** patiënt kan toegang van de praktijk intrekken; **T** periodieke steekproef op het log door de praktijk; **T** optie om per patiënt inzage te beperken tot de eigen arts | L–M |
| 3 | Misbruik van de papieren code (verloren, onderschept) | L | M | **A** code alleen bruikbaar samen met het uitgenodigde e-mailadres; kans op raden is klein (6 cijfers, maximaal 5 pogingen, limiet per IP en per e-mailadres); **A** 5 foute pogingen blokkeren de uitnodiging (hinder: de arts maakt dan een nieuwe code); **A** geldig 14 dagen; **A** code niet in e-mail; **A** arts kan nieuwe code maken (oude vervalt) | L |
| 4 | Verkeerde patiënt uitgenodigd (typfout in e-mail) | M | M | **A** arts bevestigt in persoon; **A** registratie vereist code en het uitgenodigde e-mailadres (een verkeerd adres levert de code niet op bij een ander); **A** patiënt ziet naam arts en praktijk; **A** arts kan intrekken en opnieuw uitnodigen | L |
| 5 | Datalek door kwetsbaarheid of fout in de applicatie | M | H | **A** alle gegevens versleuteld (XChaCha20-Poly1305 per record, aan record gebonden), zoekindex via HMAC; **A** CSP, CSRF, no-store, rate limits; **A** sessies versleuteld; **A** `secure_delete`; **T** pentest/onafhankelijke review; **T** responsible-disclosure-adres | M |
| 6 | Toegang tot server én `APP_KEY` door beheerder/hoster/aanvaller → alles leesbaar | L | H | **A** `APP_KEY` buiten de database; **A** database 0600, map 0700; **T** sleutel buiten de server bewaren (KMS/secret store); **T** serverbeheer loggen; **T** geheimhouding en verwerkersovereenkomst; **T** aparte sleutel per praktijk bij opschaling | M |
| 7 | Verlies van `APP_KEY` of database: wilsverklaringen onbereikbaar op het moment dat ze nodig zijn | L | H | **A** `bin/console backup`; **T** sleutelkopieën op twee plekken, gescheiden van back-ups; **T** restore-test vóór de pilot; **A** patiënt kan PDF downloaden en bewaart papieren versie (vermeld in pilotinformatie) | M |
| 8 | Wilsverklaring niet beschikbaar in een noodsituatie (storing) | M | M | **A** pilotinformatie: bewaar papieren versie; **A** PDF-export; **T** beschikbaarheids- en storingsprotocol | M |
| 9 | Patiënt verliest toegang (telefoon kwijt) | M | L | **A** herstelcodes; **A** beheerder kan 2FA van een arts resetten; **T** wachtwoord-vergeten via eenmalige link van 60 min (herstelcode alleen als HMAC bewaard, sessies beëindigd, 2FA blijft verplicht); **T** hersteltraject voor patiënten (patiënt-2FA-reset via praktijk, na identificatie) | L |
| 10 | Arts vertrekt: patiënten zonder arts, toegang van oud-arts | M | M | **A** arts verwijderen sluit sessies en toegang; patiënten blijven bij de praktijk; collega kan overnemen; historie wordt vastgelegd; **A** de bevestiging (naam bevestigende arts) blijft behouden als bewijs | L |
| 11 | BSN-verwerking zonder rechtsgrond | — | — | **A** besloten: geen BSN verwerken (optie B); risico vervalt | Vervallen |
| 12 | Opgeslagen gegevens over mensen die geen account nemen (uitnodiging) | M | L | **A** uitnodigingen verlopen na 14 dagen, opgeruimd na 30 dagen extra (`purge`); **T** purge periodiek plannen (cron) | L |
| 13 | Inzage- of exportverzoeken niet tijdig afgehandeld | L | L | **A** self-service export en verwijdering; **T** procedure en privacycontact | L |
| 14 | Phishing met de naam van de dienst (nepmail “uw code”) | M | M | **A** uitnodigingsmail bevat geen code; **A** link naar eigen domein; **T** SPF/DKIM/DMARC; **T** uitleg in pilotinformatie | L–M |
| 15 | Onbedoelde inzage door naasten of via gelekte links | M | M | **A** links intrekbaar, verloopdatum, geheime tokens (de code van de arts-link wordt versleuteld in de verklaring bewaard, zodat de patiënt hem kan terugzien en in de pdf kan zetten; een databaselek zonder sleutel geeft dus geen links); **A** arts-link met e-mailbevestiging; **A** inzagelog | L |
| 16 | Demo-omgeving: bezoeker vult toch echte gegevens in, of misbruik van het openbare demo-startpunt | M | M | **A** alleen indien `DEMO_ENABLED=true`; **A** eigen tijdelijk account per bezoeker, niemand ziet andermans gegevens; **A** automatisch verwijderd na 2 uur of bij afsluiten; **A** duidelijke waarschuwing “vul geen echte gegevens in”; **T** limiet per IP (10 per uur) en maximaal 50 tegelijk; **T** nooit mail naar demo-adressen; demo-accounts staan niet in het overzicht van huisartsen | L |

## 4. Maatregelen vóór de start (samenvatting)

1. ~~Besluit over BSN~~ genomen: geen BSN (optie B).
2. Verwerkersovereenkomsten (praktijk–exploitant; exploitant–hoster; exploitant–mailprovider).
3. Back-upbeleid, sleutelkopieën en een geslaagde restore-test.
4. Pentest of review; meldadres voor kwetsbaarheden.
5. Datalekprocedure bekend bij praktijk en exploitant.
6. Hostingkeuze binnen de EU.
7. `bin/console purge` periodiek (dagelijks) plannen.

## 5. Conclusie en advies

Met de genoemde maatregelen is het restrisico voor een kleine pilot met één praktijk aanvaardbaar, **mits** de restore-test en verwerkersovereenkomsten zijn afgerond. Blijft een hoog restrisico bestaan na de maatregelen, dan vraagt de wet voorafgaande raadpleging van de Autoriteit Persoonsgegevens (art. 36 AVG).

Opschalen naar meerdere praktijken vraagt een nieuwe DPIA (aparte sleutels per praktijk, beheerrollen, certificering NEN 7510).

## 6. Vaststelling

| | Naam | Datum | Handtekening |
|---|---|---|---|
| Exploitant | | | |
| Praktijk (verantwoordelijke) | | | |
| FG / privacyadviseur | | | |

Herbeoordeling: bij elke grote wijziging, en uiterlijk na de pilot of binnen 12 maanden.

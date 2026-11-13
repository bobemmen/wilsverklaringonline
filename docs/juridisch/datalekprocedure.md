# Datalekprocedure

*Concept. Hoort bij de verwerkersovereenkomst en de DPIA. Verantwoordelijken moeten dit kennen vóór de pilot start.*

## 1. Wat is een datalek?

Een inbreuk op de beveiliging die leidt tot vernietiging, verlies, wijziging of onbevoegde verstrekking van of toegang tot persoonsgegevens, per ongeluk of met opzet. Voorbeelden hier: een artsaccount is overgenomen; een back-up of `.env` lekt; een e-mail met uitnodiging gaat naar de verkeerde persoon; een fout in de website toont gegevens aan een ander; ransomware; verloren laptop of telefoon met toegang tot het systeem; een medewerker leest zonder reden verklaringen.

**Twijfel? Meld het.** Elke medewerker die iets verdachts ziet, meldt het direct (zie §3). Beoordelen of er sprake is van een lek doen wij samen.

## 2. Wie is verantwoordelijk voor welke melding?

| Gegevens | Verantwoordelijke (meldt aan AP en betrokkenen) | Ondersteunt |
|---|---|---|
| Patiëntaccount en inhoud van de verklaring (A, C-deel, D, F) | **Exploitant** | Praktijk |
| Identiteitsbevestiging, uitnodigingen, artsaccounts (B, E) | **Praktijk** | Exploitant (verwerker) |
| Onduidelijk of beide | Meld aan beide; zij besluiten samen binnen 24 uur wie meldt |  |

De **72 uur** gelden voor de verwerkingsverantwoordelijke en beginnen op het moment dat die er kennis van heeft genomen. Een verwerker die iets ontdekt, meldt het binnen **24 uur** aan de verantwoordelijke (zie verwerkersovereenkomst art. 8). Het weekend telt mee.

## 3. Wie bel je? (invullen vóór de pilot)

| Rol | Naam | Telefoon (24/7) | E-mail |
|---|---|---|---|
| Incidentcoördinator exploitant (technisch) | | | |
| Contactpersoon praktijk (praktijkhouder/FG) | | | |
| Privacyadviseur / FG | | | |
| Hoster (storingsmelding) | | | |

## 4. Stappenplan

**Stap 1 – Ontdekken en melden (direct).** Wie het ziet, meldt het aan de incidentcoördinator en schrijft op: tijdstip, wat er gebeurde, hoe het is ontdekt. Dit tijdstip is **T0**.

**Stap 2 – Indammen (binnen 4 uur).** Beperk de schade, noteer elke handeling:

- Account verdacht? Wijzig wachtwoord en zet 2FA terug: `php bin/console user:reset-2fa <email>`; een beheerder kan in het teamscherm "Nieuw wachtwoord" en "2FA resetten" gebruiken. Dit sluit ook alle sessies van die gebruiker.
- Alle sessies beëindigen (bij brede aanval): `sqlite3 <database> "DELETE FROM sessions"`.
- Uitnodigingen of artsaccounts misbruikt? Ruim de uitnodigingen op via het arts-scherm (intrekken) en verwijder het account.
- Lekt `.env`/`APP_KEY` of een database-back-up? Behandel alle gegevens als blootgesteld. Nieuwe sleutel en herversleuteling is op dit moment **niet automatisch** mogelijk (ontwikkelpunt: sleutelrotatie). Neem direct contact op met de privacyadviseur.
- Bewijs veilig stellen: serverlogs (`storage/logs`), webserverlog, database-kopie.

**Stap 3 – Registreren (binnen 24 uur).** Leg elk incident vast in het **datalekregister** (ook als niet meldplichtig, art. 33 lid 5 AVG): datum/tijd T0, feiten, betrokken gegevens, aantallen, gevolgen, genomen maatregelen, besluit melden ja/nee en waarom.

**Stap 4 – Beoordelen of melden aan de AP nodig is.** Meld **tenzij** het niet waarschijnlijk is dat de inbreuk een risico oplevert voor de rechten en vrijheden van personen. Bij gezondheidsgegevens en wilsverklaringen is dat risico meestal aanwezig. Afwegen: soort gegevens (bijzonder), omvang, identificeerbaarheid, ernst gevolgen, kwetsbaarheid van betrokkenen, of gegevens versleuteld waren en of de sleutel meegelekt is. Versleutelde gegevens zonder gelekte sleutel verkleinen het risico, maar de verantwoordelijke besluit.

**Stap 5 – Melden aan de AP (binnen 72 uur na T0).** Via het Meldloket Datalekken van de Autoriteit Persoonsgegevens (website van de AP). Is nog niet alles bekend? Doe een **voorlopige melding** met wat je weet en vul later aan. Een late melding vergt een onderbouwing.

**Stap 6 – Melden aan betrokkenen (onverwijld, bij hoog risico).** Bij waarschijnlijk hoog risico informeert de verantwoordelijke de patiënten (en artsen) in heldere taal: wat gebeurde er, welke gegevens, mogelijke gevolgen, wat is gedaan, wat kunt u doen (bijvoorbeeld wachtwoord wijzigen), contactpunt. Gebruik e-mail en, voor kwetsbare patiënten, zo nodig bellen of een bericht via de praktijk.

**Stap 7 – Herstellen en leren (binnen 2 weken).** Oorzaak wegnemen, test of het weg is, maatregelen aan de DPIA en de beveiliging toevoegen, korte evaluatie (wat ging goed/niet), register bijwerken. Eventueel de pentest herhalen.

## 5. Tijdlijn in één oogopslag

| Moment | Actie | Wie |
|---|---|---|
| T0 | Ontdekt; melden aan incidentcoördinator | Iedereen |
| T0 + 4 uur | Indammen gestart | Exploitant |
| T0 + 24 uur | Verwerker meldt aan verantwoordelijke; incident geregistreerd; besluit wie meldt | Exploitant / praktijk |
| T0 + 72 uur | Melding aan AP (voorlopig mag) | Verantwoordelijke |
| Onverwijld bij hoog risico | Patiënten informeren | Verantwoordelijke |
| T0 + 2 weken | Evaluatie, maatregelen, register | Beiden |

## 6. Sjabloon voor de melding (invulblad)

- Datum/tijd ontdekking (T0) en wie ontdekte het:
- Aard van het incident (wat, hoe, hoe lang al):
- Categorieën betrokkenen en geschat aantal:
- Categorieën gegevens (bijzonder? — er worden geen BSN's verwerkt) en geschat aantal records:
- Waren de gegevens versleuteld? Is de sleutel mogelijk bekend?
- Waarschijnlijke gevolgen voor betrokkenen:
- Genomen en voorgenomen maatregelen:
- Zijn betrokkenen geïnformeerd? Wanneer, hoe:
- Contactpersoon (naam, telefoon, e-mail):
- Gemeld aan de AP: ja/nee, datum, kenmerk:

## 7. Oefenen en onderhoud

Oefen de procedure vóór de start (tafeloefening van 30 minuten met praktijk en exploitant) en daarna jaarlijks. Houd de contactlijst (§3) actueel. Bewaar het datalekregister minimaal [5] jaar.

## 8. Bronnen

AVG art. 33 en 34 (melden aan AP en aan betrokkenen); AP: Meldloket Datalekken en richtlijnen; KNMG-handreiking meldplicht datalekken in de eerstelijnszorg. Controleer of een melding aan een andere toezichthouder nodig is (bijvoorbeeld de Inspectie Gezondheidszorg en Jeugd) via de privacyadviseur van de praktijk.

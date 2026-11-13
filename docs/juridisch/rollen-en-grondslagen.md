# Rollen, verantwoordelijkheden en grondslagen

*Concept. Laat toetsen door een jurist of FG.*

## 1. Partijen

| Partij | Wie | Rol |
|---|---|---|
| Patiënt | Persoon die een wilsverklaring maakt | Betrokkene |
| Praktijk | [naam pilotpraktijk] (huisartsenpraktijk, zorgaanbieder) | Verwerkingsverantwoordelijke voor de identiteitsbevestiging |
| Exploitant | *[naam rechtspersoon]* | Verwerkingsverantwoordelijke voor het patiëntaccount en de inhoud van de verklaring; verwerker voor de praktijk bij de identiteitsbevestiging |
| Hoster | *[nog te kiezen]* | Sub-verwerker |
| Mailprovider | *[nog te kiezen / eigen SMTP]* | Sub-verwerker |

## 2. Verwerkingen en wie waarvoor verantwoordelijk is

| # | Verwerking | Gegevens | Verantwoordelijke | Verwerker | Grondslag |
|---|---|---|---|---|---|
| A | Patiëntaccount en wilsverklaring (opstellen, bewaren, versies, delen met wie de patiënt kiest) | Naam, e-mailadres, wachtwoord (hash), inhoud van de verklaring (bijzondere gegevens: gezondheid, levensbeëindiging, levensovertuiging), voorkeuren | Exploitant | Hoster, mailprovider | Uitdrukkelijke toestemming patiënt (art. 6 lid 1 sub a en art. 9 lid 2 sub a AVG), vastgelegd bij registratie |
| B | Identiteitsbevestiging door de huisarts (uitnodiging, code op papier, bewijs bij de verklaring) | Naam, e-mailadres, HIS-patiënt-ID, naam arts, datum | Praktijk | Exploitant (en diens verwerkers) | Toestemming patiënt (mondeling gevraagd bij de uitnodiging, schriftelijk bij registratie) |
| C | Inzage door artsen van de praktijk in de gepubliceerde verklaring | Inhoud van de gepubliceerde verklaring | Praktijk (zodra de arts de verklaring leest); Exploitant stelt beschikbaar | Exploitant | Toestemming patiënt; intrekbaar via het dashboard |
| D | Toegangslog (wie las wanneer; IP-adres, apparaat) | Arts-/link-gegevens, IP, user agent, tijd | Exploitant | Hoster | Gerechtvaardigd belang (art. 6 lid 1 sub f) en beveiligingsplicht (art. 32); transparantie naar de patiënt |
| E | Beheer van artsaccounts (team) | Naam, e-mailadres, rol, 2FA-gegevens, herstelcodes (hash) | Praktijk | Exploitant | Uitvoering arbeids-/opdrachtrelatie en beveiligingsplicht |
| F | Technische gegevens (sessies, rate limits) | Versleutelde sessie, HMAC-tellers | Exploitant | Hoster | Gerechtvaardigd belang (beveiliging) |

**Aanbeveling:** leg de verdeling A/C (exploitant) en B/E (praktijk) vast in de verwerkersovereenkomst en in de privacyverklaring (gebeurd). Laat een jurist beoordelen of de praktijk en de exploitant voor verwerking C gezamenlijk verantwoordelijk zijn (art. 26 AVG); in dat geval is een korte regeling nodig.

## 3. Geheimhouding

Artsen hebben beroepsgeheim. De exploitant legt iedereen met toegang tot de productiegegevens geheimhouding op (verwerkersovereenkomst art. 4). De applicatie heeft geen beheerfunctie om verklaringen te lezen; de sleutel (`APP_KEY`) staat op de server. Technisch kan wie de server **en** de sleutel beheert de gegevens dus ontsleutelen. Dat risico staat in de DPIA met maatregelen (beperkte toegang, logging van serverbeheer, sleutelbewaring buiten de server op termijn).

## 4. BSN: besluit

**Besluit (optie B): de applicatie vraagt, verwerkt en bewaart geen BSN.** Uitnodigen gebeurt met naam, e-mailadres en HIS-patiënt-ID. De registratie van de patiënt wordt gekoppeld aan de uitnodiging via het e-mailadres en de code op papier. De huisarts controleert de identiteit in persoon, en dat is het bewijs bij de verklaring.

**Waarom.** Art. 46 UAVG staat het gebruik van een nummer ter identificatie, zoals het BSN, alleen toe als de wet dat voorschrijft. Voor zorgaanbieders bepaalt art. 4 van de Wet gebruik burgerservicenummer in de zorg (Wbsn-z) dat zij het BSN gebruiken om te waarborgen dat gegevens bij de juiste cliënt horen. Of de exploitant dat als verwerker namens de praktijk ook mag, en of het opstellen van een wilsverklaring daaronder valt, is niet vastgesteld. Door het BSN niet te verwerken vervalt die vraag.

**Gevolgen.**

- Iets minder koppeling tussen uitnodiging en persoon: wie het uitgenodigde e-mailadres én de code op papier heeft, kan registreren. De code is 6 cijfers met maximaal 5 pogingen en limieten per IP en per e-mailadres; de papieren code komt niet in de e-mail.
- Een onbevoegde kan met 5 foute pogingen een uitnodiging blokkeren (hinder, geen toegang); de arts maakt dan een nieuwe code.
- Zoeken door de arts kan op naam, e-mailadres en HIS-ID (niet op BSN).

**Later terugbrengen?** Dat kan alleen na een schriftelijke juridische toetsing (verwerking namens de zorgaanbieder onder art. 4 Wbsn-z, vastgelegd in de DPIA) of via DigiD (aansluiting via Logius). Het hoort dan bij een aparte wijziging van de verwerkersovereenkomst, DPIA en privacyverklaring.

## 5. Verhouding patiënt – praktijk – exploitant in de praktijk

- De huisarts nodigt alleen uit na een consult, en na mondelinge instemming van de patiënt (vinkje in het uitnodigingsformulier).
- De patiënt geeft bij registratie schriftelijk toestemming (vinkje, met tijdstip en tekstversie vastgelegd).
- Toestemming intrekken kan door de verificatie in te trekken (dan is de verklaring niet meer inzichtelijk voor de artsen van de praktijk) of het account te verwijderen.
- De praktijk is zelf verantwoordelijk voor wie in haar team toegang krijgt (praktijkbeheerder) en voor het intrekken van accounts bij vertrek. Verwijdert een beheerder een arts, dan blijven patiënten bij de praktijk staan.

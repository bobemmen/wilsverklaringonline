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
| B | Identiteitsbevestiging door de huisarts (uitnodiging, code op papier, bewijs bij de verklaring) | Naam, e-mailadres, HIS-patiënt-ID, BSN (alleen als controlewaarde), naam arts, datum | Praktijk | Exploitant (en diens verwerkers) | Toestemming patiënt (mondeling gevraagd bij de uitnodiging, schriftelijk bij registratie). Voor de BSN-controle: zie §4 |
| C | Inzage door artsen van de praktijk in de gepubliceerde verklaring | Inhoud van de gepubliceerde verklaring | Praktijk (zodra de arts de verklaring leest); Exploitant stelt beschikbaar | Exploitant | Toestemming patiënt; intrekbaar via het dashboard |
| D | Toegangslog (wie las wanneer; IP-adres, apparaat) | Arts-/link-gegevens, IP, user agent, tijd | Exploitant | Hoster | Gerechtvaardigd belang (art. 6 lid 1 sub f) en beveiligingsplicht (art. 32); transparantie naar de patiënt |
| E | Beheer van artsaccounts (team) | Naam, e-mailadres, rol, 2FA-gegevens, herstelcodes (hash) | Praktijk | Exploitant | Uitvoering arbeids-/opdrachtrelatie en beveiligingsplicht |
| F | Technische gegevens (sessies, rate limits) | Versleutelde sessie, HMAC-tellers | Exploitant | Hoster | Gerechtvaardigd belang (beveiliging) |

**Aanbeveling:** leg de verdeling A/C (exploitant) en B/E (praktijk) vast in de verwerkersovereenkomst en in de privacyverklaring (gebeurd). Laat een jurist beoordelen of de praktijk en de exploitant voor verwerking C gezamenlijk verantwoordelijk zijn (art. 26 AVG); in dat geval is een korte regeling nodig.

## 3. Geheimhouding

Artsen hebben beroepsgeheim. De exploitant legt iedereen met toegang tot de productiegegevens geheimhouding op (verwerkersovereenkomst art. 4). De applicatie heeft geen beheerfunctie om verklaringen te lezen; de sleutel (`APP_KEY`) staat op de server. Technisch kan wie de server **en** de sleutel beheert de gegevens dus ontsleutelen. Dat risico staat in de DPIA met maatregelen (beperkte toegang, logging van serverbeheer, sleutelbewaring buiten de server op termijn).

## 4. BSN: rechtsgrond (belangrijkste open punt)

**Wat wij doen.** De arts voert het BSN in; de patiënt voert het bij registratie opnieuw in. De applicatie slaat het BSN nooit leesbaar op; er wordt alleen een HMAC bewaard om te controleren dat beide ingevoerde nummers gelijk zijn, en om de arts exact op BSN in zijn eigen praktijk te laten zoeken. Het BSN wordt niet gebruikt voor andere doelen en niet gedeeld.

**Het juridische vraagstuk.** Volgens art. 46 UAVG mag een nummer dat ter identificatie van een persoon wordt gebruikt, zoals het BSN, alleen worden verwerkt als de wet dat voorschrijft of toestaat. Voor zorgaanbieders staat in art. 4 Wabvpz dat zij het BSN gebruiken om te waarborgen dat gegevens bij de juiste cliënt horen. De exploitant is zelf geen zorgaanbieder. Ons voorstel is dat de exploitant het BSN uitsluitend namens de praktijk (zorgaanbieder) verwerkt, als verwerker. Of het opstellen van een wilsverklaring en het bevestigen van de identiteit daarvoor onder “zorg” in de zin van de Wabvpz valt, en of de exploitant als verwerker mag meekijken, is **niet vastgesteld**.

**Opties.**

- **Optie A – BSN behouden na juridische toetsing.** Laat een jurist/FG schriftelijk bevestigen dat de verwerking namens de zorgaanbieder valt onder art. 4 Wabvpz, en leg dat vast in de DPIA. Voordeel: sterkste koppeling tussen uitnodiging en patiënt. Nadeel: tijd, en risico op een afwijkend oordeel.
- **Optie B – BSN uit de registratie halen.** Koppel alleen op e-mailadres + code op papier + naam. Het HIS-ID en de code blijven het bewijs; de arts heeft de identiteit in persoon gecontroleerd. Voordeel: geen BSN-vraagstuk, minder risico. Nadeel: iets minder bescherming tegen een verkeerd e-mailadres. Technisch een kleine wijziging (BSN-veld optioneel maken in uitnodiging, registratie en zoeken).
- **Optie C – DigiD later.** Identificatie via DigiD (aansluiting via Logius, voorwaarden) vervangt het zelf invoeren van het BSN. Vergt een aansluitingsproces en past eerder bij een groter platform.

**Advies:** voor de pilot optie B kiezen tenzij een toetsing vóór de start optie A bevestigt. Beslis vóór de eerste echte uitnodiging.

## 5. Verhouding patiënt – praktijk – exploitant in de praktijk

- De huisarts nodigt alleen uit na een consult, en na mondelinge instemming van de patiënt (vinkje in het uitnodigingsformulier).
- De patiënt geeft bij registratie schriftelijk toestemming (vinkje, met tijdstip en tekstversie vastgelegd).
- Toestemming intrekken kan door de verificatie in te trekken (dan is de verklaring niet meer inzichtelijk voor de artsen van de praktijk) of het account te verwijderen.
- De praktijk is zelf verantwoordelijk voor wie in haar team toegang krijgt (praktijkbeheerder) en voor het intrekken van accounts bij vertrek. Verwijdert een beheerder een arts, dan blijven patiënten bij de praktijk staan.

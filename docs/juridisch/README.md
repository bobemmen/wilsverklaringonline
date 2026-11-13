# Juridische stukken voor de pilot

**Status: concepten.** Deze stukken zijn zorgvuldig opgesteld maar niet door een jurist of functionaris gegevensbescherming (FG) getoetst. Laat ze toetsen vóór de eerste echte patiënt. Alles tussen [rechte haken] of *cursief tussen haken* moet nog worden ingevuld of besloten.

| Bestand | Inhoud | Voor wie |
|---|---|---|
| `rollen-en-grondslagen.md` | Wie is verantwoordelijke/verwerker, grondslagen, **besluit: geen BSN**, wie is waarvoor verantwoordelijk | Besluit vooraf |
| `dpia.md` | Gegevensbeschermingseffectbeoordeling (DPIA) | Exploitant + praktijk |
| `verwerkersovereenkomst.md` | Overeenkomst tussen praktijk en exploitant (art. 28 AVG) | Ondertekenen |
| `datalekprocedure.md` | Wie meldt wat, binnen 72 uur, aan wie | Intern + praktijk |
| `pilotinformatie.md` | Korte uitleg + toestemming voor patiënten (getoond op `/pilot`) | Patiënten |
| `privacyverklaring.md` | Art. 13 AVG (getoond op `/privacy`) | Patiënten |
| `gebruiksvoorwaarden.md` | Voorwaarden patiënten en artsen (getoond op `/voorwaarden`) | Patiënten, artsen |

De drie patiëntteksten worden in de app getoond. Naam en adres van de exploitant komen uit `.env` (`OPERATOR_NAME`, `OPERATOR_ADDRESS`, `PRIVACY_CONTACT_EMAIL`, `PILOT_PRACTICE_NAME`). Wijzigt een van de drie inhoudelijk, verhoog dan `Legal::VERSION` in `src/Legal.php`: bij registratie wordt per gebruiker vastgelegd welke versie hij accepteerde.

## Besluiten en acties vóór de pilot

1. **Wie is de exploitant?** (rechtspersoon of eenmanszaak, KvK, adres, contactpersoon privacy). Alle documenten gaan daarvan uit.
2. **BSN: besloten (optie B).** De applicatie vraagt, verwerkt en bewaart geen BSN meer; er is dus geen BSN-rechtsgrond nodig. Zie `rollen-en-grondslagen.md` §4.
3. **FG of privacyadviseur:** laat DPIA en documenten toetsen; de huisartsenpraktijk heeft vaak al een FG-dienst (via ketenpartner of huisartsenorganisatie).
4. **Hostingkeuze** (nog open) en verwerkersovereenkomsten met hoster en mailprovider; vul bijlage 3 van de verwerkersovereenkomst in.
5. **Sleutelbeheer en back-upbeleid** vastleggen: wie heeft toegang tot `APP_KEY`, waar liggen kopieën, back-upfrequentie en -bewaartermijn (de privacyverklaring noemt een termijn).
6. **Verwerkingsregister** (art. 30 AVG) bijwerken bij de praktijk en bij de exploitant.
7. **Pilotpraktijk informeren:** praktijkmedewerkers lezen `datalekprocedure.md` en `gebruiksvoorwaarden.md` §5.
8. **Pentest of onafhankelijke beveiligingscontrole**, en een meldadres voor kwetsbaarheden (bijvoorbeeld `security@…` en een `security.txt`).
9. **Inhoud van de wilsverklaring** laten toetsen door een arts/jurist (de teksten over euthanasie en dementie), en NVVE informeren over hergebruik van hun materiaal.

## Bronnen

- Algemene verordening gegevensbescherming (AVG): art. 6, 9, 13, 28, 30, 32–35.
- Uitvoeringswet AVG (UAVG): art. 46 (nationaal identificatienummer, zoals het BSN, alleen gebruiken als de wet dat voorschrijft).
- Wet gebruik burgerservicenummer in de zorg (Wbsn-z): art. 4 (zorgaanbieders gebruiken het BSN om te waarborgen dat gegevens bij de juiste cliënt horen) en art. 5 (zorgaanbieder stelt identiteit en BSN van de cliënt vast).
- Rijksoverheid, “Identificatieplicht zorg”: identificatie met paspoort, identiteitskaart, rijbewijs of vreemdelingendocument.
- Autoriteit Persoonsgegevens: lijst van verwerkingen waarvoor een DPIA verplicht is (Staatscourant 2019, 64418; onder meer grootschalige verwerking van gezondheidsgegevens); Meldloket Datalekken.
- KNMG: *Handreiking meldplicht datalekken in de eerstelijnszorg*.
- NEN 7510 (informatiebeveiliging in de zorg), NEN 7512 (vertrouwen bij gegevensuitwisseling), NEN 7513 (logging van toegang tot patiëntgegevens): richtsnoer voor een eventuele certificering later.

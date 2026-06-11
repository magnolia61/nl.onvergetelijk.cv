# nl.onvergetelijk.cv

## Functionele beschrijving

De `cv`-extensie beheert de kamphistorie van deelnemers en begeleiders: een automatisch bijgehouden samenvatting van hoeveel kampen iemand heeft meegemaakt, in welke jaren en in welke rol. Deze "CV"-velden worden gebruikt voor werving, selectie en het overzicht op het contactprofiel.

Wanneer een inschrijving wordt bijgewerkt, berekent `cv` op basis van alle historische en huidige participaties de gecombineerde tellers: aantal kampen als deelnemer, als begeleider, totaal aantal jaren, enzovoorts. Handmatig ingevoerde correcties op het formulier hebben voorrang boven de automatisch berekende waarden.

## Afhankelijkheden

- `nl.onvergetelijk.base`

---

## Technische documentatie

### Kernfuncties

- `cv_get_field_map()` — geeft de mapping terug van CV-veldnamen naar CiviCRM API-namen (LEID custom field group).
- `cv_civicrm_customPre($op, $groupID, $entityID, &$params)` — pre-hook: extraheert de handmatig ingevoerde waarden uit het formulier en injecteert berekende waarden terug als de formulierwaarden leeg zijn.
- `cv_civicrm_configure($contactid, $array_contact, $ditjaar_array, $context, $params_cv)` — de hoofdmotor:
  1. Configdata en status-IDs ophalen
  2. Leidende waarden bepalen: formulierwaarden hebben prioriteit over DB-waarden
  3. Deelnemer- en leidingshistorie ophalen via base helpers
  4. Consolidatielogica: tellers berekenen voor deel én leid
  5. Opslaan: via API-call of retour via hook-params (afhankelijk van context)

### Updatestrategieën
`cv_civicrm_configure` kent twee uitvoerpaden:
- **`context = 'direct'`**: slaat op via een APIv4-update-call
- **`context = 'hook'`**: geeft de waarden terug in de `$params` array, zodat de aanroepende hook ze zelf opslaat (efficiënter, minder API-calls)

### Hooks geïmplementeerd
- `civicrm_customPre`
- `civicrm_install`, `civicrm_uninstall`, `civicrm_enable`, `civicrm_disable`

### Custom field group
Schrijft naar de **LEID**-custom field group op contactniveau.

---

*Beheerd door Stichting Onvergetelijke Zomerkampen.*

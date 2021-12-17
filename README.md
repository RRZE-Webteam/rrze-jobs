# RRZE Jobs
Einbindung von Jobangeboten per Shortcode über API (zurzeit Interamt, UnivIS, BITE)

**Anbindung an BITE ist in Entwicklung**


## Download
GITHub-Repo: https://gitlab.rrze.fau.de/rrze-webteam/rrze-jobs

## Autor
RRZE-Webteam , http://www.rrze.fau.de

## Copryright
GNU General Public License (GPL) Version 2

## Verwendung

Parameter:

|Parameter|UnivIS|Interamt|BITE|Wert|Default|
|---------|------|--------|----|----|-------|
|provider|obligatorisch|obligatorisch|obligatorisch|"univis" oder "interamt" oder "bite"|in den Plugin-Einstellungen festgelegt|
|orgids|optional|optional|wird ignoriert|kommagetrennte Zahl(en)||
|internal|optional|optional|wird ignoriert|"only" => ausschliesslich interne Stellenanzeigen ausgeben / "include" => interne und nicht-interne Stellenanzeigen ausgeben / "exclude" => ausschliesslich nicht-interne Stellenanzeigen ausgeben|exclude|
|limit|optional|optional|optional|maximale Anzahl an Ergebnissen - unabhängig davon, wieviele orgids angeben wurden||
|fallback_apply|optional|optional|optional|eMail-Adresse oder Link, über den die Bewerbung erfolgen soll, wenn weder eMail-Adresse noch Bewerbungslink im Stellenangebot vorhanden ist|


Beispiele:
```html




[jobs provider="interamt" jobid="123456"]
[jobs provider="UnivIS" jobid="123456"]
[jobs provider="UnivIS" orgids="123456, 98765, 454587"]
[jobs provider="UnivIS" orgids="123456" limit="8"]
[jobs provider="UnivIS" orgids="123456" limit="12" internal="include"]
[jobs provider="interamt jobid="123456" fallback_apply="bewerbung@domain.tld"]
[jobs provider="interamt jobid="123456" fallback_apply="https://domain.tld/bewerbungsformular"]


```
Beispiele und detailierte Informationen zu den Attributen finden Sie unter https://www.wordpress.rrze.fau.de/plugins/fau-und-rrze-plugins/jobs/
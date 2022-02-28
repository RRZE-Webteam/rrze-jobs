# RRZE Jobs
Einbindung von Jobangeboten über API per Shortcode

APIs:
- BITE
- Interamt
- UnivIS


## Download
GITHub-Repo: https://gitlab.rrze.fau.de/rrze-webteam/rrze-jobs

## Autor
RRZE-Webteam , http://www.rrze.fau.de

## Copryright
GNU General Public License (GPL) Version 2

## Verwendung

Parameter:

|Parameter|UnivIS|Interamt|BITE|Wert|Default|Beispiele|
|---------|------|--------|----|----|-------|---------|
|**provider**|obligatorisch|obligatorisch|obligatorisch|"univis" oder "interamt" oder "bite"|univis|provider="bite"|
|**orgids**|optional|optional|wird ignoriert|Zahl -  mehrere werden durch Kommata getrennt||orgids="123,456,789"<br />orgids="4711"|
|**jobid**|optional|optional|optional|Zahl||jobid="123"|
|**internal**|optional|optional|wird ignoriert|"only" => ausschliesslich interne Stellenanzeigen ausgeben<br />"include" => interne und nicht-interne Stellenanzeigen ausgeben<br />"exclude" => ausschliesslich nicht-interne Stellenanzeigen ausgeben|exclude|internal="include"<br />internal="only"|
|**limit**|optional|optional|optional|maximale Anzahl an Ergebnissen - unabhängig davon, wieviele orgids angeben wurden||limit="4"|
|**orderby**|optional|optional|optional|Sortierung nach Titel, Bewerbungsbeginn, -ende oder Arbeitsbeginn|job_title|orderby="job_title"<br />orderby="application_start"<br />orderby="application_end"<br />orderby="job_start"|
|**order**|optional|optional|optional|Auf- oder absteigende Sortierung|DESC|order="ASC" (aufsteigend)<br />order="DESC" (absteigend)|
|**fallback_apply**|optional|optional|optional|eMail-Adresse oder Link, über den die Bewerbung erfolgen soll, wenn weder eMail-Adresse noch Bewerbungslink im Stellenangebot vorhanden ist||fallback_apply="bewerbung@domain.tld"<br />fallback_apply="https://domain.tld/bewerbungsformular"|
|**link_only**|wird ignoriert|wird ignoriert|optional|Ausgabe als Liste ausschliesslich mit Links zu den Stellenangeboten. Mögliche Werte: 'true' oder '1' oder 'false' oder '0'|false|link_only="1"<br />link_only="true"|





Beispiele:
```html

[jobs provider="bite"]
[jobs provider="bite" link_only="1"]
[jobs provider="bite" jobid="123456"]
[jobs provider="interamt" jobid="123456"]
[jobs provider="univis" jobid="123456"]
[jobs provider="univis" orgids="123456, 98765, 454587"]
[jobs provider="univis" orgids="123456" limit="8"]
[jobs provider="univis" orgids="123456" limit="12" internal="include"]
[jobs provider="interamt jobid="123456" fallback_apply="bewerbung@domain.tld"]
[jobs provider="interamt jobid="123456" fallback_apply="https://domain.tld/bewerbungsformular"]


```
Beispiele und detailierte Informationen zu den Attributen finden Sie unter https://www.wordpress.rrze.fau.de/plugins/fau-und-rrze-plugins/jobs/
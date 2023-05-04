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

## Copyright
GNU General Public License (GPL) Version 2

## Verwendete Drittquellen

### Parsedown

http://parsedown.org
The MIT License (MIT)
Copyright (c) 2013-2018 Emanuil Rusev, erusev.com


## Verwendung

Parameter:

|Parameter|UnivIS|Interamt|BITE|Wert|Default|Beispiele|
|---------|------|--------|----|----|-------|---------|
|**provider**|obligatorisch|obligatorisch|obligatorisch|Eine oder mehrere dieser Stellenbörsen: "univis", "interamt", "bite"|alle Provider werden abgefragt|provider="univis, bite" oder provider="univis" oder provider="interamt, bite"|
|**orgids**|optional|optional|wird ignoriert|Zahl -  mehrere werden durch Kommata getrennt||orgids="123,456,789"<br />orgids="4711"|
|**fauorg**|wird ignoriert|wird ignoriert|optional - damit filtern Sie die Stellenanzeigen von BITE anhand der "FAU Org Nummer" Ihrer Einrichtung|Zahl||fauorg="1234567890"|
|**jobid**|optional|optional|optional|Zahl||jobid="123"|
|**limit**|optional|optional|optional|maximale Anzahl an Ergebnissen - unabhängig davon, wieviele orgids angeben wurden||limit="4"|
|**orderby**|optional|optional|optional|Sortierung nach Titel, Bewerbungsbeginn, -ende oder Arbeitsbeginn|job_title|orderby="job_title"<br />orderby="application_start"<br />orderby="application_end"<br />orderby="job_start"|
|**order**|optional|optional|optional|Auf- oder absteigende Sortierung|DESC|order="ASC" (aufsteigend)<br />order="DESC" (absteigend)|
|**fallback_apply**|optional|optional|optional|eMail-Adresse oder Link, über den die Bewerbung erfolgen soll, wenn weder eMail-Adresse noch Bewerbungslink im Stellenangebot vorhanden ist||fallback_apply="bewerbung@domain.tld"<br />fallback_apply="https://domain.tld/bewerbungsformular"|
|**link_only**|wird ignoriert|wird ignoriert|optional|Ausgabe als Liste ausschliesslich mit Links zu den Stellenangeboten. Mögliche Werte: 'true' oder '1' oder 'false' oder '0'|false|link_only="1"<br />link_only="true"|





Beispiele:
```html

Alle Stellenanzeigen vom Stellenportal BITE:
[jobs provider="bite"]

Die Stellenanzeigen Ihrer Einrichtung vom Stellenportal BITE:
[jobs provider="bite" fauorg="1234567890"]

Alle Stellenanzeigen von UnivIS und Interamt, sowie die Stellenanzeigen von BITE Ihrer Einrichtung:
[jobs fauorg="1234567890"]


[jobs provider="bite" jobid="123456"]
[jobs provider="interamt" jobid="123456"]
[jobs provider="univis" jobid="123456"]

[jobs provider="univis" orgids="123456, 98765, 454587"]
[jobs provider="univis" orgids="123456" limit="8"]

[jobs provider="interamt jobid="123456" fallback_apply="bewerbung@domain.tld"]
[jobs provider="univis" jobid="123456" fallback_apply="https://domain.tld/bewerbungsformular"]


```
Beispiele und detailierte Informationen zu den Attributen finden Sie unter https://www.wordpress.rrze.fau.de/plugins/fau-und-rrze-plugins/jobs/
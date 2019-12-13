# RRZE Jobs
Einbindung von Jobangeboten per Shortcode über Portal-API (zurzeit Interamt und UnivIS)
Version 2.2.1

## Download
GITHub-Repo: https://gitlab.rrze.fau.de/rrze-webteam/rrze-jobs

## Autor
RRZE-Webteam , http://www.rrze.fau.de

## Copryright
GNU General Public License (GPL) Version 2

## Verwendung
```html
Parameter:
provider -> Zeichenkette, obligatorisch
orgids -> Zahl(en), obligatorisch (mindestens eine Zahl, mehrere durch Komma getrennt)
limit -> Zahl, optional (maximale Anzahl an Ergebnissen - unabhängig davon, wieviele orgids angeben wurden )
internal -> Zeichenkette, optional, default = "exclude" : 
    mögliche Werte: 
        "only" => ausschliesslich interne Stellenanzeigen ausgeben
        "include" => interne und nicht-interne Stellenanzeigen ausgeben
        "exclude" => ausschliesslich nicht-interne Stellenanzeigen ausgeben 
    - bei "only" und "include" wird überprüft, ob der Visitor sich im erlaubten Netzwerk befindet
fallback_apply -> optional : hier kann eine eMail-Adresse oder ein Link eingeben werden, über den die Bewerbung erfolgen soll, wenn weder eMail-Adresse noch Bewerbungslink im Stellenangebot vorhanden ist 


[jobs provider="interamt" jobid="123456"]
[jobs provider="UnivIS" jobid="123456"]
[jobs provider="UnivIS" orgids="123456, 98765, 454587"]
[jobs provider="UnivIS" orgids="123456" limit="8"]
[jobs provider="UnivIS" orgids="123456" limit="12" internal="include"]
[jobs provider="interamt jobid="123456" fallback_apply="bewerbung@domain.tld"]
[jobs provider="interamt jobid="123456" fallback_apply="https://domain.tld/bewerbungsformular"]


```
Beispiele und detailierte Informationen zu den Attributen finden Sie unter https://www.wordpress.rrze.fau.de/plugins/fau-und-rrze-plugins/jobs/
# RRZE Jobs
Einbindung von Jobangeboten per Shortcode über Portal-API (zurzeit Interamt und UnivIS)

## Download
GITHub-Repo: https://gitlab.rrze.fau.de/rrze-webteam/rrze-jobs

## Autor
RRZE-Webteam , http://www.rrze.fau.de

## Copryright
GNU General Public License (GPL) Version 2

## Verwendung

Parameter:
provider -> Zahl, obligatorisch
orgids -> Zahl(en), obligatorisch (mindestens eine Zahl, mehrere durch Komma getrennt)
limit -> Zahl, optional (maximale Anzahl an Ergebnissen - bei mehreren OrgIDs: insgesamt)
latest -> Boolean (true/false), optional (liefert die neuesten Ergebnisse mit absteigender Sortierung des Datums. Ist "limit" nicht gesetzt, dann werden alle Ergebnisse angezeigt)

```html
[jobs provider="Interamt" jobid="123456"]
[jobs provider="UnivIS" jobid="123456"]
[jobs provider="UnivIS" orgids="123456, 98765, 454587"]
[jobs provider="UnivIS" orgids="123456" limit="8"]
[jobs provider="UnivIS" orgids="123456" limit="4" latest="TRUE"]

```

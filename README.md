# Civi API

API for Timelab

## Endpoints

- `/timelabciviapi/projects`: Retrieve project information

## Technische info

- de URI's van de endpoints staan in timelabciviapi.routing.yml
- in het yml bestand staat per uri welke method van de controller uitgevoerd moet worden

## Foutcodes
- zoeken en niet vinden (bv. /timelabciviapi/individuals?email=jos@test.com): 200 en lege array
- spefieke persoon opvragen (bv. /timelabciviapi/individuals/1234): 404
- verkeerde parameter (bv. /timelabciviapi/individuals?email=): 400
- niet geauthenticeerd: 403
- kan iets niet maken (bv. persoon reeds ingeschreven, event volzet): 422
- andere fouten: 400

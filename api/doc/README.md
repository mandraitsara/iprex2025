# API Application mobile iPrex

[Usage](#usage '') | 
[Configuration](#config '') | 
[Token](#token '') |
[Actions](#actions '') 


Actions :
[debug](#a_debug '') | [install](#a_install '') | [getlotsapi](#a_getlotsapi '') | [sendLotsCaptures](#a_sendLotsCaptures '') 


---  

## Usage ## {#usage}


    {IPREX_URL}/api/{nom_action}[?|&paramètres]
    
    
Exemples :

    http://intersed.info/iprex/api/getlotsapi
    http://intersed.info/iprex/api/getlotsapi?debug


---

## Configuration {#config}

* ``$first_install``
> Force l'installation de l'API (action ``install``) avant l'appel à l'action demandée.
   
* ``$mode_debug``
> Affiche les retours visuels dans le navigateur au lieu des retours aJax.      

---

## Token {#token}

Composition :

* **Sel** : IP du client hors proxy.
> Les ``.`` de l'adresse IP sont remplacés par le caractère ``$`` et le tout encodé en *base64*.
* Une chaine alphanumérique aléatoire de longueur paramétrée.
> Moins la longueur de l'assaisonnement.
* **Poivre** : Timestamp UNIX en microsecondes.
> En *float* et sans le séparateur de décimal ``.`` pour éviter tout conflit *(clef unique)*. 

Exemple pour un token de 128 caractères :
> IP ``192.168.121.28`` deviens `192$168$121$28` puis `MTkyLjE2OC4xMjEuMjg=` (*sel*)
>
> Chaine aléatoire de 94 caractères : 128 caractères - 20 caractères (*sel*) - 14 caractères (*poivre*) : ``$a532322$ci45qcs$p748j$0p9795mytms7t1044r0b7rsp037fq74r62$h$l1193$n8lxs1fu$3g$577pl5fnr7mi``
>
> Timestamp : ``1540995317.9865`` deviens `15409953179865` (*poivre*)

```
MTkyLjE2OC4xMjEuMjg=$a532322$ci45qcs$p748j$0p9795mytms7t1044r0b7rsp037fq74r62$h$l1193$n8lxs1fu$3g$577pl5fnr7mi15409953179865
```


Les données relatives à la gestion des tokens sont enregistrées en base de données.

* Table ``pe_apitokens`` :
> 
| Champ | Type | Description |
| :------- | :------- | :------- |
| `ip` | *varchar(15)* | Adresse IP du client hors proxy pour test de validité du token qui comprend le sel de l'IP. |
| `token` | *varchar(255)* | Token généré. |
| `expire` | *datetime* | Date et heure d'expiration du token défini dans le paramétrage de l'API. |

---

## Actions {#actions}

* ### `debug` {#a_debug}   
> Passez le paramètre ``&debug`` pour forcer le mode débug et retourner un affichage HTML plutôt qu'un retour json.

* ### `install` {#a_install}
> Crée la table en base pour le stockage des tokens
    
* ### `getlotsapi` {#a_getlotsapi}
> Retourne la liste des lots en vue **Réception** et en attente de photos (*action de l'opérateur dans iPrex*) sous la forme d'un array comprennant l'ID, le numéro de lot et un token d'identification.
> 
> Cette action purge tout d'abord les tokens expirés.
>
> Si un lot est associé à un token expiré, l'opérateur devra relancer la demande depuis la vue Réception. La durée de validité des tokens est de 3H, paramétrable dans la configuration de l'API.
>
> Format de retour : *json*
> 
> Exemple :
```
    {[
>    {
>       "id" : 4, 
>       "numlot" : "192001BES", 
>       "token" : "ODIuNjQuN7m8h.....erpdn15478240913019"
>    },
>    {
>        "id" : 2,
>        "numlot":"192003CFR" ,
>        "token" : "HTIuNjQfN79f2.....jsfdn15441243481238"
>     },
>     ...
     ]}
```

* ### `sendLotsCaptures` {#a_sendLotsCaptures}
> Enregistre dans iPrex les photos transmises par l'application Android (*upload* et *BDD*) pour un lot. Plusieurs lots devront exécuter autant d'appels.
>
> Le token reste actif durant toute sa période de validité, permettant d'autres appels ultérieurs.
>
> Format d'entrée *json* : array comprenant le token, l'ID du lot et un array des photos en base 64.
>
> Exemple :
```
    {[
>    {
>    "token" : "ODIuNjQuN7m8h.....erpdn15478240913019",
>    "id_lot" : 4,
>    "photos" : 
>        [
>            "data:image/jpeg;base64,/9j/4QAYRXhpZgAASUkqAAgAAAAA..."
>        ],
>        [
>            "data:image/jpeg;base64,/Iys1xBZYqwstsVm3ZkeZ4lZHaYh..."
>        ],
>        ...
]}
``` 
>
> Valeurs de retour *json* : Vide en cas de réussite, message d'erreur dans le cas contraire :
>
``` 
    {["Token Absent"]}
    {["ID lot absent"]}
    {["Photos absentes"]}
    {["Token invalide"]}
    {["Erreur formatage retour photos (array)"]}
    {["Extension photo invalide (Autorisés : jpg/jpeg/gif/png)"]}
    {["Base64 corrompue"]}
    {["Base64 invalide"]}
    {["Echec durant l'upload"]}
    {["Echec de récupération du type de document"]}
    {["Echec d'enregistrement du document en BDD"]}
``` 
# API Application mobile INTERSED

[Usage](#usage '') | 
[Sources](#sources '') |
[Configuration](#config '') | 
[Token](#token '') |
[Enregistrements](#bdd '') |
[Classe AppAPI](#classe '') |
[Actions](#actions '')

Méthodes :
[saveToken](#m_saveToken '') | 
[delToken](#m_delToken '') | 
[genereToken](#m_genereToken '') | 
[getDateExpire](#m_getDateExpire '') | 
[getVraiIp](#m_getVraiIp '') | 
[checkToken](#m_checkToken '') | 
[checkAuth](#m_checkAuth '') 

Actions :
[debug](#a_debug '') | 
[install](#a_install '') | 
[auth](#a_auth '') | 
[getTickets](#a_getTickets '') | 
[getToken](#a_getToken '') | 
[checkToken](#a_checkToken '') | 
[delToken](#a_delToken '') | 
[purge](#a_purge '') | 
[getSources](#a_getSources '') | 
[getCategories](#a_getCategories '') | 
[getEntites](#a_getEntites '') | 
[getDemandeurs](#a_getDemandeurs '')

---  

## Usage ## {#usage}


    {GLPI_URL}/app/api.php?action={nom_action}[&paramètres]
    
*- ou -*    
    
    {GLPI_URL}/app/api.php?{nom_action}[&paramètres]

---

## Sources {#sources}

    └── app/
        ├── api.php             Script à appeler
        ├── AppAPI.class.php    Classe des méthodes
        ├── inc.config.php      Configuration de l'API
        └── doc/                Documentation

---  


## Configuration {#config}

* ``$delai_token``
> Durée de conservation des tokens générés, au format SQL (*+1 hour, +1 day*...)
   
* ``$nbCar_token``
> Nombre de caractères total du token. Le token est assaissoné avec l'IP du client et le microtime UNIX.
   
* ``$first_install``
> Force l'installation de l'API (action ``install``) avant l'appel à l'action demandée.
   
* ``$mode_debug``
> Affiche les retour visuels dans le navigateur au lieu des retours aJax.      

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

---

## Enregistrements {#bdd}

Les données relatives à la gestion des tokens sont enregistrées en base de données dans la base *GLPI*.

* Table ``glpi_apptokens`` :
> 
| Champ | Type | Description |
| :------- | :------- | :------- |
| `users_id` | *int(11)* | ID de l'utilisateur GLPI associé au token généré *(défini à l'authentification)*. |
| `ip` | *varchar(15)* | Adresse IP du client hors proxy pour test de validité du token qui comprend le sel de l'IP. |
| `token` | *varchar(255)* | Token généré. |
| `expire` | *datetime* | Date et heure d'expiration du token défini dans le paramétrage de l'API. |

---

## Méthodes de la classe AppAPI {#classe}

* ### `saveToken` {#m_saveToken}  
> *(public)* Enregistre un Token.
>
> *Valeurs de retour :*
> - `true` : Token enregistré
> - `false` : Erreur lors de l'exécution de la requête
    
* ### `delToken` {#m_delToken}     
> *(public)* Supprime un Token d'après les paramètres de recherche.
>
> *Valeurs de retour :*
> - `true` : Token supprimé
> - `false` : Erreur lors de l'exécution de la requête
    
* ### `genereToken` {#m_genereToken}    
> *(private)* Génère un nouveau Token.
>
> *Valeur de retour :* ``token`` (*string*) Token généré.

* ### `getDateExpire` {#m_getDateExpire}          
> *(private)*  Retourne la date d'expiration en fonction du paramètre de configuration.
>
> *Valeur de retour :* ``date`` (*string*) Date d'expiration.
    
* ### `getVraiIp` {#m_getVraiIp}   
> *(private)*  Retourne l'IP rélle du client et non celle d'un proxy
>
> *Valeur de retour :* ``IP`` (*string*) IP réelle du client.   

 * ### `checkToken` {#m_checkToken}      
> *(public)* Vérifie la validité d'un token en base (*IP, expiration*...)
>
> *Valeurs de retour :*
> - ``ID`` (*int*) de l'user GLPI.
> - ``false`` (*bool*) si le token est invalide.
    
* ### `checkAuth` {#m_checkAuth}    
> *(public)* Vérifie l'authentification d'un user GLPI.
>
> *Valeurs de retour :*
> - ``ID`` (*int*) de l'user GLPI.
> - ``0`` (*int*) en cas de mot de passe incorrect.
> - ``-0`` (*int*) en cas d'utilisateur inconnu.
    


---

## Actions {#actions}

* ### `debug` {#a_debug}   
> Passez le paramètre ``&debug`` pour forcer le mode débug et retourner un affichage HTML plutôt qu'un retour json.

* ### `install` {#a_install}
> Crée la table en base pour le stockage des tokens.
    
* ### `auth` {#a_auth}
> Authentifie un utilisateur GLPI.
>
> *Exemple :*
>
> ``` {GLPI_URL}/app/api.php?auth&p={mot_de_passe_clair}&n={login}```
>
> *Paramètres* :     
>
>  - ``p`` : mot de passe en clair
>  - ``n`` : login (champ "*name*" dans GLPI)
>    
> *Valeurs de retour :*
> - Objet ``json`` comprenant le token, le nom, le prénom et le profil de l'utilisateur si l'accès a été autorisé *(json)*
> - ``0`` Accès refusé *(int)*
> - ``-1`` Utilisateur inconnu ou données incomplètes *(int)*
    
* ### `getTickets` {#a_getTickets}
> Retourne un objet *JSON* de la liste des tickets  d'un utilisateur par son token.
> 
> *Exemple :*
>
> ``` {GLPI_URL}/app/api.php?getTickets&token={token}[&status|&type|&urgence]```
> 
> *Paramètres* :     
> - ``token`` : Token de l'utilisateur
> - ``status`` : Status du ticket (*optionnel*). 
>   - ``1`` Nouveau 
>   - ``2`` En cours (Attribué) 
>   - ``3`` En cours (Planifié)
>   - ``4`` En attente
>   - ``5`` Résolu 
>   - ``6`` Clos  
> - ``type`` : Type du ticket (*optionnel*).   
>   - ``1`` Incident
>   - ``2`` Demande
> - ``urgence`` : Urgence du ticket (*optionnel*). 
>   -  ``2`` Basse
>   - ``3`` Moyenne
>   - ``4`` Haute
  
        
* ### `getToken` {#a_getToken}
> Génère un nouveau token anonyme conformément à la configuration de l'API (pour tests uniquement).
>
> Retourne le token généré (*string*) ou `0` (*int*) en cas d'erreur.  
      
* ### `checkToken` {#a_checkToken}        
> Vérifie la validité d'un token. 
> 
> *Paramètres* :    
> - ``token`` : token à tester *(string)*
>    
> *Valeurs de retour :*    
> - ``1`` (*int*) Token valide
> - ``0`` (*int*) Token invalide

* ### `delToken` {#a_delToken}    
> Supprime un token correspondant à au moins un des paramètres.
>
> *Exemple :* 
>
> ``` {GLPI_URL}/app/api.php?delToken&token={token}[&ip|&type|&date]```     
>
> *Paramètres* :     
> - ``token`` : token à tester (*string*)
> - ``ip`` : adresse IP (*xxx.xxx.xxx.xxx*) liée au token
> - ``date`` date au format FR (*YYYY-MM-DD*) d'expiration du token
>
> *Valeurs de retour :*    
 > - ``1`` (*int*) Token valide
 > - ``0`` (*int*) Token invalide
 
* ### `purge` {#a_purge}   
> Supprime tous les tokens enregistrés en base dont la date d'expiration est dépassée. 
>  
> *Valeurs de retour :*    
> - ``1`` (*int*) Purge effectuée
> - ``0`` (*int*) Erreur de traitement

 * ### `getSources` {#a_getSources}  
> Retourne la liste des *sources de la demande* disponibles.
>
> *Valeur de retour :*  ``json`` (*objet*)
> 
| Clé | Valeur |
| :------- | :------- |
| `id` | Libellé de la demande |
    
   
* ### `getCategories` {#a_getCategories}      
> Retourne la liste des *catégories* disponibles pour les entités liées aux habilitations de profils de l'utilisateur. 
>
> *Exemple :* 
>
> ``` {GLPI_URL}/app/api.php?getCategories&token={token}```     
>
> *Paramètres* :
> - ``token`` : Token de l'utilisateur pour identifier l'entité cible *(string)*
>    
> *Valeurs de retour :*    
> - ``0`` (*int*) Token invalide
> - ``json`` (*objet*) 
>   
| Clé | Valeur |
| :------- | :------- |
| `id` | Libellé de la catégorie | 

* ### `getEntites` {#a_getEntites}  
> Retourne la liste des *entités* (hors entité racine) avec récursivité. On demande le token par sécurité car ce sont des données potentiellement sensibles. 
>
> *Exemple :* 
>
> ``` {GLPI_URL}/app/api.php?getEntites&token={token}```  
>
> *Paramètres* :
> - ``token`` : Token en cours de validité *(string)*
>
> *Valeurs de retour :*    
> - ``0`` (*int*) Token invalide
> - ``json`` (*objet*) 
>   
```clike
{
>  id : {
>    "name"       : "Nom de l'entité",
>    "short_name" : "Nom court"
>    },
>  ...
}
```

* ### `getDemandeurs` {#a_getDemandeurs}  
> Retourne la liste des demandeurs à la création d'un ticket avec récursivité de l'entité. On demande l'ID de l'entité sélectionnée et le token par sécurité car ce sont des données potentiellement sensibles.
>   
> *Exemple :* 
> 
> ``` {GLPI_URL}/app/api.php?getDemandeurs&token={token}&ent={id}``  
>
> *Paramètres* :
> - ``token`` : Token en cours de validité *(string)*    
> - ``ent`` : ID (> 0) de l'entité sélectionnée *(int)* 
>
> *Valeurs de retour :*    
> - ``0`` (*int*) Token invalide
> - ``json`` (*objet*) 
>   
```clike
{
>  id_user : {
>    "firstname" : "Prénom",
>    "lastname"  : "Nom"
>    },
>  ...
}
```    

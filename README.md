CBO Framework
=============
Version 2.4.3
Auteur : Cédric Bouillon

Installation
------------
Renseignez tous les paramètres de déploiement du framework dans :

Exécutez ``scripts/php/install.php``

Consultez le toolKit du mode Debug pour plus d'informations.

Arborescence
------------

> - /class
> `Classes PHP Objets/Managers & PHPMailer relatives`
> - /css
> `Styles CSS`
> - /img
> `Images sources`
> - /incules
> `Inclusions pages`
> - /outils
> `Archives des outils de Backup et de Check à déployer`
> - /scripts
>    - /js
> `Fichiers JS/jQuery`
>    - /php
> `Fichiers de scripts PHP système`
>    - /ajax
> `Fichiers de scripts PHP/aJax`
>    - /log
> `Fichiers de logs générés`
> - /temp
> `Fichiers générés`
> - /templates
> `Fichiers .tpl`
> - /uploads
> `Fichiers uploadés`
> - /vendor
> `Sources des dépendances`

Fichiers coeur
---------------
```
index.php
_class-generator.php
_etat_config.php
_info.php
_phpinfo.php
_toolkit.php
_variables.php
├── class/
│   ├── Outils.class.php
│   ├── Pagination.class.php
│   ├── pCache.class.php
│   ├── pChart.class.php
│   ├── PDOEx.class.php
│   ├── PHPMailer.php
│   ├── SMTP.php
│   └── Upload.class.php
├── css/
│   ├── _class-generator.css
│   ├── _toolkit.css
│   ├── install.css
│   ├── main.css
│   └── responsive.css
│   └── rougetemp.css
├── img/
│   ├── favicon.ico
│   └── favicon.png
├── includes/
│   ├── debug.php
│   ├── header.php
│   ├── header-content.php
│   ├── footer.php
│   └── footer-content.php
├── templates/
│   └── index.tpl
└── scripts/
    ├── js/
    │   ├── _class-generator.js
    │   ├── _toolkit.js
    │   ├── commons.js
    │   └── main.js
    ├── ajax/
    │   ├── fct_ajax.php    
    │   └── fct_install.php 
    └── php/
        ├── config.params.php
        ├── charger_class.php
        ├── cnx.php
        └── config.php
        └── install.php
        └── download.php
```

Fichiers d'exemple
------------------
```
./
├── class/
│   ├── Objet.class.php
│   └── ObjetManager.class.php
└── scripts/
    └── php/
        ├── deconnexion.php
        └── get_test.php
```

Dépannage
---------
* Modifiez le nom de la variable ``$_SESSION['cbofsessdate']`` afin de forcer un nouveau compilage de la configuration en cas de conflit de session.
* Démarrer le framework à l'adresse ``scripts/php/install.php`` pour regénérer un fichier de configuration propre.
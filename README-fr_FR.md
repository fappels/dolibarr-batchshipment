# BATCHSHIPMENT POUR [DOLIBARR ERP & CRM](https://www.dolibarr.org)

## Fonctionnalités

BatchShipment est un module de préparation de commandes pour Dolibarr qui permet au personnel d'entrepôt de prélever des produits pour plusieurs commandes clients en même temps.

Fonctionnalités principales :

- Créez une liste de préparation à partir d'une sélection de commandes clients afin de prélever des articles pour plusieurs commandes en une seule opération.
- Définissez les emplacements de stock source et sélectionnez les numéros de lot/série à utiliser pour le prélèvement.
- Valeurs par défaut automatiques pour les emplacements et la sélection de lot/numéro de série basées sur des règles FIFO pour déterminer le meilleur stock à utiliser pour la préparation des commandes.
- Effectuez le prélèvement en mode une étape (articles triés immédiatement par commande) ou en mode deux étapes (rassemblez d'abord tous les articles prélevés, puis affectez-les aux commandes dans une seconde étape).
- Lorsque les prélèvements sont validés, le module génère automatiquement les expéditions correspondantes pour chaque commande liée.
- Étape finale de vérification pour marquer les expéditions comme terminées.

Ce module accélère les opérations d'entrepôt en minimisant les déplacements et en garantissant l'utilisation correcte des lots/séries et de la FIFO lors de l'exécution de plusieurs commandes ensemble.


![Screenshot batchshipment](img/screenshot_batchshipment_fr.png?raw=true "BatchShipment")


D'autres modules externes sont disponibles sur [Dolistore.com](https://www.dolistore.com).

## Translations

Les traductions peuvent être effectuées manuellement en modifiant les fichiers situés dans les répertoires du module sous `langs`.

<!--
This module contains also a sample configuration for Transifex, under the hidden directory [.tx](.tx), so it is possible to manage translation using this service.

For more information, see the [translator's documentation](https://wiki.dolibarr.org/index.php/Translator_documentation).

There is a [Transifex project](https://transifex.com/projects/p/dolibarr-module-template) for this module.
-->


## Installation

Prérequis : vous devez disposer du logiciel Dolibarr ERP & CRM. Vous pouvez le télécharger depuis [Dolistore.org](https://www.dolibarr.org).
Vous pouvez également obtenir une instance prête à l'emploi dans le cloud sur https://saas.dolibarr.org


### À partir du fichier ZIP et de l'interface graphique

Si le module se présente sous la forme d'un fichier ZIP prêt à être déployé, dont le nom est `module_BatchShipment-version.zip` (par exemple, lorsque vous le téléchargez depuis une place de marché comme [Dolistore](https://www.dolistore.com)),
rendez-vous dans le menu `Accueil > Configuration > Modules > Déployer un module externe` et téléchargez le fichier ZIP.

<!--

Note: If this screen tells you that there is no "custom" directory, check that your setup is correct:

- In your Dolibarr installation directory, edit the `htdocs/conf/conf.php` file and check that following lines are not commented:

    ```php
    //$dolibarr_main_url_root_alt ...
    //$dolibarr_main_document_root_alt ...
    ```

- Uncomment them if necessary (delete the leading `//`) and assign the proper value according to your Dolibarr installation

    For example :

    - UNIX:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';
        ```

    - Windows:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';
        ```
-->


### Depuis GIT repository

Clone the repository in `$dolibarr_main_document_root_alt/batchshipment`

```shell
git clone git@github.com:fappels/dolibarr-batchshipment.git batchshipment
```

### Dernières étapes

À l'aide de votre navigateur :

  - Connectez-vous à Dolibarr en tant que super-administrateur
  - Allez dans « Configuration » > « Modules »
  - Vous devriez maintenant pouvoir trouver et activer le module



## Licences

### Code principal

GPLv3 ou (à votre choix) toute version ultérieure. Consultez le fichier COPYING pour plus d'informations.

### Documentation

Tous les textes et fichiers Lisez-moi sont sous licence [GFDL](https://www.gnu.org/licenses/fdl-1.3.en.html).

Traduit avec DeepL.com (version gratuite)

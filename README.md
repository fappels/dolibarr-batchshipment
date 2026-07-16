# BATCHSHIPMENT FOR [DOLIBARR ERP & CRM](https://www.dolibarr.org)

## Features

BatchShipment is a batch picking module for Dolibarr that lets warehouse staff pick products for multiple customer orders at the same time.

Key features:

- Create a picking list from a selection of customer orders so you can pick items for several orders in one operation.
- Define source stock locations and select lot/serial numbers to use for the pick.
- Automatic defaults for locations and lot/serial selection based on FIFO rules to force the best stock to use for picking.
- Perform picking in one-stage mode (items immediately sorted per order) or two-stage mode (collect all picked items first, then allocate them to orders in a second stage).
- When pickings are validated the module automatically generates the corresponding shipments for each related order.
- Final verification step to mark shipments as finished.

This module speeds up warehouse operations by minimizing trips and ensuring correct lot/serial and FIFO usage when fulfilling multiple orders together.


![Screenshot batchshipment](img/screenshot_batchshipment.png?raw=true "BatchShipment")


Other external modules are available on [Dolistore.com](https://www.dolistore.com).

## Usage

### Setup

- **One stage / two stage**: choose whether products are sorted right after picking (one stage) or sorted separately afterwards, during a dedicated loading step (two stage).
- **Choose warehouse and lot/serial while picking**: off means the pick location and lot/serial must be defined before picking starts; on means they can also be chosen or adjusted while picking is in progress.

### Workflow

1. From the customer order list, filter the validated orders and use the "shippable" column to select the orders to pick. Use the mass action to create a new picking list or add the selection to an existing one.
2. In the created picking list, review and adjust the pick locations and lot/serial numbers. Lines are prefilled with the best available values. To confirm a line, tick its checkbox and set the location and lot/serial. You can also split a line if you need to pick from multiple lots/serials or multiple locations.
3. Validate the picking list to start picking. During picking, enter the picked quantities, then tick the lines and click the "Pick" button to confirm them.
4. Validate the picking:
   - In **one-stage** mode, the shipments corresponding to the orders are created automatically.
   - In **two-stage** mode, you then sort the products by "loading" and enter the loaded quantities. Tick the lines and click the "Load" button to confirm loading. The corresponding shipments are created when the loading is validated.
5. Check the shipments. Once checked, close all shipments and mark the orders as treated.

## Translations

Translations can be completed manually by editing files in the module directories under `langs`.

<!--
This module contains also a sample configuration for Transifex, under the hidden directory [.tx](.tx), so it is possible to manage translation using this service.

For more information, see the [translator's documentation](https://wiki.dolibarr.org/index.php/Translator_documentation).

There is a [Transifex project](https://transifex.com/projects/p/dolibarr-module-template) for this module.
-->


## Installation

Prerequisites: You must have Dolibarr ERP & CRM software installed. You can download it from [Dolistore.org](https://www.dolibarr.org).
You can also get a ready-to-use instance in the cloud from https://saas.dolibarr.org


### From the ZIP file and GUI interface

If the module is a ready-to-deploy zip file, so with a name `module_BatchShipment-version.zip` (e.g., when downloading it from a marketplace like [Dolistore](https://www.dolistore.com)),
go to menu `Home> Setup> Modules> Deploy external module` and upload the zip file.

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


### From a GIT repository

Clone the repository in `$dolibarr_main_document_root_alt/batchshipment`

```shell
git clone git@github.com:fappels/dolibarr-batchshipment.git batchshipment
```

### Final steps

Using your browser:

  - Log into Dolibarr as a super-administrator
  - Go to "Setup"> "Modules"
  - You should now be able to find and enable the module



## Licenses

### Main code

GPLv3 or (at your option) any later version. See file COPYING for more information.

### Documentation

All texts and readme's are licensed under [GFDL](https://www.gnu.org/licenses/fdl-1.3.en.html).

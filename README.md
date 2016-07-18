# CiviCRM Redsys Payment Processor #

This is a Payment Processor for CiviCRM that allows you to work with Redsys from within CiviCRM.

For more information about CiviCRM payment processors, see:
http://book.civicrm.org/user/current/contributions/payment-processors/

## Release Notes ##

### v1.4 ###

- When is used in webforms, returns to thankyou page defined or back to the webform if the contribution is canceled in Redsys UI

### v1.3 ###

- Send email receipt on Contribution completion if it is set up
- For Event Registration pages, change Participant's status on Contribution completion

### v1.2.1 ###

Version 1.2 has been updated to meet the new Redsys requirements regarding password sha256 encryption.
More information here: https://canales.redsys.es/canales/ayuda/migracionSHA256.html

## Contact ##

* iXiam Global Solutions: <info@ixiam.com>

## Sponsorship ##

This extension was funded by:

* iXiam Global Solutions - <http://www.ixiam.com>
* Amnesty International Spain - <https://www.es.amnesty.org>
* Fundación Niños con Amor - <ninosconamor@hotmail.com>
* Universidad Carlos III de Madrid - <http://www.uc3m.es>

Collaborators

* Carlos Capote: <carlos@librecool.es >


## How to install ##

This is a standard CiviCRM extension and can be directly installed from your CiviCRM instance.

* Standard installation process: Fore more information about how to install CiviCRM extensions, see: http://wiki.civicrm.org/confluence/display/CRMDOC/Extensions
* Manual installation process: You can also install this extension manually:
  * Extract the content of this extension in your CiviCRM extensions directory
  * Install Extension in CiviCRM (Administer / System Settings / Manage Extensions)

### Configuration ###

![Screenshot](https://raw.githubusercontent.com/amnesty/civicrm-redsys/master/res/payment-processor-config.png "Screenshot")

After installing and activating the extension, you'll need to configure your payment processor:

* Add a new Payment Processor (Administer / System Settings / Payment Processor)
* Select Redsys Payment Processor as Payment Processor Type
* Configure it with your Mechant Account Id (número de comercio) and Encription Password (clave secreta de encriptación)

### Requirements ###

This payment processor works with CiviCRM 4.4 or newer versions.

Prior to CiviCRM 4.4.5, there is a bug that affects Payment Processors. If your using a prior version (< 4.4.5), you must apply the following patch.

The patch has been included in the file `CRM/Core/Payment.patched.php`. To apply the patch, you just have to rename the file to `CRM/Core/Payment.php`.

For more information about the patch, see:
https://issues.civicrm.org/jira/browse/CRM-14396

## License ##

Redsys Payment Processor for CiviCRM. Copyright (C) 2013 - 2015 Amnesty International (originally developed by Ixiam http://www.ixiam.com).

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program (see LICENSE.txt). If not, see http://www.gnu.org/licenses/.

CiviCRM Redsys Payment Processor
================================


REQUIREMENTS
------------

**CiviCRM 4.4+**


IMPORTANT: Apply patch if needed
--------------------------------

Prior to **CiviCRM 4.4.5** there is a bug that affects Payment Processors
More info here: https://issues.civicrm.org/jira/browse/CRM-14396

If you are using **CiviCRM < 4.4.5** you must enable the patch that comes with this extension in order to override *CRM_Core_Payment* class

- Rename the file `CRM/Core/Payment.patched.php` to `CRM/Core/Payment.php`


INSTALLATION
------------

1) Extract the content of this extension in your CiviCRM extensions' directory

2) Install Extension in CiviCRM (Administer / System Settings / Manage Extensions).
For more details on Extensions: https://wiki.civicrm.org/confluence/display/CRMDOC/Extensions

3) Add a new Payment Processor (Administer / System Settings / Payment Processor)

4) Select Redsys Payment Processor y configure it with your Mechant Account Id (*NÃºmero de Comercio*) and Encription Password (*Clave Secreta de EncriptaciÃ³n*)


ToDo
-----

1) Change Participant's Status based on Redsys IPN Callback. The Business Logic needs to be defined here (If applies)


LICENSE
-------

Amnesty / Redsys Payment Processor for CiviCRM. Copyright (C) 2013 Amnesty International.

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program (see LICENSE.txt). If not, see http://www.gnu.org/licenses/.


AUTHOR INFO
-----------

**iXiam Global Solutions**

http:///www.ixiam.com

Luciano Spiegel <l.spiegel@ixiam.com>

Rubén Pineda <r.pineda@ixiam.com>
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

4) Select Redsys Payment Processor y configure it with your Mechant Account Id (*Número de Comercio*) and Encription Password (*Clave Secreta de Encriptación*)


AUTHOR INFO
-----------

**iXiam Global Solutions**

http:///www.ixiam.com

Luciano Spiegel <l.spiegel@ixiam.com>

Rubén Pineda <r.pineda@ixiam.com>
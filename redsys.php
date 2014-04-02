<?php

require_once 'redsys.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function redsys_civicrm_config(&$config) {
  _redsys_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function redsys_civicrm_xmlMenu(&$files) {
  _redsys_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function redsys_civicrm_install() {
  return _redsys_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function redsys_civicrm_uninstall() {
  return _redsys_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function redsys_civicrm_enable() {
  return _redsys_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function redsys_civicrm_disable() {
  return _redsys_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function redsys_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _redsys_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function redsys_civicrm_managed(&$entities) {
  $entities[] = array(
    'module' => 'com.ixiam.payment.redsys',
    'name' => 'Redsys Payment Processor',
    'entity' => 'PaymentProcessorType',
    'params' => array(
      'version' => 3,
      'name' => 'Redsys',
      'title' => 'Redsys Payment Processor',
      'description' => 'Works with Servired (Sermepa) and 4B (Pasat).',
      'class_name' => 'Payment_Redsys',
      'billing_mode' => 'notify',
      'user_name_label' => 'Número de comercio',
      'password_label' => 'Clave secreta de encriptación',
      'url_site_default'=> 'https://sis.redsys.es/sis/realizarPago',
      'url_site_test_default' => 'https://sis-t.redsys.es:25443/sis/realizarPago',
      'is_recur' => 0,
      'payment_type' => 1
    ),
  );
}



/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function redsys_civicrm_caseTypes(&$caseTypes) {
  _redsys_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function redsys_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _redsys_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

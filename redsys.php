<?php

require_once 'redsys.civix.php';
use CRM_Redsys_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function redsys_civicrm_config(&$config) {
  _redsys_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function redsys_civicrm_xmlMenu(&$files) {
  _redsys_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function redsys_civicrm_postInstall() {
  _redsys_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function redsys_civicrm_install() {
  $params = array(
    'name' => 'Redsys',
    'title' => 'Redsys Payment Processor',
    'description' => 'Works with Servired (Sermepa) and 4B (Pasat).',
    'class_name' => 'Payment_Redsys',
    'billing_mode' => 'notify',
    'user_name_label' => 'Número de comercio',
    'password_label' => 'Clave secreta de encriptación',
    'url_site_default' => 'https://sis.redsys.es/sis/realizarPago',
    'url_site_test_default' => 'https://sis-t.redsys.es:25443/sis/realizarPago',
    'is_recur' => 0,
    'payment_type' => 1,
  );
  $result = civicrm_api3('PaymentProcessorType', 'create', $params);
  return _redsys_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function redsys_civicrm_uninstall() {
  $params = array(
    'sequential' => 1,
    'name' => 'Redsys',
  );
  $result = civicrm_api('PaymentProcessorType', 'get', $params);
  if ($result["count"] == 1) {
    $params = array(
      'sequential' => 1,
      'id' => $result["id"],
    );
    $result = civicrm_api3('PaymentProcessorType', 'delete', $params);
  }
  return _redsys_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function redsys_civicrm_enable() {
  _redsys_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function redsys_civicrm_disable() {
  _redsys_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function redsys_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _redsys_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function redsys_civicrm_managed(&$entities) {
  _redsys_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function redsys_civicrm_caseTypes(&$caseTypes) {
  _redsys_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function redsys_civicrm_angularModules(&$angularModules) {
  _redsys_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function redsys_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _redsys_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function redsys_civicrm_entityTypes(&$entityTypes) {
  _redsys_civix_civicrm_entityTypes($entityTypes);
}

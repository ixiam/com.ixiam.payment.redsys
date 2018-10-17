<?php

use CRM_Redsys_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Redsys_Upgrader extends CRM_Redsys_Upgrader_Base {

  public function install() {
    $this->buildMenu();
    return TRUE;
  }

  public function upgrade_4015() {
    $this->buildMenu();
    return TRUE;
  }

  private static function buildMenu(){
    $query = "SELECT id FROM `civicrm_navigation` WHERE name = 'CiviContribute'";
    $dao = CRM_Core_DAO::executeQuery($query);
    if ($dao->fetch()) {
      $menu_params = array(
        'label' => E::ts('Redsys Settings', array('domain' => 'com.ixiam.payment.redsys')),
        'url' => 'civicrm/redsys/settings',
        'permission' => array('administer OfflinePay'),
        'permission_operator' => 'AND',
        'has_separator' => '1',
        'is_active' => '1',
        'parent_id' => $dao->id,
      );
      $parent = CRM_Core_BAO_Navigation::add($menu_params);

      // Also reset navigation.
      CRM_Core_Menu::store();
      CRM_Core_BAO_Navigation::resetNavigation();
    }
    $dao->free();
  }

}

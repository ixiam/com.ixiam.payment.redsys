<?php

require_once 'CRM/Core/Form.php';

class CRM_Redsys_Form_Settings extends CRM_Core_Form {
  public function buildQuickForm() {
    $this->add('checkbox', 'ipn_http', 'Use http for IPN Callback');
    $this->add('text', 'merchant_terminal', 'Merchant Terminal', array('size' => 5));
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));
    parent::buildQuickForm();
  }

  function setDefaultValues() {
    $defaults = array();
    $redsys_settings = CRM_Core_BAO_Setting::getItem("Redsys Settings", 'redsys_settings');
    if (!empty($redsys_settings)) {
      $defaults = $redsys_settings;
    }
    return $defaults;
  }

  public function postProcess() {
    $values = $this->exportValues();
    $redsys_settings['ipn_http'] = $values['ipn_http'];
    $redsys_settings['merchant_terminal'] = $values['merchant_terminal'];
    CRM_Core_BAO_Setting::setItem($redsys_settings, "Redsys Settings", 'redsys_settings');
    CRM_Core_Session::setStatus(ts('Redsys Settings Saved', array( 'domain' => 'com.ixiam.payment.redsys')), 'Configuration Updated', 'success');

    parent::postProcess();
  }

}

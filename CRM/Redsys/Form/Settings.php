<?php

require_once 'CRM/Core/Form.php';

class CRM_Redsys_Form_Settings extends CRM_Core_Form {
  public function buildQuickForm() {
    $this->add('checkbox', 'ipn_http', 'Use http for IPN Callback');
    $this->add('text', 'merchant_terminal', 'Merchant Terminal', array('size' => 5));

    $paymentProcessors = $this->getPaymentProcessors();
    foreach( $paymentProcessors as $paymentProcessor ) {
      $settingCode = 'merchant_terminal_' . $paymentProcessor[ "id" ];
      $settingTitle = $paymentProcessor[ "name" ] . " (" .
        ( $paymentProcessor["is_test"] == 0 ? "Live" : "Test" ) . ")";
      $this->add('text', $settingCode, $settingTitle, array('size' => 5));
    }

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
    
    $paymentProcessors = $this->getPaymentProcessors();
    foreach( $paymentProcessors as $paymentProcessor ) {
      $settingId = 'merchant_terminal_' . $paymentProcessor[ "id" ];
      $redsys_settings[$settingId] = $values[$settingId];
    }
    
    CRM_Core_BAO_Setting::setItem($redsys_settings, "Redsys Settings", 'redsys_settings');
    CRM_Core_Session::setStatus(ts('Redsys Settings Saved', array( 'domain' => 'com.ixiam.payment.redsys')), 'Configuration Updated', 'success');

    parent::postProcess();
  }

  public function getPaymentProcessors() {
    // Get the Redsys payment processor type
    $redsysName = array( 'name' => 'Redsys' );
    $paymentProcessorType = civicrm_api3( 'PaymentProcessorType', 'getsingle', $redsysName );

    // Get the payment processors of Redsys type
    $redsysType = array(
      'payment_processor_type_id' => $paymentProcessorType[ 'id' ],
      'is_active' => 1 );
    $paymentProcessors = civicrm_api3( 'PaymentProcessor', 'get', $redsysType );

    return $paymentProcessors["values"];
  }
}

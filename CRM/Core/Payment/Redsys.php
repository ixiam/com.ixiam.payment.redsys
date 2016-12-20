<?php
/**
 * CiviCRM Payment Processor for Redsys (before called Sermepa).
 *
 * Redsys is a company based in Spain. Many banks are using its
 * payment processor for their payment processors.
 */

require_once 'CRM/Core/Payment.php';
require_once 'includes/apiRedsys.php';

class CRM_Core_Payment_Redsys extends CRM_Core_Payment {
  CONST REDSYS_CURRENCY_EURO = 978;
  CONST REDSYS_LANGUAGE_SPANISH = 1;
  CONST REDSYS_LANGUAGE_BASQUE = 13;
  CONST REDSYS_LANGUAGE_CATALAN = 3;
  CONST REDSYS_LANGUAGE_GALICIAN = 12;
  CONST REDSYS_TRANSACTION_TYPE_OPERATION_STANDARD = 0;

  /**
  * We only need one instance of this object. So we use the singleton
  * pattern and cache the instance in this variable
  *
  * @var object
  * @static
  */
  static private $_singleton = null;

  /**
  * mode of operation: live or test
  *
  * @var object
  */
  protected $_mode = null;

  /**
   * Payment Type Processor Name
   *
   * @var string
   */
  protected $_processorName = null;

  /**
  * Constructor
  *
  * @param string $mode the mode of operation: live or test
  *
  * @return void
  */
  function __construct($mode, &$paymentProcessor) {
    $this->_mode              = $mode;
    $this->_paymentProcessor  = $paymentProcessor;
    $this->_processorName     = 'Redsys';
  }

  /**
  * Singleton function used to manage this object
  *
  * @param string $mode the mode of operation: live or test
  *
  * @return object
  * @static
  *
  */
  static function &singleton($mode, &$paymentProcessor) {
    $processorName = $paymentProcessor["name"];
    if (self::$_singleton[$processorName] === NULL ) {
      self::$_singleton[$processorName] = new self($mode, $paymentProcessor);
    }
    return self::$_singleton[$processorName];
  }

  /**
  * This function checks to see if we have the right config values
  *
  * @return string the error message if any
  * @public
  */
  function checkConfig() {
    $config = CRM_Core_Config::singleton();
    $error = array();

    if (empty($this->_paymentProcessor["user_name"])) {
      $error[] = ts( "Merchant Code is not set in the Redsys Payment Processor settings." );
    }
    if (empty($this->_paymentProcessor["password"])) {
      $error[] = ts( "Merchant Password is not set in the Redsys Payment Processor settings." );
    }

    if (!empty($error)) {
      return implode("<p>", $error);
    } else {
      return NULL;
    }
  }

  /**
   * This function is not implemented, as long as this payment
   * procesor is notify mode only.
   *
   * @param type $params
   */
  function doDirectPayment( &$params ) {
    CRM_Core_Error::fatal( ts( "This function is not implemented" ) );
  }

  /**
   * This function calls the Redsys servers and sends them information
   * about the payment.
   */
  function doTransferCheckout(&$params, $component = 'contribute') {

    $config = CRM_Core_Config::singleton();

    if ($component != 'contribute' && $component != 'event') {
      CRM_Core_Error::fatal(ts('Component is invalid'));
    }

    if( array_key_exists( 'webform_redirect_success', $params ) ) {
      $returnURL = $params['webform_redirect_success'];
      $cancelURL = $params['webform_redirect_cancel'];
    } else {
      $url       = ($component == 'event') ? 'civicrm/event/register' : 'civicrm/contribute/transact';
      $cancel    = ($component == 'event') ? '_qf_Register_display' : '_qf_Main_display';
      $returnURL = CRM_Utils_System::url($url,
        "_qf_ThankYou_display=1&qfKey={$params['qfKey']}",
        TRUE, NULL, FALSE
      );


      $cancelUrlString = "$cancel=1&cancel=1&qfKey={$params['qfKey']}";
      if (CRM_Utils_Array::value('is_recur', $params)) {
        $cancelUrlString .= "&isRecur=1&recurId={$params['contributionRecurID']}&contribId={$params['contributionID']}";
      }

      $cancelURL = CRM_Utils_System::url(
        $url,
        $cancelUrlString,
        TRUE, NULL, FALSE
      );
    }

    $merchantUrlParams = "contactID={$params['contactID']}&contributionID={$params['contributionID']}";
    if ($component == 'event') {
      $merchantUrlParams .= "&eventID={$params['eventID']}&participantID={$params['participantID']}";
    }
    else {
      $membershipID = CRM_Utils_Array::value('membershipID', $params);
      if ($membershipID) {
        $merchantUrlParams .= "&membershipID=$membershipID";
      }
      $contributionPageID = CRM_Utils_Array::value('contributionPageID', $params);
      if ($contributionPageID) {
        $merchantUrlParams .= "&contributionPageID=$contributionPageID";
      }
      $relatedContactID = CRM_Utils_Array::value('related_contact', $params);
      if ($relatedContactID) {
        $merchantUrlParams .= "&relatedContactID=$relatedContactID";

        $onBehalfDupeAlert = CRM_Utils_Array::value('onbehalf_dupe_alert', $params);
        if ($onBehalfDupeAlert) {
          $merchantUrlParams .= "&onBehalfDupeAlert=$onBehalfDupeAlert";
        }
      }
    }
    $merchantUrl = $config->userFrameworkBaseURL . 'civicrm/payment/ipn?processor_name=Redsys&mode=' . $this->_mode . '&md=' . $component . '&qfKey=' . $params["qfKey"] . '&' . $merchantUrlParams;

    // Force http if set
    $redsys_settings = CRM_Core_BAO_Setting::getItem("Redsys Settings", 'redsys_settings');
    if($redsys_settings['ipn_http'] == '1')
      $merchantUrl = preg_replace('/^https:/i', 'http:', $merchantUrl);

    // Get the terminal for this payment processor
    $paymentProcessorId = $params['payment_processor'];
    if( array_key_exists('merchant_terminal_' . $paymentProcessorId, $redsys_settings) ) {
      if( $redsys_settings['merchant_terminal_' . $paymentProcessorId] ) {
        $merchantTerminal = $redsys_settings['merchant_terminal_' . $paymentProcessorId];
      }
    }
    
    // Use the default terminal if the processor doesn't have an assigned one
    if( ! $merchantTerminal ) {
      $merchantTerminal = empty($redsys_settings['merchant_terminal']) ? 1 :
        $redsys_settings['merchant_terminal'];
    }

    $miObj = new RedsysAPI;
    $miObj->setParameter("Ds_Merchant_Amount", $params["amount"] * 100);
    $miObj->setParameter("Ds_Merchant_Order", strval(self::formatAmount($params["contributionID"], 12)));
    $miObj->setParameter("Ds_Merchant_MerchantCode", $this->_paymentProcessor["user_name"]);
    $miObj->setParameter("Ds_Merchant_Currency", self::REDSYS_CURRENCY_EURO);
    $miObj->setParameter("Ds_Merchant_TransactionType", self::REDSYS_TRANSACTION_TYPE_OPERATION_STANDARD);
    $miObj->setParameter("Ds_Merchant_Terminal", $merchantTerminal);
    $miObj->setParameter("Ds_Merchant_MerchantURL", $merchantUrl);
    $miObj->setParameter("Ds_Merchant_UrlOK", $returnURL);
    $miObj->setParameter("Ds_Merchant_UrlKO", $cancelURL);
    $miObj->setParameter("Ds_Merchant_ProductDescription", $params["contributionType_name"]);
    $miObj->setParameter("Ds_Merchant_Titular", $params["first_name"] . " " . $params["last_name"]   );
    $miObj->setParameter("Ds_Merchant_ConsumerLanguage", self::REDSYS_LANGUAGE_SPANISH);

    $version = "HMAC_SHA256_V1";

    $signature = $miObj->createMerchantSignature($this->_paymentProcessor["password"]);

    // Print the tpl to redirect and send POST variables to RedSys Getaway
    $template = CRM_Core_Smarty::singleton();
    $tpl = 'CRM/Core/Payment/Redsys.tpl';

    $template->assign('signature', $signature);
    $redsysParamsJSON = $miObj->createMerchantParameters();
    $template->assign('redsysParamsJSON', $redsysParamsJSON);
    $template->assign('version', $version);
    $template->assign('redsysURL', $this->_paymentProcessor["url_site"]);

    print $template->fetch($tpl);

    CRM_Utils_System::civiExit();
  }

  public function handlePaymentNotification() {
    $input = $ids = $objects = array();
    $ipn = new CRM_Core_Payment_RedsysIPN();

    // load vars in $input, &ids
    $ipn->getInput($input, $ids);
    CRM_Core_Error::debug_log_message("Redsys IPN Response: Parameteres received \n input: " . print_r($input, TRUE) . "\n ids: " . print_r($ids, TRUE) );

    $paymentProcessorID = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_PaymentProcessorType', $this->_processorName, 'id', 'name');
    if (!$ipn->validateData($this->_paymentProcessor, $input, $ids, $objects, TRUE, $paymentProcessorID)) {
      CRM_Core_Error::debug_log_message("Redsys Validation failed");
      return FALSE;
    }

    return $ipn->single($input, $ids, $objects, FALSE, FALSE);
  }

  static function formatAmount($amount, $size, $pad = 0){
    $amount_str = preg_replace('/[\.,]/', '', strval($amount));
    $amount_str = str_pad($amount_str, $size, $pad, STR_PAD_LEFT);
    return $amount_str;
  }

  static function trimAmount($amount, $pad = '0'){
    return ltrim(trim($amount), $pad);
  }
}

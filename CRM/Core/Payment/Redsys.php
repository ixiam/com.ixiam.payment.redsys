<?
/**
 * CiviCRM Payment Processor for Redsys (before called Sermepa).
 *
 * Redsys is a company based in Spain. Many banks are using its
 * payment processor for their payment processors.
 */

require_once 'CRM/Core/Payment.php';

class CRM_Core_Payment_Redsys extends CRM_Core_Payment {
  CONST REDSYS_CURRENCY_EURO = 978;
  CONST REDSYS_LANGUAGE_SPANISH = 1;
  CONST REDSYS_LANGUAGE_BASQUE = 13;
  CONST REDSYS_LANGUAGE_CATALAN = 3;
  CONST REDSYS_LANGUAGE_GALICIAN = 12;
  CONST REDSYS_TRANSACTION_TYPE_OPERATION_STANDARD = 0;
  CONST REDSYS_RESPONSE_CODE_ACCEPTED = '0000';

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
  * @static
  */
  static protected $_mode = null;

  /**
   * Payment Type Processor Name
   *
   * @var string
   */
  static protected $_processorName = null;

  /**
  * Constructor
  *
  * @param string $mode the mode of operation: live or test
  *
  * @return void
  */
  function __construct($mode, &$paymentProcessor) {
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = ts( "Redsys" );
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

    if ($component == 'event') {
      $notifyURL .= "&eventID={$params['eventID']}&participantID={$params['participantID']}";
    }

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


    $redsysParams["Ds_Merchant_Amount"] = $params["amount"] * 100;
    $redsysParams["Ds_Merchant_Currency"] = self::REDSYS_CURRENCY_EURO;
    $redsysParams["Ds_Merchant_Order"] = self::formatAmount($params["contributionID"], 12);
    $redsysParams["Ds_Merchant_ProductDescription"] = $params["contributionType_name"];
    $redsysParams["Ds_Merchant_Titular"] = $params["first_name"] . " " . $params["last_name"];
    $redsysParams["Ds_Merchant_MerchantCode"] = $this->_paymentProcessor["user_name"];    
       
    $merchantUrl = $config->userFrameworkBaseURL . 'civicrm/payment/ipn?processor_name=Redsys&mode=' . $this->_mode . '&md=' . $component . '&qfKey=' . $params["qfKey"];
    $redsysParams["Ds_Merchant_MerchantURL"] = $merchantUrl;
    $redsysParams["Ds_Merchant_UrlOK"] =  $returnURL;
    $redsysParams["Ds_Merchant_UrlKO"] = $cancelURL;
    $redsysParams["Ds_Merchant_ConsumerLanguage"] = self::REDSYS_LANGUAGE_SPANISH;
    $redsysParams["Ds_Merchant_Terminal"] = 1;
    $redsysParams["Ds_Merchant_TransactionType"] = self::REDSYS_TRANSACTION_TYPE_OPERATION_STANDARD;    

    $signature = strtoupper(sha1( $redsysParams["Ds_Merchant_Amount"] .
      $redsysParams["Ds_Merchant_Order"] .
      $redsysParams["Ds_Merchant_MerchantCode"] .
      $redsysParams["Ds_Merchant_Currency"] .
      $redsysParams["Ds_Merchant_TransactionType"] .
      $redsysParams["Ds_Merchant_MerchantURL"] .
      $this->_paymentProcessor["password"] )
    );

    $redsysParams["Ds_Merchant_MerchantSignature"] = $signature;

    // Print the tpl to redirect and send POST variables to RedSys Getaway
    $template = CRM_Core_Smarty::singleton();
    $tpl = 'CRM/Core/Payment/Redsys.tpl';

    $template->assign('redsysParams', $redsysParams);
    $template->assign('redsysURL', $this->_paymentProcessor["url_site"]);
    
    print $template->fetch($tpl);    
    
    CRM_Utils_System::civiExit();
  }


  protected function isValidResponse($params){
    // MerchantCode is valid  
    if($params['Ds_MerchantCode'] != $this->_paymentProcessor["user_name"]){
      CRM_Core_Error::debug_log_message("Redsys Response param Ds_MerchantCode incorrect"); 
      return false;
    }

    $signature = strtoupper(sha1( $params["Ds_Merchant_Amount"] .
        $params["Ds_Amount"] .
        $params["Ds_Order"] .
        $params["Ds_MerchantCode"] .
        $params["Ds_Currency"] .
        $params["Ds_Response"] .
        $this->_paymentProcessor["password"] )
    );

    // SHA Signature is valid
    if($params['Ds_Signature'] != $signature){    
      CRM_Core_Error::debug_log_message("Redsys Response param Ds_Signature incorrect"); 
      return false;
    }

    // Contribution exists and is valid
    $contribution = new CRM_Contribute_BAO_Contribution();
    $contribution->id = self::trimAmount($params['Ds_Order']);
    if (!$contribution->find(TRUE)) {
      CRM_Core_Error::debug_log_message("Could not find contribution record: {$contribution->id} in IPN request: ".print_r($params, TRUE));
      echo "Failure: Could not find contribution record for {$contribution->id}<p>";
      return FALSE;
    }

    return true;  
  }

  public function handlePaymentNotification() {          

    $errors = array(
      "101" => "Tarjeta caducada",
      "102" => "Tarjeta en excepción transitoria o bajo sospecha de fraude",
      "104" => "Operación no permitida para esa tarjeta o terminal",
      "9104" => "Operación no permitida para esa tarjeta o terminal",
      "116" => "Disponible insuficiente",
      "118" => "Tarjeta no registrada",
      "129" => "Código de seguridad (CVV2/CVC2) incorrecto",
      "180" => "Tarjeta ajena al servicio",
      "184" => "Error en la autenticación del titular",
      "190" => "Denegación sin especificar Motivo",
      "191" => "Fecha de caducidad errónea",
      "202" => "Tarjeta en excepción transitoria o bajo sospecha de fraude con retirada de tarjeta",
      "912" => " Emisor no disponible",
      "9912" => "Emisor no disponible",
    );


    $module = self::retrieve('md', 'String', 'GET', false);
    $qfKey = self::retrieve('qfKey', 'String', 'GET', false);

    $response = array();
    $response['Ds_Date']              = self::retrieve('Ds_Date', 'String', 'POST', true);
    $response['Ds_Hour']              = self::retrieve('Ds_Hour', 'String', 'POST', true);
    $response['Ds_SecurePayment']     = self::retrieve('Ds_SecurePayment', 'String', 'POST', true);
    $response['Ds_Card_Country']      = self::retrieve('Ds_Card_Country', 'Integer', 'POST', true);
    $response['Ds_Amount']            = self::retrieve('Ds_Amount', 'Integer', 'POST', true);
    $response['Ds_Currency']          = self::retrieve('Ds_Currency', 'Integer', 'POST', true);
    $response['Ds_Order']             = self::retrieve('Ds_Order', 'String', 'POST', true);
    $response['Ds_MerchantCode']      = self::retrieve('Ds_MerchantCode', 'String', 'POST', true);
    $response['Ds_Terminal']          = self::retrieve('Ds_Terminal', 'String', 'POST', true);
    $response['Ds_Signature']         = self::retrieve('Ds_Signature', 'String', 'POST', true);
    $response['Ds_Response']          = self::retrieve('Ds_Response', 'String', 'POST', true);
    $response['Ds_MerchantData']      = self::retrieve('Ds_MerchantData', 'String', 'POST', true);
    $response['Ds_TransactionType']   = self::retrieve('Ds_TransactionType', 'String', 'POST', true);
    $response['Ds_ConsumerLanguage']  = self::retrieve('Ds_ConsumerLanguage', 'String', 'POST', true);
    $response['Ds_AuthorisationCode'] = self::retrieve('Ds_AuthorisationCode', 'String', 'POST', true);     
    

    if($this->isValidResponse($response)){
      switch ($module) {
        case 'contribute':
          if ($response['Ds_Response'] == self::REDSYS_RESPONSE_CODE_ACCEPTED) {            
            $query = "UPDATE civicrm_contribution SET trxn_id='" . $response['Ds_AuthorisationCode'] . "', contribution_status_id=1 where id='" . self::trimAmount($response['Ds_Order']) . "'";            
            CRM_Core_DAO::executeQuery($query);          
          }
          else {
            $error = self::trimAmount($response['Ds_Response']);
            if(array_key_exists($error, $errors)) {
              $error = $errors[$error];
            }
            $cancel_date = CRM_Utils_Date::currentDBDate();

            $query = "UPDATE civicrm_contribution SET contribution_status_id=3, cancel_reason = '" . $error . "' , cancel_date = '" . $cancel_date . "' where id='" . self::trimAmount($response['Ds_Order']) . "'";            
            CRM_Core_DAO::executeQuery($query);
          }
          break;
        case 'event':
          if ($response['Ds_Response'] == self::REDSYS_RESPONSE_CODE_ACCEPTED) {            
            $query = "UPDATE civicrm_contribution SET trxn_id='" . $response['Ds_AuthorisationCode'] . "', contribution_status_id=1 where id='" . self::trimAmount($response['Ds_Order']) . "'";            
            CRM_Core_DAO::executeQuery($query);          
          }
          else {
            $error = self::trimAmount($response['Ds_Response']);
            if(array_key_exists($error, $errors)) {
              $error = $errors[$error];
            }
            $cancel_date = CRM_Utils_Date::currentDBDate();

            $query = "UPDATE civicrm_contribution SET contribution_status_id=3, cancel_reason = '" . $error . "' , cancel_date = '" . $cancel_date . "' where id='" . self::trimAmount($response['Ds_Order']) . "'";            
            CRM_Core_DAO::executeQuery($query);
          }
          break;        
          
        default:
          require_once 'CRM/Core/Error.php'; 
          CRM_Core_Error::debug_log_message("Could not get module name from request url");             
      }
    }
  }

  static function formatAmount($amount, $size, $pad = 0){
    $amount_str = preg_replace('/[\.,]/', '', strval($amount));
    $amount_str = str_pad($amount_str, $size, $pad, STR_PAD_LEFT);
    return $amount_str;
  }

  static function trimAmount($amount, $pad = '0'){
    return ltrim(trim($amount), $pad);
  }

  static function retrieve($name, $type, $location = 'POST', $abort = true) {
    static $store = null;
    $value = CRM_Utils_Request::retrieve($name, $type, $store, false, null, $location);
    if ($abort && $value === null) {
      CRM_Core_Error::debug_log_message("Could not find an entry for $name in $location");
      echo "Failure: Missing Parameter<p>";
      exit();
    }
    return $value;
  }
}
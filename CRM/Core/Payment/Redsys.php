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

    $merchantUrl = $config->userFrameworkBaseURL . 'civicrm/payment/ipn?processor_name=Redsys&mode=' . $this->_mode . '&md=' . $component . '&qfKey=' . $params["qfKey"];

    $miObj = new RedsysAPI;
    $miObj->setParameter("DS_MERCHANT_AMOUNT",$params["amount"] * 100);
    $miObj->setParameter("DS_MERCHANT_ORDER",strval(self::formatAmount($params["contributionID"], 12)));
    $miObj->setParameter("DS_MERCHANT_MERCHANTCODE",$this->_paymentProcessor["user_name"]);
    $miObj->setParameter("DS_MERCHANT_CURRENCY",self::REDSYS_CURRENCY_EURO);
    $miObj->setParameter("DS_MERCHANT_TRANSACTIONTYPE",self::REDSYS_TRANSACTION_TYPE_OPERATION_STANDARD);
    $miObj->setParameter("DS_MERCHANT_TERMINAL",1);
    $miObj->setParameter("DS_MERCHANT_MERCHANTURL",$merchantUrl);
    $miObj->setParameter("DS_MERCHANT_URLOK",$returnURL);
    $miObj->setParameter("DS_MERCHANT_URLKO",$cancelURL);

    $miObj->setParameter("DS_MERCHANT_PRODUCTDESCRIPTION",$params["contributionType_name"]);
    $miObj->setParameter("DS_MERCHANT_TITULAR",$params["first_name"] . " " . $params["last_name"]   );
    $miObj->setParameter("DS_MERCHANT_CONSUMERLANGUAGE",self::REDSYS_LANGUAGE_SPANISH);

    $version="HMAC_SHA256_V1";

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


  protected function isValidResponse($params){
    // MerchantCode is valid
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



    return true;
  }

  public function handlePaymentNotification() {
    $errors = array(
      "101"  => "Tarjeta caducada",
      "102"  => "Tarjeta en excepción transitoria o bajo sospecha de fraude",
      "106"  => "Intentos de PIN excedidos",
      "125"  => "Tarjeta no efectiva",
      "129"  => "Código de seguridad (CVV2/CVC2) incorrecto",
      "180"  => "Tarjeta ajena al servicio",
      "184"  => "Error en la autenticación del titular",
      "190"  => "Denegación del emisor sin especificar motivo",
      "191"  => "Fecha de caducidad errónea",
      "202"  => "Tarjeta en excepción transitoria o bajo sospecha de fraude con retirada de tarjeta",
      "904"  => "Comercio no registrado en FUC",
      "909"  => "Error de sistema",
      "913"  => "Pedido repetido",
      "944"  => "Sesión Incorrecta",
      "950"  => "Operación de devolución no permitida",
      "912"  => "Emisor no disponible",
      "9912" => "Emisor no disponible",
      "9064" => "Número de posiciones de la tarjeta incorrecto",
      "9078" => "Tipo de operación no permitida para esa tarjeta",
      "9093" => "Tarjeta no existente",
      "9094" => "Rechazo servidores internacionales",
      "9104" => "Comercio con “titular seguro” y titular sin clave de compra segura",
      "9218" => "El comercio no permite op. seguras por entrada /operaciones",
      "9253" => "Tarjeta no cumple el check-digit",
      "9256" => "El comercio no puede realizar preautorizaciones",
      "9257" => "Esta tarjeta no permite operativa de preautorizaciones",
      "9261" => "Operación detenida por superar el control de restricciones en la entrada al SIS",
      "9915" => "A petición del usuario se ha cancelado el pago",
      "9929" => "Anulación de autorización en diferido realizada por el comercio",
      "9997" => "Se está procesando otra transacción en SIS con la misma tarjeta",
      "9998" => "Operación en proceso de solicitud de datos de tarjeta",
      "9999" => "Operación que ha sido redirigida al emisor a autenticar",
    );

    $module = self::retrieve('md', 'String', 'GET', false);
    $qfKey = self::retrieve('qfKey', 'String', 'GET', false);


    $miObj = new RedsysAPI;

    $response = array();
    $response["version"] = $_POST["Ds_SignatureVersion"];
    $response["parameters"] = $_POST["Ds_MerchantParameters"];
    $response["signature"] = $_POST["Ds_Signature"];

    $decodecResponseJson = $miObj->decodeMerchantParameters($response["parameters"]);
    $decodecResponse = json_decode($decodecResponseJson);

    $signatureNotif = $miObj->createMerchantSignatureNotif($this->_paymentProcessor["password"],$response["parameters"]);


    // Validations
    if($decodecResponse->Ds_MerchantCode != $this->_paymentProcessor["user_name"]){
      CRM_Core_Error::debug_log_message("Redsys Response param Ds_MerchantCode incorrect");
      return false;
    }
    // Contribution exists and is valid
    $contribution = new CRM_Contribute_BAO_Contribution();
    $contribution->id = self::trimAmount($decodecResponse->Ds_Order);
    if (!$contribution->find(TRUE)) {
      CRM_Core_Error::debug_log_message("Could not find contribution record: {$contribution->id} in IPN request: ".print_r($params, TRUE));
      echo "Failure: Could not find contribution record for {$contribution->id}<p>";
      return FALSE;
    }


    if ($signatureNotif === $response["signature"]) {
      switch ($module) {
        case 'contribute':
          if ($decodecResponse->Ds_Response == self::REDSYS_RESPONSE_CODE_ACCEPTED) {
            $query = "UPDATE civicrm_contribution SET trxn_id='" . $decodecResponse->Ds_AuthorisationCode . "', contribution_status_id=1 where id='" . self::trimAmount($decodecResponse->Ds_Order) . "'";
            CRM_Core_DAO::executeQuery($query);
          }
          else {
            $error = self::trimAmount($decodecResponse->Ds_Response);
            if(array_key_exists($error, $errors)) {
              $error = $errors[$error];
            }
            $cancel_date = CRM_Utils_Date::currentDBDate();

            $query = "UPDATE civicrm_contribution SET contribution_status_id=3, cancel_reason = '" . $error . "' , cancel_date = '" . $cancel_date . "' where id='" . self::trimAmount($decodecResponse->Ds_Order) . "'";
            CRM_Core_DAO::executeQuery($query);
          }
          break;
        case 'event':
          if ($decodecResponse->Ds_Response == self::REDSYS_RESPONSE_CODE_ACCEPTED) {
            $query = "UPDATE civicrm_contribution SET trxn_id='" . $decodecResponse->Ds_AuthorisationCode . "', contribution_status_id=1 where id='" . self::trimAmount($decodecResponse->Ds_Order) . "'";
            CRM_Core_DAO::executeQuery($query);
          }
          else {
            $error = self::trimAmount($decodecResponse->Ds_Response);
            if(array_key_exists($error, $errors)) {
              $error = $errors[$error];
            }
            $cancel_date = CRM_Utils_Date::currentDBDate();
            $query = "UPDATE civicrm_contribution SET contribution_status_id=3, cancel_reason = '" . $error . "' , cancel_date = '" . $cancel_date . "' where id='" . self::trimAmount($decodecResponse->Ds_Order) . "'";
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

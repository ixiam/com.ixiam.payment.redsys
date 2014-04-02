<?
/**
 * CiviCRM Payment Processor for Redsys (before called Sermepa).
 *
 * Redsys is a company based in Spain. Many banks are using its
 * payment processor for their payment processors.
 */

require_once 'CRM/Core/Payment.php';

class CRM_Core_Payment_Redsys extends CRM_Core_Payment {
  CONST CURRENCY_EURO = 978;
  CONST LANGUAGE_SPANISH = 1;
  CONST LANGUAGE_BASQUE = 13;
  CONST LANGUAGE_CATALAN = 3;
  CONST LANGUAGE_GALICIAN = 12;
  CONST TRANSACTION_TYPE_OPERATION_STANDARD = 0;

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
  function doTransferCheckout( &$params, $component ) {
    
    $config = CRM_Core_Config::singleton();    

    $redsysParams["Ds_Merchant_Amount"] = $params["amount"] * 100;
    $redsysParams["Ds_Merchant_Currency"] = self::CURRENCY_EURO;
    $redsysParams["Ds_Merchant_Order"] = $this->_formatAmount($params["contributionID"], 12);
    $redsysParams["Ds_Merchant_ProductDescription"] = $params["contributionType_name"];
    $redsysParams["Ds_Merchant_Titular"] = $params["first_name"] . " " . $params["last_name"];
    $redsysParams["Ds_Merchant_MerchantCode"] = $this->_paymentProcessor["user_name"];
    $redsysParams["Ds_Merchant_MerchantURL"] = CRM_Utils_System::url( "civicrm/payment/ipn", "processor_name=Redsys&mode=" . $this->_mode, TRUE );
    $redsysParams["Ds_Merchant_UrlOK"] = CRM_Utils_System::url( "civicrm/redsys/paymentok", "", TRUE );
    $redsysParams["Ds_Merchant_UrlKO"] = CRM_Utils_System::url( "civicrm/redsys/paymentko", "", TRUE );
    $redsysParams["Ds_Merchant_ConsumerLanguage"] = self::LANGUAGE_SPANISH;
    $redsysParams["Ds_Merchant_Terminal"] = 1;
    $redsysParams["Ds_Merchant_TransactionType"] = self::TRANSACTION_TYPE_OPERATION_STANDARD;    

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
    
    
  }


  /*
   *  This is the function which handles the response
   * when zaakpay redirects the user back to our website
   * after transaction.
   * Refer to the $this->data['returnURL'] in above function to see how the Url should be created
   */

  public function handlePaymentNotification() {

    require_once 'CRM/Utils/Array.php';

    $module = CRM_Utils_Array::value('md', $_GET);
    $qfKey = CRM_Utils_Array::value('qfKey', $_GET);
    $invoiceId = CRM_Utils_Array::value('inId', $_GET);

    switch ($module) {
      case 'contribute':
        if ($_POST['responseCode'] == 100) {
          $query = "UPDATE civicrm_contribution SET trxn_id='" . $_POST['orderId'] . "', contribution_status_id=1 where invoice_id='" . $invoiceId . "'";
          CRM_Core_DAO::executeQuery($query);
          $url = CRM_Utils_System::url('civicrm/contribute/transact', "_qf_ThankYou_display=1&qfKey={$qfKey}", FALSE, NULL, FALSE
          );
        }
        else {
          CRM_Core_Session::setStatus(ts($_POST['responseDescription']), ts('Zaakpay Error:'), 'error');
          $url = CRM_Utils_System::url('civicrm/contribute/transact', "_qf_Confirm_display=true&qfKey={$qfKey}", FALSE, NULL, FALSE
          );
        }

        break;

      case 'event':

        if ($_POST['responseCode'] == 100) { // success code
          $participantId = CRM_Utils_Array::value('pid', $_GET);
          $eventId = CRM_Utils_Array::value('eid', $_GET);

          $query = "UPDATE civicrm_participant SET status_id = 1 where id =" . $participantId . " AND event_id=" . $eventId;
          CRM_Core_DAO::executeQuery($query);

          $query = "UPDATE civicrm_contribution SET trxn_id='" . $_POST['orderId'] . "', contribution_status_id=1 where invoice_id='" . $invoiceId . "'";

          CRM_Core_DAO::executeQuery($query);

          $url = CRM_Utils_System::url('civicrm/event/register', "_qf_ThankYou_display=1&qfKey={$qfKey}", FALSE, NULL, FALSE
          );
        }
        else { // error code
          CRM_Core_Session::setStatus(ts($_POST['responseDescription']), ts('Zaakpay Error:'), 'error');
          $url = CRM_Utils_System::url('civicrm/event/register', "_qf_Confirm_display=true&qfKey={$qfKey}", FALSE, NULL, FALSE
          );
        }

        break;

      default:
        require_once 'CRM/Core/Error.php';
        CRM_Core_Error::debug_log_message("Could not get module name from request url");
        echo "Could not get module name from request url\r\n";
    }
    CRM_Utils_System::redirect($url);
  }

  protected function _formatAmount($amount, $size, $pad = 0){
    $amount_str = preg_replace('/[\.,]/', '', strval($amount));
    $amount_str = str_pad($amount_str, $size, $pad, STR_PAD_LEFT);
    return $amount_str;
  }
}
<?php

class CRM_Core_Payment_RedsysIPN extends CRM_Core_Payment_BaseIPN {

  CONST REDSYS_RESPONSE_CODE_ACCEPTED = '0000';

  private $_errors;
  private $_redsysAPI;

  function __construct() {
    parent::__construct();

    $this->_redsysAPI  = new RedsysAPI;
    $this->_errors = array(
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
      "9913" => "Error en la confirmación que el comercio envía al TPV Virtual (solo aplicable en la opción de sincronización SOAP)",
      "9914" => "Confirmación “KO” del comercio (solo aplicable en la opción de sincronización SOAP)",
      "9915" => "A petición del usuario se ha cancelado el pago",
      "9928" => "Anulación de autorización en diferido realizada por el SIS (proceso batch)",
      "9929" => "Anulación de autorización en diferido realizada por el comercio",
      "9997" => "Se está procesando otra transacción en SIS con la misma tarjeta",
      "9998" => "Operación en proceso de solicitud de datos de tarjeta",
      "9999" => "Operación que ha sido redirigida al emisor a autenticar",
    );
  }

  function single(&$input, &$ids, &$objects, $recur = FALSE, $first = FALSE) {
    $contribution = &$objects['contribution'];

    if (!$recur) {
      if ($contribution->total_amount != $input['amount']) {
        CRM_Core_Error::debug_log_message("Amount values dont match between database and IPN request");
        echo "Failure: Amount values dont match between database and IPN request<p>";
        return FALSE;
      }
    }

    $transaction = new CRM_Core_Transaction();
    if ($input['Ds_Response'] != self::REDSYS_RESPONSE_CODE_ACCEPTED) {
      $error = self::trimAmount($input['Ds_Response']);
      if(array_key_exists($error, $this->_errors)) {
        $input['reasonCode'] = $this->_errors[$error];
      }

      CRM_Core_Error::debug_log_message("Redsys IPN Response: About to cancel contr \n input: " . print_r($input, TRUE) . "\n ids: " . print_r($ids, TRUE) . "\n objects: " . print_r($objects, TRUE) );
      return $this->cancelled($objects, $transaction, $input);
    }
    // check if contribution is already completed, if so we ignore this ipn
    if ($contribution->contribution_status_id == 1) {
      $transaction->commit();
      CRM_Core_Error::debug_log_message("returning since contribution has already been handled");
      echo "Success: Contribution has already been handled<p>";
      return TRUE;
    }

    CRM_Core_Error::debug_log_message("Redsys IPN Response: About complete trans \n input: " . print_r($input, TRUE) . "\n ids: " . print_r($ids, TRUE) . "\n objects: " . print_r($objects, TRUE) );
    return $this->completeTransaction($input, $ids, $objects, $transaction, $recur);
  }

  function getInput(&$input, &$ids) {
    $input = array(
      // GET Parameters
      'module'             => self::retrieve('md', 'String', 'GET', true),
      'component'          => self::retrieve('md', 'String', 'GET', true),
      'qfKey'              => self::retrieve('qfKey', 'String', 'GET', false),
      'contributionID'     => self::retrieve('contributionID', 'String', 'GET', true),
      'contactID'          => self::retrieve('contactID', 'String', 'GET', true),
      'eventID'            => self::retrieve('eventID', 'String', 'GET', false),
      'participantID'      => self::retrieve('participantID', 'String', 'GET', false),
      'membershipID'       => self::retrieve('membershipID', 'String', 'GET', false),
      'contributionPageID' => self::retrieve('contributionPageID', 'String', 'GET', false),
      'relatedContactID'   => self::retrieve('relatedContactID', 'String', 'GET', false),
      'onBehalfDupeAlert'  => self::retrieve('onBehalfDupeAlert', 'String', 'GET', false),
      // POST Parameters
      'Ds_SignatureVersion'   => self::retrieve('Ds_SignatureVersion', 'String', 'POST', true),
      'Ds_MerchantParameters' => self::retrieve('Ds_MerchantParameters', 'String', 'POST', true),
      'Ds_Signature'          => self::retrieve('Ds_Signature', 'String', 'POST', true),
    );
    $decodecResponseJson           = $this->_redsysAPI->decodeMerchantParameters($input["Ds_MerchantParameters"]);
    $decodecResponse               = json_decode($decodecResponseJson);
    $input['Ds_MerchantCode']      = $decodecResponse->Ds_MerchantCode;
    $input['Ds_Response']          = $decodecResponse->Ds_Response;
    $input['Ds_AuthorisationCode'] = $decodecResponse->Ds_AuthorisationCode;
    $input['Ds_Amount']            = $decodecResponse->Ds_Amount;
    $input['amount']               = number_format(($decodecResponse->Ds_Amount / 100), 2);
    $input['trxn_id']              = $decodecResponse->Ds_AuthorisationCode;

    $ids = array(
      'contribution'  => $input['contributionID'],
      'contact'       => $input['contactID'],
    );
    if ($input['module'] == "event") {
      $ids['event']       = $input['eventID'];
      $ids['participant'] = $input['participantID'];
    }
    else {
      $ids['membership']          = $input['membershipID'];
      $ids['related_contact']     = $input['relatedContactID'];
      $ids['onbehalf_dupe_alert'] = $input['onBehalfDupeAlert'];
    }
  }

  function validateData($paymentProcessor, &$input, &$ids, &$objects, $required = TRUE, $paymentProcessorID = NULL) {
    $signatureNotif = $this->_redsysAPI->createMerchantSignatureNotif($paymentProcessor["password"], $input["Ds_MerchantParameters"]);

    if($input['Ds_MerchantCode'] != $paymentProcessor["user_name"]){
      CRM_Core_Error::debug_log_message("Redsys Response param Ds_MerchantCode incorrect");
      return false;
    }

    if ($signatureNotif !== $input['Ds_Signature']){
      CRM_Core_Error::debug_log_message("Redsys signature doesn't match");
      return false;
    }

    return parent::validateData($input, $ids, $objects, $required, $paymentProcessorID);
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

  static function trimAmount($amount, $pad = '0'){
    return ltrim(trim($amount), $pad);
  }

}

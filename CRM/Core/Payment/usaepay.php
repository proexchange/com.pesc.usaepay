<?php

require_once 'CRM/Core/Payment.php';

class CRM_Core_Payment_usaepay extends CRM_Core_Payment {
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
  //static protected $_mode = null;

  /**
   * Constructor
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return void
   */
  function __construct( $mode, &$paymentProcessor ) {
    //$this->_mode             = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName    = ts('USAePay');
  }

  /**
   * singleton function used to manage this object
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return object
   * @static
   *
   */
  static function &singleton( $mode, &$paymentProcessor ) {
      $processorName = $paymentProcessor['name'];
      if (self::$_singleton[$processorName] === null ) {
          self::$_singleton[$processorName] = new CRM_Core_Payment_usaepay( $mode, $paymentProcessor );
      }
      return self::$_singleton[$processorName];
  }

  /**
   * This function checks to see if we have the right config values
   *
   * @return string the error message if any
   * @public
   */
  function checkConfig( ) {
    $config = CRM_Core_Config::singleton();

    $error = array();

    if (empty($this->_paymentProcessor['user_name'])) {
      $error[] = ts('The "Bill To ID" is not set in the Administer CiviCRM Payment Processor.');
    }

    if (!empty($error)) {
      return implode('<p>', $error);
    }
    else {
      return NULL;
    }
  }

  function doDirectPayment(&$params) {

    //$params['gross_amount'] = $params['amount'];

    $fullname = array(
      $params['billing_first_name'],
      $params['billing_middle_name'],
      $params['billing_last_name']
    );
    $exp_month = str_pad($params['credit_card_exp_date']['M'], 2, 0, STR_PAD_LEFT);
    $exp_year = substr($params['credit_card_exp_date']['Y'], -2);

    $tran = new umTransaction;

    $paymentProcessor = civicrm_api3('PaymentProcessor', 'getsingle', array(
      'id' => $params['payment_processor_id'],
    ));

    $tran->gatewayurl = $paymentProcessor['url_site'];

    $tran->addcustomer  = "no";

    $tran->key = $this->_paymentProcessor['user_name'];
    $tran->pin = $this->_paymentProcessor['password'];
    $tran->ip = $_SERVER['REMOTE_ADDR'];
    $tran->testmode = 0;
    $tran->command = 'sale';

    $tran->card = $params['credit_card_number'];
    $tran->cvv2 = $params['cvv2'];
    $tran->exp = $exp_month . $exp_year;

    $tran->amount = $params['amount'];
    $tran->cardholder = implode(' ', $fullname);
    $tran->billfname = $params['billing_first_name'];
    $tran->billlname = $params['billing_last_name'];
    $tran->street = $params['billing_street_address-5'];
    $tran->zip = $params['billing_postal_code-5'];
    $tran->invoice = $params['invoiceID'];
    $tran->description = 'Processed via CiviCRM';

    /* Recurring */
    if(!empty($params['is_recur'])){
      $tran->gatewayurl = $paymentProcessor['url_recur'];
      $tran->addcustomer  = "yes";
      $tran->numleft  = $params['installments'];
      $tran->start = date('Ymd');
      $tran->custid = $params['contributionRecurID'];
      //recurring schedule
      switch ($params['frequency_unit']) {
        case "day":
          $tran->schedule = "daily";
          break;
        case "week":
          $tran->schedule = "weekly";
          break;
        case "month":
          $tran->schedule = "monthly";
          break;
        case "year":
          $tran->schedule = "annually";
          break;
        default:
            $tran->schedule = "monthly";
      }
    }

    if($tran->Process()) {

      $params['contribution_status_id'] = 1;
      $params['payment_status_id'] = 1;
      $params['trxn_id']=$tran->refnum;
      if(!empty($params['is_recur'])){
        // update recur processor_id with custnum
        CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_ContributionRecur', $params['contributionRecurID'],'processor_id', $tran->custnum);
      }

    }
    else {
      $params['trxn_id']=$tran->refnum;
      return self::error($tran->error, $tran->errorcode);
    }
    return $params;
  }

  public function &error($error = NULL, $errorcode = 0) {
    $e = CRM_Core_Error::singleton();
    $e->push($errorcode, 0, NULL, $error);
    return $e;
  }

  /**
   * Sets appropriate parameters for checking out to USAePay
   *
   * @param array $params  name value pair of contribution datat
   *
   * @return void
   * @access public
   *
   */
  function doTransferCheckout( &$params, $component ) {

  }
}

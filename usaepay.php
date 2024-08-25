<?php

require_once 'usaepay.civix.php';
require_once 'packages/usaepay-php/usaepay.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function usaepay_civicrm_config(&$config) {
  _usaepay_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function usaepay_civicrm_install() {
  _usaepay_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function usaepay_civicrm_enable() {
  _usaepay_civix_civicrm_enable();
  usaepay_jobCreate();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function usaepay_civicrm_disable() {

  //disable payment processors that use these payment processor types
  $paymentProcessors = civicrm_api3('PaymentProcessor', 'get', array(
    'sequential' => 1,
    'payment_processor_type_id' => array('IN' => array("USAePay Payments Credit Card", "USAePay Payments ACH")),
  ));
  foreach ($paymentProcessors['values'] as $pp) {
    civicrm_api3('PaymentProcessor', 'create', array('id' => $pp['id'],'is_active' => 0));
  }
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function usaepay_civicrm_managed(&$entities) {
  $entities[] = array(
    'module' => 'com.pesc.usaepay',
    'name' => 'USAePay Credit Card',
    'entity' => 'PaymentProcessorType',
    'params' => array(
      'version' => 3,
      'name' => 'USAePay Payments Credit Card',
      'title' => 'USAePay Payments Credit Card',
      'description' => 'USAePay credit card payment processor',
      'class_name' => 'Payment_usaepay',
      'billing_mode' => 'form',
      'user_name_label' => 'Source Key',
      'password_label' => 'PIN',
      'url_site_default' => 'https://www.usaepay.com/gate',
      'url_recur_default' => 'https://www.usaepay.com/gate',
      'url_site_test_default' => 'https://sandbox.usaepay.com/gate',
      'url_recur_test_default' => 'https://sandbox.usaepay.com/gate',
      'is_recur' => 1,
      'payment_type' => 1,
    ),
  );
  $entities[] = array(
    'module' => 'com.pesc.usaepay',
    'name' => 'USAePay ACH',
    'entity' => 'PaymentProcessorType',
    'params' => array(
      'version' => 3,
      'name' => 'USAePay Payments ACH',
      'title' => 'USAePay Payments ACH',
      'description' => 'USAePay ACH payment processor',
      'class_name' => 'Payment_usaepayACH',
      'billing_mode' => 'form',
      'user_name_label' => 'Source Key',
      'password_label' => 'PIN',
      'url_site_default' => 'https://www.usaepay.com/gate',
      'url_recur_default' => 'https://www.usaepay.com/gate',
      'url_site_test_default' => 'https://sandbox.usaepay.com/gate',
      'url_recur_test_default' => 'https://sandbox.usaepay.com/gate',
      'is_recur' => 1,
      'payment_type' => 5,
      'payment_instrument_id' => 5, /* "EFT/ACH"  */
    ),
  );

  return;
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *

 // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function usaepay_civicrm_navigationMenu(&$menu) {
  _usaepay_civix_insert_navigation_menu($menu, NULL, array(
    'label' => ts('The Page', array('domain' => 'com.pesc.usaepay')),
    'name' => 'the_page',
    'url' => 'civicrm/the-page',
    'permission' => 'access CiviReport,access CiviContribute',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _usaepay_civix_navigationMenu($menu);
} // */


/**
 * Create Hourly Scheduled Job for Usaepay.Fetchtransactions
 * @return boolean success
 */
function usaepay_jobCreate() {
  $currentDomainid=CRM_Core_Config::domainID();
  $result = civicrm_api3('Job', 'get', array('sequential' => 1, 'name' => 'USAePay Fetch Transactions', 'domain_id' => $currentDomainid));
  if($result['count'] < 1) {
    $result = civicrm_api3('Job', 'create', array(
      'sequential' => 1,
      'run_frequency' => 'Hourly',
      'name' => 'USAePay Fetch Transactions',
      'description' => '',
      'is_active' => false,
      'api_entity' => 'Usaepay',
      'api_action' => 'Fetchtransactions',
      'domain_id' => $currentDomainid,
      'parameters' => 'sourcekey=enterkeyhere - required
pin=0000 - optional'
    ));
    return $result['is_error'];
  } else {
    return false;
  }
}

/**
 * Perform CiviCRM API call to grab most recent successful Usaepay.Fetchtransactions
 * @return datetime YYYY-MM-DD HH:MM:SS
 */
function usaepay_recentFetchSuccess() {
  try {
    $result = civicrm_api3('JobLog', 'get', array(
      'sequential' => 1,
      'name' => 'USAePay Fetch Transactions',
      'description' => array('LIKE' => "%Finished execution of USAePay Fetch Transactions with result: Success%"),
      'options' => array('sort' => 'run_time DESC', 'limit' => 1),
      'return' => array('run_time'),
    ));
  }
  catch (CiviCRM_API3_Exception $e) {
    $error = $e->getMessage();
  }
  if(!empty($error)){
      if (strpos($error, 'API (JobLog, get) does not exist') !== false) {
        return 0;
      }
    }
  if(!empty($result['values']))
    return $result['values'][0]['run_time'];
}

/**
 * Pulls out relevant information from API results
 * @return array
 */
function usaepay_getRelevant($usaepayResults,$source=''){
  //empty array to hold relevant info
  $relevant = array();
  //loop thru api results
  foreach ($usaepayResults as $tr) {
    //if source name filter is used, filter those out
    if(!empty($source)){
      if($tr['Source'] != $source){
        continue;
      }
    }
    //get only important info
    $t = array(
      'FirstName' => $tr['BillingAddress']['FirstName'], 
      'LastName' => $tr['BillingAddress']['LastName'],
      'DateTime' => $tr['DateTime'],
      'Amount' => $tr['Details']['Amount'],
      'Result' => $tr['Response']['Result'],
      'Status' => $tr['Response']['Status'],
      'RefNum' => $tr['Response']['RefNum'],
      'Invoice' => $tr['Details']['Invoice'],
      'CustomerID' => $tr['CustomerID'],
      'Source' => $tr['Source'],
    );
    //assemble clean array
    array_push($relevant, $t);
  }
  return $relevant;
}

/**
 * Perform search of civi for mathching contributions
 * return only NEW payments not yet entered in CiviCRM
 * @return array
 */
function usaepay_getNewContibutions($relevant){
  $contributionsNew = array();
  foreach ($relevant as $tr) {
    $contribution = civicrm_api3('Contribution', 'get', array(
      'sequential' => 1,
      'trxn_id' => $tr['RefNum'],
    ));
    //matched payments
    if(empty($contribution['count']) && empty($contribution['is_error'])){
      array_push($contributionsNew, $tr);
    }
  }
  return $contributionsNew;
}

/**
 * Accepts array of NEW Payments 
 * Perform search of civi for mathching recurring contributions
 * then add contribution to DB
 * @return array
 */
function usaepay_addRecurringContributionPayment($contributionsNew){
  $contributionsAdded = array();
  foreach ($contributionsNew as $addPayment) {
    if(!empty($addPayment['CustomerID'])){
      //Look for matching recurring contributions and customerIDs
      $contribRecur = civicrm_api3('ContributionRecur', 'getsingle', array(
        'id' => $addPayment['CustomerID'],
      ));
      //make sure a recurring contribution is defined in CivCRM
      if(empty($contribRecur['is_error'])){
        $addContribParams = array(
          'financial_type_id' => $contribRecur['financial_type_id'],
          'contact_id' => $contribRecur['contact_id'],
          'total_amount' => $addPayment['Amount'],
          'receive_date' => $addPayment['DateTime'],
          'invoice_id' => $addPayment['Invoice'],
          'invoice_number' => $addPayment['Invoice'],
          'trxn_id' => $addPayment['RefNum'],
          'contribution_recur_id' => $addPayment['CustomerID'],
        );
        if($addPayment['Result'] == 'Error'){
          $addContribParams['contribution_status_id'] = 'Failed';
        }
        $addContrib = civicrm_api3('Contribution', 'create', $addContribParams);
        array_push($contributionsAdded, $addContrib);
      }
    }
  }
  return $contributionsAdded;
}

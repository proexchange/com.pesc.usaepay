<?php

/**
 * Usaepay.FetchTransactions API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_usaepay_fetchtransactions_spec(&$spec) {
  $spec['sourcekey']['api.required'] = 1;
  $spec['pin']['api.required'] = 1;
  $spec['sourcename']['api.required'] = 0;
}

/**
 * Usaepay.FetchTransactions API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_usaepay_fetchtransactions($params) {
  if (array_key_exists('sourcekey', $params)) {


    watchdog('usaepay-apiparams','<pre>'.print_r($params,1).'</pre>');

    //for live server use 'www' for test server use 'sandbox'
    $wsdl='https://sandbox.usaepay.com/soap/gate/0AE595C1/usaepay.wsdl';

    // instantiate SoapClient object as $client
    $client = new SoapClient($wsdl);

    $sourcekey = $params['sourcekey'];
    $pin = $params['pin'];
    $sourcename = (empty($params['sourcename'])) ? '' : $params['sourcename'];


    // generate random seed value
    $seed=time() . rand();

    // make hash value using sha1 function
    $clear= $sourcekey . $seed . $pin;
    $hash=sha1($clear);

    // assembly ueSecurityToken as an array
    $token=array(
    'SourceKey'=>$sourcekey,
    'PinHash'=>array(
       'Type'=>'sha1',
       'Seed'=>$seed,
       'HashValue'=>$hash
     ),
     'ClientIP'=>$_SERVER['REMOTE_ADDR'],
    );

    $recent = usaepay_recentFetchSuccess();
    watchdog('usaepay-recent','<pre>'.print_r($recent,1).'</pre>');

    try { 
      // Create search parameter list 
      $search=array( 
        array( 
          'Field'=>'created',  
          'Type'=>'gt',  
          'Value'=>$recent), 
        array( 
          'Field'=>'RecCustID',  
          'Type'=>'gt',  
          'Value'=>'0'),
      );


    
      $start=0; 
      $limit=999; 
      $matchall=true; 
      $sort='created';

      watchdog('usaepay-search','<pre>'.print_r($search,1).'</pre>');

      //RUN transaction search
      $res=$client->searchTransactions($token,$search,$matchall,$start,$limit,$sort); 

      //Make nice (non-object) array
      $resarray = json_decode(json_encode($res->Transactions), true);
      watchdog('usaepay-results','<pre>'.print_r($resarray,1).'</pre>');

      //Get only relevant infomation
      $relevant = usaepay_getRelevant($resarray,$sourcename);
      watchdog('usaepay-relevant','<pre>'.print_r($relevant,1).'</pre>');

      //look in CiviCRM for mathcing contribution, return only NEW payments not in DB
      $contributionsNew = usaepay_getNewContibutions($relevant);
      watchdog('usaepay-new','<pre>'.print_r($contributionsNew,1).'</pre>');

      $contributionsAdded = usaepay_addRecurringContributionPayment($contributionsNew);
      watchdog('usaepay-added','<pre>'.print_r($contributionsAdded,1).'</pre>');

      // Retrun results
      return civicrm_api3_create_success($contributionsAdded, $params, 'NewEntity', 'NewAction');
    } 
    catch(SoapFault $e) {
      throw new API_Exception(/*errorMessage*/ print_r($e->getMessage(),1).' | LastResponse:'.print_r($client->__getLastResponse(),1), /*errorCode*/ 0000);
    } 

  }
  else {
    throw new API_Exception(/*errorMessage*/ 'Error', /*errorCode*/ 0);
  }
}





<?php
/**
 * @author     Ahmad Rajabi (Ahmad@rajabi.us)
 * @copyright  2016 Ahmad Rajabi
 */
require_once 'conf.php';
// checked all fields to set value
if (isset($_POST['reference']) && isset($_POST['currency']) && isset($_POST['amount']) && isset($_POST['customer']) && isset($_POST['started']) && isset($_POST['expires']) && isset($_POST['gateway']) && isset($_POST['return_url']) && isset($_POST['signature'])) {

  // test fields function
   function test_data($data)
   {
       $data = trim($data);
       $data = stripslashes($data);
       $data = htmlspecialchars($data);

       return $data;
   }

// Get user ip function
    function getUserIP()
    {
        $client = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote = $_SERVER['REMOTE_ADDR'];

        if (filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        } else {
            $ip = $remote;
        }

        return $ip;
    }

    // set values to var
    $ref = test_data($_POST['reference']);
    $currency = test_data($_POST['currency']);
    $amount = test_data($_POST['amount']);
    $customer = test_data($_POST['customer']);
    $started = test_data($_POST['started']);
    $expires = test_data($_POST['expires']);
    $gateway = test_data($_POST['gateway']);
    $re_url = test_data($_POST['return_url']);
    $sig = test_data($_POST['signature']);
    $rial = $amount * $rset; //change USD to IRR
    $recipt = 000; //difault res , it's update afther transaction
    $ip = getUserIP(); // customer real ip
    $callback = $backurl."?oid={$oid}"; //callback url
    $status = 'pending';

   //check fields for not empty
   if (!empty($ref) && !empty($currency) && !empty($amount) && !empty($customer) && !empty($started) && !empty($expires) && !empty($gateway) && !empty($re_url) && !empty($sig)) {
       // set data to array for generate signature
   $fields = ['reference' => $ref, 'currency' => $currency, 'amount' => $amount, 'customer' => $customer, 'started' => $started, 'expires' => $expires, 'gateway' => $gateway, 'return_url' => $re_url];


       function sign($s, $s2, $f)
       {
           $f = implode('', $f).$s;

           return hash_hmac('sha512', $f, $s2);
       }

       $sig1 = sign($secret_key, $secret_key2, $fields);

       if ($sig == $sig1) {
           $query = 'INSERT INTO `resello` (`id` ,`customer` ,`amount` ,`ip` ,`status` ,`recipt` ,`reference` ,`irr`) VALUES (NULL ,?,?,?,?,?,?,?)';
           $stmt = $db->prepare($query);
           $stmt->bindParam(1, $customer);
           $stmt->bindParam(2, $amount);
           $stmt->bindParam(3, $ip);
           $stmt->bindParam(4, $status);
           $stmt->bindParam(5, $recipt);
           $stmt->bindParam(6, $ref);
           $stmt->bindParam(7, $rial);
           $stmt->execute();
           $oid = $db->LastInsertId();
           if ($stmt->rowCount() == 1) {
               $client = new SoapClient('https://de.zarinpal.com/pg/services/WebGate/wsdl', ['encoding' => 'UTF-8']);
               $result = $client->PaymentRequest(
                              [
                                      'MerchantID'    => $merchantCode,
                                      'Amount'        => $rial,
                                      'Description'   => $customer.' - '.$oid,
                                      'Email'         => null,
                                      'Mobile'        => null,
                                      'CallbackURL'   => $callback,
                                  ]
          );

               if ($result->Status == 100) {
                   header('Location: https://www.zarinpal.com/pg/StartPay/'.$result->Authority);
               } else {
                   echo'ERR: '.$result->Status;
               }
           } else {
               die('Data insert broken');
           }
       } else {
           die('Signature is not valid');
       }
   } else {
       die('Err');
   }
}

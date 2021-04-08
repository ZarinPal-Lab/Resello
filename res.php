<?php
/**
 * @package    Resello custom payment gateway
 * @author     Ahmad Rajabi & Armin Zahedi
 * @copyright  2016 Ahmad Rajabi
 */
require_once("conf.php");
// checked all fields to set value
if(isset($_POST['reference']) && isset($_POST['currency']) && isset($_POST['amount']) && isset($_POST['customer']) && isset($_POST['started']) && isset($_POST['expires']) && isset($_POST['gateway']) && isset($_POST['return_url']) && isset($_POST['signature'])){

    // test fields function
    function test_data($data){

        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;

    }

// Get user ip function
    function getUserIP(){

        $client  = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote  = $_SERVER['REMOTE_ADDR'];

        if(filter_var($client, FILTER_VALIDATE_IP))
        {
            $ip = $client;
        }
        elseif(filter_var($forward, FILTER_VALIDATE_IP))
        {
            $ip = $forward;
        }
        else
        {
            $ip = $remote;
        }

        return $ip;
    }

    // set values to var
    $ref       = test_data($_POST['reference']);
    $currency  = test_data($_POST['currency']);
    $amount    = test_data($_POST['amount']);
    $customer  = test_data($_POST['customer']);
    $started   = test_data($_POST['started']);
    $expires   = test_data($_POST['expires']);
    $gateway   = test_data($_POST['gateway']);
    $re_url    = test_data($_POST['return_url']);
    $sig       = test_data($_POST['signature']);
    $rial      = $amount * $rset ;//change USD to IRR
    $recipt    = 000 ; //difault res , it's update afther transaction
    $ip        = getUserIP(); // customer real ip
    $status    ='pending';

    //check fields for not empty
    if(!empty($ref) && !empty($currency) && !empty($amount) && !empty($customer) && !empty($started) && !empty($expires) && !empty($gateway) && !empty($re_url) && !empty($sig)){
        // set data to array for generate signature
        $fields = array(
            'reference'  => $ref,
            'currency'   => $currency,
            'amount'     => $amount,
            'customer'   => $customer,
            'started'    => $started,
            'expires'    => $expires,
            'gateway'    => $gateway,
            'return_url' => $re_url
        );


        function sign($s, $s2, $f) {

            $f = implode('', $f) . $s;
            return hash_hmac('sha512', $f, $s2);
        }

        $sig1 = sign($secret_key, $secret_key2, $fields);

        if($sig == $sig1){

            $query = "INSERT INTO `resello` (`id` ,`customer` ,`amount` ,`ip` ,`status` ,`recipt` ,`reference` ,`irr`) VALUES (NULL ,?,?,?,?,?,?,?)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1,$customer);
            $stmt->bindParam(2,$amount);
            $stmt->bindParam(3,$ip);
            $stmt->bindParam(4,$status);
            $stmt->bindParam(5,$recipt);
            $stmt->bindParam(6,$ref);
            $stmt->bindParam(7,$rial);
            $stmt->execute();
            $oid      = $db->LastInsertId();
            $callback = $backurl."?oid={$oid}"; //callback url
            if($stmt->rowCount() == 1){

                $param_request = array(
                    'merchant_id' => $merchantCode,
                    'amount' => $rial * 10,
                    'description' => $customer." - ".$oid,
                    'callback_url' => $callback
                );
                $jsonData = json_encode($param_request);

                $ch = curl_init('https://api.zarinpal.com/pg/v4/payment/request.json');
                curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v4');
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($jsonData)
                ));


                $result = curl_exec($ch);
                $err = curl_error($ch);
                $result = json_decode($result, true, JSON_PRETTY_PRINT);
                curl_close($ch);

                if($result['data']['code'] == 100)
                {
                    Header('Location: https://www.zarinpal.com/pg/StartPay/'.$result['data']["authority"]);
                } else {
                    echo'ERR: '.$result['errors']['code'];
                }

            }else{

                die('Data insert broken') ;
            }
        }else{

            die("Signature is not valid");
        }

    }else{

        die("Err");
    }

}
?>

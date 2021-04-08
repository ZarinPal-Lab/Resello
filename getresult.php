<?php
/**
 * @package    Resello custom payment gateway
 * @author     Ahmad Rajabi & Armin Zahedi
 * @copyright  2021 Ahmad Rajabi
 */
require_once ("conf.php");

//check Get Data
function test_data($data){

    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;

}
//set variable
$Authority  = test_data($_GET['Authority']);
$status     = test_data($_GET['Status']);
$invoiceNum = test_data($_GET['oid']);
$st         = 'paid';
//signature fungtion
function sign($s, $s2, $fi) {

    $fi = implode('', $fi) . $s;
    return hash_hmac('sha512', $fi, $s2);
}

$query = "SELECT * FROM `resello` WHERE `id` = ?";
$stmt  = $db->prepare($query);
$stmt->bindParam(1,$invoiceNum);
$stmt->execute();
$row   = $stmt->fetch();

$reference = $row['reference'];
$damount   = $row['irr'];
$melissa   = array('reference'=>$reference ,'status'=>'AUTHORISED');

$signature = sign($secret_key, $secret_key2, $melissa);

if($status == 'OK'){

    $param_verify = array("merchant_id" => $merchantCode, "authority" => $Authority, "amount" => $damount * 10);
    $jsonData = json_encode($param_verify);
    $chq = curl_init('https://api.zarinpal.com/pg/v4/payment/verify.json');
    curl_setopt($chq, CURLOPT_USERAGENT, 'ZarinPal Rest Api v4');
    curl_setopt($chq, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($chq, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($chq, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chq, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ));

    $result = curl_exec($chq);
    $err = curl_error($chq);
    curl_close($chq);
    $result = json_decode($result, true);
    
    if($result['data']['code'] == 100){

        $f = array(
            'reference' => $reference,
            'status'    => 'AUTHORISED',
            'signature' => $signature
        );

        $fields_string = '';

        foreach($f as $key=>$value) {

            $fields_string .= $key . '=' . $value . '&';
        }


        $f_s = rtrim($fields_string, '&');
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_URL, $notify);
        curl_setopt($ch, CURLOPT_POST, count($f));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $f_s);
        $curl = curl_exec($ch);
        curl_close($ch);

        if($curl){

            $q   = "UPDATE `resello` SET `status` = ? , `recipt` = ? WHERE `id` = ?";
            $stm = $db->prepare($q);
            $stm->bindParam(1,$st);
            $stm->bindParam(2,$result['data']['ref_id']);
            $stm->bindParam(3,$invoiceNum);
            $r   = $stm->execute();

            if($r){

                echo "Your payment has been successful and your transaction id is : " . $result['data']['ref_id'] ."<br/><br/>";
                echo "Please click <a href=\"$url\">here</a> to back to your cutomer panel";


            }else{

                die("query failed , please contact system admin");
            }



        }else{

            die("Curl error , please contact system admin . ERR = 1");
        }



    }else{


        $mahak     = array('reference'=>$reference ,'status'=>'FAILED');

        $signature = sign($secret_key, $secret_key2, $mahak);


        $f = array(

            'reference' => $reference,
            'status'    => 'FAILED',
            'signature' => $signature
        );

        $fields_string = '';

        foreach($f as $key=>$value) {

            $fields_string .= $key . '=' . $value . '&';
        }

        $f_s = rtrim($fields_string, '&');
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_URL, $notify);
        curl_setopt($ch, CURLOPT_POST, count($f));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $f_s);
        $curl = curl_exec($ch);
        curl_close($ch);
        if($curl){
            header("Location: $url");
        }else{
            die("Curl error , please contact system admin . ERR = 2");
        }
    }
}else{

    $mahak     = array('reference'=>$reference ,'status'=>'FAILED');

    $signature = sign($secret_key, $secret_key2, $mahak);


    $f = array(

        'reference' => $reference,
        'status'    => 'FAILED',
        'signature' => $signature
    );

    $fields_string = '';

    foreach($f as $key=>$value) {

        $fields_string .= $key . '=' . $value . '&';
    }

    $f_s = rtrim($fields_string, '&');
    $ch  = curl_init();
    curl_setopt($ch, CURLOPT_URL, $notify);
    curl_setopt($ch, CURLOPT_POST, count($f));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $f_s);
    $curl = curl_exec($ch);
    curl_close($ch);
    if($curl){
        header("Location: $url");
    }else{
        die("Curl error , please contact system admin . ERR = 3");
    }

}
?>

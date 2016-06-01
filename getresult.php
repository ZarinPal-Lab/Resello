<?php
/**
 * @author     Ahmad Rajabi (Ahmad@rajabi.us)
 * @copyright  2016 Ahmad Rajabi
 */
require_once 'conf.php';
require_once 'functions.php';

    //set variable
    $Authority = test_data($_GET['Authority']);
    $status = test_data($_GET['Status']);
    $invoiceNum = test_data($_GET['oid']);
    $st = 'paid';

    $query = 'SELECT * FROM `resello` WHERE `id` = ?';
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $invoiceNum);
    $stmt->execute();
    $row = $stmt->fetch();

    $reference = $row['reference'];
    $damount = $row['irr'];
    $melissa = ['reference' => $reference, 'status' => 'AUTHORISED'];

    $signature = sign($secret_key, $secret_key2, $melissa);

    if ($status == 'OK') {
        $client = new SoapClient('https://www.zarinpal.com/pg/services/WebGate/wsdl', ['encoding' => 'UTF-8']);

        $result = $client->PaymentVerification([
            'MerchantID'     => $merchantCode,
            'Authority'      => $Authority,
            'Amount'         => $damount,
        ]);
        if ($result->Status == 100) {
            $f = [
                'reference' => $reference,
                'status'    => 'AUTHORISED',
                'signature' => $signature,
            ];

            $fields_string = '';

            foreach ($f as $key => $value) {
                $fields_string .= $key.'='.$value.'&';
            }


            $f_s = rtrim($fields_string, '&');
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $notify);
            curl_setopt($ch, CURLOPT_POST, count($f));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $f_s);
            $curl = curl_exec($ch);
            curl_close($ch);

            if ($curl) {
                $q = 'UPDATE `resello` SET `status` = ? , `recipt` = ? WHERE `id` = ?';
                $stm = $db->prepare($q);
                $stm->bindParam(1, $st);
                $stm->bindParam(2, $result->RefID);
                $stm->bindParam(3, $invoiceNum);
                $r = $stm->execute();

                if ($r) {
                    echo 'Your payment has been successful and your transaction id is : '.$result->RefID.'<br/><br/>';
                    echo "Please click <a href=\"$url\">here</a> to back to your cutomer panel";
                } else {
                    die('query failed , please contact system admin');
                }
            } else {
                die('Curl error , please contact system admin . ERR = 1');
            }
        } else {
            $mahak = ['reference' => $reference, 'status' => 'FAILED'];

            $signature = sign($secret_key, $secret_key2, $mahak);

            $f = [
                'reference' => $reference,
                'status'    => 'FAILED',
                'signature' => $signature,
            ];

            $fields_string = '';

            foreach ($f as $key => $value) {
                $fields_string .= $key.'='.$value.'&';
            }

            $f_s = rtrim($fields_string, '&');
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $notify);
            curl_setopt($ch, CURLOPT_POST, count($f));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $f_s);
            $curl = curl_exec($ch);
            curl_close($ch);
            if ($curl) {
                header("Location: $url");
            } else {
                die('Curl error , please contact system admin . ERR = 2');
            }
        }
    } else {
        $mahak = ['reference' => $reference, 'status' => 'FAILED'];

        $signature = sign($secret_key, $secret_key2, $mahak);

        $f = [
            'reference' => $reference,
            'status'    => 'FAILED',
            'signature' => $signature,
        ];

        $fields_string = '';

        foreach ($f as $key => $value) {
            $fields_string .= $key.'='.$value.'&';
        }

        $f_s = rtrim($fields_string, '&');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $notify);
        curl_setopt($ch, CURLOPT_POST, count($f));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $f_s);
        $curl = curl_exec($ch);
        curl_close($ch);
        if ($curl) {
            header("Location: $url");
        } else {
            die('Curl error , please contact system admin . ERR = 3');
        }
    }

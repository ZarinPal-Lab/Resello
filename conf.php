<?php
/**
 * @package    Resello custom payment gateway
 * @author     Ahmad Rajabi (Ahmad@rajabi.us)
 * @copyright  2016 Ahmad Rajabi
 */
$merchantCode = ""; // set merchant code here . for example = "123456" (string)
$secret_key   = ""; //set secret key 1 (string)
$secret_key2  = ""; // set secret key 2 (string)
$notify       = ""; // set notify address (string)
$url          = "http://"; //set domain customer panel link (string)
$backurl      = "http://"; //set getresult.php path (string)
$rset         = 38; // change currency from usd to toman (integer)
define('USER', ''); //set database username (string)
define('PASS', ''); //set database password (string)
define('DBHOST', 'localhost'); //set database address (string)
define('DBNAME', 'yourdbname'); //set database name (string)
try{
$pdo = "mysql:host=".DBHOST.";dbname=".DBNAME;
$db = new PDO($pdo,USER,PASS);
return $db ;
}
catch(PDOException $e){

	echo "Database connection failed";
}
?>

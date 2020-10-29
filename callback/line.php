<?php
    include_once("../config.php");
    include_once("../class/pay.class.php");
    include_once("../class/payLinePay.class.php");

	$line = new payLinePay(
		LINE_PAY_CHANNELID,
		LINE_PAY_CHANNELSECRET,
		LINE_PAY_MERCHANTNAME
	);

	if($_GET["transactionId"]){
		if(!$orderNumber = $line->check($_GET["transactionId"],$_GET["amount"])){
			exit("付款失敗");
		}
		$orderNumber = substr($orderNumber, 0, ORDER_SIZE);
		exit("付款成功");
	}
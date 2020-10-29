<?php
    include_once("../config.php");
    include_once("../class/pay.class.php");
    include_once("../class/payYiPay.class.php");

    $data = $_POST;

	$yipay = new MTsung\payYiPay(
		YI_PAY_MERCHANTID,
		YI_PAY_HASHKEY,
		YI_PAY_HASHIV
	);

	//檢查回傳是否正確
	if($yipay->getCheckCode($data,"2") != $data["checkCode"]){
		exit("Check code error.");
	}

	$orderNumber = substr($data["orderNo"], 0, ORDER_SIZE);

	if($data["statusCode"] == "00"){//成功
		exit("付款成功");
	}

	exit("error:".$data["statusCode"].". ".$data["statusMessage"]);
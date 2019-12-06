<?php
    include_once("../config.php");
    include_once("../class/pay.class.php");
    include_once("../class/payFisc.class.php");

    $data = $_POST;
    
	$fisc = new payFisc(
		FISC_MER_ID,
		FISC_MERCHANT_ID,
		FISC_TERMINAL_ID,
		FISC_MERCHANT_NAME
	);

	//檢查碼
	if(!$fisc->checkRespToken($data)){
		exit("Check code error.");
	}

	//真實訂單編號
	$orderNumber = substr($data["lidm"], 0, ORDER_SIZE);

	//成功
	if($data["status"] == "0"){
		exit("付款成功");
	}

	//失敗
	exit("error:".$data["errcode"].". ".$data["errDesc"]);
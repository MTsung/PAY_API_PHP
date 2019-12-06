<?php
    include_once("../config.php");
    include_once("../class/pay.class.php");
    include_once("../class/payEpos.class.php");

    $data = $_POST;
    
    $epos = new payEpos(
		EPOS_MER_ID,
		EPOS_MERCHANT_ID,
		EPOS_TERMINAL_ID,
		EPOS_CHECK_ID
	);

	//檢查碼
	if(!$epos->checkCheckValue($data)){
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
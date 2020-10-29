<?php
    include_once("../config.php");
    include_once("../class/pay.class.php");
    include_once("../class/payPayNow.class.php");

    $data = $_POST;
    
	$payNow = new payPayNow(
		PAY_NOW_WEBNO,
		PAY_NOW_PASSWORD,
		PAY_NOW_ECPLATFORM
	);

	//檢查碼
	if(!$payNow->checkPassCode($data)){
		exit("Check code error.");
	}

	$data = array_map("urldecode", $data);

	$orderNumber = substr($data["OrderNo"], 0, ORDER_SIZE);

	if($data["TranStatus"] == "S"){//S 表交易成功；F 表交易失敗
		exit("付款成功");
	}
	
	//失敗
	exit("error:".$data["TranStatus"].". ".$data["ErrDesc"]);
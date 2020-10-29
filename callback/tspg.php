<?php
    include_once("../config.php");
    include_once("../class/pay.class.php");
    include_once("../class/payTspgPay.class.php");

	$tspg = new payTspgPay(
		TSPG_MID,
		TSPG_TID
	);

	//POST 參數僅顯示 判斷是否付款 以幕後傳送的json為準
	if($_POST){
		exit($_POST["ret_code"].". ".$_POST["ret_msg"]);
	}

	//json
	if(!$data = json_decode(file_get_contents('php://input'), true)){
    	exit('Data is Null.');
	}

	//檢查mid tid是否正確
	if($data["mid"] != TSPG_MID || $data["tid"] != TSPG_TID){
		exit("tspg mid or tid error.");
	}

	$orderNumber = substr($data["params"]["order_no"], 0, ORDER_SIZE);

	if($data["params"]["ret_code"] == "00"){//成功
		exit("付款成功");
	}

	exit("error");
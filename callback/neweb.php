<?php
    include_once("../config.php");
    include_once("../class/pay.class.php");
    include_once("../class/payNeweb.class.php");

    $data = $_POST;

	$neweb = new payNeweb(
		NEWEB_MERCHANT_ID,
		NEWEB_HASH_KEY,
		NEWEB_HASH_IV
	);

	//檢查回傳是否正確
	if(!$neweb->checkTradeSha($data["TradeSha"],$data["TradeInfo"])){
		exit("Check code error.");
	}

	//data解碼
	$data = $neweb->decodeTradeInfo($data["TradeInfo"]);

	//真實訂單編號
	$orderNumber = substr($data["Result"]["MerchantOrderNo"], 0, ORDER_SIZE);

	//成功
	if(strtoupper($data["Status"]) == "SUCCESS" && isset($data["Result"]["PayTime"])){
		//ReturnURL 與 NotifyURL 均會攜帶回應參數回傳，請勿設定相同網址進而造成交易誤判。例：ReturnURL 與 NotifyURL 設定相同網址，則該網址會接收到兩次付款完成資訊，但實際付款完成只有一次，將會影響商店出貨及帳務的正確性。
		if(isset($_GET["isNotify"]) && $_GET["isNotify"]=="1"){
			//也是付款成功 
			//以幕後方式回傳給商店相關支付結果資料
		}else{
			exit("付款成功");
		}
	}

	//失敗
	exit("error:".$data["Status"].". ".$data["Message"]);
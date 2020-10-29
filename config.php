<?php
	//網站根目錄
	define('WEB_PATH', str_replace(str_replace("\\","/",$_SERVER['DOCUMENT_ROOT']),"",str_replace("\\","/",dirname(__FILE__))));

	//是否ssl
	define('HTTP', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? "https://" : "http://");
	
	//網站網址
	define('HTTP_PATH', HTTP.$_SERVER['HTTP_HOST'].WEB_PATH.'/');

	//訂單編號長度
	//傳送到金流端會加入後墜，避免訂單編號重複
	define('ORDER_SIZE', 10);

	//第一銀行
	define('FISC_IS_TEST', true);
	define('FISC_MER_ID', '');
	define('FISC_MERCHANT_ID', '');
	define('FISC_TERMINAL_ID', '');
	define('FISC_MERCHANT_NAME', '');
	define('FISC_CALLBACK', HTTP_PATH.'callback/fisc.php');

	//華南
	define('EPOS_IS_TEST', true);
	define('EPOS_MER_ID', '');
	define('EPOS_MERCHANT_ID', '');
	define('EPOS_TERMINAL_ID', '');
	define('EPOS_CHECK_ID', '');
	define('EPOS_CALLBACK', HTTP_PATH.'callback/epos.php');

	//藍新
	define('NEWEB_IS_TEST', true);
	define('NEWEB_MERCHANT_ID', '');
	define('NEWEB_HASH_KEY', '');
	define('NEWEB_HASH_IV', '');
	define('NEWEB_CALLBACK', HTTP_PATH.'callback/neweb.php');

	//LINE PAY
	define('LINE_PAY_IS_TEST', true);
	define('LINE_PAY_CHANNELID', '');
	define('LINE_PAY_CHANNELSECRET', '');
	define('LINE_PAY_MERCHANTNAME', '');
	define('LINE_PAY_CALLBACK', HTTP_PATH.'callback/line.php?amount=');


	abstract class paymentMethodType{

		const FISC             = 1;//信用卡刷卡(財金資訊股份有限公司)
		const EPOS             = 2;//信用卡刷卡(華南銀行)
		const CREDIT_NEWEB     = 3;//信用卡一次付清(藍新)
		const ANDROIDPAY_NEWEB = 4;//Google Pay(藍新)
		const SAMSUNGPAY_NEWEB = 5;//Samsung Pay(藍新)
		const INSTFLAG_NEWEB   = 6;//信用卡分期(藍新)
		const CREDITRED_NEWEB  = 7;//信用卡紅利(藍新)
		const UNIONPAY_NEWEB   = 8;//信用卡銀聯卡(藍新)
		const WEBATM_NEWEB     = 9;//WEBATM(藍新)
		const VACC_NEWEB       = 10;//ATM 轉帳(藍新)
		const CVS_NEWEB        = 11;//超商代碼繳費(藍新)
		const BARCODE_NEWEB    = 12;//超商條碼繳費(藍新)
		const P2G_NEWEB        = 13;//ezPay 電子錢包(藍新)
		const CVSCOM_NEWEB_N   = 14;//超商取貨不付款(藍新)
		const CVSCOM_NEWEB_Y   = 15;//超商取貨付款(藍新)
		const LINE_PAY         = 16;//LINE PAY

	}


	//測試資料
	$order = [
		"orderNumber" => "MT00000001",
		"total" => 500,
		"freight" => 60,
		"handlingFee" => 30,
		"buyEmail" => ""
	];
	$orderList = [
		[
			"name" => "商品名稱",
			"count" => 1
		],
		[
			"name" => "商品名稱1",
			"count" => 2
		],
	];
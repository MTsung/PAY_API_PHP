<?php

/**
* 藍新
*/
class payNeweb extends pay{

	protected $cipherMethod = "AES-256-CBC";//加密方式
	protected $Version = "1.5";//版本
	protected $RespondType = "JSON";//回傳解密後的data格式
	protected $serviceURL;
	protected $invoiceURL;
	protected $returnURL = NEWEB_CALLBACK;
	protected $notifyURL = NEWEB_CALLBACK."?isNotify=1";
	protected $backURL = HTTP_PATH;
	protected $MerchantID;
	protected $HashKey;
	protected $HashIV;
	protected $rand;
	protected $isTest = NEWEB_IS_TEST;

	/**
	 * [__construct description]
	 * @param [type] $MerchantID [description]
	 * @param [type] $HashKey    [description]
	 * @param [type] $HashIV     [description]
	 */
	function __construct($MerchantID,$HashKey,$HashIV){
		if($this->isTest){
			//測試
			$this->serviceURL = "https://ccore.newebpay.com/MPG/mpg_gateway";
		}else{
			$this->serviceURL = "https://core.newebpay.com/MPG/mpg_gateway";
		}
		$this->MerchantID = $MerchantID;
		$this->HashKey = $HashKey;
		$this->HashIV = $HashIV;
		$this->rand = strtoupper(base_convert(microtime(true) % 100000,10,36));				//訂單編號後墜 防止訂單編號重複用
	}

	/**
	 * 創建金流訂單
	 * @return [type] [description]
	 */
	function createOrder($order,$orderList){

		$data["MerchantID"] = $this->MerchantID;
		$data["Version"] = $this->Version;

		//JSON 或是 String。
		$data["RespondType"] = $this->RespondType;

		//時間戳
		$data["TimeStamp"] = time();

		//1.商店自訂訂單編號，限英、數字、”_ ”格式。例：201406010001。
		//2.長度限制為 30 字。
		//3.同一商店中此編號不可重覆。
		$data["MerchantOrderNo"] = $order["orderNumber"].$this->rand;

		//1.純數字不含符號，例：1000。
		//2.幣別：新台幣。
		$data["Amt"] = $order["total"] + $order["freight"] + $order["handlingFee"];

		//商品資訊
		//1.限制長度為 50 字。
		//2.編碼為 Utf-8 格式。
		//3.請勿使用斷行符號、單引號等特殊符號避免無法顯示完整付款頁面。
		//4.若使用特殊符號，系統將自動過濾。
		$goodsName = "";
		foreach ($orderList as $k => $v){
			if($goodsName!=''){
				$goodsName .= ',';
			}
			$goodsName .= $v["name"];			
		}
		$data["ItemDesc"] = $this->stringFilter($goodsName,50);

		//1.交易完成後，以 Form Post 方式導回商店頁面。
		//2.若為空值，交易完成後，消費者將停留在藍新金流付款或取號完成頁面。
		//3.只接受 80 與 443 Port。
		$data["ReturnURL"] = $this->returnURL;

		//1.以幕後方式回傳給商店相關支付結果資料
		$data["NotifyURL"] = $this->notifyURL;

		//1.系統取號後以 form post 方式將結果導回商店指定的網址
		// $data["CustomerURL"] = "";

		//1.當交易取消時，平台會出現返回鈕，使消費者依以此參數網址返回商店指定的頁面。
		//2.此參數若為空值時，則無返回鈕。
		$data["ClientBackURL"] = $this->backURL;

		//於交易完成或付款完成時，通知付款人使用。
		$data["Email"] = $order["buyEmail"];

		// 1 = 須要登入藍新金流會員
		// 0 = 不須登入藍新金流會員
		$data["LoginType"] = 0;


		//----------------------金流開關選擇----------------------
		switch ($order["paymentMethod"]) {
			case paymentMethodType::CREDIT_NEWEB:
				//信用卡一次付清
				$data["CREDIT"] = 1;
				break;
			case paymentMethodType::ANDROIDPAY_NEWEB:
				//Google Pay
				$data["ANDROIDPAY"] = 1;
				break;
			case paymentMethodType::SAMSUNGPAY_NEWEB:
				//Samsung Pay
				$data["SAMSUNGPAY"] = 1;
				break;
			case paymentMethodType::INSTFLAG_NEWEB:
				//信用卡分期
				//1.此欄位值=1 時，即代表開啟所有分期期別，且不可帶入其他期別參數。
				//2.此欄位值為下列數值時，即代表開啟該分期期別。
				//	3=分 3 期功能
				//	6=分 6 期功能
				//	12=分 12 期功能
				//	18=分 18 期功能
				//	24=分 24 期功能
				//	30=分 30 期功能
				//3.同時開啟多期別時，將此參數用”，”(半形)分隔，例如：3,6,12，代表開啟 分 3、6、12 期的功能。
				//4. 此欄位值=０或無值時，即代表不開啟分期。
				$data["InstFlag"] = 1;
				break;
			case paymentMethodType::CREDITRED_NEWEB:
				//信用卡紅利
				$data["CreditRed"] = 1;
				break;
			case paymentMethodType::UNIONPAY_NEWEB:
				//信用卡銀聯卡
				$data["UNIONPAY"] = 1;
				break;
			case paymentMethodType::WEBATM_NEWEB:
				//WEBATM
				$data["WEBATM"] = 1;
				break;
			case paymentMethodType::VACC_NEWEB:
				//ATM 轉帳
				$data["VACC"] = 1;
				break;
			case paymentMethodType::CVS_NEWEB:
				//超商代碼繳費
				$data["CVS"] = 1;
				break;
			case paymentMethodType::BARCODE_NEWEB:
				//超商條碼繳費
				$data["BARCODE"] = 1;
				break;
			case paymentMethodType::P2G_NEWEB:
				//ezPay 電子錢包
				$data["P2G"] = 1;
				break;
			case paymentMethodType::CVSCOM_NEWEB_Y:
				// 啟用超商取貨付款
				// 1.使用前，須先登入藍新金流會員專區啟用物流並設定退貨門市與取貨人相關資訊。
				// 	1 = 啟用超商取貨不付款
				// 	2 = 啟用超商取貨付款
				// 	3 = 啟用超商取貨不付款及超商取貨付款
				// 	0 或者未有此參數，即代表不開啟。
				// 2.當該筆訂單金額小於 30 元或大於 2 萬元時，即使此參數設定為啟用，MPG 付款頁面仍不會顯示此支付方式選項。
				$data["CVSCOM"] = 2;
				break;
			case paymentMethodType::CVSCOM_NEWEB_N:
				// 啟用超商取貨不付款
				$data["CVSCOM"] = 1;
				break;
		}

		//----------------------金流開關選擇----------------------

		$data["TradeInfo"] = $this->getTradeInfo($data);
		$data["TradeSha"] = $this->getTradeSha($data["TradeInfo"]);

		$this->formSubmit($this->serviceURL,$data);
	}

	/**
	 * 檢查回傳資訊是否正確
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	function checkTradeSha($TradeSha,$TradeInfo){
		if($TradeSha != $this->getTradeSha($TradeInfo)){
			return false;
		}
		return true;
	}

	/**
	 * 1. 將交易資料的 AES 加密字串前後加上商店專屬加密 HashKey 與商店專屬加密 HashIV。
	 * 2. 將串聯後的字串用 SHA256 壓碼後轉大寫。
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	function getTradeSha($TradeInfo){
		$tempString = "HashKey=".$this->HashKey."&".$TradeInfo."&HashIV=".$this->HashIV;
		$tempString = strtoupper(hash("sha256", $tempString));
		return $tempString;
	}

	/**
	 * 將交易資料透過商店專屬加密 HashKey 與商店專屬加密 HashIV，產生 AES 256 加密交易資料。
	 * 文件 AES 256 加密語法範例
	 * @param  string $parameter [description]
	 * @return [type]            [description]
	 */
	function getTradeInfo($data){
		$tempString = http_build_query($data);
		$tempString = $this->addpadding($tempString);
		if ($this->HashKey && $this->HashIV) {
			$tempString = openssl_encrypt(
				$tempString,
				$this->cipherMethod,
				$this->HashKey,
				OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING,
				$this->HashIV
			);
		}
		$tempString = bin2hex($tempString);
		$tempString = trim($tempString);
		return $tempString;
	}
	function addpadding($string, $blocksize = 32){
		$len = strlen($string);
		$pad = $blocksize - ($len % $blocksize);
		$string .= str_repeat(chr($pad), $pad);
		return $string;
	}

	/**
	 * 文件 AES 256 解密語法範例
	 * @param  [type] $value [description]
	 * @return [type]            [description]
	 */
	function decodeTradeInfo($value){
		$tempString = hex2bin($value);
		if ($this->HashKey && $this->HashIV) {
			$tempString = openssl_decrypt(
				$tempString,
				$this->cipherMethod,
				$this->HashKey,
				OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING,
				$this->HashIV
			);
		}
		$tempString = $this->strippadding($tempString);
		return json_decode($tempString,true);
	}
	function strippadding($string){
		$slast = ord(substr($string, -1));
		$slastc = chr($slast);
		$pcheck = substr($string, -$slast);
		if(preg_match("/$slastc{" . $slast . "}/", $string)){
			$string = substr($string, 0, strlen($string) - $slast);
			return $string;
		}
		return false;
	}
}

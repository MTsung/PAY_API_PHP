<?php

/**
 * YI PAY (乙禾)
 */
class payYiPay extends pay{
	protected $cipherMethod = "AES-256-CBC";//加密方式
	protected $Version = "1.5";//版本
	protected $serviceURL;
	protected $returnURL = TI_PAY_CALLBACK;
	protected $backURL = HTTP_PATH;
	protected $merchantId;
	protected $HashKey;
	protected $HashIV;
	protected $rand;
	protected $isTest = YI_PAY_IS_TEST;

	/**
	 * [__construct description]
	 * 乙禾測試環境的串接參數皆為固定值,請使用以下的參數進行測試:
	 * 商家編號 1604000006
	 * Key zBaw7bzzD8K1THSGoIbev08xEJp5yzyeuv1MWJDR2L0=
	 * IV YeQInQjfelvkBcWuyhWDAw==
	 * @param [type] $merchantId [description]
	 * @param [type] $HashKey    [description]
	 * @param [type] $HashIV     [description]
	 */
	function __construct($merchantId,$HashKey,$HashIV){
		if($this->isTest){
			$this->serviceURL = "https://gateway-test.yipay.com.tw/payment";
		}else{
			$this->serviceURL = "https://gateway.yipay.com.tw/payment";
		}
		$this->merchantId = $merchantId;
		$this->HashKey = base64_decode($HashKey);//乙禾給的有先base64編碼起來
		$this->HashIV = base64_decode($HashIV);//乙禾給的有先base64編碼起來
		$this->rand = strtoupper(base_convert(microtime(true) % 100000,10,36));				//訂單編號後墜 防止訂單編號重複用
	}

	/**
	 * 創建金流訂單
	 * @return [type] [description]
	 */
	function createOrder($order,$orderList){

		$order["formData"] = json_decode($order["formData"],true);

		$data["merchantId"] = $this->merchantId;

		//1 信用卡付款
		//2 信用卡 3D 付款
		//3 超商代碼繳費
		//4 ATM 虛擬帳號繳款
		$data["type"] = "1";

		//大於 0 之正整數、不含小數點及逗號,僅限新台幣付款
		$data["amount"] = ($order["total"] + $order["freight"])."";//要字串
		
		//商家自訂的訂單編號,為唯一
		$data["orderNo"] = $order["orderNumber"].$this->rand;

		//消費者購買內容
		$goodsName = "";
		foreach ($orderList as $k => $v){
			if($goodsName!=''){
				$goodsName .= ',';
			}
			$goodsName .= $v["name"];			
		}
		$data["orderDescription"] = $this->stringFilter($goodsName,50);

		//當交易完成後,系統會由付款頁面返回至商家的頁面,並一併將結果以 POST 方式回傳至該網址
		$data["returnURL"] = $this->returnURL;

		//若填寫此參數時,於付款頁面上會顯示「取消付款返回商家網站」的連結,當消費者按下後會導回商家指定網址,並不帶入任何參數
		$data["cancelURL"] = $this->backURL;

		//當交易完成後,系統會將交易結果以背景方式 POST 至該網址
		$data["backgroundURL"] = "";

		//當交易成功時將交易結果寄送至商家指定的 Email,若有多筆 Email 以分號「;」區隔。請注意此處為通知商家消費者已完成交易用
		$data["notificationEmail"] = $order["formData"]["BuyEmail"];

		//檢查碼
		$data["checkCode"] = $this->getCheckCode($data);

		$this->formSubmit($this->serviceURL,$data);
	}

	/**
	 * 檢查碼
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	function getCheckCode($data,$type='1'){
		$temp = [];
		switch ($type) {
			case '1'://檢查碼組成參數(傳送) (傳送都一樣)
				$temp["merchantId"] = $data["merchantId"];
				$temp["amount"] = $data["amount"];
				$temp["orderNo"] = $data["orderNo"];
				$temp["returnURL"] = $data["returnURL"];
				$temp["cancelURL"] = $data["cancelURL"];
				$temp["backgroundURL"] = $data["backgroundURL"];

				break;
			case '2'://檢查碼組成參數(接收) (1 信用卡)
				$temp["merchantId"] = $data["merchantId"];
				$temp["amount"] = $data["amount"];
				$temp["orderNo"] = $data["orderNo"];
				$temp["returnURL"] = $this->returnURL;//乙禾不會回傳這幾個值
				$temp["cancelURL"] = $this->backURL;//乙禾不會回傳這幾個值
				$temp["backgroundURL"] = "";//乙禾不會回傳這幾個值
				$temp["transactionNo"] = $data["transactionNo"];
				$temp["statusCode"] = $data["statusCode"];
				$temp["approvalCode"] = $data["approvalCode"];
				break;
			
			default:
				# code...
				break;
		}

		//步驟 1、組成 JSON 字串
		$tempString = json_encode($temp,JSON_UNESCAPED_SLASHES);

		//步驟 2、AES 加密
		$tempString = openssl_encrypt(
			$tempString,
			$this->cipherMethod,
			$this->HashKey,
			0,
			$this->HashIV
		);

		//步驟 3、SHA1
		$tempString = sha1($tempString);
		
		return $tempString;
	}
}
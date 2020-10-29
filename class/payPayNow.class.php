<?php

/**
 * PAY NOW
 */
class payPayNow extends pay{
	protected $serviceURL;
	protected $WebNo;
	protected $Password;
	protected $ECPlatform;
	protected $rand;
	protected $isTest = PAY_NOW_IS_TEST;

	/**
	 * [__construct description]
	 * @param [type] $WebNo      賣家登入帳號，如身分證開頭請為大寫傳送。
	 * @param [type] $Password   商家交易密碼
	 * @param [type] $ECPlatform 商家網站名稱 (例 : XXX 購物網、對外商店名稱)
	 */
	function __construct($WebNo,$Password,$ECPlatform){
		if($this->isTest){
			//測試
			$this->serviceURL = "https://test.paynow.com.tw/service/etopm.aspx";
		}else{
			$this->serviceURL = "https://www.paynow.com.tw/service/etopm.aspx";
		}
		$this->WebNo = ucfirst($WebNo);														//賣家登入帳號，如身分證開頭請為大寫傳送。
		$this->Password = $Password;
		$this->ECPlatform = $ECPlatform;
		$this->rand = strtoupper(base_convert(microtime(true) % 100000,10,36));				//訂單編號後墜 防止訂單編號重複用
	}

	/**
	 * 創建金流訂單
	 * @return [type] [description]
	 */
	function createOrder($order,$orderList){

		$order["formData"] = json_decode($order["formData"],true);

		$data["WebNo"] = $this->WebNo;
		$data["ECPlatform"] = $this->ECPlatform;
		$data["ReceiverName"] = $order["formData"]["BuyName"];								//消費者姓名
		$data["ReceiverID"] = $order["formData"]["BuyEmail"];								//消費者身分證/Email/手機號碼
		$data["ReceiverTel"] = $order["formData"]["BuyPhone"];								//消費者姓名
		$data["ReceiverEmail"] = $order["formData"]["BuyEmail"];							//消費者Email
		$data["OrderNo"] = $order["orderNumber"].$this->rand; 								//訂單編號
		$data["TotalPrice"] = $order["total"] + $order["freight"];							//交易金額
		$data["OrderInfo"] = $this->ECPlatform;												//商家自訂交易訊息
		$data["PayType"] = "01";															//01 : 信用卡
																							//02 : WebATM
																							//03 : 虛擬帳號
																							//05 : 代碼繳費
																							//09 : 銀聯
																							//10 : 超商條碼
																							//11 : 分期付款                  			

        //將以下的順序組合起來成一字串 :( WebNo & OrderNo & Total Price& 商家交易密碼) 並使用 SHA-1 雜湊函數取得組合字串的雜湊值
		$data["PassCode"] = strtoupper(sha1($data["WebNo"].$data["OrderNo"].$data["TotalPrice"].$this->Password)); 	//交易驗證碼

        //※ 所有參數傳遞時，請以URL Encode 編碼，所有網頁字集為UTF-8
        $data = array_map("urlencode", $data);

		$this->formSubmit($this->serviceURL,$data);
	}

	/**
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function checkPassCode($data){
		return ($data["PassCode"] == strtoupper(sha1($data["WebNo"].$data["OrderNo"].$data["TotalPrice"].$this->Password)));
	}
}
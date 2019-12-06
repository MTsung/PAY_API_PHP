<?php

/**
 * 第一銀行 財金資訊股份有限公司
 * 通行碼 : fisc
 */
class payFisc extends pay{

	protected $serviceURL;
	protected $returnURL = FISC_CALLBACK;
	protected $merchantID;
	protected $terminalID;
	protected $merchantName;
	protected $rand;
	protected $isTest = FISC_IS_TEST;
	protected $token = "1qaz2wsx3edc4rfv";/** 需到 特店管理區 > 安全性作業管理 > 參數驗證管理 > 新增token "1qaz2wsx3edc4rfv" > 請銀行端IT人工放行 **/

	/**
	 * @param [type] $merID        網站特店自訂代碼(請注意 merID 與 MerchantID 不同)
	 * @param [type] $merchantID   收單銀行授權使用的特店代號(由收單銀行編製提供)
	 * @param [type] $terminalID   收單銀行授權使用的機台代號(由收單銀行編製提供)
	 * @param string $merchantName 特店網站或公司名稱，僅供顯示。
	 *                             行動支付交易，最大長度 12 位
	 *                             銀聯交易限定僅能為英、數字、空白及『-』，最大長度 25 位。
	 */
	function __construct($merID,$merchantID,$terminalID,$merchantName=""){
		if($this->isTest){
			//測試
			$this->serviceURL = "https://www.focas-test.fisc.com.tw/FOCAS_WEBPOS/online/";
		}else{
			$this->serviceURL = "https://www.focas.fisc.com.tw/FOCAS_WEBPOS/online/";
		}
		$this->merID = $merID;
		$this->merchantID = $merchantID;
		$this->terminalID = $terminalID;
		$this->merchantName = $this->stringFilter($merchantName,60);						//店名
		$this->rand = strtoupper(base_convert(microtime(true) % 100000,10,36));				//訂單編號後墜 防止訂單編號重複用
	}

	/**
	 * 創建金流訂單
	 * @return [type] [description]
	 */
	function createOrder($order,$orderList){

		$data["merID"] = $this->merID;
		$data["MerchantID"] = $this->merchantID;
		$data["TerminalID"] = $this->terminalID;											
		$data["MerchantName"] = $this->merchantName;										//店名
		$data["customize"] = 0;																//不使用客製化授權頁
		$data["lidm"] = $order["orderNumber"].$this->rand; 									//訂單編號
		$data["purchAmt"] = $order["total"] + $order["freight"] + $order["handlingFee"];	//交易金額
		$data["AuthResURL"] = $this->returnURL;												//通知回傳的網址
        $data['LocalDate'] = date('Ymd');                       							//交易日期
        $data['LocalTime'] = date('His');                       							//交易時間
		$data["token"] = $this->token;														//驗證參數
        $data['reqToken'] = $this->getReqToken($data);                       				//交易驗證碼

		$this->formSubmit($this->serviceURL,$data);
	}

	/**
	 * 取得交易驗證碼
	 * 公式：
	 * 		SHA-256(訂單編號&交易金額&驗證參數&特店代號&端末代號&交易時間)
	 * 
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function getReqToken($data){
		$temp = array(
			$data["lidm"],								//訂單編號
			$data["purchAmt"],							//交易金額
			$this->token,								//驗證參數
			$data["MerchantID"],						//特店代號
			$data["TerminalID"],						//端末代號
			$data["LocalDate"].$data["LocalTime"]		//交易時間
		);

		$temp = implode("&",$temp);
		return strtoupper(hash('sha256', $temp));
	}

	/**
	 * 檢查token
	 * 公式：
	 * 		成功
	 * 			SHA-256(授權結果狀態&訂單編號&驗證參數&授權碼&交易回應時間&特店代號&端末代號) 
	 *   	失敗
	 *    		SHA-256(授權結果狀態&錯誤碼&訂單編號&驗證參數&交易回應時間&特店代號&端末代號)
	 * 
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function checkRespToken($data){
		//測試環境時不檢查
		if($isTest){
			return true;
		}

		if($data["status"]=="0"){//成功
			$temp = array(
				$data["status"],							//授權結果狀態
				$data["lidm"],								//訂單編號
				$this->token,								//驗證參數
				$data["authCode"],							//授權碼
				$data["authRespTime"],						//交易回應時間
				$this->merchantID,							//特店代號
				$this->terminalID							//端末代號
			);
		}else{
			$temp = array(
				$data["status"],							//授權結果狀態
				$data["errcode"],							//錯誤碼
				$data["lidm"],								//訂單編號
				$this->token,								//驗證參數
				$data["authRespTime"],						//交易回應時間
				$this->merchantID,							//特店代號
				$this->terminalID							//端末代號
			);
		}

		$temp = implode("&",$temp);

		if(strtoupper($data["respToken"]) != strtoupper(hash('sha256', $temp))){
			return false;
		}
		return true;
	}
}
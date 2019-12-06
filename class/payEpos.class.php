<?php

/**
* 華南
*/
class payEpos extends pay{

	protected $serviceURL;
	protected $returnURL = EPOS_CALLBACK;
	protected $merID;
	protected $merchantID;
	protected $terminalID;
	protected $checkID;
	protected $rand;
	protected $isTest = EPOS_IS_TEST;

	/**
	 * @param [type] $merID        特店網站之代碼,如 327。請注意 merID 與 MerchantID 不同。
	 * @param [type] $merchantID   收單銀行授權使用的特店代號,固定長度為 15 位數字。
	 * @param [type] $terminalID   收單銀行授權使用的機台代號,固定長度為 8 位數字。
	 * @param [type] $checkID      請登入特店帳務系統後,於左方選單 「系統管理」 > 「識別碼查詢及API 下載」 的功能當中,
	 *                             輸入特店通行碼後按下確認,系統會顯示「特店識別碼」(若您沒有此功能,請先向往來分行提出申請)
	 */
	function __construct($merID,$merchantID,$terminalID,$checkID){
		if($this->isTest){
			//測試
			$this->serviceURL = "https://eposnt.hncb.com.tw/transaction/api-auth/";
		}else{
			$this->serviceURL = "https://eposn.hncb.com.tw/transaction/api-auth/";
		}
		$this->merID = $merID;
		$this->merchantID = $merchantID;
		$this->terminalID = $terminalID;
		$this->checkID = $checkID;
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
		$data["customize"] = 0;																//不使用客製化授權頁
		$data["lidm"] = $order["orderNumber"].$this->rand; 									//訂單編號
		$data["purchAmt"] = $order["total"] + $order["freight"] + $order["handlingFee"];	//交易金額

		// $data["txType"] = 0;																//不分期
		// $data["NumberOfPay"] = 3;														//分期期數不得小於 3,期數必須依照收單銀行的規範。請確認是否有向銀行申請分期付款服務。

		$data["encode"] = "UTF-8";															//刷卡網頁特約商店名稱編碼方式

		$data["AutoCap"] = "0";																//表示授權成功後,是否由系統繼續執行轉入請款檔作業。
																							// 0: 表示不轉入請款檔(系統內定作業)
																							// 1: 表示自動轉入請款檔。

		$data["AuthResURL"] = $this->returnURL;												//通知回傳的網址
		$data["AuthInfoPage"] = "Y";														//Y:預設值。交易取得授權後,會顯示系統預設的相關授權訊息結果頁面。
		$data['checkValue'] = $this->getCheckValue($data);                       			//交易驗證碼

		$this->formSubmit($this->serviceURL,$data);
	}

	/**
	 * 取得驗證碼
	 * 公式：
	 * 		checkValue 運算原則如下:
	 *		(1) 以 MD5 方式運算”特店識別碼”與”訂單編號”字串,運算結果
	 *			得到一組 32 bytes ASCII 字串。(特店識別碼(checkID) 請依上
	 *			圖所示取得。)
	 *		(2) 以 MD5 運算參數包含第 1 項所得 32 bytes ASCII 字串與該筆訂
	 *			單各項參數(merID、特店代號、端末機代號、刷卡金額)之組合,
	 *			所有參數以英文符號 “|” 隔開。
	 *		(3) 得到運算結果得到一組 32 bytes ASCII,請取該字串最後 16 碼
	 *			當做 checkValue。
	 *
	 * 		checkValue 運算式如下:
	 *		(1) V1 = MD5(checkID + “|” + lidm) // V1: 為 128 bits
	 *		(2) HV1 = V1 轉成 16 進位之 ASCII 字串 // HV1 共 32 位 ASCII
	 *		(3) V2 = MD5(HV1 + “|” + MerchantID + “|” +TerminalID + “|” +purchAmt))
	 *		(4) HV2 = V2 轉成 16 進位之 ASCII 字串 //共 32 位 ASCII
	 *		(5) checkValue = HV2(17:32) // 取 HV2 最後 16 碼
	 * 
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function getCheckValue($data){
		$V1 = $HV1 = md5(implode("|",[
			$this->checkID,
			$data["lidm"]
		]));
		$V2 = $HV2 = md5(implode("|",[
			$HV1,
			$this->merchantID,
			$this->terminalID,
			$data["purchAmt"]
		]));
		$checkValue = substr($HV2,-16,16);
		return $checkValue;
	}

	/**
	 * 檢查驗證碼
	 * 公式：
	 * 		1. 一般卡交易 (VISA、MASTER、JCB) 授權結果 checkValue 運算方式如下:
	 *			(1) 步驟 1: 計算公式: result1 = MD5(特店識別碼 + “|” +訂單編號 lidm)
	 *				並將 result1 轉成 16 進位之 ASCII 字串
	 *			(2) 步驟 2: 計算公式: result2 = MD5( result1 + “|” + status + “|” + errcode +
	 *				“|” + authCode + “|” + authAmt + ”|” + xid)
	 *				並將 result2 轉成 16 進位之 ASCII 字串
	 *			(3) 步驟 3: 計算公式: checkValue = result2 取末 16 碼
	 * 
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function checkCheckValue($data){
		$result1 = md5(implode("|",[
			$this->checkID,
			$data["lidm"]
		]));
		$result2 = md5(implode("|",[
			$result1,
			$data["status"],
			$data["errcode"],
			$data["authCode"],
			$data["authAmt"],
			$data["xid"]
		]));
		$checkValue = substr($result2,-16,16);

		if(strtoupper($data["checkValue"]) != strtoupper($checkValue)){
			return false;
		}
		return true;
	}
}
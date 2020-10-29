<?php

/**
 * 台新
 */
class payTspgPay extends pay{
	var $checkURL;
	var $serviceURL;
	var $returnURL = TSPG_CALLBACK;
	var $mid;
	var $tid;
	var $rand;
	var $isTest = TSPG_IS_TEST;

	/**
	 * [__construct description]
	 * @param [type] $mid 特店代號
	 * @param [type] $tid 端末代號
	 */
	function __construct($mid,$tid){
		if($this->isTest){
			//測試
			$this->serviceURL = "https://tspg-t.taishinbank.com.tw/tspgapi/restapi/auth.ashx";
			$this->checkURL = "https://tspg-t.taishinbank.com.tw/tspgapi/restapi/other.ashx";
		}else{
			$this->serviceURL = "https://tspg.taishinbank.com.tw/tspgapi/restapi/auth.ashx";
			$this->checkURL = "https://tspg.taishinbank.com.tw/tspgapi/restapi/other.ashx";
		}
		$this->mid = $mid;
		$this->tid = $tid;
		$this->rand = strtoupper(base_convert(microtime(true) % 100000,10,36));				//訂單編號後墜 防止訂單編號重複用
	}

	/**
	 * 查詢交易結果
	 * order_status 訂單狀態碼 說明
	 * 02 已授權
	 * 03 已請款
	 * 04 請款已清算
	 * 06 已退貨
	 * 08 退貨已清算
	 * 12 訂單已取消
	 * ZP 訂單處理中
	 * ZF 授權失敗
	 * @param  [type] $order_no [description]
	 * @return [type]           [description]
	 */
	function checkOrder($order_no){
		$data["sender"] = "rest";
		$data["ver"] = "1.0.0";
		$data["mid"] = $this->mid;
		$data["tid"] = $this->tid;
		$data["pay_type"] = 1;
		$data["tx_type"] = 7;
		$data["params"] = [
			"order_no" => $order_no,
			"result_flag" => '1'
		];
		$temp = $this->curl($this->checkURL,"POST",$data,[],['Content-Type: application/json']);
		$temp = json_decode($temp,true);
		return $temp["params"];//不一定會有order_status
	}

	/**
	 * 創建金流訂單
	 * @return [type] [description]
	 */
	function createOrder($order,$orderList){

		$data["sender"] = "rest";
		$data["ver"] = "1.0.0";
		$data["mid"] = $this->mid;
		$data["tid"] = $this->tid;
		$data["pay_type"] = 1;
		$data["tx_type"] = 1;
							//交易類型：
							//		1:授權
							//		3:請款
							//		4:取消請款
							//		5:退貨
							//		6:取消退貨（銀聯卡UnionPay無此功能）
							//		7:查詢
							//		8:取消授權
		$goodsName = "";
		foreach ($orderList as $k => $v){
			if($goodsName!=''){
				$goodsName .= ',';
			}
			$goodsName .= $v["name"];			
		}

		$data["params"] = [
			"layout" => "1",											//1 電腦版畫面,2 手機板畫面
			"order_no" => $order["orderNumber"].$this->rand, 			//訂單編號
			"amt" => ($order["total"] + $order["freight"])."00",		//交易金額 包含兩位小數，如 100 代表 1.00 元。
			"cur" => "NTD",
			"order_desc" => $this->stringFilter($goodsName,40),
			"capt_flag" => "1", 										//授權同步請款標記 0:不同步請款 1:同步請款 (若使用「TSPG 系統自動請款」作業方式，請設定為0)
			"result_flag" => "0",										//若為 1，則 TSPG 會在傳送交易資料至「指定交易資料回傳網址(result_url)」時，一併傳送本交易之詳細資料
			"post_back_url" => $this->returnURL,
			"result_url" => $this->returnURL,
		];

		$temp = $this->curl($this->serviceURL,"POST",$data,[],['Content-Type: application/json']);
		$temp = json_decode($temp,true);

		if(isset($temp["params"]["ret_code"]) && $temp["params"]["ret_code"] == "00"){
			header("Location: ".$temp["params"]["hpp_url"]);			//HPP方式
		}else{
			echo "error: ".$temp["params"]["ret_code"].". ".$temp["params"]["ret_msg"];
			exit;
		}
	}

	function curl($url,$type='GET',$data=array(),$options=array(),$header=array()) {
		$ch = curl_init();

		if(strtoupper($type) == "GET"){
			$url = $url."?".http_build_query($data);
		}else{
			$options[CURLOPT_POST] = true;
			$options[CURLOPT_POSTFIELDS] = json_encode($data);
		}

		$defaultOptions = array(
			CURLOPT_RETURNTRANSFER => true, // 不直接出現回傳值
			CURLOPT_URL => $url,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_HTTPHEADER => $header
		);
		$options = $options + $defaultOptions;
		curl_setopt_array($ch, $options);

		$response = curl_exec($ch);

		if (!$response) {
			$this->message = curl_error($ch);
			return false;
		}

		return $response;
	}
}
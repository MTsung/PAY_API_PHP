<?php

/**
 * LINE PAY
 */
class payLinePay extends pay{
		protected $serviceURL;
		protected $ChannelId;
		protected $ChannelSecret;
		protected $merchantName;
		protected $rand;
		protected $isTest = LINE_PAY_IS_TEST;

		/**
		 * [__construct description]
		 * @param [type] $ChannelId     [description]
		 * @param [type] $ChannelSecret [description]
		 * @param string $merchantName  店家名稱
		 */
		function __construct($ChannelId,$ChannelSecret,$merchantName=""){
			if($this->isTest){
				$this->serviceURL = "https://sandbox-api-pay.line.me/v2/payments/";
			}else{
				$this->serviceURL = "https://api-pay.line.me/v2/payments/";
			}
			$this->ChannelId = $ChannelId;
			$this->ChannelSecret = $ChannelSecret;
			$this->merchantName = $this->stringFilter($merchantName,60);						//店名
			$this->rand = strtoupper(base_convert(microtime(true) % 100000,10,36));				//訂單編號後墜 防止訂單編號重複用
		}

		/**
		 * 創建金流訂單
		 * @return [type] [description]
		 */
		function createOrder($order,$orderList){
			$data["productName"] = $this->merchantName;
			$data["productImageUrl"] = HTTP_PATH."images/logo.png";//LOGO
			$data["amount"] = $order["total"] + $order["freight"];
			$data["currency"] = "TWD";
			$data["confirmUrl"] = LINE_PAY_CALLBACK.$data["amount"];
			$data["orderId"] = $order["orderNumber"].$this->rand;

			$r = json_decode($this->curlLine($this->serviceURL."request",$data),true);
			$_SESSION["line_return"] = $r;
			$_SESSION["line_return"]["amount"] = $data["amount"];

			if($r["returnCode"] != "0000"){//錯誤
				echo $r["returnMessage"];
				exit;
			}

			header('Location: '.$r["info"]["paymentUrl"]["web"]);
		}

		//檢查是否正確
		function check($transactionId,$amount){
			$data["amount"] = $amount;
			$data["currency"] = "TWD";
			$r = json_decode($this->curlLine($this->serviceURL.$transactionId."/confirm",$data),true);
			
			if($r["returnCode"] == "0000"){//正確
				return $r["info"]["orderId"];
			}
			return false;
		}

		/**
		 * curlLine
		 * @param  [type]  $url     
		 * @param  array   $data    
		 * @param  integer $timeout 
		 * @return [type]           
		 */
		function curlLine($url,$data=array(),$timeout=30){

			$header = array(
				'Content-Type:application/json',
				'X-LINE-ChannelId:'.$this->ChannelId,//, 
				'X-LINE-ChannelSecret:'.$this->ChannelSecret//
			);

		    $ch = curl_init();
		    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		    curl_setopt($ch, CURLOPT_URL, $url);
		    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		    curl_setopt($ch, CURLOPT_POST, true);
		    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

		    $response = curl_exec($ch);

		    curl_close($ch);

		    return $response;
		}
	}
<?php
    include_once("config.php");
    include_once("class/pay.class.php");
    include_once("class/payFisc.class.php");
    include_once("class/payEpos.class.php");
    include_once("class/payNeweb.class.php");
    include_once("class/payLinePay.class.php");
    include_once("class/payPayNow.class.php");

    if(isset($_POST["paymentMethod"])){
    	$order["paymentMethod"] = $_POST["paymentMethod"];
    	switch ($_POST["paymentMethod"]) {
    		case paymentMethodType::FISC:
				$fisc = new payFisc(
					FISC_MER_ID,
					FISC_MERCHANT_ID,
					FISC_TERMINAL_ID,
					FISC_MERCHANT_NAME
				);
				$fisc->createOrder($order,$orderList);
    			break;
			case paymentMethodType::EPOS:
				$epos = new payEpos(
					EPOS_MER_ID,
					EPOS_MERCHANT_ID,
					EPOS_TERMINAL_ID,
					EPOS_CHECK_ID
				);
				$epos->createOrder($order,$orderList);
    			break;
			case paymentMethodType::CREDIT_NEWEB:
			case paymentMethodType::ANDROIDPAY_NEWEB:
			case paymentMethodType::SAMSUNGPAY_NEWEB:
			case paymentMethodType::INSTFLAG_NEWEB:
			case paymentMethodType::CREDITRED_NEWEB:
			case paymentMethodType::UNIONPAY_NEWEB:
			case paymentMethodType::WEBATM_NEWEB:
			case paymentMethodType::VACC_NEWEB:
			case paymentMethodType::CVS_NEWEB:
			case paymentMethodType::BARCODE_NEWEB:
			case paymentMethodType::P2G_NEWEB:
			case paymentMethodType::CVSCOM_NEWEB_N:
			case paymentMethodType::CVSCOM_NEWEB_Y:
				$neweb = new payNeweb(
					NEWEB_MERCHANT_ID,
					NEWEB_HASH_KEY,
					NEWEB_HASH_IV
				);
				$neweb->createOrder($order,$orderList);
    			break;
			case paymentMethodType::LINE_PAY:
				$line = new payLinePay(
					LINE_PAY_CHANNELID,
					LINE_PAY_CHANNELSECRET,
					LINE_PAY_MERCHANTNAME
				);
				$line->createOrder($order,$orderList);
    			break;
			case paymentMethodType::PAY_NOW:
				$payNow = new payPayNow(
					PAY_NOW_WEBNO,
					PAY_NOW_PASSWORD,
					PAY_NOW_ECPLATFORM
				);
				$payNow->createOrder($order,$orderList);
    			break;
    	}
    }
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.rawgit.com/TeaMeow/TocasUI/2.3.2/dist/tocas.css" rel='stylesheet'>
    <script src="https://cdn.rawgit.com/TeaMeow/TocasUI/2.3.2/dist/tocas.js"></script>
    <title>PAY_API_PHP | MTsung</title>
</head>
<body>

    <!-- 主要容器 -->
    <div class="ts very narrow container">
    	<br><br><br>
        <!-- 表單 -->
        <form class="ts relaxed form" method="POST">

			<div class="ts message">
			    <div class="header">測試資料</div>
				<pre><?php print_r($order);?></pre>
				<pre><?php print_r($orderList);?></pre>
			</div>

            <!-- 單個欄位 -->
            <div class="field">
                <label>方式</label>
                <select name="paymentMethod" required>
                    <option value="">請選擇</option>
                    <option value="1">信用卡刷卡(財金公司國際)</option>
					<option value="2">信用卡刷卡(華南銀行)</option>
					<option value="3">信用卡一次付清(藍新)</option>
					<option value="4">Google Pay(藍新)</option>
					<option value="5">Samsung Pay(藍新)</option>
					<option value="6">信用卡分期(藍新)</option>
					<option value="7">信用卡紅利(藍新)</option>
					<option value="8">信用卡銀聯卡(藍新)</option>
					<option value="9">WEBATM(藍新)</option>
					<option value="10">ATM 轉帳(藍新)</option>
					<option value="11">超商代碼繳費(藍新)</option>
					<option value="12">超商條碼繳費(藍新)</option>
					<option value="13">ezPay 電子錢包(藍新)</option>
					<option value="14">超商取貨不付款(藍新)</option>
					<option value="15">超商取貨付款(藍新)</option>
					<option value="16">LINE PAY</option>
					<option value="17">PAY NOW</option>
                </select>
            </div>
            <!-- / 單個欄位 -->

            <!-- 按鈕 -->
            <button class="ts fluid primary button" type="submit">傳送</button>
            <!-- / 按鈕 -->
        </form>
        <!-- / 表單 -->
    </div>
    <!-- / 主要容器 -->
</body>
</html>
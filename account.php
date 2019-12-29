<?php
include("includes/core.php");

$content = "";

if($user){
	$content .= "<h1>Account</h1>";

	$content .= "<h3>Address</h3>";
	$content .= $user['address'];
	$content .= "<h3>Balance</h3>";
	$content .= toSatoshi($user['balance'])." Satoshi<br /><br />";

	$expressCryptoApiToken = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '10'")->fetch_assoc()['value'];
	$expressCryptoUserToken = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '18'")->fetch_assoc()['value'];
	$faucetpayApiToken = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '19'")->fetch_assoc()['value'];
	$blockioApiKey = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '20'")->fetch_assoc()['value'];
	$blockioPin = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '21'")->fetch_assoc()['value'];


	if($expressCryptoApiToken AND $expressCryptoUserToken)
			$expressCryptoWithdrawal = true;
		else
			$expressCryptoWithdrawal = false;

	if($faucetpayApiToken)
			$faucetPayWithdrawal = true;
		else
			$faucetPayWithdrawal = false;

	if($blockioApiKey AND $blockioPin)
			$blockioWithdrawal = true;
		else
			$blockioWithdrawal = false;


	if($expressCryptoWithdrawal == true OR $faucetPayWithdrawal == true OR $blockioWithdrawal == true){

		if($_GET['withdr']){
			if($_GET['withdr'] == "ec"){
				if(toSatoshi($user['balance']) < $thresholdGateway){
					$content .= alert("warning", "Please reach firstly the withdrawal threshold of ".$thresholdGateway." Satoshis.");
				} else {
					$mysqli->query("UPDATE faucet_user_list Set balance = '0' WHERE id = '{$user['id']}'");
					$expressCrypto = new ExpressCrypto($expressCryptoApiToken, $expressCryptoUserToken, $realIpAddressUser);
					$result = $expressCrypto->sendPayment($user['ec_userid'], "BTC", toSatoshi($user['balance']));
					if($result['status'] == 200){
						$mysqli->query("INSERT INTO faucet_transactions (userid, type, amount, timestamp) VALUES ('{$user['id']}', 'Withdraw', '{$user['balance']}', '$timestamp')");
						$content .= alert("success", $result['message']);
					} else {
						$mysqli->query("UPDATE faucet_user_list Set balance = '{$user['balance']}' WHERE id = '{$user['id']}'");
						$content .= alert("danger", $result['message']);
					}
				}
			} else if($_GET['withdr'] == "fp"){
				if(toSatoshi($user['balance']) < $thresholdGateway){
					$content .= alert("warning", "Please reach firstly the withdrawal threshold of ".$thresholdGateway." Satoshis.");
				} else {
					$mysqli->query("UPDATE faucet_user_list Set balance = '0' WHERE id = '{$user['id']}'");
					$faucetpay = new FaucetPay($faucetpayApiToken, "BTC");
					$result = $faucetpay->send($user['address'], toSatoshi($user['balance']));
					if($result["success"] === true){
						$mysqli->query("INSERT INTO faucet_transactions (userid, type, amount, timestamp) VALUES ('{$user['id']}', 'Withdraw', '{$user['balance']}', '$timestamp')");
						$content .= $result["html"];
					} else {
						$mysqli->query("UPDATE faucet_user_list Set balance = '{$user['balance']}' WHERE id = '{$user['id']}'");
						$content .= $result["html"];
					}
				}
			} else if($_GET['withdr'] == "direct"){

				if(toSatoshi($user['balance']) < $thresholdDirect){
					$content .= alert("warning", "Please reach firstly the withdrawal threshold of ".$thresholdDirect." Satoshis.");
				} else {
					$mysqli->query("UPDATE faucet_user_list Set balance = '0' WHERE id = '{$user['id']}'");

					$version = 2;
					$block_io = new BlockIo($blockioApiKey, $blockioPin, $version);

					$WithdrawData = $block_io->withdraw(array('amounts' => $user['balance'], 'to_addresses' => $user['address'], 'priority' => 'low'));

					if($WithdrawData->status == "success"){
						$mysqli->query("INSERT INTO faucet_transactions (userid, type, amount, timestamp) VALUES ('{$user['id']}', 'Withdraw', '{$user['balance']}', '$timestamp')");
						$TXID = $WithdrawData->data->txid;
						$content .= alert("success", "Withdrawal successful. TXID: ".$TXID);
					} else {
						$mysqli->query("UPDATE faucet_user_list Set balance = '{$user['balance']}' WHERE id = '{$user['id']}'");
						$content .= alert("danger", "Unexpected error occured.");
					}
				}

			}
		} else {
			$withdrawalAvailable = false;

			$thresholdGateway = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '23'")->fetch_assoc()['value'];
			if($expressCryptoWithdrawal == true AND toSatoshi($user['balance']) >= $thresholdGateway){
				$withdrawalButtonLink .= '<li><a href="account.php?withdr=ec">ExpressCrypto</a></li>';
				$withdrawalAvailable = true;
			}

			if($faucetPayWithdrawal == true AND toSatoshi($user['balance']) >= $thresholdGateway){
				$withdrawalButtonLink .= '<li><a href="account.php?withdr=fp">FaucetPay</a></li>';
				$withdrawalAvailable = true;
			}
			

			$thresholdDirect = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '24'")->fetch_assoc()['value'];
			if($blockioWithdrawal == true AND toSatoshi($user['balance']) >= $thresholdDirect){
				$withdrawalButtonLink .= '<li><a href="account.php?withdr=direct">Direct</a></li>';
				$withdrawalAvailable = true;
			}

			if($withdrawalAvailable == false){
				$thresholdAlert = ($thresholdDirect < $thresholdGateway) ? $thresholdDirect : $thresholdGateway;
				$content .= alert("info", "Withdrawal threshold of ".$thresholdAlert." Satoshis hasn't been reached yet.");
			} else {
				$content .= '<div class="btn-group">
				  <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				    Withdraw <span class="caret"></span>
				  </button>
				  <ul class="dropdown-menu">
				    '.$withdrawalButtonLink.'
				  </ul>
				</div><br />';
			}
		}

	} else {
		$content .= alert("warning", "The site owner hasn't configured the withdrawal methods yet.");
	}
	
	$content .= "<br /><br />";

	// Total Stats

	$TotalClaims = $mysqli->query("SELECT COUNT(id) FROM faucet_transactions WHERE type = 'Payout' AND userid = '{$user['id']}'")->fetch_row()[0];
	$TotalClaimed = $mysqli->query("SELECT SUM(amount) FROM faucet_transactions WHERE type = 'Payout' AND userid = '{$user['id']}'")->fetch_row()[0];
	$TotalClaimed = $TotalClaimed ? $TotalClaimed : 0;

	// 24 Hours stats

	$Last24Hours = time() - 86400;
	$Last24HoursClaims = $mysqli->query("SELECT COUNT(id) FROM faucet_transactions WHERE type = 'Payout' AND userid = '{$user['id']}' AND timestamp > '$Last24Hours'")->fetch_row()[0];
	$Last24HoursClaimed = $mysqli->query("SELECT SUM(amount) FROM faucet_transactions WHERE type = 'Payout' AND userid = '{$user['id']}' AND timestamp > '$Last24Hours'")->fetch_row()[0];
	$Last24HoursClaimed = $Last24HoursClaimed ? $Last24HoursClaimed : 0;

	// Referral

	$TotalReferralPayout = $mysqli->query("SELECT SUM(amount) FROM faucet_transactions WHERE type = 'Referral Payout' AND userid = '{$user['id']}'")->fetch_row()[0];
	$TotalReferralPayout = $TotalReferralPayout ? $TotalReferralPayout : 0;

	$Last24HoursReferralPayout = $mysqli->query("SELECT SUM(amount) FROM faucet_transactions WHERE type = 'Referral Payout' AND userid = '{$user['id']}' AND timestamp > '$Last24Hours'")->fetch_row()[0];
	$Last24HoursReferralPayout = $Last24HoursReferralPayout ? $Last24HoursReferralPayout : 0;


	$content .= "<h3>Stats</h3>
	<div class='row'>
	<div class='col-md-12'>
		<h3>All time</h3>
	</div>
	<div class='col-md-4'>
		<h4>Total Claims</h4>
		<b>".$TotalClaims."</b>
	</div>
	<div class='col-md-4'>
		<h4>Total Claimed</h4>
		<b>".toSatoshi($TotalClaimed)."</b><br />Satoshi
	</div>
	<div class='col-md-4'>
		<h4>Total Referral Payout</h4>
		<b>".toSatoshi($TotalReferralPayout)."</b><br />Satoshi
	</div>
	<div class='col-md-12'>
		<h3>Last 24 Hours</h3>
	</div>
	<div class='col-md-4'>
		<h4>Claims</h4>
		<b>".$Last24HoursClaims."</b>
	</div>
	<div class='col-md-4'>
		<h4>Claimed</h4>
		<b>".toSatoshi($Last24HoursClaimed)."</b><br />Satoshi
	</div>
	<div class='col-md-4'>
		<h4>Total Referral Payout</h4>
		<b>".toSatoshi($Last24HoursReferralPayout)."</b><br />Satoshi
	</div>
	</div>";

	$headTable = "<h3>Last 15 Transactions</h3><center><table class='table' style='text-align: center; width: 400px;'border='0' cellpadding='2' cellspacing='2'>
	  <thead>
	    <tr>
	      <td>Type</td>
	      <td>Time</td>
	      <td>Amount</td>
	    </tr>
	    </thead>";

	$bodyTable = "<tbody>";

	$UserTransactions = $mysqli->query("SELECT * FROM faucet_transactions WHERE userid = '{$user['id']}' ORDER BY id DESC LIMIT 15");
	while($Tx = $UserTransactions->fetch_assoc()){
		$bodyTable .= "<tr>
						<td>".$Tx['type']."</td>
						<td>".findTimeAgo($Tx['timestamp'])."</td>
						<td>".$Tx['amount']."</td>
					</tr>";
	}

	$footerTable = "</tbody></table></center>";

	$content .= $headTable.$bodyTable.$footerTable;

} else {
	header("Location: index.php");
	exit;
}

$tpl->assign("content", $content);
$tpl->display();
?>

<?php
require 'config.php';

$removeLiveCampaignStatus = "UPDATE aster_campaigns SET dial_statuses='' WHERE campaign NOT IN (SELECT name FROM scheduler WHERE type='vb' AND status='Y' and start_time<NOW() and end_time>NOW())";
mysql_query($removeLiveCampaignStatus);
echo $today . " | Updated Non-Live campaign Status";
echo "\n";

$vbSql = "SELECT name,type,(select redial_option from aster_campaigns where campaign = a.name) as redial FROM scheduler a WHERE type='vb' AND status='Y' and start_time<NOW() and end_time>NOW()";
$vbCount = 0;
$vbQuery = mysql_query($vbSql);
$vbCount = mysql_num_rows($vbQuery);
while ($vbArray = mysql_fetch_array($vbQuery)) {
	$campaign = $vbArray['name'];
	$redial = $vbArray['redial'];
	 $checkAgentStatus = "SELECT campaign,number_group,vb_type FROM aster_campaigns WHERE active='Y' AND campaign='$campaign'";
	$queryAgentStatus = mysql_query($checkAgentStatus);
	while ($agentStatusRow = mysql_fetch_array($queryAgentStatus)) {
		$campaign = $agentStatusRow['campaign'];
		$listid = $agentStatusRow['number_group'];
		$updateCampaignStatus = "UPDATE aster_campaigns SET dial_statuses='LIVE' WHERE campaign='$campaign'";
		$today . " | Updated Live campaign Status | " . $campaign . "\n";
		mysql_query($updateCampaignStatus);
		echo $hooper = "select list_id,lead_id,replace(replace(replace(replace(phone_number, '+', ''), '-', ''), ')', '('),' ','') as phone,replace(replace(replace(replace(alt_number1, '+', ''), '-', ''), ')', '('),' ','') as alt_number,amount,due_date,billdate,first_name from aster_list where hopper_status=0 and active='Y' and list_id = '$listid' order by lead_id limit 20";

		$query = mysql_query($hooper);
		$i = 0;
		$insertSql = '';
		while ($result = mysql_fetch_array($query)) {
			if ($i == 0) {

			} else {
				$insertSql .= ',';
			}

			$phone = $result['phone'];
			$alt_number = $result['alt_number'];
			$listid = $result['list_id'];
			$leadid = $result['lead_id'];
			$amount = $result['amount'];
			$due_date = $result['due_date'];
			$billdate = $result['billdate'];
			$first_name = $result['first_name'];
			$insertSql .= "('$listid','$leadid','$phone','$campaign','$amount','$due_date','$billdate','$first_name','$alt_number')";
			$updateSql = "UPDATE aster_list SET hopper_status='1' WHERE lead_id='$leadid'";
			mysql_query($updateSql);
			echo $today . " | Updated buffer status for lead | " . $leadid . "\n\n";
			$i++;
		}
		if (strlen($insertSql) > 1) {
			$insertAllSql = "INSERT INTO aster_buffer(list_id,lead_id,phone_number,campaign,amount,due_date,bill_date,first_name,alt_number)values$insertSql";
			mysql_query($insertAllSql);
		}
	}
}

$removeLead = "DELETE FROM aster_buffer WHERE flag=1 and status >= '$redial'";
/*mysql_query($removeLead);
echo $today . " | Removed dialed numbers from buffer\n\n";*/
?>
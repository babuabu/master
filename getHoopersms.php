<?php
$connect = mysql_connect("localhost","root","asterisk");
mysql_select_db("bsnl", $connect);

$old_date = date('l, F d y h:i:s');             
$old_date_timestamp = strtotime($old_date);
$today = date('Y-m-d H:i:s', $old_date_timestamp);
$today = date("Y-m-d G:i:s"); 

$removeLiveCampaignStatus = "UPDATE aster_campaigns SET dial_statuses='' WHERE campaign NOT IN (SELECT name FROM scheduler WHERE type='vb' AND status='Y' and start_time<NOW() and end_time>NOW())";
mysql_query($removeLiveCampaignStatus);
echo $today." | Updated Non-Live campaign Status";
echo "<BR>";

$vbSql = "SELECT name,type,(select redial_option from aster_campaigns where campaign = a.name) as redial FROM scheduler a WHERE type='vb' AND status='Y' and start_time<NOW() and end_time>NOW()";
$vbCount=0;
$vbQuery = mysql_query($vbSql);
$vbCount = mysql_num_rows($vbQuery);
while($vbArray = mysql_fetch_array($vbQuery))
{
	$campaign = $vbArray['name'];
	$redial = $vbArray['redial'];
	// $campaign."\n\n";
	$checkAgentStatus = "SELECT campaign,number_group,sms_type FROM aster_campaigns WHERE active='Y' AND campaign='$campaign'";
	$queryAgentStatus = mysql_query($checkAgentStatus);
	while($agentStatusRow = mysql_fetch_array($queryAgentStatus))
	{
		$campaign = $agentStatusRow['campaign'];  
		$listid = $agentStatusRow['number_group'];  

		$sms_type = $agentStatusRow['sms_type'];


		if($sms_type=="Y"){
		$updateCampaignStatus = "UPDATE aster_campaigns SET dial_statuses='LIVE' WHERE campaign='$campaign'";
		$today." | Updated Live campaign Status | ".$campaign."\n";
		mysql_query($updateCampaignStatus);
		 $hooper = "select list_id,lead_id,replace(replace(replace(replace(phone_number, '+', ''), '-', ''), ')', '('),' ','') as phone,amount,due_date from aster_list where hoppersms_status='0' and active='Y' and list_id = '$listid' order by lead_id limit 20";

		$query = mysql_query($hooper);
		$i=0;
		$insertSql='';
		while($result = mysql_fetch_array($query))
		{ 
			if($i==0)
			{

			}
			else
			{
				$insertSql .= ',';
			}

			$phone = $result['phone'];
			$listid = $result['list_id'];
			$leadid = $result['lead_id'];
			$amount = $result['amount'];
			$due_date = $result['due_date'];
			$insertSql .= "('$listid','$leadid','$phone','$campaign','$amount','$due_date')";
			$updateSql = "UPDATE aster_list SET hoppersms_status='1' WHERE lead_id='$leadid'";
			mysql_query($updateSql);
			echo $today." | Updated buffer status for lead | ".$leadid."\n\n";
			$i++;
		}
		if(strlen($insertSql) > 1 )
		{
			$insertAllSql = "INSERT INTO aster_smsbuffer(list_id,lead_id,phone_number,campaign,amount,due_date)values$insertSql";
			mysql_query($insertAllSql);
		}
	}
}
}

$removeLead = "DELETE FROM aster_smsbuffer WHERE flag='1'";
mysql_query($removeLead);
echo $today." | Removed dialed numbers from buffer\n\n";
?>

<?php
/*
    By Martin COLEMAN (C) 2012. All rights reserved.
    Released under the Basic Software License v1.0.
    See COPYING file for details.
*/
include("conf.php");

$ns1_ip="NULL";
$ns2_ip="NULL";

show_header();
if(isset($_POST['submit']))
{
	$domain=$_POST['domain'];
	$userid=$_SESSION['userid'];
	$username=$_SESSION['username'];
	if(strlen($userid)<1)
	{
		echo "Error validating user session.\n"; die;
	}
	if(strlen($username)<5)
	{
		echo "Error validating user name.\n"; die;
	}
	$ns1=$_POST['ns1'];
	$ns2=$_POST['ns2'];
	if( ($ns1=="enter here") || ($ns2=="enter here") )
	{
		echo "<font color='#ff0000'><b>Error</b></font> Please change the nameservers to your own.\n"; die;
	}
	if( ($ns1=="") || ($ns2==""))
	{
		echo "<font color='#ff0000'><b>Error</b></font> Please change the nameservers to your own.\n"; die;
	}
	if( (isset($_POST['ns1_ip'])) && (strlen($_POST['ns1_ip'])>0) )
	{
		$ns1_ip=$_POST['ns1_ip'];
		if(validateIPAddress($ns1_ip)==0)
		{
			echo "<font color='#ff0000'><b>Error</b></font> NS1 Custom Nameserver must be a valid IPv4 address"; die;
		}
	}
	if( (isset($_POST['ns2_ip'])) && (strlen($_POST['ns2_ip'])>0) )
	{
		$ns2_ip=$_POST['ns2_ip'];
		if(validateIPAddress($ns2_ip)==0)
		{
			echo "<font color='#ff0000'><b>Error</b></font> NS2 Custom Nameserver must be a valid IPv4 address"; die;
		}
	}
	if( (strlen($domain)<2) && (strlen($domain)>50) && (strlen($ns1)<5) && (strlen($ns2)<5) )
	{
		echo "<font color='#ff0000'><b>Error</b></font> Domain details must adhere to standard lengths.\n"; die;
	}
	if($ns1 == $ns2)
	{
		echo "<font color='#FF8C00'><b>Please Note:</b></font> We highly recommend that you use two different nameserver values instead of the same one.<BR>\n";
	}
	echo "Processing ".$domain.$TLD."...";
	if(domain_taken($domain))
	{
		echo "Sorry, this domain has already been submitted for processing. If you believe this to be in error or you would like to dispute the previous registration, please contact us using the domain <a href=\"abuse.php\">abuse</a> page</a>. Thank you.";
		die;
	}
	$get_user_details="SELECT name, email FROM users WHERE userid='".$userid."' AND username='".$username."' LIMIT 1";
	$base=sqlite_open("OZ_tld.sq3", 0666);
	$get_user_details_results = sqlite_query($base, $get_user_details);
	$get_user_details_arr=sqlite_fetch_array($get_user_details_results);
	$name=$get_user_details_arr['name'];
	$email=$get_user_details_arr['email'];
	date_default_timezone_set('Australia/Brisbane');
	$rightnow=strftime('%Y-%m-%d');
	$expires=strftime('%Y-%m-%d', strtotime("$rightnow +1 year"));
	if( (strlen($ns1_ip)>7) && (strlen($ns2_ip)>7))
	{
		$query = "INSERT INTO domains (domain, name, email, ns1, ns2, ns1_ip, ns2_ip, registered, expires, updated, userid) VALUES('".$domain."', '".$name."', '".$email."', '".$ns1."', '".$ns2."', '".$ns1_ip."', '".$ns2_ip."', '".$rightnow."', '".$expires."', '".$rightnow."', '".$userid."')";
	} else {
		$query = "INSERT INTO domains (domain, name, email, ns1, ns2, registered, expires, updated, userid) VALUES('".$domain."', '".$name."', '".$email."', '".$ns1."', '".$ns2."', '".$rightnow."', '".$expires."', '".$rightnow."', '".$userid."')";
	}
	// echo "DEBUG: [".$query."]";
	// $base=sqlite_open("OZ_tld.sq3", 0666);
	sqlite_query($base, $query);

	/* flag init_tld */
	$inittld_file="/tmp/inittld.flag";
	$fh=fopen($inittld_file, 'w');
	fwrite($fh, "1");
	fclose($fh);

	echo "<font color=\"#008000\"><b>Complete</b></font><BR>Congratulations! Your new domain has been registered and should be live within the next 24 hours.";
} else {
	die("Error. Incorrect call.");
}
?>
</body>
</html>
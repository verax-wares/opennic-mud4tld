<?php
/*
   MUD4TLD - Martin's User and Domain system for Top Level Domains.
   Written 2012-2014 By Martin COLEMAN.
   This software is hereby dedicated to the public domain.
   Made for the OpenNIC Project.
	http://www.mchomenet.info/mud4tld.html

   v0.1
   - Domain registration only. No processing.
   
   v0.2 - First (limited) public release
   - User registration implemented.
   - User details can be updated.
   - Domain registration tied to user account.
   - Domain details can be update.
   - Basic back-end domain initialisation.
   
   v0.3
   - Better handling of domain name during domain registration.
   - Improved domain initialisation during processing.
   - Implemented basic framework for domain expiration.
   
   v0.4
   - Supports custom nameservers.
   - Sanitising of domain name and usernames.
   
   v0.5
   - Added validation of IP addresses for custom nameservers.
   - Further error checking during domain registration and details.

   v0.6 (Thanks to Jamyn Shanley for extensive testing!) - 2012-06-02
   - Enforce restriction on minimum password length.
   - Enforce restriction on minimum email length.
   - Validate emails.
   - Enforce validation on country setting.
   - Improved nameserver handling during domain registration.
   - Improved hyphen detection in domain registration (whoops! how'd I let that one slip by?)
   
   v0.62 - 2012-06-05
   - Fixed small problem in the error checking logic for domain processing.
   - Fixed small bug in domain update.
   
   v0.65 - 2012-06-08
   - Added domain updated and expires segments.
   - Added basic design for future super-admin module.
   - Fixed a few spacing and formatting glitches.
   
   v0.66 - 2012-06-11
    -Further improved init_tld integration.
    
   v0.67 - 2012-06-14
   - Added optional developer link for RM-API access.
   
   v0.68 - 2012-06-18
   - Added simple framework for future MySQL support.
   
   v0.69 - 2012-06-22
   - Further nameserver checking and validation.
   
   v0.70 - 2012-06-23
   - Improved SQLite2-SQLite3 for PHP5 transition code.
   
   v0.71 - 2012-07-07 (approx)
   - Transitioning code to RM API.
   
   v0.72 - 2012-07-13
   - Moved more DB-specific stuff to conf.php to allow a more abstract framework for additional DB system support.
   - Added more basic MySQL support.

   v0.75 - 2012-07-??
   - Bunch of small fixes.
   - Refactored a bit for better RM API support.
   
   v0.76 - 2012-08-30
   - Fixed a few WIP bugs and glitches.
   - Improved some more RM-API integration code.
   
   v0.77 - 2014-02-23
   - Updated license.
   - Fixed DB name link below.

   v0.77a - 2014-04-24
   - Dedicated to the public domain.
   
   v0.78 - 2014-0502 (Mario Rodriguez mrodrigu@gmail.com)
   - Added RFC & ISO compliant in domain names (db structure, size validations & block reserved)
   - Added MySQLi functionality
   - Added rm_api support using curl instead of fopen
   - Improved password hash to use SHA-256 instead of MD5
*/
session_start();
$TLD="oz";
$server='opennic.'.$TLD;
$ws_title="dot OZ";
$domain_expires=1; // to allow domains to expire
$sw_version="0.78";
$dev_link=0;
$user="demo"; /* for registrars */
$userkey="0123456789012345"; /* for registrars */
$tld_svr="http://opennic.".$TLD."/rm/rm_api.php";
$mysql_support=1;
$mysql_server='localhost';
$mysql_username='nicuser';
$mysql_password='dummy';
$mysql_database='nicoz_opennic';
$tld_db="../".$TLD."_tld.sq3";
$specialNamesOpenNIC='[register|registrar|opennic|openic|www|web|http|https|ftp|ftps|ldap|mail|pop|pop3|smtp|nic|dot|com|org|net|gov|biz|info|name]';
$specialNamesDNS='/^(dns|ns|nameserver)[0-9]*$/';
$specialNamesRFC6761='[test|localhost|invalid|example|alt]';
$specialNamesIANA='[aso|dnso|icann|internic|pso|afrinic|apnic|arin|gtld-servers|iab|iana|iana-servers|iesg|ietf|irtf|istf|lacnic|latnic|rfc-editor|ripe|root-servers]';

function database_open_now($location=null,$mode=null)	//mode is not used
{
    global $mysql_support, $mysql_server, $mysql_username, $mysql_password, $mysql_database;
    if($mysql_support==1)
    {
		$handle=mysqli_connect($mysql_server, $mysql_username, $mysql_password, $mysql_database) or die("MySQL connect error");
	} else {
		$handle = new SQLite3($location);
	}
	return $handle;
}

function database_query_now($dbhandle,$query)
{
    global $mysql_support;
	if($mysql_support==1)
	{
		$result = mysqli_query($dbhandle, $query);
	} else {
		$array['dbhandle'] = $dbhandle;
		$array['query'] = $query;
		$result = $dbhandle->query($query);
	}
    return $result;
}

function database_fetch_array_now(&$result) //,$type)
{
    global $mysql_support;
	if($mysql_support==1)
	{
		$resx=mysqli_fetch_array($result);
	} else {
		#Get Columns
		$i = 0;
		while ($result->columnName($i))
		{
			$columns[ ] = $result->columnName($i);
			$i++;
		}

		$resx = $result->fetchArray(SQLITE3_ASSOC);
	}
    return $resx;
}

function database_close_now($dbhandle) {
    global $mysql_support;
	if($mysql_support==1)
	{
		if (!empty($dbhandle)) mysql_close($dbhandle);
	}
}

function dbNumRows($qid)
{
  global $mysql_support;
  $numRows = 0;
  if ($mysql_support) {
		$numRows=mysqli_num_rows($qid);
  } else {
	  while ($rowR = database_fetch_array_now($qid))
		$numRows++;
	  $qid->reset ();
  }
  return ($numRows);
}

function domain_taken($domain)
{
	global $TLD, $user, $userkey, $tld_svr, $specialNamesOpenNIC, $specialNamesRFC6761, $specialNamesIANA, $specialNamesDNS;
	if ((preg_match($specialNamesOpenNIC, $domain))
		|| (preg_match($specialNamesRFC6761, $domain))
		|| (preg_match($specialNamesIANA, $domain))
		|| (preg_match($specialNamesDNS, $domain))
		) return true;	// user is trying to take an special name
	$URL=$tld_svr."?cmd=check&user=".$user."&userkey=".$userkey."&tld=".$TLD."&domain=".$domain;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $URL);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $ret_data = curl_exec($ch);
    curl_close($ch);
	if ($ret_data=='0') $ret_data=false;
	if ($ret_data=='1') $ret_data=true;
	return $ret_data;
}

function username_taken($username)
{
	global $tld_db;
	$base=database_open_now($tld_db, 0666);
	$query = "SELECT username FROM users WHERE username='".$username."' LIMIT 1";
	// echo "<BR><B>DEBUG: [".$query."]</B><BR>";
	$results = database_query_now($base, $query);
	if(dbNumRows($results))
	{
		return 1;
	} else {
		return 0;
	}
}

function show_header()
{
	global $ws_title, $TLD;
	echo "<html>\n<head>\n<title>".$ws_title."</title>\n</head>\n<body>\n";
	echo "<p align=\"left\"><a href=\"index.php\">opennic.".$TLD."</a></p>\n";
	if(!isset($_SESSION['username']))
	{
		echo "<p align=\"right\">Already have an account? <a href=\"user.php?action=frm_login\">Log in</a> or <a href=\"user.php?action=frm_register\">Register</a></p>\n";
	} else {
		echo "<p align=\"right\">Hello <b style=\"color: red;\">".$_SESSION['username']."</b>! [<a href=\"user.php?action=view_account\">My Account</a>]&nbsp;[<a href=\"user.php?action=logout\">Logout</a>].</p>\n";
	}
}

function clean_up_input($str)
{
	$new_str=htmlspecialchars(stripslashes($str));
	$new_str=preg_replace("/[^a-zA-Z0-9\-]/","", $new_str); /* replace characters we do not want */
	$new_str=preg_replace('/^[\-]+/','',$new_str); /* remove starting hyphens */
	$new_str=preg_replace('/[\-]+$/','',$new_str); /* remove ending hyphens */
	$new_str=str_replace(" ", "", $new_str); /* remove spaces */
	$new_str=strtolower($new_str); /* all lower case to remove confusion */
	return $new_str;
}

//function to validate ip address format in php by Roshan Bhattarai(http://roshanbh.com.np)
function validateIPAddress($ip_addr)
{
	// first of all the format of the ip address is matched
	if(preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/",$ip_addr))
	{
		// now all the intger values are separated
		$parts=explode(".",$ip_addr);
		// now we need to check each part can range from 0-255
		foreach($parts as $ip_parts)
		{
			if(intval($ip_parts)>255 || intval($ip_parts)<0)
				return 0; // if number is not within range of 0-255
		}
		return 1;
	}
	else
		return 0; // if format of ip address doesn't matches
}

function unique_id($l = 8)
{
	return substr(md5(uniqid(mt_rand(), true)), 0, $l);
}

function confirm_user($username)
{
	global $tld_db;
	$query = "UPDATE users SET verified=1 WHERE username='".$username."'";
	$base=database_open_now($tld_db, 0666);
	database_query_now($base, $query);
}
?>

<?php
/*
MariaDB - dnsman
Database: dnsman

User: dnsman
Password: gYCj49rQpoqlG9ODljyH
*/
session_start();
$TLD="chan";
$server='opennic.'.$TLD;
$ws_title="dot CHAN";
$domain_expires=1; // to allow domains to expire
$sw_version="0.78";
$dev_link=0;
$user="demo"; /* for registrars */
$userkey="0123456789012345"; /* for registrars */
#TEMP FIX
#$tld_svr="http://opennic.".$TLD."/rm/rm_api.php";
$tld_svr="http://chandns.net:82/rm/rm_api.php";
$mysql_support=1;
$mysql_server='localhost';
$mysql_username='dnsman';
$mysql_password='gYCj49rQpoqlG9ODljyH';
$mysql_database='dnsman';
$user_table='users';
$domain_table='domains';
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

function database_new_handle()
{
	global $mysql_server, $mysql_database, $mysql_username, $mysql_password;
	try {
		$dbh = new PDO("mysql:host=$mysql_server;dbname=$mysql_database", "$mysql_username", "$mysql_password");
		return $dbh;
	} catch (PDOException $e) {
		print "<b>Error!: " . $e->getMessage() . "</b><br/>";
		die();
	}
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

	$query = "SELECT * FROM domains WHERE domain='".$domain."' LIMIT 1";
	$results = database_query_now($base, $query);
	if(dbNumRows($results))
	{
		return 1;
	} else {
		return 0;
	}

	#$URL=$tld_svr."?cmd=check&user=".$user."&userkey=".$userkey."&tld=".$TLD."&domain=".$domain;
	#$ch = curl_init();
	#curl_setopt($ch, CURLOPT_URL, $URL);
    #curl_setopt($ch, CURLOPT_HEADER, 0);
    #curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    #$ret_data = curl_exec($ch);
    #curl_close($ch);
	#if ($ret_data=='0') $ret_data=false;
	#if ($ret_data=='1') $ret_data=true;
	#return $ret_data;
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
	$base=database_open_now($tld_db, 0666);
	$query = "UPDATE users SET verified=1 WHERE username='".$username."'";
	database_query_now($base, $query);
}
?>

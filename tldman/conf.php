<?php
/*
   MUD4TLD v0.61
   Martin's User and Domain system for Top Level Domains.
   (C) 2012 Martin COLEMAN. All rights reserved.
   Licensed under Basic Software License v1.0 (see COPYING for details)
   Made for the OpenNIC Project.

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
*/
session_start();
$TLD=".oz";
$ws_title="dot OZ";
$domain_expires=1; // for future use. to allow domains to expire
$sw_version="0.62";

function sqlite_open($location,$mode)
{
    $handle = new SQLite3($location);
    return $handle;
}

function sqlite_query($dbhandle,$query)
{
    $array['dbhandle'] = $dbhandle;
    $array['query'] = $query;
    $result = $dbhandle->query($query);
    return $result;
}

function sqlite_fetch_array(&$result) //,$type)
{
    #Get Columns
    $i = 0;
    while ($result->columnName($i))
    {
        $columns[ ] = $result->columnName($i);
        $i++;
    }

    $resx = $result->fetchArray(SQLITE3_ASSOC);
    return $resx;
}

function dbNumRows($qid)
{
  $numRows = 0;
  while ($rowR = sqlite_fetch_array ($qid))
    $numRows++;
  $qid->reset ();
  return ($numRows);
}

function domain_taken($domain)
{
	$base=sqlite_open("OZ_tld.sq3", 0666);
	$query = "SELECT domain FROM domains WHERE domain='".$domain."' LIMIT 1";
	// echo "<BR><B>DEBUG: [".$query."]</B><BR>";
	$results = sqlite_query($base, $query);
	if(dbNumRows($results))
	{
		return 1;
	} else {
		return 0;
	}
}

function username_taken($username)
{
	$base=sqlite_open("OZ_tld.sq3", 0666);
	$query = "SELECT username FROM users WHERE username='".$username."' LIMIT 1";
	// echo "<BR><B>DEBUG: [".$query."]</B><BR>";
	$results = sqlite_query($base, $query);
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
	echo "<p align=\"left\"><a href=\"index.php\">opennic".$TLD."</a></p>\n";
	if(!isset($_SESSION['username']))
	{
		echo "<p align=\"right\">Already have an account? <a href=\"user.php?action=frm_login\">Log in</a> or <a href=\"user.php?action=frm_register\">Register</a></p>\n";
	} else {
		echo "<p align=\"right\">Hello ".$_SESSION['username']."! [<a href=\"user.php?action=view_account\">My Account</a>]&nbsp;[<a href=\"user.php?action=logout\">Logout</a>].</p>\n";
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

?>
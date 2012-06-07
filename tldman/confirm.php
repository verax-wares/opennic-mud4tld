<?php
/*
    By Martin COLEMAN (C) 2012. All rights reserved.
    Released under the Basic Software License v1.0.
    See COPYING file for details.
*/
include("conf.php");
if(isset($_REQUEST['username']))
{
	$username=$_REQUEST['username'];
	if(isset($_REQUEST['userkey']))
	{
		$userkey=$_REQUEST['userkey'];
	} else {
		die("Userkey required.");
	}
	$clean_username=clean_up_input($username);
	if(username_taken($clean_username)==0)
	{
		show_header();
		echo "Sorry, that username does not exist.\n";
		echo "</body></html>\n";
		die;
	}

	$myFile = "/tmp/".$clean_username.".txt";
	$fh = fopen($myFile, 'r') or die("Can't open user key verification.");
	$theData=fread($fh,filesize($myFile));
	fclose($fh);
	if($theData != $userkey)
	{
		die("Invalid user key.");
	}
	unlink($myFile);

	$query = "UPDATE users SET verified=1 WHERE username='".$clean_username."'";
	$base=sqlite_open("OZ_tld.sq3", 0666);
	sqlite_query($base, $query);
	show_header();
	echo "Your account for ".$clean_username." is now confirmed. You may now login using the link above to start registering domains.";
	echo "</body></html>\n";
} else {
	die("Data error.");
};
?>
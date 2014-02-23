<?php
/*
    By Martin COLEMAN (C) 2012-2014. All rights reserved.
    Released under the 2-clause BSD license.
    See COPYING file for details.
*/
include("conf.php");

function form_check_domain()
{
	global $TLD;
?>
<p>
<form action="domain.php" method="post">
Domain name <input type="text" name="domain">.<?php echo $TLD; ?>&nbsp;<input type="submit" name="check" value="Check">
<input type="hidden" name="action" value="check_domain">
</form>
</p>
<?php
}

function check_domain($domain)
{
    global $TLD;
	/* sanity check the domain */
	$name=htmlspecialchars(stripslashes($domain));
	$name=preg_replace("/[^a-zA-Z0-9\-]/","", $name); /* replace characters we do not want */
	$name=preg_replace('/^[\-]+/','',$name); /* remove starting hyphens */
	$name=preg_replace('/[\-]+$/','',$name); /* remove ending hyphens */
	$name=str_replace(" ", "", $name); /* remove spaces */
	$name=str_replace("--", "-", $name); /* remove double hyphens */
	$name=strtolower($name); /* all lower case to remove confusion */
	if( (strlen($name)<2) || (strlen($name)>50))
	{
		echo "Sorry, domain names must contain at least 2 characters and be no longer than 50 characters.";
		echo "Please go back and try again.";
		die;
	}
	if(strlen($name)>2)
	{
		echo "Checking ".$name.".".$TLD." for you...";
		if(domain_taken($name))
		{
			echo "<font color=\"#ff0000\"><b>Taken</b></font><BR><BR>Sorry, that name is already taken.";
		} else {
			echo "<font color=\"#008000\"><b>Available!</b></font><BR><BR>Congratulations! ".$name.".".$TLD." is available.\n";
			echo "Would you like to register it now?\n<form action=\"register.php\" method=\"post\">\n<input type=\"hidden\" name=\"domain\" value=\"".$name."\">\n<input type=\"submit\" name=\"submit\" value=\"Yes!\">\n</form>\n";
		}
		echo "You can use the form below to search for another domain if you like.";
	}
	form_check_domain();
}

function frm_register_domain($domain)
{
	global $TLD, $ws_title;
?>
<table width="500" align="center">
<tr><td align="center"><h1><?php echo $ws_title; ?> Registration</h1></td></tr>
<tr><td>
<p>Please fill out the information below. Make sure the details are correct before clicking "Register Domain" as incorrect details may delay the registration process.</p>
</td></tr>
<tr><td align="center">
<p><br><font color="#008000">You are registering <b><?php echo $domain.".".$TLD; ?></b></font><BR>To register a different domain, please <a href="domain.php">check</a> it first.</p>
<?php
if(!isset($_SESSION['username']))
{
	echo "You must <a href=\"user.php?action=frm_login\">login</a> first before trying to register a domain.";
} else {
?>
<form action="process.php" method="post">
<table width="450" border=0 cellspacing=1 cellpadding=0>
<tr><td colspan="2">&nbsp;</td></tr>
<tr><td width="200" valign="top">Username</td><td><?php echo $_SESSION['username']; ?><BR><font size="-1">(not you? <a href="user.php?action=frm_login">Login</a> as the correct user)</font></td></tr>
<tr><td colspan="2">&nbsp;</td></tr>
<tr><td colspan="2"><b>Nameserver Settings</b></td></tr>
<tr><td>NS1 <font size="-1">eg: ns1.mywebhost.com</font></td><td><input type="text" name="ns1" value="enter here"></td></tr>
<tr><td>NS2 <font size="-1">eg: ns2.mywebhost.com</font></td><td><input type="text" name="ns2" value="enter here"></td></tr>
<tr><td colspan="2">&nbsp;</td></tr>
<tr><td colspan="2">Custom Nameservers <font size="-1">(Experts only, can be left blank)</font></td></tr>
<tr><td>NS1 <font size="-1">(IPv4 only)</font></td><td><input type="text" name="ns1_ip"></td></tr>
<tr><td>NS2 <font size="-1">(IPv4 only)</font></td><td><input type="text" name="ns2_ip"></td></tr>
<tr><td colspan="2">&nbsp;</td></tr>
<tr><td colspan="2" align="center"><input type="submit" name="submit" value="Register Domain"></tr></tr>
</table>
<?php
echo "<input type=\"hidden\" name=\"domain\" value=\"".$domain."\">\n";
?>
</form>
<?php
}
?>
</td></tr>
</table>
<?php
}

function register_domain($domain, $ns1, $ns2, $ns1_ip, $ns2_ip)
{
	global $TLD, $user, $userkey;

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
	if( ($ns1=='') || ($ns2==''))
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
	if( (strlen($ns1_ip)>7) && (strlen($ns2_ip)>7))
	{
		$URL=$tld_svr."?cmd=register&user=".$user."&userkey=".$userkey."&tld=".$tld."&domain=".$domain."&name=".$name."&email=".$email."&ns1=".$ns1."&ns2=".$ns2."&ns3=".$ns3."&ns2=".$ns2;
	} else {
		$URL=$tld_svr."?cmd=register&user=".$user."&userkey=".$userkey."&tld=".$tld."&domain=".$domain."&name=".$name."&email=".$email."&ns1=".$ns1."&ns2=".$ns2;
	}
	$handle=fopen($URL, "r");
	$ret_data=fread($handle, 1024);
	fclose($handle);

	switch($ret_data)
	{
		case "0":
			echo "<font color=\"#800000\"><b>Error</b></font><BR>An error occured during registration. Please try again.";
			break;
		case "1":
			echo "<font color=\"#008000\"><b>Complete</b></font><BR>Congratulations! Your new domain has been registered and should be live within the next 24 hours.";
			break;
		case "255":
			echo "<font color=\"#800000\"><b>Server Error</b></font><BR>A server error has occured. Please contact this site's administrators.";
			break;
	}
}

function delete_domain($domain)
{
	global $tld_svr, $user, $userkey, $TLD;

	show_header();
	$userid=$_SESSION['userid'];
	$URL=$tld_svr."?cmd=delete&user=".$user."&userkey=".$userkey."&tld=".$TLD."&domain=".$domain;
	$handle=fopen($URL, "r");
	$ret_data=fread($handle, 10);
	fclose($handle);
	switch($ret_data)
	{
        case "0":
            echo "<center><b>Error, domain not deleted</b>. Possibly an administrative glitch.</center>";
            break;
        case "1":
            echo "<center><b>Domain deleted</b>. Changes may take up to 24 hours to take effect.</center>";
            break;
        case "255":
            echo "Server error occured.";
            break;
        default:
            echo "An unknown problem has occured. Please try again later.";
            break;
	}
}

function frm_delete_domain($domain)
{
	global $TLD;
	
	show_header();
	?>
	<center>
	<h2>Cancel <?php echo $domain.$TLD; ?> Registration</h2>
	<form action="domain.php" method="post">
	This means you will no longer be able to manage it and that someone else may register it instead.<BR>
	Are you sure you wish to delete <b><?php echo $domain.".".$TLD;?>?</b><BR>
	<input type="checkbox" name="delete">Yes <input type="submit" value="Confirm">
	<input type="hidden" name="domain" value="<?php echo $domain; ?>">
	<input type="hidden" name="action" value="confirm_delete_domain">
	</form>
	</center>
	<?php
}

function frm_view_domain($domain)
{
	global $TLD, $tld_db;

	show_header();
	$userid=$_SESSION['userid'];
	$base=sqlite_open_now($tld_db, 0666);
	$query = "SELECT * FROM domains WHERE userid='".$userid."' AND domain='".$domain."' LIMIT 1";
	$results = sqlite_query_now($base, $query);
	$arr=sqlite_fetch_array_now($results);
	$real_userid=$arr['userid'];
	if($userid != $real_userid)
	{
		echo "<font color=\"#ff0000\"><b>Error: You do not have permission to modify this domain.</b></font>";
		die;
	}
	echo "<center><h2>".$domain.$TLD." Modification</h2>\n";
	echo "Registered: ".$arr['registered']."<BR><BR>\n";
?>
<form action="domain.php" method="post">
<table width="320" border=0 cellspacing=2 cellpadding=0>
<tr><td colspan="2"><b>Nameserver Settings</b></td></tr>
<tr><td>NS1</td><td><input type="text" name="ns1" value="<?php echo $arr['ns1']; ?>"></td></tr>
<tr><td>NS2</td><td><input type="text" name="ns2" value="<?php echo $arr['ns2']; ?>"></td></tr>
<tr><td colspan="2">&nbsp;</td></tr>
<tr><td colspan="2">Custom Nameserver Settings<BR><font size="-1">(Experts only)</font></td></tr>
<tr><td>NS1</td><td><input type="text" name="ns1_ip" value="<?php echo $arr['ns1_ip']; ?>"><font size="-1">IPv4 only</font></td></tr>
<tr><td>NS2</td><td><input type="text" name="ns2_ip" value="<?php echo $arr['ns2_ip']; ?>"><font size="-1">IPv4 only</font></td></tr>
<tr><td colspan="2">&nbsp;</td></tr>
<tr><td colspan="2" align="center">
<input type="hidden" name="domain" value="<?php echo $domain; ?>">
<input type="hidden" name="action" value="update">
<input type="submit" name="submit" value="Update Domain">
</td></tr></table>
</form>
<p>&nbsp;</p>

<font color="#ff0000"><b>Careful!</b></font>
<form action="domain.php" method="post">
<input type="hidden" name="action" value="delete_domain">
<input type="hidden" name="domain" value="<?php echo $domain; ?>">
<input type="submit" value="Delete Domain">
</form>
<?php
}

function update_domain($domain, $ns1, $ns2, $ns1_ip, $ns2_ip)
{
	global $TLD, $tld_db;

	show_header();
	$updated=strftime('%Y-%m-%d');
	$userid=$_SESSION['userid'];
	$base=sqlite_open_now($tld_db, 0666);
	$query = "SELECT userid FROM domains WHERE userid='".$userid."' AND domain='".$domain."' LIMIT 1";
	$results = sqlite_query_now($base, $query);
	$arr=sqlite_fetch_array_now($results);
	$real_userid=$arr['userid'];
	if($userid != $real_userid)
	{
		echo "<font color=\"#ff0000\"><b>Error: You do not have permission to modify this domain.</b></font>";
		die;
	}
	echo "Updating ".$domain.$TLD."...";
	if(($ns1_ip != "NULL") && ($ns2_ip != "NULL"))
	{
		if( (!validateIPAddress($ns1_ip)) && (!validateIPAddress($ns2_ip)) )
		{
			echo "Error. NS1 and NS2 custom nameservers must be IP addresses.";
		} else {
			$query = "UPDATE domains SET ns1='".$ns1."', ns2='".$ns2."', ns1_ip='".$ns1_ip."', ns2_ip='".$ns2_ip."', updated='".$updated."' WHERE domain='".$domain."'";
		}
	} else {
		$query = "UPDATE domains SET ns1='".$ns1."', ns2='".$ns2."', updated='".$updated."' WHERE domain='".$domain."'";
	}
	sqlite_query_now($base, $query);
	echo "Done. The changes should take effect within the hour. Please be aware some networks may not see the changes for up to 72 hours.<BR>";
	if($ns1 == $ns2)
	{
		echo "<b>Please Note:</b> We highly recommend that you use two different nameserver values instead of the same one.";
	}
}

function check_domain1($domain)
{
	/* sanity check the domain */
	$name=htmlspecialchars(stripslashes($domain));
	$name=preg_replace("/[^a-zA-Z0-9\-]/","", $name); /* replace characters we do not want */
	$name=preg_replace('/^[\-]+/','',$name); /* remove starting hyphens */
	$name=preg_replace('/[\-]+$/','',$name); /* remove ending hyphens */
	$name=str_replace(" ", "", $name); /* remove spaces */
	$name=str_replace("--", "-", $name); /* remove double hyphens */
	$name=strtolower($name); /* all lower case to remove confusion */
	if( (strlen($name)<2) || (strlen($name)>50))
	{
		echo "Sorry, domain names must contain at least 2 characters and be no longer than 50 characters.";
		echo "Please go back and try again.";
		die;
	}
	if(strlen($name)>1)
	{
		echo "Checking ".$name.$TLD." for you...";
		if(domain_taken($name))
		{
			echo "<font color=\"#ff0000\"><b>Taken</b></font><BR><BR>Sorry, that name is already taken.";
		} else {
			echo "<font color=\"#008000\"><b>Available!</b></font><BR><BR>Congratulations! ".$name.$TLD." is available.\n";
			echo "Would you like to register it now?\n<form action=\"register.php\" method=\"post\">\n<input type=\"hidden\" name=\"domain\" value=\"".$name."\">\n<input type=\"submit\" name=\"submit\" value=\"Yes!\">\n</form>\n";
		}
		echo "You can use the form below to search for another domain if you like.";
	}
	echo "<p><BR>\n<form action=\"domain.php\" method=\"post\">\nDomain name <input type=\"text\" name=\"domain\">".$TLD."&nbsp;<input type=\"submit\" name=\"check\" value=\"Check\">\n</form>\n</p>\n</center>\n";
}

if(isset($_REQUEST['action']))
{
	$action=$_REQUEST['action'];
	switch($action)
	{
		case "frm_check_domain":
			form_check_domain();
			break;
		case "check_domain":
			if(!isset($_POST['domain']))
			{
				echo "Error. No domain specified."; die;
			}
			$domain=$_POST['domain'];
			check_domain($domain);
			break;
		case "frm_register_domain":
			frm_register_domain();
			break;
		case "register_domain":
			if(!isset($_POST['domain']))
			{
				echo "Error. No domain specified."; die;
			}
			$domain=$_POST['domain'];
			if(!isset($_POST['ns1_ip']))
			{
				$ns1_ip="NULL";
			}
			if(!isset($_POST['ns2_ip']))
			{
				$ns2_ip="NULL";
			}
			$ns2_ip="NULL";
			register_domain($domain, $ns1, $ns2, $ns1_ip, $ns2_ip);
			break;
		case "confirm_delete_domain":
			if(!isset($_POST['domain']))
			{
				echo "Error. No domain specified."; die;
			}
			$domain=$_POST['domain'];
			if(!isset($_POST['delete']))
			{
				echo "Error. Deletion validation failed."; die;
			}
			delete_domain($domain);
			break;
		case "delete_domain":
			$domain=$_POST['domain'];
			frm_delete_domain($domain);
			break;
		case "modify":
			if(!isset($_SESSION['userid']))
			{
				die("Domain modification not allowed.");
			}
			if(!isset($_REQUEST['domain']))
			{
				die("Invalid domain request");
			}
			$domain=$_REQUEST['domain'];
			frm_view_domain($domain);
			break;
		case "update":
			if(!isset($_SESSION['userid']))
			{
				die("Domain modification not allowed.");
			}
			if(!isset($_POST['domain']))
			{
				die("Invalid domain request");
			}
			$domain=$_POST['domain'];
			
			/* standard nameservers */
			if(!isset($_POST['ns1']))
			{
				die("Nameserver 1 is required.");
			}
			$ns1=$_POST['ns1'];
			if($ns1=='')
			{
				die("Nameserver 1 is required.");
			}
			if(!isset($_POST['ns2']))
			{
				die("Nameserver 2 is required.");
			}
			$ns2=$_POST['ns2'];
			if($ns2=='')
			{
				die("Nameserver 2 is required.");
			}
			if( (strlen($ns1)<7) && (strlen($ns2)<7) )
			{
				die("Nameservers need to be at least 7 characters long.");
			}
			/* deal with custom nameservers */
			if(isset($_POST['ns1_ip']))
			{
				if(strlen($_POST['ns1_ip'])>0)
				{
					$ns1_ip=$_POST['ns1_ip'];
				} else {
					$ns1_ip="NULL";
				}
			}
			if(isset($_POST['ns2_ip']))
			{
				if(strlen($_POST['ns2_ip'])>0)
				{
					$ns2_ip=$_POST['ns2_ip'];
				} else {
					$ns2_ip="NULL";
				}
			}

			update_domain($domain, $ns1, $ns2, $ns1_ip, $ns2_ip);
			break;
		case "check_domain":
			$domain=$_POST['domain'];
			check_domain($domain);
			break;
		default:
			echo "Invalid command.";
			die;
	}
} else {
	show_header();
	echo "Unspecified error.";
}
?>
</body>
</html>

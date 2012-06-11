<?php
/*
    By Martin COLEMAN (C) 2012. All rights reserved.
    Released under the Basic Software License v1.0.
    See COPYING file for details.
*/
include("conf.php");

function delete_domain($domain)
{
	show_header();

	$userid=$_SESSION['userid'];
	$base=sqlite_open("OZ_tld.sq3", 0666);
	$query = "SELECT userid FROM domains WHERE userid='".$userid."' AND domain='".$domain."' LIMIT 1";
	$results = sqlite_query($base, $query);
	$arr=sqlite_fetch_array($results);
	$real_userid=$arr['userid'];
	if($userid != $real_userid)
	{
		echo "<font color=\"#ff0000\"><b>Error: You do not have permission to modify this domain.</b></font>";
		die;
	}
	// echo "<center>This is in testing at the moment and is non-functional.<BR>Please try again at a later time.</center>";
	$del_query="DELETE FROM domains WHERE domain='".$domain."'";
	sqlite_query($base, $del_query);
	echo "<center><b>Domain deleted</b>. Changes may take up to 24 hours to take effect.</center>";
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
	Are you sure you wish to delete <b><?php echo $domain.$TLD;?>?</b><BR>
	<input type="checkbox" name="delete">Yes <input type="submit" value="Confirm">
	<input type="hidden" name="domain" value="<?php echo $domain; ?>">
	<input type="hidden" name="action" value="confirm_delete_domain">
	</form>
	</center>
	<?php
}

function frm_view_domain($domain)
{
	global $TLD;

	show_header();
	$userid=$_SESSION['userid'];
	$base=sqlite_open("OZ_tld.sq3", 0666);
	$query = "SELECT * FROM domains WHERE userid='".$userid."' AND domain='".$domain."' LIMIT 1";
	$results = sqlite_query($base, $query);
	$arr=sqlite_fetch_array($results);
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
	global $TLD;

	show_header();
	$updated=strftime('%Y-%m-%d');
	$userid=$_SESSION['userid'];
	$base=sqlite_open("OZ_tld.sq3", 0666);
	$query = "SELECT userid FROM domains WHERE userid='".$userid."' AND domain='".$domain."' LIMIT 1";
	$results = sqlite_query($base, $query);
	$arr=sqlite_fetch_array($results);
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
	sqlite_query($base, $query);
	echo "Done. The changes should take effect within the hour. Please be aware some networks may not see the changes for up to 72 hours.<BR>";
	if($ns1 == $ns2)
	{
		echo "<b>Please Note:</b> We highly recommend that you use two different nameserver values instead of the same one.";
	}

	/* flag init_tld */
	$inittld_file="/tmp/inittld.flag";
	$fh=fopen($inittld_file, 'w');
	fwrite($fh, "1");
	fclose($fh);
}

function check_domain($domain)
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
	// $name=$name.$TLD; removed for v0.3
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
			if(!isset($_POST['ns2']))
			{
				die("Nameserver 2 is required.");
			}
			$ns2=$_POST['ns2'];

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
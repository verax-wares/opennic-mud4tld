<?php
/*
   MUD4TLD - Martin's User and Domain system for Top Level Domains.
   Written 2012-2014 By Martin COLEMAN.
   This software is hereby dedicated to the public domain.
   Made for the OpenNIC Project.
   http://www.mchomenet.info/mud4tld.html
*/
include("conf.php");

function login($username, $password)
{
	global $tld_db;
	$username=htmlspecialchars(stripslashes($username));
	$password=htmlspecialchars(stripslashes($password));
	$base=database_open_now($tld_db, 0666);
	$real_password=hash('sha256',$password);
	$query = "SELECT userid, username, email FROM users WHERE username='".$username."' AND password='".$real_password."' AND verified=1 LIMIT 1";
	$results = database_query_now($base, $query);
	if(dbNumRows($results))
	{
		$arr=database_fetch_array_now($results);
		$_SESSION['username'] = $username;
		$_SESSION['userid'] = $arr['userid'];
		// $_SESSION['email'] = $arr['email'];
		// $_SESSION['country'] = $arr['country'];
		header("location: index.php");
	} else {
		show_header();
		echo "Incorrect username or password or account not verified. Please try again.";
		die();
	}
}

function form_login()
{
	global $TLD;
	show_header();
?>
<form action="user.php" method="post">
<table width="400" align="center">
<tr><td colspan="2" align="center"><h2><?php echo $TLD; ?> User Login</h2></td></tr>
<tr><td valign="top">Username:</td><td><input type="text" name="username"></td></tr>
<tr><td valign="top">Password:</td><td><input type="password" name="password"></td></tr>
<tr><td colspan="2" align="center"><input type="submit" name="submit" value="Login"></td></tr>
<tr><td colspan="2" align="center"><br><a href="user.php?action=frm_send_password">Send new password</a></td></tr>
</table>
<input type="hidden" name="action" value="login">
</form>
<?php
}

function frm_send_password()
{
	global $TLD;
	show_header();
?>
<form action="user.php" method="post">
<table width="400" align="center">
<tr><td colspan="2" align="center"><h2><?php echo $TLD; ?> Send Password</h2></td></tr>
<tr><td valign="top">Username:</td><td><input type="text" name="username"></td></tr>
<tr><td valign="top">Email:</td><td><input type="text" name="email"></td></tr>
<tr><td colspan="2" align="center"><input type="submit" name="action" value="Send_Password"></td></tr>
</table>
</form>
<?php
}

function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $count = mb_strlen($chars);
    for ($i = 0, $result = ''; $i < $length; $i++) {
        $index = rand(0, $count - 1);
        $result .= mb_substr($chars, $index, 1);
    }
    return $result;
}

function send_password($user, $email) {
	global $tld_db, $TLD, $server;

	$user=htmlspecialchars(stripslashes($user));
	$email=htmlspecialchars(stripslashes($email));
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		echo "Invalid email account. Please try again.";
		die();
	}
	$base=database_open_now($tld_db, 0666);
	$query = "SELECT userid FROM users WHERE username='".$user."' AND email='".$email."' AND verified=1 LIMIT 1";
	$results = database_query_now($base, $query);
	if(dbNumRows($results))
	{
		$newPass=generatePassword(8);
		$real_password=hash('sha256',$newPass);
		$query = "UPDATE users set password='".$real_password."' WHERE username='".$user."' AND email='".$email."'";
		$results = database_query_now($base, $query);
		if (!empty($results)) {
			$to      = $email;
			$subject = $server.' new password';
			$message = 'You have requested a new password'."\r\n".'Your new password is: '.$newPass."\r\n\r\nThank you for use ".$server." services\r\n\r\n";
			$headers = 'From: Opennic <no-reply@'.$server. ">\r\n";
			mail($to, $subject, $message, $headers);
			header("location: index.php");
		}
	} else {
		show_header();
		echo "Incorrect username or email or account not verified. Please try again.";
		die();
	}
}

function form_register()
{
	global $TLD;
	show_header();
?>
<form action="user.php" method="post">
<table width="400" align="center">
<tr><td colspan="2" align="center"><h2><?php echo $TLD; ?> User Registration</h2><font size="-1">All entries must be at least 5 characters long.</font></td></tr>
<tr><td>Email</td><td><input type="text" name="email1" maxlength="50"><sup>*</sup></td></tr>
<tr><td>Email confirmation</td><td><input type="text" name="email2" maxlength="50"><sup>*</sup></td></tr>
<tr><td>Username</td><td><input type="text" name="username" maxlength="20"></td></tr>
<tr><td valign="top">Password</td><td><input type="password" name="password1"><sup>**</sup></td></tr>
<tr><td valign="top">Password Confirm</td><td><input type="password" name="password2"></td></tr>
<tr><td colspan="2" align="center"><input type="submit" name="submit" value="Register"></td></tr>
<tr><td colspan="2"><font size="-1">
<sup>*</sup> Choose a reliable email as this can only be changed later by contacting support.<br>
<sup>**</sup> This is encrypted and cannot be retrieved.<br>
</font></td></tr>
</table>
<input type="hidden" name="action" value="register">
</form>
<?php
}

function register($username, $email, $password)
{
	global $TLD, $tld_db;

	show_header();
	
	/* prepare clean data */
	$username=htmlspecialchars(stripslashes($username));
	$password=htmlspecialchars(stripslashes($password));
	#$name=htmlspecialchars(stripslashes($name));
	$email=htmlspecialchars(stripslashes($email));
	
	/* perform validation checks */
	if(filter_var($email, FILTER_VALIDATE_EMAIL) == FALSE)
	{
		echo "Not a valid email address";
		die();
	}
	$username=clean_up_input($username); /* just in case */
	$username=strtolower($username);
	if(username_taken($username))
	{
		echo "That username is already taken. Please try using another, different username.";
		die();
	}
	
	/* let the user know */
	echo "Creating new account for $username<BR>\n";

	/* generate user verification key */
	$userkeyfile="tmp/".$username.".ukf";	// some environments does not allow execuion outside its boundaries even /tmp
	$fh=fopen($userkeyfile, 'w') or die("Can't create user key verification file. Please report this to the admin.");
	$userkey=unique_id(16);
	fwrite($fh, $userkey);
	fclose($fh);

	/* prepare account */
	$base=database_open_now($tld_db, 0666);
	$real_password=hash('sha256',$password);
	date_default_timezone_set('Etc/UTC');
	$registered=strftime('%Y-%m-%d');
	#$query = "INSERT INTO users (username, password, email, registered, verified)
	#		VALUES('".$username."', '".$real_password."', '".$email."', '".$registered."', 0)";
	#$results = database_query_now($base, $query);
	$results = database_pdo_query("INSERT INTO users (username, password, email, registered, verified) VALUES('".$username."', '".$real_password."', '".$email."', '".$registered."', 0)");
	
	/* construct email */
	$msg_FROM = "FROM: hostmaster@opennic.".$TLD;
	$msg_subject = "OpenNIC ".$TLD." User Registration.";
	$msg = "Welcome ".$username." to OpenNIC.".$TLD."!\n\n";
	$msg .= "Your details are:\n";
	$msg .= "Username: ".$username."\n";
	$msg .= "Password: (The one you specified during sign up. Remember, this is encrypted and cannot be retrieved.)\n\n";
	$msg .= "Always ensure your contact details are up to date.\n\n";
	$msg .= "To confirm this email and activate your account, please visit http://opennic.".$TLD."/confirm.php?username=".$username."&userkey=".$userkey."\nYou have 24 hours to activate your account, otherwise it will be deleted.\n\n";
	$msg .= "Thank you for your patronage.\nOpenNIC".$TLD." Administration.\n";
	mail($email, $msg_subject, $msg, $msg_FROM);
	echo "If registration was successful, you should receive an email shortly. Please contact hostmaster@opennic.".$TLD." if you do not receive one within 24 hours. Please ensure that email address is on your email whitelist.";
	// echo "DEBUG: [".$msg."]";
}

function dashboard()
{
	global $TLD, $domain_expires, $tld_db, $user_table;
	show_header();
	
	$username=$_SESSION['username'];
	$userid=$_SESSION['userid'];

	// echo "<p align=\"right\"><a href=\"user.php?action=logout\">Logout</a></p>\n";
	echo "<center><H2>Welcome to ".$username."'s Dashboard for .".$TLD."</H2>\n";
	echo "<b>My .".$TLD." domains</b><BR><BR>";
	$base=database_open_now($tld_db, 0666);
	$query="SELECT domain, registered, expires FROM domains WHERE userid=".$userid."";
	$results = database_query_now($base, $query);
	if(dbNumRows($results))
	{
		echo "<table width=\"400\" align=\"center\" border=0 cellspacing=1 cellpadding=0>\n";
		echo "<tr><td>Domain Name</td><td>Created</td>";
		if($domain_expires==1)
		{
			echo "<td>Expires</td>";
		}
		echo "</tr>\n";

		while($arr=database_fetch_array_now($results))
		{
			echo "<tr><td><a href=\"domain.php?action=modify&domain=".$arr['domain']."\">".$arr['domain'].'.'.$TLD."</a></td><td>".$arr['registered']."</td>";
			if($domain_expires==1)
			{
				echo "<td>".$arr['expires']."</td>";
			}
			echo "</tr>\n";
		}
		echo "</table>\n";
	} else {
		echo "You do not have any domains registered.\n";
	}
	echo "You can register a new ".$TLD." <a href=\"domain.php?action=frm_check_domain\">here</a>.";

	$get_user_details="SELECT email, pgpkey FROM users WHERE userid='".$userid."' AND username='".$username."' LIMIT 1";
	$base=database_open_now($tld_db, 0666);
	$get_user_details_results = database_query_now($base, $get_user_details);
	$get_user_details_arr=database_fetch_array_now($get_user_details_results);
	#$name=$get_user_details_arr['name'];
	#$get_user_details_arr=databse_pdo_query("SELECT email, pgpkey FROM $user_table WHERE userid='$userid' AND username='$username' LIMIT 1";
	$email=$get_user_details_arr['email'];
	#$country=$get_user_details_arr['country'];
	$pgpkey=$get_user_details_arr['pgpkey'];
?>
<BR><BR>
<form action="user.php" method="post">
<table width="450" align="center">
<tr><td colspan="2" align="center"><b>.<?php echo $TLD; ?> User Details</b></td></tr>
<tr><td>Email</td><td><?php echo $email; ?><sup>*</sup></td></tr>
<tr><td>Current Password</td><td><input type="password" name="password"></td></tr>
<tr><td valign="top">Password</td><td><input type="password" name="password1"><BR><font size="-1">(Must be at least 5 characters long)</font></td></tr>
<tr><td>Password Confirm</td><td><input type="password" name="password2"></td></tr>
<tr><td>PGP Key*</td><td><textarea rows="40" cols="85" name="pgpkey" wrap="physical"><?php echo $pgpkey; ?></textarea></td></tr>
<tr><td colspan="2" align="center"><input type="submit" name="submit" value="Update"></td></tr>
<input type="hidden" name="action" value="update_account">
<tr><td colspan="2">
<font size="-1">
<sup>*</sup>Please contact support to change this.<BR>
<sup>**</sup>This is optional and for our statistics only.
</font></td></tr>
</table>
</form>
<?php
	echo "</center>";
}

function update_account($password, $pgpkey)
{
	global $user_table;
	show_header();
	$changed=0;
	if(!isset($_SESSION['userid']))
	{
		echo "No valid account."; die();
	}
	$userid=$_SESSION['userid'];
	if ( strlen($password) > 0 )
	{
		$password=htmlspecialchars(stripslashes($password));
		$real_password=hash('sha256',$password);
		$pass_ret = database_pdo_query("UPDATE $user_table SET password='$real_password' WHERE userid='$userid'");
		$changed=1;
		echo "debug pass updated<br>";
	}
	if ( strlen($pgpkey) > 0 )
	{
		echo "debug pgpkey gt 0 <br>";
		if(!validatePGP($pgpkey))
		{
			echo "Invalid PGP key<br>";
			die();
		}
		echo "pgp key passes validation<br>";
		$pgp_ret = database_pdo_query("UPDATE $user_table SET pgpkey='$pgpkey' WHERE userid='$userid'");
	}
	echo "Details updated.";
}

if(!isset($_REQUEST['action']))
{
	echo "Invalid command.";
	die();
} else {
	$action=$_REQUEST['action'];
	switch($action)
	{
		case "frm_login":
			form_login();
			break;
		case "frm_register":
			form_register();
			break;
		case "frm_send_password":
			frm_send_password();
			break;
		case "Send_Password":
			if(isset($_POST['email']) && isset($_POST['username'])) {
				$user=$_POST['username'];
				$email=$_POST['email'];
				send_password($user, $email);
			} else {
				echo "Data error. Please retry.";
				die();
			}
		case "login":
			if(isset($_POST['username']) && isset($_POST['password']))
			{
				$username=$_POST['username'];
				$password=$_POST['password'];
			} else {
				echo "Data error. Please retry.";
				die();
			}
			if( (strlen($username)<4) && (strlen($password)<6) )
			{
				echo "Invalid data (username>=4 chars, password >=6 chars). Please try again.";
				die();
			}
			login($username, $password);
			break;
		case "register":
			if(isset($_POST['username']) && isset($_POST['password1']) && isset($_POST['password2']) && isset($_POST['email1'])  && isset($_POST['email2']))
			{
				$username=$_POST['username'];
				$password1=$_POST['password1'];
				$password2=$_POST['password2'];
				$email=$_POST['email1'];
				$email2=$_POST['email2'];
				#$name=$_POST['name'];
			} else {
				echo "Data error. Please retry.";
				die();
			}
			if($password1 != $password2)
			{
				echo "Sorry, passwords do not match. Please try again.";
				die();
			}
			if($email != $email2)
			{
				echo "Sorry, email confirmation do not match. Please try again.";
				die();
			}
			if( (strlen($name)<5) && (strlen($username)<5) && (strlen($password1)<5) && (strlen($email)<5) )
			{
				echo "Invalid data. Please try again.";
				die();
			}
			register($username, $email, $password1);
			break;
		case "update_account":
			if(!isset($_POST['password']))
			{
				echo "Current password not specified.";
				die();
			}
			$password=$_POST['password'];
			$password=str_replace(" ", "", $password);
			if(strlen($password)<6)
			{
				echo "Password should be at least 6 characters long.\n"; die();
			}
			if(!isset($_POST['pgpkey']))
			{
				echo "Data error. Please retry.  PGPKEY not set.";
				die();
			} else {
				$pgpkey=$_POST['pgpkey'];
			}
			if(isset($_POST['password1']))
			{
				$password1=$_POST['password1'];
				$password2=$_POST['password2'];
				if($password1 != $password2)
				{
					echo "Sorry, passwords do not match. Please try again.";
					die();
				}
				$password=$password1;
			}
			if(strlen($password1)>0)
			{
				if(strlen($password1)<6)
				{
					echo "Remember, your new password needs to be at least 6 characters long.";
					die();
				}
				$password=$password1;
			}
			update_account($password, $pgpkey);
			break;
		case "view_account":
			dashboard();
			break;
		case "logout":
			session_destroy();
			header("location: index.php");
			break;
		default:
			echo "Invalid sub-command.";
			die();
	}
}
?>

<?php echo "<center><font size=\"-1\">Powered by MUD4TLD v".$sw_version."</font></center>\n"; ?>
</body>
</html>

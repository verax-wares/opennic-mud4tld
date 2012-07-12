<?php
/*
    By Martin COLEMAN (C) 2012. All rights reserved.
    Released under the Basic Software License v1.0.
    See COPYING file for details.
*/
include("conf.php");
show_header();
?>

<table width="500" align="center">
<tr><td align="center"><h1>dot OZ Registration</h1></td></tr>
<tr><td>
<p>Please fill out the information below. Make sure the details are correct before clicking "Register Domain" as incorrect details may delay the registration process.</p>
</td></tr>
<tr><td align="center">
<p><br><font color="#008000">You are registering <b><?php echo $_POST['domain'].$TLD; ?></b></font><BR>To register a different domain, please <a href="check.php">check</a> it first.</p>
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
echo "<input type=\"hidden\" name=\"domain\" value=\"".$_POST['domain']."\">\n";
?>
</form>
<?php
}
?>
</td></tr>
</table>

</body>
</html>

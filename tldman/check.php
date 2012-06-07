<?php
/*
    By Martin COLEMAN (C) 2012. All rights reserved.
    Released under the Basic Software License v1.0.
    See COPYING file for details.
*/
include("conf.php");
show_header();

echo "<center><h2>".$TLD."Registration</h2>\n";

if(isset($_POST["check"]))
{
	$domain=$_POST['domain'];
	
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
}
?>

<p><BR>
<form action="check.php" method="post">
Domain name <input type="text" name="domain"><?php echo $TLD; ?>&nbsp;<input type="submit" name="check" value="Check">
</form>
</p>
</center>

</body>
</html>

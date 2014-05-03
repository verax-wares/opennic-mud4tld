<?php
/*
   MUD4TLD - Martin's User and Domain system for Top Level Domains.
   Written 2012-2014 By Martin COLEMAN.
   This software is hereby dedicated to the public domain.
   Made for the OpenNIC Project.
   http://www.mchomenet.info/mud4tld.html
*/
/* Sample index page. Do what you want with it. Public domain. */
include("conf.php");
show_header();
?>
<center><h1>.OZ</h1></center>

<table width="600" align="center">
<tr><td align="center">
<p>dot OZ is the TLD (Top Level Domain) custom made for Australians, but still available to everyone. dot OZ is targeted to those who may want a domain with the cultural association with Australia, but without the requirements of .com.au, net.au or org.au registrations.</p>
<p>Registering a dot OZ is completely free<sup>*</sup>.</p>
<p align="center">
<form action="domain.php" method="post">
Check domain <input type="text" name="domain">.OZ&nbsp;<input type="hidden" name="action" value="check_domain"><input type="submit" value="Check!">
</form>
</p>
<p>&nbsp;</p>
<p>Is someone abusing a dot OZ? Report spam or illegal material coming from a dot OZ via the <a href="abuse.php">abuse</a> form.</p>
<p><font size="-1"><sup>*</sup> Provided the registrant adheres to the <a href="charter.htm">charter</a>.</font></p>
</td></tr></table>

<?php
if($dev_link==1)
{
	echo "<p><center><font size=\"-1\">Do you run a DNS service or domain registrar? Find out how to offer ".$TLD." domains to your users <a href=\"api/index.html\">here</a></font></center></p>";
}
?>

</body>
</html>

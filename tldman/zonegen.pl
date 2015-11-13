#!/usr/bin/perl
use warnings;
use strict;
use DBI;
use File::Copy;

my $sql_db = "dnsman";
my $sql_user = "dnsman";
my $sql_pass = "PASSWORD";
my $domain_table = "domains";
my $sql_server = "172.0.0.1";

my $tld = "chan";
my $logfile = "/var/log/zonegen.log";
my $outfile = "/tmp/generator.zone";
my $oldfile = "/tmp/generator.old.zone";
my $workingfile = "/etc/bind/master/chan.zone";
my $templatefile = "/etc/bind/master/chan.template.zone";

my $dbh;
my $sth;
my $results;
my $diff;
my $difflines;
my $rndcout;
my $oldsum;
my $newsum;

my @tarr = localtime;
my $year = $tarr['5'] + 1900;
my $month = sprintf ("%2d", $tarr['4'] + 1);
$month =~ tr/ /0/;
my $day = sprintf ("%2d", $tarr['3']);
$day =~ tr/ /0/;
my $hour = $tarr['2'];
my $minute = $tarr['1'];

my $stime = "$year$month$day";
my $ltime = "$year-$month-${day}_${hour}_$minute";

#get previous serial
my $serial;
open ( LOG, "<", "$logfile" );
while (my $line = <LOG>) 
{
	if ( $line =~ /^New zone serial is: (\d{8})(\d{2})/ )
	{
		if ( $1 eq $stime )
		{
			$serial = ("$1"."$2") + 1;
		}
		else
		{
			$serial = "$stime"."00";
		}
	}
	else
	{
		$serial = "$stime"."00";
	}
}
close(LOG);

#set up the file
#copy ("$templatefile", "$outfile") or die ("unable to set up template");
open ( TEMP, "<", "$templatefile" );
open ( FH, ">", "$outfile" );

while (my $line = <TEMP>) 
{
	if ( $line =~ /^(\s+?)(\d{8})(\d{2})\s+?; Serial\s*?$/ )
	{
		$line = "${1}${serial}\t; Serial\n";
	}
	print FH "$line";
}

close(TEMP);

$dbh = DBI->connect("dbi:mysql:${sql_db}:${sql_server}", $sql_user, $sql_pass) or die ($DBI::errstr);

$sth = $dbh->prepare("SELECT * FROM $domain_table");
$sth->execute();
$results = $sth->fetchall_hashref("domain");
$dbh->disconnect;


foreach my $row_ref (sort(keys(%{$results})))
{
	my %row = %{%{$results}{"$row_ref"}};
	
	print FH "\n\n;$row{'domain'}.$tld\n";

	if ( $row{'isns'} )
	{
		#add nameserver entries
		if ( $row{'ns1'} ne "" )
		{
			if ( $row{'ns1'} =~ /$row{'domain'}.$tld$/ )
			{
				#add glue records if the NS is under their domain
				if ( $row{'ns1_ip'} ne "" )
				{
					print FH "$row{'domain'}.$tld\t\tIN\tNS\t$row{'ns1'}\n";
					print FH "$row{'ns1'}\t\tIN\tA\t$row{'ns1_ip'}\n";
				}
			}
			else
			{
				print FH "$row{'domain'}.$tld\t\tIN\tNS\t$row{'ns1'}\n";
			}
		}
		if ( $row{'ns2'} ne "" )
		{
			if ( $row{'ns2'} =~ /$row{'domain'}\.$tld$/ )
			{
				#add glue records if the NS is under their domain
				if ( $row{'ns2_ip'} ne "" )
				{
					print FH "$row{'domain'}.$tld\t\tIN\tNS\t$row{'ns2'}\n";
					print FH "$row{'ns2'}\t\tIN\tA\t$row{'ns2_ip'}\n";
				}
			}
			else
			{
				print FH "$row{'domain'}.$tld\t\tIN\tNS\t$row{'ns2'}\n";
			}
		}
	}
	else
	{
		if ( ( $row{'ns1_ip'} ne "" ) and ( $row{'ns1'} =~ /$row{'domain'}\.$tld$/ ) )
		{
			print FH "$row{'ns1'}\t\tIN\tA\t$row{'ns1_ip'}\n";
		}
		else
		{
			print FH ";Address 1 invalid\n";
		}
		if ( ( $row{'ns2_ip'} ne "" ) and ( $row{'ns2'} =~ /$row{'domain'}\.$tld$/ ) )
		{
			print FH "$row{'ns2'}\t\tIN\tA\t$row{'ns2_ip'}\n";
		}
		else
		{
			print FH ";Address 2 invalid\n";
		}
	}
	
	if ( $row{'email'} ne "" )
	{
		print FH "$row{'domain'}.$tld\t\tIN\tRP\t$row{'email'} $row{'domain'}.$tld\n";
	}

	if ( $row{'txt'} ne "" )
	{
		print FH "$row{'domain'}.$tld\t\tIN\tTXT\t\"$row{'txt'}\"\n";
	}
}

close(FH);
open ( LOG, ">>", "$logfile" );

$diff = `diff $outfile $oldfile`;
$difflines =`diff $outfile $oldfile | wc -l`;

if ( $difflines == 4 )
{
	print LOG "$ltime - Zone unchanged\n";
}
else
{
	print LOG "$ltime - Zone updated\n";
	print LOG "$diff\n";
	copy("$oldfile", "$oldfile.$ltime") or die ("Unable to copy backup");
	copy("$outfile", "$workingfile") or die ("Unable to copy working file");
	#$rndcout = `rndc reload`;
	$rndcout = "server reload successful\n";
	if ( $rndcout ne "server reload successful\n" ) 
	{
		copy("$oldfile", "$workingfile") or die ("Unable to restore backup");
		#`rndc reload`;
		close(LOG);
		die("Error in generated zone file:\n$rndcout");
	}
	copy("$outfile", "$oldfile") or die ("Unable to copy oldfile");
	print LOG "\nNew zone serial is: $serial\n";
}

close(LOG);

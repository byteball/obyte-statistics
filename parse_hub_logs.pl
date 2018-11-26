#!/usr/bin/perl
use strict;
use warnings;
use DBI;
use Date::Parse;
use Data::Dumper;

#A quick and dirty script to grep connnected waller number from hub logs and store 
#the history in table hub_stats.
#Will then be displayed on the home page.
#This script should be ran periodicaly from a cron job.

#The hub should be started as follow:
#/usr/local/bin/node /home/byteball-hub/start.js | grep connection 1>> /var/www/byteball-hub/log

#hub_stats table is as follow:
#CREATE TABLE `hub_stats` (
#`id` int(11) NOT NULL AUTO_INCREMENT,
#`UTC_datetime` timestamp NULL DEFAULT NULL,
#`connected_wallets` int(11) DEFAULT NULL,
#PRIMARY KEY (`id`)
#) ENGINE=InnoDB DEFAULT CHARSET=latin1;

	
binmode STDOUT, ":utf8";
use utf8;

use JSON;

my $json;

my $timestamp=`date +%s`;
$timestamp=$timestamp*1000;
my $connected_users=0;
my $HTML;

my $dbh;
my $sth;
my $Mysql_dbh;
my $Mysql_sth;
my $Mysql_rv;

my $dbfile="/root/.config/byteball-hub/byteball.sqlite";

$dbh = DBI->connect("dbi:SQLite:dbname=$dbfile","","") or die $DBI::errstr;

my $log=`tail /var/www/byteball-hub/log`;


my @log_array=split /\n/,$log;
my $log_array_lenght=scalar @log_array;

#search for:
#GMT+0100 (CET): 10 incoming connections,
if ($log_array[$log_array_lenght-2] =~ m/(\d+) incoming/) {
	$connected_users=$1;
} elsif ($log_array[$log_array_lenght-3] =~ m/(\d+) incoming/){#maybe in previous line ?
	$connected_users=$1;
} elsif ($log_array[$log_array_lenght-4] =~ m/(\d+) incoming/){#maybe in previous line ?
	$connected_users=$1;
}else{

}

if ($connected_users>0){
	my $peers_string="";
	$sth = $dbh->prepare("SELECT peer_host FROM peers");
	$sth->execute();
	while (my $query_result = $sth->fetchrow_hashref){
		$peers_string.=$query_result->{peer_host}.="<br>" if($query_result->{peer_host} !~/byteball\.fr/);

	}
	#print $peers_string;
	$sth->finish();
	$dbh->disconnect;
	#insertion mysql
	$Mysql_dbh=connect_Mysql_base();
	$Mysql_sth=$Mysql_dbh->prepare ("INSERT INTO hub_stats (connected_wallets, UTC_datetime ) values ('$connected_users', UTC_TIMESTAMP())");
	$Mysql_rv = $Mysql_sth->execute;

	dump_json("/var/www/html/hub_stats.json","hub_stats","UTC_datetime","connected_wallets");
	$Mysql_sth->finish();
	$Mysql_dbh->disconnect;
	
}

sub dump_json{

	my @fields=@_;
	my $filename=$fields[0];
	my $table=$fields[1];
		
	open(my $fh2, '>', $filename) or die "Could not open file '$filename' $!";
	my $buff="[\n";
	$Mysql_sth=$Mysql_dbh->prepare ("select * from $table ORDER BY id ASC");
	$Mysql_rv = $Mysql_sth->execute;
	my $row_numbers = $Mysql_sth->rows;
	my $i=1;
	while (my $query_result = $Mysql_sth->fetchrow_hashref){
		my $timestamp=convert_to_unix_timestamp($query_result->{$fields[2]});
		$timestamp=($timestamp+7200)*1000;
		if($i<$row_numbers){
			$buff.="{\"t\":".$timestamp.",\"a\":".$query_result->{$fields[3]}."},";	
		}else{
			$buff.="{\"t\":".$timestamp.",\"a\":".$query_result->{$fields[3]}."}";	
		}		
		$i++;
	}
	$buff.="]";


	print $fh2 $buff;
	close $fh2;

}

sub convert_to_unix_timestamp {
	my $time=shift;
	return str2time($time);
}


sub connect_Mysql_base {
	my $local_dbh;
	my $host="localhost";
	my $database="byteball";
	my $USER="root";
	my $PASSWORD="xxxxxxxx";
	eval {$local_dbh = DBI->connect("DBI:mysql:database=$database;host=$host",
                            $USER, $PASSWORD, {RaiseError => 1})};
	if ($@) {
		my $alerte_subject  = "Erreur connexion base from bb hub script (hub stats)";
		send_email ('hub-alerte@byteball.fr','contact@byteball.fr',$@, $alerte_subject);
		exit;
	} else {return $local_dbh;}
}

sub send_email {
	my ($mailfrom, $mailto, $message, $subject) = @_;
	my $MAILLER = '/usr/sbin/sendmail -t -oi -oem';

	my $buff="";
	$buff.= "To: ";
	$buff.= "$mailto\n";
	$buff.= "From: ";
	$buff.= "$mailfrom\n";
	$buff.= "Subject: ";
	$buff.= "$subject\n\n";
	$buff.= "$message";

	open (MAIL, "|$MAILLER") or die "Can't open $MAILLER: $!\n";
	print MAIL $buff;
	close MAIL or return undef;

	return 1;
}

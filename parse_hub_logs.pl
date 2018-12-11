#!/usr/bin/perl
use strict;
use warnings;
use DBI;
use Date::Parse;
use Data::Dumper;

#A quick and dirty script to grep connnected wallets number from hub logs and store 
#the history in table hub_stats.
#Will then be displayed on the home page.
#This script should be ran periodicaly from a cron job.

#The hub should be started as follow:
#node start.js > log

	
binmode STDOUT, ":utf8";
use utf8;

use JSON;

my $json;

my $timestamp=`date +%s`;
$timestamp=$timestamp*1000;
my $connected_users=0;
my $HTML;

my $dbh;
my $stats_dbh;
my $sth;

my $dbfile=$ENV{"HOME"}."/.config/byteball-hub/byteball.sqlite";
$dbh = DBI->connect("dbi:SQLite:dbname=$dbfile","","") or die $DBI::errstr;

my $stats_dbfile=$ENV{"HOME"}."/.config/byteball-hub/stats.sqlite";
$stats_dbh = DBI->connect("dbi:SQLite:dbname=$stats_dbfile","","") or die $DBI::errstr;

my $log=`grep connections ../byteball-hub/log | tail`;


my @log_array=split /\n/,$log;
my $log_array_length=scalar @log_array;

#search for:
#GMT+0100 (CET): 10 incoming connections,
if ($log_array[$log_array_length-2] =~ m/(\d+) incoming/) {
	$connected_users=$1;
} elsif ($log_array[$log_array_length-3] =~ m/(\d+) incoming/){#maybe in previous line ?
	$connected_users=$1;
} elsif ($log_array[$log_array_length-4] =~ m/(\d+) incoming/){#maybe in previous line ?
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
	#insertion 
	$sth=$stats_dbh->prepare ("INSERT INTO hub_stats (connected_wallets) values ('$connected_users')");
	$sth->execute;

	dump_json("www/hub_stats.json","hub_stats","UTC_datetime","connected_wallets");
	$sth->finish();	
}

sub dump_json{

	my @fields=@_;
	my $filename=$fields[0];
	my $table=$fields[1];
		
	open(my $fh2, '>', $filename) or die "Could not open file '$filename' $!";
	my $buff="[\n";
	$sth=$stats_dbh->prepare ("select * from $table ORDER BY id ASC");
	$sth->execute;
	my $row_numbers = $sth->rows;
	my $i=1;
	while (my $query_result = $sth->fetchrow_hashref){
		my $timestamp=convert_to_unix_timestamp($query_result->{$fields[2]});
		$timestamp=$timestamp*1000;
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



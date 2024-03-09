#!/usr/bin/perl
use strict;
use warnings;
use DateTime;
use Date::Parse;
use JSON;
use Data::Dumper;
use LWP::Simple;

binmode STDOUT, ":utf8";
use utf8;

#this script is to be triggered each hour from cron

use DBI;
my $dbh;
my $stats_dbh;
my $sth;
my $sth2;

my $dbfile=$ENV{"HOME"}."/.config/obyte-hub/byteball.sqlite";
$dbh = DBI->connect("dbi:SQLite:dbname=$dbfile","","") or die $DBI::errstr;
$dbh->prepare("PRAGMA busy_timeout=30000")->execute();

my $stats_dbfile="stats.sqlite";
$stats_dbh = DBI->connect("dbi:SQLite:dbname=$stats_dbfile","","") or die $DBI::errstr;
$stats_dbh->prepare("PRAGMA busy_timeout=30000")->execute();

my $total_value=0;
my $others_value=0;
my $diversity_index=0;

my @default_witnesses=("BVVJ2K7ENPZZ3VYZFWQWK7ISPCATFIW3",
"DJMMI5JYA5BWQYSXDPRZJVLW3UGL3GJS",
"FOPUBEUPBC6YLIQDLKL6EW775BMV7YOH",
"GFK3RDAPQLLNCMQEVGGD2KCPZTLSG3HN",
"H5EZTQE7ABFH27AUDTQFMZIALANK6RBG",
"I2ADHGP4HL6J37NQAD73J7E5SKFIXJOT",
"JPQKPRI5FMTQRJF4ZZMYZYDQVRD55OTC",
"OYW2XTDKSNKGSEZ27LMGNOPJSYIXHBHC",
"S7N5FE42F6ONPNDQLCF64E2MGFYKQR2I",
"UENJPVZ7HVHM6QGVGT6MWOJGGRTUTJXQ",
"JEDZYC2HMGDBIDQKG3XSTXUSHMCBK725",
"TKT4UESIKTTRALRRLWS4SENSTJX6ODCW");
	
my $HTML;
my $witnesses_stats=undef;

#get latest mci
$sth = $dbh->prepare("SELECT min(main_chain_index) AS min_index, max(main_chain_index) AS max_index FROM (SELECT main_chain_index, creation_date FROM units ORDER BY rowid DESC LIMIT 43200) WHERE creation_date >= datetime('now', '-12 hours')");
$sth->execute();
my $query_result = $sth->fetchrow_hashref;
my $start_mci=$query_result->{min_index};
my $last_mci=$query_result->{max_index};

#who are all actives witnesses?
my @array_of_serial_posting_witnesses;
my $our_witness_count=0;
$sth = $dbh->prepare("select address, count(*) as total_count from witnessing_outputs where main_chain_index >= $start_mci and main_chain_index <=$last_mci group by address order by total_count DESC");
$sth->execute();
while (my $query_result = $sth->fetchrow_hashref){
	push @array_of_serial_posting_witnesses, $query_result->{address};
	$witnesses_stats->{$query_result->{address}}->{text}="Unknown: contact us to be listed here";
	$witnesses_stats->{$query_result->{address}}->{status}="Unknown";
	$witnesses_stats->{$query_result->{address}}->{arrow}="";
}

#witnesses "market share"
my @array_of_witnesses;
my $witnesses_market;
$sth = $dbh->prepare("select count(distinct units.unit) as total_seen, unit_witnesses_1.address, max(units.main_chain_index) as max_mci, max(units.creation_date) as max_creation_date, min(units.creation_date) as min_creation_date from units left join unit_witnesses as unit_witnesses_1 on ( unit_witnesses_1.unit = units.witness_list_unit or unit_witnesses_1.unit = units.unit ) left join unit_authors on unit_authors.unit = units.unit left join unit_witnesses as unit_witnesses_2 on unit_witnesses_2.address = unit_authors.address where 1 and units.sequence='good' and units.main_chain_index >= $start_mci and unit_witnesses_2.address is NULL group by unit_witnesses_1.address order by total_seen desc");

$sth->execute();
while (my $query_result = $sth->fetchrow_hashref){
	push @array_of_witnesses, $query_result->{address};

	$witnesses_market->{$query_result->{address}}->{total_seen}=$query_result->{total_seen};
	$witnesses_market->{$query_result->{address}}->{last_mci}=$query_result->{max_mci};
	$witnesses_market->{$query_result->{address}}->{last_seen}=$query_result->{max_creation_date};

}

#fill also @array_of_witnesses with posting (but non used by any en user) witness
foreach (@array_of_serial_posting_witnesses){
	my $buff=$_;
	my $is_found=0;
	foreach (@array_of_witnesses){
		$is_found=1 if($buff eq $_);
	}
	push @array_of_witnesses,$_ if (!$is_found);
}

my $buff_html_array="";
my $i=1;
my $max_end_users_seen_units=0;
my $stats_range=0;
foreach (@array_of_witnesses)#last timestamp
{
	#how many time have we seen this witness except on its own posted units?
	$witnesses_stats->{$_}->{validations_count}=0;
	$witnesses_stats->{$_}->{validations_count}=$witnesses_market->{$_}->{total_seen} if (defined $witnesses_market->{$_}->{total_seen});  
	$max_end_users_seen_units=$witnesses_market->{$_}->{total_seen} if (defined $witnesses_market->{$_}->{total_seen} && $witnesses_market->{$_}->{total_seen}>$max_end_users_seen_units);
	$witnesses_stats->{$_}->{last_seen_mci_timestamp}="<center>> 12h</center>";
	$witnesses_stats->{$_}->{last_seen_mci_timestamp}=$witnesses_market->{$_}->{last_seen} if ($witnesses_market->{$_}->{last_seen});
	$witnesses_stats->{$_}->{last_seen_mci}="<center>> 12h</center>";
	$witnesses_stats->{$_}->{last_seen_mci}=$witnesses_market->{$_}->{last_mci} if(defined $witnesses_market->{$_}->{last_mci});
	$witnesses_stats->{$_}->{arrow}="";
	  
	$total_value+=$witnesses_stats->{$_}->{validations_count};
	  
	my $buff=$_;
	if($_ eq 'MEJGDND55XNON7UU3ZKERJIZMMXJTVCV'){
		$witnesses_stats->{$_}->{text}="byteball.fr";
		$witnesses_stats->{$_}->{status}="Independent";
		$others_value+=$witnesses_stats->{$_}->{validations_count};
	}
	elsif($_ eq '4GDZSXHEFVFMHCUCSHZVXBVF5T2LJHMU'){
		$witnesses_stats->{$_}->{text}="Rogier Eijkelhof";
		$witnesses_stats->{$_}->{status}="Independent";
		$others_value+=$witnesses_stats->{$_}->{validations_count};
	}
	elsif($_ eq 'FAB6TH7IRAVHDLK2AAWY5YBE6CEBUACF'){
		$witnesses_stats->{$_}->{text}="Fabien Marino";
		$witnesses_stats->{$_}->{status}="Independent";
		$others_value+=$witnesses_stats->{$_}->{validations_count};
	}
	elsif($_ eq '7ULGTPFB72TOYA67YNGMX2Y445FSTL7O'){
		$witnesses_stats->{$_}->{text}="Portabella (slack user)";
		$witnesses_stats->{$_}->{status}="Independent";
		$others_value+=$witnesses_stats->{$_}->{validations_count};
	}
	elsif($_ eq 'Z65GI4TTOZ6KOXDX7LQN4AVOFI6DLSJG'){
		$witnesses_stats->{$_}->{text}="rubbish0815 (slack user)";
		$witnesses_stats->{$_}->{status}="Independent";
		$others_value+=$witnesses_stats->{$_}->{validations_count};
	}
	elsif($_ eq 'D3FLI2E6SQS437P57DKBYIBL3EZTZXCQ'){
		$witnesses_stats->{$_}->{text}="Piiper (slack user)";
		$witnesses_stats->{$_}->{status}="Independent";
		$others_value+=$witnesses_stats->{$_}->{validations_count};
	}
	elsif($_ eq 'RSOWAONJBM5WTB7UKWHQOGDB4NSOKKOO'){
		$witnesses_stats->{$_}->{text}="yamaoka (slack user)";
		$witnesses_stats->{$_}->{status}="Independent";
		$others_value+=$witnesses_stats->{$_}->{validations_count};
	}
	elsif($_ eq '4FIZC3KZ3ZQSSVOKFEUHKCTQWAWD6YMF'){
		$witnesses_stats->{$_}->{text}="Raivo Malter";
		$witnesses_stats->{$_}->{status}="Independent";
		$others_value+=$witnesses_stats->{$_}->{validations_count};
	}
	elsif($_ eq 'IMMP5FWQXY6IZ53OIYQ46PHSI5T3MAYQ'){
		$witnesses_stats->{$_}->{text}="Demelza Hays";
		$witnesses_stats->{$_}->{status}="Independent";
		$others_value+=$witnesses_stats->{$_}->{validations_count};
	}
	elsif($_ eq '25XDFVFRP7BZ2SNSESFKUTF52W42JCSL'){
		$witnesses_stats->{$_}->{text}="Brad Morrison";
		$witnesses_stats->{$_}->{status}="Independent";
		$others_value+=$witnesses_stats->{$_}->{validations_count};
	}
	elsif($_ eq 'QR542JXX7VJ5UJOZDKHTJCXAYWOATID2'){
		$witnesses_stats->{$_}->{text}="Bittrex";
		$witnesses_stats->{$_}->{status}="Exchange";
		$others_value+=$witnesses_stats->{$_}->{validations_count};
	}
	elsif($_ eq '2TO6NYBGX3NF5QS24MQLFR7KXYAMCIE5'){
		$witnesses_stats->{$_}->{text}="Bosch Connectory Stuttgart";
		$witnesses_stats->{$_}->{status}="Independent";
		$others_value+=$witnesses_stats->{$_}->{validations_count};
	}
	elsif($_ eq 'DXYWHSZ72ZDNDZ7WYZXKWBBH425C6WZN'){
		$witnesses_stats->{$_}->{text}="Altos Engineering (formerly Bind Creative)";
		$witnesses_stats->{$_}->{status}="Independent";
		$others_value+=$witnesses_stats->{$_}->{validations_count};
	}
	elsif($_ eq 'APABTE2IBKOIHLS2UNK6SAR4T5WRGH2J'){
		$witnesses_stats->{$_}->{text}="PolloPollo";
		$witnesses_stats->{$_}->{status}="Independent";
		$others_value+=$witnesses_stats->{$_}->{validations_count};
	}
	elsif($_ eq 'UE25S4GRWZOLNXZKY4VWFHNJZWUSYCQC'){
		$witnesses_stats->{$_}->{text}="IFF at University of Nicosia";
		$witnesses_stats->{$_}->{status}="Independent";
		$others_value+=$witnesses_stats->{$_}->{validations_count};
	}
	elsif($_ eq 'JMFXY26FN76GWJJG7N36UI2LNONOGZJV'){
		$witnesses_stats->{$_}->{text}="CryptoShare Studio";
		$witnesses_stats->{$_}->{status}="Independent";
		$others_value+=$witnesses_stats->{$_}->{validations_count};
	}
	elsif($_ eq 'FL3LIHRXYE6PS7AADJLDOYZKDO2UVVNS'){
		$witnesses_stats->{$_}->{text}="Travin Keith";
		$witnesses_stats->{$_}->{status}="Independent";
		$others_value+=$witnesses_stats->{$_}->{validations_count};
	}
	elsif ( grep( /^$buff$/, @default_witnesses ) ){
		$witnesses_stats->{$_}->{text}="Tony Churyumoff";
		$witnesses_stats->{$_}->{status}="Founder";
	}
	else{
		$witnesses_stats->{$_}->{text}="Unknown user";
		$witnesses_stats->{$_}->{status}="Independent";
		$others_value+=$witnesses_stats->{$_}->{validations_count};
	}
	$stats_range=$max_end_users_seen_units;
	my $percentage=calculate_percent($witnesses_stats->{$_}->{validations_count});
	$buff_html_array.="<tr><th><font color=\"green\" valign=\"top\">".$witnesses_stats->{$_}->{arrow}."</font></th><th>#".$i."</th><td><a class=\"address\" href=\"https://explorer.obyte.org/#".$_."\" target=\"_blank\">".$_."</a></td><td><center>".$witnesses_stats->{$_}->{validations_count}."</center></td><td>".$percentage."</td><td><center>".$witnesses_stats->{$_}->{last_seen_mci}."<center></td><td>".$witnesses_stats->{$_}->{last_seen_mci_timestamp}."</td><td>".$witnesses_stats->{$_}->{status}."</td><td>".$witnesses_stats->{$_}->{text}."</td></tr>\n";
	
	if(0){
		print "$_ nbre validation : $witnesses_stats->{$_}->{validations_count}\n";
		print "last seen mci timestamp $witnesses_stats->{$_}->{last_seen_mci_timestamp}\n";
		print "last seen mci: $witnesses_stats->{$_}->{last_seen_mci}\n";
	}
	$i++;
	#insert if needed in table seen_witnesses
	$sth=$stats_dbh->prepare("SELECT count(*) as total_count FROM seen_witnesses where address='$_'");
	$sth->execute();
	my $query_result2 = $sth->fetchrow_hashref;
	if($query_result2->{total_count}==0){
		$sth=$stats_dbh->prepare("INSERT INTO seen_witnesses (address) values('$_')");
		$sth->execute();
	}
	 
}#end of foreach arrayofwitnesses
	

	

$buff_html_array.="";


my $update=DateTime->now();
my $total_active_witnesses=$i-1;

$HTML->{Array}=$buff_html_array;
$HTML->{update}=$update;
$HTML->{total_active}=$total_active_witnesses;
#open the stat template and output the witnesses.php public php script
my $template='witnesses_template.php';
my $new_stats=get_content($template,$HTML);
my $filename = 'www/witnesses.php';
open(my $fh, '>', $filename) or die "Could not open file '$filename' $!";
print $fh $new_stats;
close $fh;


#pass 2: top 100
my $total_add_with_balance=0;
$sth = $stats_dbh->prepare("select count(*) as total from richlists where snapshot = date('now')");
$sth->execute();
$query_result = $sth->fetchrow_hashref;
if ($query_result->{total}==0) {
	$stats_dbh->prepare('BEGIN')->execute();
	$stats_dbh->prepare('DELETE FROM richlist')->execute();

	my $circulation_supply=1000000000000000;
	my @assets = (
		'base', # bytes
		# Bonded Stablecoins - USD tokens
		'IpO7JJ0r7+Kq6nqKSr8Auwj8t+66bt/76rFiGzamrSg=',
		'eCpmov+r6LOVNj8KD0EWTyfKPrqsG3i2GgxV4P+zE6A=',
		'0IwAk71D5xFP0vTzwamKBwzad3I1ZUjZ1gdeB5OnfOg=',
		'4t1FplfMcmIFg9VrTj0CiwS6/OfWHZ8wZnAr6BW2rvY=',
		# Bonded Stablecoins - BTC tokens
		'H3yTRHYmq7lSE+0zLMPeMUV0OocdWLgahNueoefb8oo=',
		'viWGuQQnKBkXbuBFryfT3oJd+KHRWMtCDfy7ZEJguaA=',
		'/hYmnT8qDvPhKVQCbMLibB47qMu1DnKf0BKzYWe836c=',
		'/HOZkibdHHxUgk9cBgaVMMqqwqaMRNTD4etrapVngUY=',
		# Bonded Stablecoins - ETH tokens
		'hvoh6+rFuaiXbDijCf7z8ttJBwzuzMlqt2CkFCR1lW0=',
		'yZXvEHHPSEGWFLNuNY8D7U6ukyytHOFpPw/ntpImK9c=',
		'TKTmSpLhY2CF8s93OSVJvp/0e9pxrIeGrUm4c1svOLQ=',
		'P3a+J2ALWSIAMGUvA6MN3zRsQrVS318E/i9kEPuIVDM=',
		# Bonded Stablecoins - GBYTE tokens
		'kJ/2JkNR0i8quYMA7tWCO/fcOVEyPJMrFg9uAmz2Kuw=',
		'2rionwusffAB8EkuubZY4XV2F4ZRfR0wlLC080kJU1M=',
		'yVP89X/wYcvPm5zzhffLnz7Rt+4EdrfDJFJaQc7dSc4=',
		'xgmjTZN/nQgsilrsWV3CrFHgA33hYudNyzaer/+yaoA=',
		# Bonded Stablecoins - XAU tokens
		'whhEzaermRqpF4q+EmdCvazqw6WchWeHQhBRSGFgUDY=',
		'quDyKoFEB5Ww27IC9XKd9Pixvw7quvyfiRYVyhZZsiU=',
		'n4wyr7LfGSdwfGQpPLe0Hc7Q6VTNIMdX7XxvBT5+L9Y=',
		'04p03vbBhbJaFynRssYgJzLQyx3gvZCHyNsnfuwTV4Q=',
		# Counterstake Bridge imported tokens
		'RF/ysZ/ZY4leyc3huUq1yFc0xTS0GdeFQu8RmXas4ys=',
		'S/oCESzEO8G2hvQuI6HsyPr0foLfKwzs+GU73nO9H40=',
		'AHVV8Um6AwHY9/nsX/YMZkWSBptWdn4g9aYVhNLcUWs=',
		'Rqd8mi8+pOnlieU13G7RFFBJnY71D2/opd3ssaEcMZU=',
		'vApNsebTEPb3QDNNfyLsDB/iI5st9koMpAqvADzTw5A=',
		'zN8X/+o3iXuhmfwNMVcI+pKRJmzLvFbrJ3yvjCHbRBE='
	);
	foreach ( @assets ) {
		my $richlist_id=0;
		my $archive_limit=26;
		my $asset_sql = $_ eq 'base' ? 'NULL' : "'$_'";
		my $limit_sql = $_ eq 'base' ? '' : " LIMIT $archive_limit";
		$sth = $dbh->prepare("SELECT address, COUNT(*) AS utxos, SUM(amount) AS amount FROM outputs WHERE asset IS $asset_sql AND is_spent = 0 GROUP BY asset, address ORDER BY amount DESC$limit_sql");
		$sth->execute();
		while (my $query_result = $sth->fetchrow_hashref){
			$richlist_id++;
			if ($_ eq 'base') {
				#problematics addresses
				next if $query_result->{address} eq 'mtdc7zuhmdu3ph2rrmhcmm4plc2xkhtj';#yes, lowercase
				next if $query_result->{address} eq 'GVVHBOGQFAZJW54m37LPSHZOYWZ2Z47T';
				next if $query_result->{address} eq 'ZQ4NJ2YZGUGIPU2F2DOAIIH67MBY4AHG';
				#not in circulation addresses
				$circulation_supply -= $query_result->{address} eq 'MZ4GUQC7WUKZKKLGAS3H3FSDKLHI7HFO' ? $query_result->{amount} : 0;
				$circulation_supply -= $query_result->{address} eq 'BZUAVP5O4ND6N3PVEUZJOATXFPIKHPDC' ? $query_result->{amount} : 0;
				$circulation_supply -= $query_result->{address} eq 'TUOMEGAZPYLZQBJKLEM2BGKYR2Q5SEYS' ? $query_result->{amount} : 0;
				$circulation_supply -= $query_result->{address} eq 'FCXZXQR353XI4FIPQL6U4G2EQJL4CCU2' ? $query_result->{amount} : 0;
				$total_add_with_balance = $richlist_id;
				my $sth2=$stats_dbh->prepare ("INSERT INTO richlist (id, amount, address) VALUES($richlist_id, '$query_result->{amount}','$query_result->{address}')");
				$sth2->execute;
			}
			#new richlists
			if ($richlist_id <= $archive_limit) {
				my $sth2=$stats_dbh->prepare ("INSERT INTO richlists (id, amount, address, utxos, asset, snapshot) VALUES($richlist_id, '$query_result->{amount}','$query_result->{address}','$query_result->{utxos}',$asset_sql, date('now'))");
				$sth2->execute;
			}
		}
	}
	$stats_dbh->prepare('COMMIT')->execute();

	if ($total_add_with_balance) {
		my $filename_supply = 'www/coin_info.json';
		open(my $fh_supply, '>', $filename_supply) or die "Could not open file '$filename_supply' $!";
		my $json_supply = encode_json {
			circulating_supply => ($circulation_supply/1000000000),
			total_supply => 1000000,
			max_supply => 1000000
		};
		print $fh_supply $json_supply;
		close $fh_supply;

		my $filename_supply_txt = 'www/circulating_supply.txt';
		open(my $fh_supply_txt, '>', $filename_supply_txt) or die "Could not open file '$filename_supply_txt' $!";
		my $txt_supply = $circulation_supply/1000000000;
		print $fh_supply_txt $txt_supply;
		close $fh_supply_txt;
	}
}


#pass 3: trafic
#All trafic within the last 12 hours
$sth = $dbh->prepare("select count(*) as total from units where units.main_chain_index >= $start_mci");
$sth->execute();
$query_result = $sth->fetchrow_hashref;
my $total_units=$query_result->{total};

#all stables units
$sth = $dbh->prepare("select count(*) as total from units where units.main_chain_index >= $start_mci AND is_stable='1'");
$sth->execute();
$query_result = $sth->fetchrow_hashref;
my $total_stable_units=$query_result->{total};

my $percent=10;#little alarm system to Tonych
if($total_stable_units < $total_units*(1-$percent/100)){
	my $alerte_subject  = "Alert! Too many non stable units in the Obyte network!";
	my $body="My current alert trigger is non stable vs total units less than ".$percent." %.\n\nHowever, over the last 12 hours I see:\nTotal units posted: ".$total_units."\nTotal stables units: ".$total_stable_units."\n";
	send_email ('noreply@byteball.fr','byteball@byteball.org',$body, $alerte_subject);

}
	
#all units out of main chain
$sth = $dbh->prepare("select count(*) as total from units where units.main_chain_index >= $start_mci AND is_stable='1' AND is_on_main_chain='0'");
$sth->execute();
$query_result = $sth->fetchrow_hashref;
my $total_stable_units_sidechain=$query_result->{total};

#all units but witnesses units
$sth = $dbh->prepare("select units.* from units left join unit_authors on unit_authors.unit = units.unit left join unit_witnesses on unit_witnesses.address = unit_authors.address where units.main_chain_index >= $start_mci and unit_witnesses.address is NULL group by units.unit");
$sth->execute();
$query_result = $sth->fetchrow_hashref;
my $total_sidechain_units_witnesses_excluded=0;
my $total_units_witnesses_excluded=0;
my $total_payload=0;
my $single_sig_count=0;
my $multisig_count=0;
my $smart_contract_count=0;

my $latest_definition_cash="";

while (my $query_result = $sth->fetchrow_hashref){
	$total_payload+=$query_result->{payload_commission};
	$total_units_witnesses_excluded++;
	$total_sidechain_units_witnesses_excluded+=1 if($query_result->{is_on_main_chain}==0);

	$sth2=$dbh->prepare("SELECT * FROM unit_authors where unit='$query_result->{unit}'");
	$sth2->execute();
	while (my $query_result2 = $sth2->fetchrow_hashref){
		if ($query_result2->{definition_chash}){
			$latest_definition_cash=$query_result2->{definition_chash};
		} else {
			$latest_definition_cash=$query_result2->{address};
		}
		my $sth4=$dbh->prepare("SELECT definition FROM definitions where definition_chash='$latest_definition_cash'");
		$sth4->execute();
		my $query_result4=$sth4->fetchrow_hashref;
		if ($query_result4) {
			my $buff=$query_result4->{definition};
			my @eclated_result=split/\,/,$buff;
			if($eclated_result[0] =~ /^\[\"sig\"$/) {
				$single_sig_count++;
			}elsif($eclated_result[0] =~ /^\[\"r of set\"$/){
				$multisig_count++;
			}else{
				$smart_contract_count++;
			}
		}
	}
}
	
my $ratio=sprintf("%.2f",($total_stable_units/$total_units)*100);
my $total_payload_for_db=$total_payload;
$total_payload=set_coma_separators($total_payload);

$sth = $dbh->prepare("select count(*) as total from aa_responses where mci >= $start_mci");
$sth->execute();
$query_result = $sth->fetchrow_hashref;
$total_units_witnesses_excluded-=$query_result->{total};
my $aa_count=$query_result->{total};

#how many hubs and wallets
$sth=$stats_dbh->prepare ("select count(*) as total_count from geomap where type<>'full_wallet'");
$sth->execute;
$query_result = $sth->fetchrow_hashref();
my $total_hubs=$query_result->{total_count};

$sth=$stats_dbh->prepare ("select count(*) as total_count from geomap where type='full_wallet'");
$sth->execute;
$query_result = $sth->fetchrow_hashref();
my $total_full_wallets=$query_result->{total_count};

my $price_content = get 'https://api.coingecko.com/api/v3/simple/price?ids=byteball&vs_currencies=usd,btc';
my $price_json = decode_json $price_content;
my $dollar_rate = $price_json->{byteball}->{usd};

#all that into bb_stats table...
$sth=$stats_dbh->prepare ("INSERT INTO bb_stats ( total_active_witnesses, multisigned_units, smart_contract_units, total_units, total_stable_units, total_units_witnesses_excluded, stable_ratio, total_payload, total_add_with_balance, total_stable_units_sidechain, total_sidechain_units_WE, total_full_wallets, total_hubs, aa_units, dollar_rate) values 
('$total_active_witnesses','$multisig_count','$smart_contract_count','$total_units','$total_stable_units','$total_units_witnesses_excluded','$ratio','$total_payload_for_db','$total_add_with_balance','$total_stable_units_sidechain','$total_sidechain_units_witnesses_excluded','$total_full_wallets','$total_hubs','$aa_count', '$dollar_rate')");
$sth->execute;

#json dump
dump_json("www/bb_stats.json","bb_stats","UTC_datetime","total_units","total_stable_units","stable_ratio",
"total_units_witnesses_excluded","multisigned_units","smart_contract_units","total_payload","aa_units","dollar_rate");
		

$sth->finish() if defined $sth;
$sth2->finish() if defined $sth2;
$dbh->disconnect;
$stats_dbh->disconnect;

		
sub dump_json{

	my @fields=@_;
	my $filename=$fields[0];
	my $table=$fields[1];
		
	open(my $fh2, '>', $filename) or die "Could not open file '$filename' $!";
	my $buff="";
	$sth=$stats_dbh->prepare ("select * from $table ORDER BY id DESC LIMIT 5000");
	$sth->execute;
	my $i=0;
	while (my $query_result = $sth->fetchrow_hashref){
		my $timestamp=convert_to_unix_timestamp($query_result->{$fields[2]});
		$timestamp=($timestamp+7200)*1000;
		my $point = encode_json {
			t => $timestamp,
			a => $query_result->{$fields[3]},
			b => $query_result->{$fields[4]},
			c => $query_result->{$fields[5]},
			d => $query_result->{$fields[6]},
			e => $query_result->{$fields[7]},
			f => $query_result->{$fields[8]},
			g => $query_result->{$fields[9]},
			h => $query_result->{$fields[10]},
			i => $query_result->{$fields[11]}
		};
		if ($i>0){
			$buff=",".$buff;
		}
		$buff=$point.$buff;
		$i++;
	}
	$buff="[".$buff."]";

	print $fh2 $buff;
	close $fh2;

}

sub convert_to_unix_timestamp {
	my $time=shift;
	return str2time($time);
}

sub Indols{
	my $amount=shift;
	my $rate=shift;
	return(set_coma_separators(sprintf "%.2f",($amount/1000000000)*$rate));
	
}

sub set_coma_separators {
	my $input_string=shift;
	$input_string =~ s/(\d)(?=(\d{3})+(\D|$))/$1\,/g;
	return($input_string);
}

sub get_content {

	my $content;

	my ($template,$HTML) = @_;
	open (FILE, "<$template") or die "Couldn't open $template: $!\n";
	while (<FILE>) {
		s/\{\{(.*?)\}\}/$HTML->{$1}/g;
		$content .= $_;
	} 
	close FILE;

	return $content;
}

sub calculate_percent {
	my $value=shift;

	my $output=($value/$stats_range)*100;
	
	return sprintf("%.2f",$output);
	
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

-- tables to be created on a sql server

CREATE TABLE `bb_stats` (
  `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  `witness_diversity_index` varchar(10) DEFAULT NULL,
  `dollar_rate` float(10,0) DEFAULT NULL,
  `total_active_witnesses` mediumint(9) DEFAULT NULL,
  `multisigned_units` int(11) DEFAULT NULL,
  `smart_contract_units` int(11) DEFAULT NULL,
  `total_units` int(11) DEFAULT NULL,
  `total_stable_units` int(11) DEFAULT NULL,
  `total_stable_units_sidechain` int(11) DEFAULT NULL,
  `total_units_witnesses_excluded` int(11) DEFAULT NULL,
  `total_sidechain_units_WE` int(11) DEFAULT NULL,
  `stable_ratio` float DEFAULT NULL,
  `total_payload` int(11) DEFAULT NULL,
  `total_add_with_balance` int(11) DEFAULT NULL,
  `total_full_wallets` int(11) DEFAULT NULL,
  `total_hubs` int(11) DEFAULT NULL,
  `registered_users` int(11) DEFAULT '0',
  `non_US` int(11) DEFAULT '0',
  `accredited_investors` int(11) DEFAULT NULL,
  `UTC_datetime` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE `seen_witnesses` (
  `address` varchar(40) NOT NULL PRIMARY KEY,
  `first_seen` DATE NOT NULL DEFAULT (date('now'))
);


CREATE TABLE `richlist` (
  `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  `address` varchar(32) NOT NULL UNIQUE,
  `amount` bigint(20) NOT NULL
);



CREATE TABLE `daily_stats` (
  `day` date NOT NULL PRIMARY KEY,
  `units_w` int NOT NULL,
  `units_nw` int NOT NULL,
  `payload_nw` int NOT NULL,
  `payload_w` int NOT NULL,
  `sidechain_units` int NOT NULL,
  `addresses` int NOT NULL,
  `new_addresses` int NOT NULL
);


CREATE TABLE `mci_timestamps` (
  `main_chain_index` INT NOT NULL PRIMARY KEY,
  `date` datetime NOT NULL
);

CREATE TABLE `hub_stats` (
  `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  `UTC_datetime` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `connected_wallets` INT DEFAULT NULL
);

CREATE TABLE `geomap` (
  `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  `type` VARCHAR(15) CHECK (`type` IN('hub','relay','full_wallet')) NOT NULL,
  `IP` varchar(15) NOT NULL,
  `longit` float NOT NULL,
  `latt` float NOT NULL,
  `description` varchar(50) NOT NULL,
  `is_ok` tinyint(1) NOT NULL DEFAULT '1',
  `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE `bb_stats` ADD COLUMN `aa_units` int(11) DEFAULT 0;
ALTER TABLE `geomap` ADD COLUMN `country_code` varchar(2) DEFAULT NULL;

CREATE TABLE `richlists` (
  `id` INTEGER NOT NULL,
  `address` varchar(32) NOT NULL,
  `asset` varchar(44) DEFAULT NULL,
  `amount` bigint(20) NOT NULL,
  `utxos` int(11) DEFAULT NULL,
  `snapshot` varchar(10) DEFAULT NULL,
  UNIQUE  (address, asset, snapshot)
);
CREATE INDEX richlistsIndexBySnapshot ON richlists(snapshot);

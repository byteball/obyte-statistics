/*jslint node: true */
'use strict';
const fs = require('fs');
const async = require('async');
const network = require('ocore/network.js');

let peer_witnesses = {};

async.parallel([
	(cb) => { getWitnesses('wss://obyte.org/bb', cb) },
	(cb) => { getWitnesses('wss://hub.connectory.io/bb', cb) },
	(cb) => { getWitnesses('wss://hub.obytechina.org/bb', cb) },
	(cb) => { getWitnesses('wss://relay.bytes.cash/bb', cb) }
], () => {
	console.log(peer_witnesses);
	process.exit();
});

function getWitnesses(peer_url, cb) {
	network.findOutboundPeerOrConnect(peer_url, (err, ws) => {
		if (err) return cb();
		console.log('------- findOutboundPeerOrConnect', err, ws.peer);
		// function parameters: websocket, command, params, bReroutable, callback
		network.sendRequest(ws, 'get_witnesses', null, false, (ws, req, witnesses) => {
			console.log('------- Requested: get_witnesses', ws.peer);
			peer_witnesses[ws.peer] = witnesses;
			return cb();
		});
	});
}

Array.prototype.forEachAsync = async function(fn) {
	for (let t of this) { await fn(t) }
}

//witnessTable();

async function witnessTable() {
	let witness_matrix = {};
	let witness_list_units = {};

	// get current mci
	let units = await db.query("SELECT max(main_chain_index) AS max_index FROM units;", []);
	if (!units.length) return console.error('units - 0 results');

	// witnesses who have been selected to witness past 1000 mci units
	let witnessing_outputs = await db.query("SELECT main_chain_index AS mci, address \
			FROM witnessing_outputs \
			WHERE main_chain_index > ? AND main_chain_index <= ? \
			ORDER BY witnessing_outputs.rowid DESC;", [units[0].max_index-10000, units[0].max_index]);

	if (!witnessing_outputs.length) return console.error('witnessing_outputs - 0 results');

	await witnessing_outputs.forEachAsync(async (witnessing_output) => {
		// only do it for the latest output
		if (witness_matrix[witnessing_output.address]) return;
		// get the witness lists of the last transaction of those witnesses from above query
		let witness_list = await db.query("SELECT units.witness_list_unit \
				FROM units \
				JOIN unit_authors ON units.unit = unit_authors.unit \
				WHERE units.main_chain_index = ? AND unit_authors.address = ? \
				LIMIT 1;", [witnessing_output.mci, witnessing_output.address]);

		if (!witness_list.length) {
			// console.error('witness_list - 0 results', witnessing_output.mci, witnessing_output.address);
			return;
		}
		console.log('witness_list_unit', witnessing_output.address, witness_list[0].witness_list_unit);

		let unit_witnesses = [];
		if (typeof witness_list_units[witness_list[0].witness_list_unit] == 'undefined') {
			// get the witnesses of these witness lists
			unit_witnesses = await db.query("SELECT address \
					FROM unit_witnesses \
					WHERE unit_witnesses.unit = ?;", [witness_list[0].witness_list_unit]);

			witness_list_units[witness_list[0].witness_list_unit] = unit_witnesses;
			if (!unit_witnesses.length) return console.error('unit_witnesses - 0 results');
		}
		else {
			unit_witnesses = witness_list_units[witness_list[0].witness_list_unit];
		}

		// convert sqlite result to array
		witness_matrix[witnessing_output.address] = unit_witnesses.map( (unit_witness) => {
			return unit_witness.address;
		});
	});

	let witness_table = {};
	let index_length = 3;
	// build empty table
	Object.keys(witness_matrix).sort().forEach( (key) => {
		witness_matrix[key].sort().forEach( (witness) => {
			Object.keys(witness_matrix).sort().forEach( (key2) => {
				let key_name = key.substr(0, index_length) +'...';
				let witness_name = witness.substr(0, index_length) +'...';
				let key_name2 = key2.substr(0, index_length) +'...';
				// init assoc arrays
				if (typeof witness_table[key_name] == 'undefined') {
					witness_table[key_name] = {};
				}
				if (typeof witness_table[key_name2] == 'undefined') {
					witness_table[key_name2] = {};
				}
				if (typeof witness_table[witness_name] == 'undefined') {
					witness_table[witness_name] = {};
				}
				// set empty cells
				witness_table[key_name][key_name] = null;
				witness_table[key_name2][key_name] = null;
				witness_table[witness_name][key_name] = null;
				witness_table[witness_name][key_name2] = null;
			});
		});
	});
	// fill empty table cells
	Object.keys(witness_matrix).sort().forEach( (key) => {
		witness_matrix[key].sort().forEach( (witness) => {
			let key_name = key.substr(0, index_length) +'...';
			let witness_name = witness.substr(0, index_length) +'...';
			// just in case
			if (typeof witness_table[witness_name] == 'undefined') {
				witness_table[witness_name] = {};
			}
			// themselves on their witness list
			if (key === witness) {
				witness_table[witness_name][key_name] = true;
			}
			// mark on those who have picked as witness, not who they picked
			else if (witness_matrix[key] !== 'undefined') {
				witness_table[witness_name][key_name] = 'true';
			}
		});
	});

	// if (Number(process.version.match(/^v(\d+)/)[1]) < 10) {
	// 	console.log(JSON.stringify(witness_matrix));
	// 	console.log(witness_table);
	// 	console.error('No table, JSON dump of both arrays because lower than Node.js v10');
	// }
	// else {
	// 	// draws nice table in console on Node.js v10 and above
	// 	console.table(witness_table);
	// }
	let json_output = {last_updated: new Date().toUTCString(), table: witness_table};
	fs.writeFileSync(__dirname +'/www/peer_witnesses.json', JSON.stringify(json_output));
	process.exit();
}
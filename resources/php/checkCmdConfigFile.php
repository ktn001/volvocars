<?php

$cmdsFile = realpath(__DIR__ . '/../../core/config/cmds.json');
print "configFile: " . $cmdsFile . "\n";

$cmds = [];
foreach(json_decode(file_get_contents($cmdsFile),true) as $cmd){
	$cmds[$cmd['logicalId']] = $cmd;
}

print "check des 'configuration[dependTo]'...\n";
foreach ($cmds as $cmd){
	if(isset($cmd['configuration']['dependencies'])){
		$logicalIds = $cmd['configuration']['dependencies'];
		if (!is_array($logicalIds)) {
			$logicalIds = array($logicalIds);
		}
		foreach ($logicalIds as $logicalId) {
			if (!isset($cmds[$logicalId])){
				print "ERREUR: commande $logicalId introuvable (depend de " . $cmd['logicalId'] . ")\n";
				continue;
			}
			if (!isset($cmds[$logicalId]['configuration'])) {
				print "ERREUR: Pas de configuration pour $locicalId  (depend de " . $cmd['logicalId'] . ")\n";
				continue;
			}
			if (!isset($cmds[$logicalId]['configuration']['dependTo'])) {
				print "ERREUR: Pas de dependTo pour $logicalId  (depend de " . $cmd['logicalId'] . ")\n";
				continue;
			}
			$dependTo = $cmds[$logicalId]['configuration']['dependTo'];
			if (!is_array($dependTo)) {
				$dependTo = array($dependTo);
			}
			if (!in_array($cmd['logicalId'],$dependTo)){
				print "ERREUR: Pas de dependTo " . $cmd['logicalId'] . " pour la commande $logicalId \n";
				continue;
			}
		}
	}
}

print "check des 'configuration[dependencies]'...\n";
foreach ($cmds as $cmd){
	if(isset($cmd['configuration']['dependTo'])){
		$logicalIds = $cmd['configuration']['dependTo'];
		if (!is_array($logicalIds)) {
			$logicalIds = array($logicalIds);
		}
		foreach ($logicalIds as $logicalId) {
			if (!isset($cmds[$logicalId])){
				print "ERREUR: commande $logicalId introuvable (dependance de " . $cmd['logicalId'] . ")\n";
				continue;
			}
			if (!isset($cmds[$logicalId]['configuration'])) {
				print "ERREUR: Pas de configuration pour $locicalId  (dependance de " . $cmd['logicalId'] . ")\n";
				continue;
			}
			if (!isset($cmds[$logicalId]['configuration']['dependencies'])) {
				print "ERREUR: Pas de dependencies pour $logicalId  (dependance de " . $cmd['logicalId'] . ")\n";
				continue;
			}
			$dependencies = $cmds[$logicalId]['configuration']['dependencies'];
			if (!is_array($dependencies)) {
				$dependencies = array($dependencies);
			}
			if (!in_array($cmd['logicalId'],$dependencies)){
				print "ERREUR: Pas de dependencies " . $cmd['logicalId'] . " pour la commande $logicalId \n";
				continue;
			}
		}
	}
}

#!/usr/bin/php
<?php

/*

snmPort version 1.5

Test� sur :
-- Version du serveur MySQL : 5.0.45
-- Version de PHP : 5.1.6

Cr�ation  		: S�bastien LEVREL bas� sur les recherches de GETNODE par Yves PICQ
Date cr�ation	: 04/07/2008
Derni�re modifications : 03/10/2008

�volutions possibles : 
-Ajout nom VLAN ?
Liste de nom des VLAN
snmpwalk -v 2c -c publicsap 10.176.254.15 .1.3.6.1.4.1.2272.1.3.2.1.2

-Ajout du type d'�quipement ?
Liste de type (Baystack470, etc.) d'�quipement de le pile
snmpwalk -v 2c -c publicsap 10.176.254.15 enterprises.45.1.6.3.3.1.1.6.8

-Affichage du VLAN par d�faut ?
//ancien $OID_VLAN 	= '.1.3.6.1.4.1.2272.1.3.3.1.7';		// OID contenant la liste des VLAN d�faut par PORT
// ancien $Numero_VLAN = $Listesnmp_VLAN[$Recherche_VLANPORT];

-Mettre decouverte dans les CGI (compil�) ?

-Cr�er une fonction CRC error

-Cr�er une fonction temp�te de diffusion

*/

require('include/config.php');



function Nettoyage_MACHINE($Supr_CONDITION){
	
$Max_ABSENCE = '90';		//	Nombre de jours d'absence maximum avant effacement de la machine
$Nettoyage_MACHINE = "DELETE FROM decouverte WHERE DATEDIFF( NOW() , `DEC_DATE` ) > '".$Max_ABSENCE."' ".$Supr_CONDITION.";";
$Nettoyage_BDD = mysql_query($Nettoyage_MACHINE) or die('Erreur SQL ! '.$Nettoyage_MACHINE.'<br>'.mysql_error());
}	//	FIN Fonction Nettoyage_MACHINE

function MajPORT($MajPORT_Cible,$MajPORT_Communaute){
$OID_PORTPILE = 'IF-MIB::ifIndex'; 			// OID contenant la liste des PORT de la PILE
$OID_EQUIP 	= '.1.3.6.1.2.1.2.2.1.2';			// OID contenant la liste des equipements avec NOM, UNIT et PORT
$OID_VLAN 	= '.1.3.6.1.4.1.2272.1.3.3.1.3';		// OID contenant la liste des VLAN par PORTPILE
$OID_TAG 	= '.1.3.6.1.4.1.2272.1.3.3.1.4'; 		// OID contenant la liste des TAG (VLAN) par PORTPILE
$OID_NEG 	= '.1.3.6.1.4.1.2272.1.4.10.1.1.11';	// OID contenant la liste NEGOCIATIONS par PORTPILE
$OID_DUPLEX 	= '.1.3.6.1.4.1.2272.1.4.10.1.1.12'; 	// OID contenant la liste DUPLEX par PORTPILE
$OID_DEBIT 	= '.1.3.6.1.4.1.2272.1.4.10.1.1.15'; 	// OID contenant la liste des DEBITS par PORTPILE

$Erreur_PORT = "ERREUR : La requ�te PORT snmp n'a pas aboutie pour ".$MajPORT_Cible."."; //retour en cas d'erreur
$Reussite_PORT = "INFO : La requ�te PORT snmp c'est bien d�roul� pour ".$MajPORT_Cible."."; //retour en cas de reussite
	
	$Listesnmp_PORTPILE = snmprealwalk($MajPORT_Cible, $MajPORT_Communaute, $OID_PORTPILE);// R�cup�re la liste des PORT de la pile
	if(empty($Listesnmp_PORTPILE)){ return $Erreur_PORT;} // La requ�te snmp n'a rien renvoy�e
	else{ // La requ�te snmp c'est bien d�roul�e
	$Listesnmp_EQUIP= snmprealwalk($MajPORT_Cible, $MajPORT_Communaute, $OID_EQUIP); 	// R�cup�re la liste des equipements avec NOM, UNIT et PORT
	$Listesnmp_VLAN = snmprealwalk($MajPORT_Cible, $MajPORT_Communaute, $OID_VLAN); 	// R�cup�re la liste des VLAN par PORTPILE
	$Listesnmp_TAG = snmprealwalk($MajPORT_Cible, $MajPORT_Communaute, $OID_TAG); 		// R�cup�re la liste des TAG (VLAN) par PORTPILE
	$Listesnmp_NEG = snmprealwalk($MajPORT_Cible, $MajPORT_Communaute, $OID_NEG); 		// R�cup�re la liste des NEGOCIATIONS par PORTPILE
	$Listesnmp_DUPLEX = snmprealwalk($MajPORT_Cible, $MajPORT_Communaute, $OID_DUPLEX); 	// R�cup�re la liste DUPLEX par PORTPILE
	$Listesnmp_DEBITS= snmprealwalk($MajPORT_Cible, $MajPORT_Communaute, $OID_DEBIT); 	// R�cup�re la liste des DEBITS par PORTPILE

foreach($Listesnmp_PORTPILE as $snmp_PORTPILE => $Valeur_PORTPILE){ // Pour chaque PORTPILE

$Recherche_EQUIP = "IF-MIB::ifDescr.".$Valeur_PORTPILE;

$Pattern  = "/(?<=module|unit|port) \d?\d/i";										// (?<=) qui pr�c�de, \d d�cimal, ? si existe, /recherche insensible � la casse/i 

preg_match_all($Pattern, $Listesnmp_EQUIP[$Recherche_EQUIP], $Tableau_EQUIP_UNITPORT);		// Recherche les chiffres qui suivent module ou unit ou port

if(empty($Tableau_EQUIP_UNITPORT['0']['1'])){													// Si le tableau n'a pas deux valeurs

$Valeur_EQUIP_UNIT = "1";																							
$Valeur_EQUIP_PORT = $Valeur_PORTPILE;

}else{																																// Le tableau � bien deux valeurs

$Valeur_EQUIP_UNIT = $Tableau_EQUIP_UNITPORT['0']['0'];
$Valeur_EQUIP_PORT = $Tableau_EQUIP_UNITPORT['0']['1'];

}

$Valeur_EQUIP_UNIT = sprintf('%01d',$Valeur_EQUIP_UNIT);			// Met le port sur deux chiffres
$Valeur_EQUIP_PORT = sprintf('%02d',$Valeur_EQUIP_PORT);			// Met le port sur deux chiffres

$Recherche_VLANPORT = "SNMPv2-SMI::enterprises.2272.1.3.3.1.3.".$Valeur_PORTPILE;		// Recherche dans le tableau la cl� correspondante � l'OID fonction du PORTPILE
$Liste_VLAN = str_replace(array('"',' '),'',$Listesnmp_VLAN[$Recherche_VLANPORT]);	// Efface les guillememets et espaces
$Tableau_VLAN = str_split($Liste_VLAN, 4);		// D�coupe la liste de VLAN tous les 4 caract�res vers un tableau $Tableau_VLAN 			

foreach($Tableau_VLAN as $cle_VLAN => $Numero_VLAN){	// Pour chaque valeur du tableau $Tableau_VLAN

	$Tableau_VLAN[$cle_VLAN] = hexdec($Tableau_VLAN[$cle_VLAN]);			// Transforme le contenu en decimal
	
}								// FIN Boucle $Tableau_VLAN

$Liste_VLAN = implode (',',$Tableau_VLAN);		// R�assemble les parties pour former la liste de VLAN sur le port s�par� de virgules

$Recherche_TAGPORT = "SNMPv2-SMI::enterprises.2272.1.3.3.1.4.".$Valeur_PORTPILE;
$Valeur_TAG = $Listesnmp_TAG[$Recherche_TAGPORT];
$Recherche_NEGPORT = "SNMPv2-SMI::enterprises.2272.1.4.10.1.1.11.".$Valeur_PORTPILE;
$Valeur_NEG = $Listesnmp_NEG[$Recherche_NEGPORT];
$Recherche_DUPLEXPORT = "SNMPv2-SMI::enterprises.2272.1.4.10.1.1.12.".$Valeur_PORTPILE;
$Valeur_DUPLEX = $Listesnmp_DUPLEX[$Recherche_DUPLEXPORT];
$Recherche_DEBITPORT = "SNMPv2-SMI::enterprises.2272.1.4.10.1.1.15.".$Valeur_PORTPILE;
$Valeur_DEBIT = $Listesnmp_DEBITS[$Recherche_DEBITPORT]; 		

if($Valeur_NEG == "2"){$Valeur_NEG = "Inactive";}	// Toute modification doit �tre repercut�e dans bdd
elseif($Valeur_NEG =="1"){$Valeur_NEG = "Active";}

if($Valeur_TAG == "1"){$Valeur_TAG = "Access";} 				
elseif($Valeur_TAG =="2"){$Valeur_TAG = "Trunk";}
elseif($Valeur_TAG =="5"){$Valeur_TAG = "untagPvidOnly";}
elseif($Valeur_TAG =="6"){$Valeur_TAG = "tagPvidOnly";}

if($Valeur_DUPLEX == "1"){$Valeur_DUPLEX = "Half-Duplex";}
elseif($Valeur_DUPLEX == "2"){$Valeur_DUPLEX = "Full-Duplex";} 

$Valeur_3PORTPILE = sprintf('%03d',$Valeur_PORTPILE);	// Met le PORT de la PILE sur 3 chiffres

$Maj_DEC_PORT = "UPDATE decouverte SET `DEC_VLAN` = '".$Liste_VLAN."', `DEC_TAG` = '".$Valeur_TAG."', `DEC_NEG` = '".$Valeur_NEG."', `DEC_DUPLEX` = '".$Valeur_DUPLEX."', `DEC_DEBIT` = '".$Valeur_DEBIT."', `DEC_DATE` = NOW() WHERE `DEC_Nom_EQUIP` = '".$MajPORT_Cible."' AND `DEC_PORTPILE`	= '".$Valeur_3PORTPILE."' AND `DEC_IP` = '' ;";
																		 
mysql_query($Maj_DEC_PORT) or die('Erreur SQL !'.$Maj_DEC_PORT.'<br>'.mysql_error()); // Insertions des donn�es
                                           
if(mysql_affected_rows() == "0"){		//	Si la Maj n'a pas �t� faite c'est que c'est un nouveau PORT
$Nouv_DEC_PORT = "INSERT INTO decouverte (
								`DEC_Nom_EQUIP` ,
								`DEC_UNIT` ,
								`DEC_PORT` ,
								`DEC_PORTPILE` ,
								`DEC_VLAN` ,
								`DEC_TAG` ,
								`DEC_NEG` ,
								`DEC_DUPLEX` ,
								`DEC_DEBIT`	,
								`DEC_DATE` ,
								`DEC_DEPUIS`) VALUES ('"
									.strtoupper($MajPORT_Cible)."','"
									.$Valeur_EQUIP_UNIT."','"
									.$Valeur_EQUIP_PORT."','"
									.$Valeur_3PORTPILE."','"
									.$Liste_VLAN."','"
									.$Valeur_TAG."','"
									.$Valeur_NEG."','"
									.$Valeur_DUPLEX."','"
									.$Valeur_DEBIT."',
									NOW(),
									NOW());";
	mysql_query($Nouv_DEC_PORT) or die('Erreur SQL !'.$Nouv_DEC_PORT.'<br>'.mysql_error()); // Insertions des donn�es
}

}		// FIN de la boucle PORTPILE
	return $Reussite_PORT;
	} // FIN de la condition du bon d�roulement de la premi�re requ�te
} // FIN Fonction MajPORT

function MajMAC($MajMAC_Cible,$MajMAC_Communaute){
$OID_PORTMAC 	= '.1.3.6.1.2.1.17.4.3.1.2'; 		// OID contenant la liste des PORT de la pile associ� aux adresses MAC

$Erreur_MAC = "ERREUR : La requ�te MAC snmp n'a pas aboutie pour ".$MajMAC_Cible."."; //retour en cas d'erreur
$Reussite_MAC = "INFO : La requ�te MAC snmp c'est bien d�roul� pour ".$MajMAC_Cible."."; //retour en cas de reussite

	$Listesnmp_PORTMAC = snmprealwalk($MajMAC_Cible, $MajMAC_Communaute, $OID_PORTMAC); 	// R�cup�re la liste des PORT de la pile associ� au adresse MAC
	if(empty($Listesnmp_PORTMAC)){ return $Erreur_MAC;} // La requ�te snmp n'a rien renvoy�e
	else{ // La requ�te snmp c'est bien d�roul�e
foreach($Listesnmp_PORTMAC as $snmp_PORTMAC => $Valeur_PORTMAC){ // Pour chaque PORTPILE associ� � une MAC

$Tableau_LIMIT[$Valeur_PORTMAC] = $Tableau_LIMIT[$Valeur_PORTMAC] + 1;

}		// FIN de la boucle PORTMAC de comptage de MAC par PORT pour recherche des port interco

foreach($Listesnmp_PORTMAC as $snmp_PORTMAC => $Valeur_PORTMAC){ // Pour chaque PORTPILE associ� � une MAC

$Valeur_3PORTMAC = sprintf('%03d',$Valeur_PORTMAC);				// Met le PORT de la PILE sur 3 chiffres

$Requete_UNITPORT = "SELECT `DEC_UNIT`,`DEC_PORT` FROM `decouverte` WHERE `DEC_PORTPILE` = '".$Valeur_3PORTMAC."' AND `DEC_Nom_EQUIP` = '".$MajMAC_Cible."' AND `DEC_TAG` != '';";
$Requete_UNITPORT_BDD = mysql_query($Requete_UNITPORT) or die('Erreur SQL !'.$Requete_UNITPORT.'<br>'.mysql_error()); // Recherche l'unit et le port correspondant � ce portpile
$Tableau_UNITPORT = mysql_fetch_array($Requete_UNITPORT_BDD);

if($Tableau_LIMIT[$Valeur_PORTMAC] <= "19"){		// V�rifie si c'est un PORT qui a 20 (0 � 19) MAC ou plus 

$Tableau_PORTMAC_MAC = explode('.',$snmp_PORTMAC, 7);		// D�coupe la cl� OID en 6 avec $Tableau_IPMAC_IP['6'] qui contient l'adresse MAC sous la forme "0.0.116.151.85.92"
$Tableau_PORTMAC_MAC = explode('.',$Tableau_PORTMAC_MAC['6']);		// D�coupe l'adresse MAC � chaque "."

		foreach($Tableau_PORTMAC_MAC as $cle_PORTMAC_MAC => $Morceau_MAC){	// Pour chaque valeur du tableau $Tableau_PORTMAC_MAC

		$Tableau_PORTMAC_MAC[$cle_PORTMAC_MAC] = sprintf('%02X',$Morceau_MAC);	// Transforme le contenu en Hexa � 2 chiffres en majuscule

		}	// FIN Boucle $Tableau_IPMAC_MAC

$Adresse_MAC = implode (':',$Tableau_PORTMAC_MAC);		// R�assemble les parties pour former l'adresse MAC s�par� de ":"

// Change l'emplacement PORTPILE et Nom_EQUIP de la machine et la date DEPUIS sa premi�re decouverte, si elle n'est plus sur le m�me PORTPILE ou Nom_EQUIP
$Emplacement_DEC_PORTMAC = "UPDATE decouverte SET	`DEC_Nom_EQUIP` = '".strtoupper($MajMAC_Cible)."', `DEC_UNIT` = '".$Tableau_UNITPORT['DEC_UNIT']."', `DEC_PORT`= '".$Tableau_UNITPORT['DEC_PORT']."', `DEC_PORTPILE` = '".$Valeur_3PORTMAC."', `DEC_DEPUIS` = NOW() 
														WHERE	`DEC_PORTMAC` = '".$Adresse_MAC."' AND `DEC_TAG` = '' AND (`DEC_Nom_EQUIP` != '".$MajMAC_Cible."' OR `DEC_PORTPILE` != '".$Valeur_3PORTMAC."');";

mysql_query($Emplacement_DEC_PORTMAC) or die('Erreur SQL !'.$Emplacement_DEC_PORTMAC.'<br>'.mysql_error()); // Insertions des donn�es

// Met � jour la DATE de la derni�re decouverte
$Maj_DEC_PORTMAC = "UPDATE decouverte SET	`DEC_DATE` = NOW() 
										WHERE	`DEC_PORTMAC` = '".$Adresse_MAC."' AND `DEC_TAG` = '' ;";
										
mysql_query($Maj_DEC_PORTMAC) or die('Erreur SQL !'.$Maj_DEC_PORTMAC.'<br>'.mysql_error()); // Insertions des donn�es



if(mysql_affected_rows() == "0"){		//	Si la Maj n'a pas �t� faite c'est que c'est une nouvelle adresse MAC donc elle est inser�

$Nouv_DEC_PORTMAC = "INSERT INTO decouverte (
								`DEC_Nom_EQUIP` ,
								`DEC_UNIT` ,
								`DEC_PORT` ,
								`DEC_PORTPILE` ,
								`DEC_PORTMAC` ,
								`DEC_TAG` ,
								`DEC_NEG` ,
								`DEC_DUPLEX` ,
								`DEC_DATE` ,
								`DEC_DEPUIS` 
								) VALUES ('"
									.strtoupper($MajMAC_Cible)."','"
									.$Tableau_UNITPORT['DEC_UNIT']."','"
									.$Tableau_UNITPORT['DEC_PORT']."','"
									.$Valeur_3PORTMAC."','"
									.$Adresse_MAC."',
									'',
									'',
									'',
									NOW(),
									NOW());";
									
mysql_query($Nouv_DEC_PORTMAC) or die('Erreur SQL !'.$Nouv_DEC_PORTMAC.'<br>'.mysql_error()); // Insertions des donn�es
}	// FIN de l'insertion en cas de nouvelle entr�e

			
}else{	// Sinon le consid�re comme un port d'inteconnection
	$Maj_DEC_INTER = "UPDATE decouverte SET `DEC_PORTMAC`= 'PORT INTERCO' WHERE `DEC_Nom_EQUIP` = '".$MajMAC_Cible."' AND `DEC_PORTPILE` = '".$Valeur_3PORTMAC."';";
	mysql_query($Maj_DEC_INTER) or die('Erreur SQL !'.$Maj_DEC_INTER.'<br>'.mysql_error()); // Mise � jour des donn�es
}  // FIN de la v�rification Port interco
}		// FIN de la boucle PORTMAC pour INSERT DANS SQL
	return $Reussite_PORT;
	} // FIN de la condition du bon d�roulement de la premi�re requ�te
} // FIN Fonction MajMAC

function MajIPDNS($MajIPDNS_Cible,$MajIPDNS_Communaute){
$OID_IPMAC	= 'ipNetToMediaPhysAddress';		// OID contenant les adresses MAC rang�es par des cl�s representant les IP

$Erreur_IPMAC = "ERREUR : La requ�te IPMAC snmp n'a pas aboutie pour ".$MajIPDNS_Cible."."; //retour en cas d'erreur
$Reussite_IPMAC = "INFO : La requ�te IPMAC snmp c'est bien d�roul� pour ".$MajIPDNS_Cible."."; //retour en cas de reussite

	$Listesnmp_IPMAC = snmprealwalk($MajIPDNS_Cible, $MajIPDNS_Communaute, $OID_IPMAC);
	if(empty($Listesnmp_IPMAC)){ return $Erreur_IPMAC;} // La requ�te snmp n'a rien renvoy�e
	else{ // La requ�te snmp c'est bien d�roul�e
		
		foreach($Listesnmp_IPMAC as $cle_IPMAC_IP => $Valeur_IPMAC_MAC){	// Pour chaque association IP MAC

			$Tableau_IPMAC_IP = explode('.',$cle_IPMAC_IP, 3);		// D�coupe la cl� OID en 3 avec $Tableau_IPMAC_IP['2'] qui contient l'adresse IP
			$Tableau_IPMAC_MAC = explode(':',$Valeur_IPMAC_MAC);		// D�coupe l'adresse MAC � chaque ":"
				foreach($Tableau_IPMAC_MAC as $cle_IPMAC_MAC => $Adresse_MAC){	// Pour chaque valeur du tableau $Tableau_IPMAC_MAC

					$Tableau_IPMAC_MAC[$cle_IPMAC_MAC] = hexdec($Tableau_IPMAC_MAC[$cle_IPMAC_MAC]);		// Transforme le contenu en decimal
					$Tableau_IPMAC_MAC[$cle_IPMAC_MAC] = sprintf('%02X',$Tableau_IPMAC_MAC[$cle_IPMAC_MAC]);	// Transforme le contenu en Hexa � 2 chiffres en majuscule


				}	// FIN Boucle $Tableau_IPMAC_MAC
			$Adresse_DNS = gethostbyaddr($Tableau_IPMAC_IP['2']);
			$Valeur_IPMAC_MAC = implode (':',$Tableau_IPMAC_MAC);		// R�assemble les parties pour former l'adresse MAC s�par� de ":"
			$Maj_DEC_IPMAC = "UPDATE decouverte SET `DEC_IP`= '".$Tableau_IPMAC_IP['2']."', `DEC_DNS`= '".$Adresse_DNS."' WHERE `DEC_PORTMAC` = '".$Valeur_IPMAC_MAC."';";
			mysql_query($Maj_DEC_IPMAC) or die('Erreur SQL !'.$Maj_DEC_IPMAC.'<br>'.mysql_error()); // Mise � jour des donn�es dans la base equipements
		}
			return $Reussite_PORT;
	} // FIN de la condition du bon d�roulement de la premi�re requ�te
} // FIN Fonction MajIPDNS

function MajSysup($MajSysup_Cible,$MajSysup_Communaute){
$OID_SYSUP 	= 'system.sysUpTime.0'; 			// OID contenant le temps d'activit� depuis le d�marrage

$Erreur_SYSUP = "ERREUR : La requ�te SYSUP snmp n'a pas aboutie pour ".$MajSysup_Cible."."; //retour en cas d'erreur
$Reussite_SYSUP = "INFO : La requ�te SYSUP snmp c'est bien d�roul� pour ".$MajSysup_Cible."."; //retour en cas de reussite

	$snmp_SYSUP = snmpget($MajSysup_Cible, $MajSysup_Communaute, $OID_SYSUP); 			// R�cup�re le temps d'activit� depuis le d�marrage
	if(empty($snmp_SYSUP)){ return $Erreur_SYSUP;} // La requ�te snmp n'a rien renvoy�e
	else{ // La requ�te snmp c'est bien d�roul�e

		$Maj_EQUIP = "UPDATE equipements SET `EQUIP_Sysup`='".$snmp_SYSUP."',`EQUIP_IP`='".gethostbyname($MajSysup_Cible)."' WHERE `EQUIP_Nom`='".$MajSysup_Cible."';";

		mysql_query($Maj_EQUIP) or die('Erreur SQL !'.$Maj_EQUIP.'<br>'.mysql_error()); // Mise � jour des donn�es dans la base equipements
			return $Reussite_SYSUP;
	} // FIN de la condition du bon d�roulement de la premi�re requ�te
} // FIN Fonction MajSysup


	// BASH DEBUT V�rifie si Les options sont correctes, sinon affiche l'aide
if($argc < "2" || ((in_array('-p',$argv) == FALSE) && (in_array('-m',$argv) == FALSE) && (in_array('-m',$argv) == FALSE) && (in_array('-i',$argv) == FALSE) && (in_array('-s',$argv) == FALSE) && (in_array('-u',$argv) == FALSE))){
?>

Ligne de commande

Utilisation :
<?php echo $argv[0]; ?> [option]

Pour lancer la mise � jour entrer :

-p	Met � jour les propri�t�s des ports 
-m	Met � jour les adresses MAC 
-i	Met � jour les adresses IP - DNS 
-s	Met � jour les SysUpTime et adresse IP des l'�quipements
-u	Met � jour un unique �quipement (-p & -m & -i & -s)

Toute autre option affiche l'aide. 

Exemple :

<?php echo $argv[0]; ?> -p -s 
Met � jour les propri�t�s des ports et le SysUpTime avec l'adresse IP des �quipements.

<?php echo $argv[0]; ?> -u SM-01 SAP-PP-1
Met � jour les entr�es correspondantes � SM-01 en utilisant SAP-PP-1 pour faire une correspondance entre IP et MAC.

<?php
} else {	// BASH Les options sont correctes

$date = date("d-m-Y");
$heure = date("H:i:s");
?>

D�marrage de la d�couverte. D�but du script � <?php echo $heure; ?> le <?php echo $date; ?> ...

<?php

if ($cle = array_search('-u',$argv)){	// Maj d'un �quipement unique

$Supr_CONDITION = "AND DEC_Nom_EQUIP = '".$argv[$cle+1]."'";	// Efface les machines absentes pour cette pile
Nettoyage_MACHINE($Supr_CONDITION);

$Communaute_EQUIP = "SELECT EQUIP_Communaute FROM equipements WHERE EQUIP_Nom = '".$argv[$cle+1]."';";	// V�rifie si l'�quipement existe dans la base et prend sa communaut�
$Communaute_EQUIP_BDD = mysql_query($Communaute_EQUIP) or die('Erreur SQL !'.$Communaute_EQUIP.'<br>'.mysql_error());
$Communaute = mysql_fetch_array($Communaute_EQUIP_BDD);

$passport_EQUIP = "SELECT EQUIP_Nom,EQUIP_Communaute FROM equipements WHERE EQUIP_Type = 'passport';";	//  Fait une liste des passport
$passport_EQUIP_BDD = mysql_query($passport_EQUIP) or die('Erreur SQL !'.$passport_EQUIP.'<br>'.mysql_error());
		$Verif_PORT = ""; // vide $Verif_PORT
		$Verif_PORT = MajPORT($argv[$cle+1],$Communaute['EQUIP_Communaute']);// Lancement de la fonction MajPORT et mise du retour dans $Verif
		echo $Verif_PORT."\n"; // Affiche le message r�ponse de la fonction MajPORT
		if(ereg("INFO :",$Verif_PORT)){ // La fonction � repondu correctement
		MajMAC($argv[$cle+1],$Communaute['EQUIP_Communaute']); // Lancement de la fonction MajMAC
		
		while($Boucle_EQUIP = mysql_fetch_array($passport_EQUIP_BDD)){ // Boucle sur chaque passport     
		MajIPDNS($Boucle_EQUIP['EQUIP_Nom'],$Communaute['EQUIP_Communaute']); // Lancement de la fonction MajIPDNS
		}																		// FIN boucle passport
			MajSysup($argv[$cle+1],$Communaute['EQUIP_Communaute']); // Lancement de la fonction MajSysup
		}else{
			echo "MAC,IP, SysUpTime n'ont pu �tre mis � jour suite � l'erreur.";
			}// FIN de verif de la r�ponse de la fonction MajPORT"			 
}else{	// Sinon faire une boucle sur chaque �quipement 

$Supr_CONDITION = "";	// Efface toutes les machines absentes
Nettoyage_MACHINE($Supr_CONDITION);
	
$requete_EQUIP ="SELECT EQUIP_Nom,EQUIP_Communaute,EQUIP_Type FROM equipements ORDER BY EQUIP_Type;";	// Range les �quipements par type (pile ou passport)
$EQUIP_BDD =mysql_query($requete_EQUIP) or die('Erreur SQL !'.$requete_EQUIP.'<br>'.mysql_error());

while($Boucle_EQUIP = mysql_fetch_array($EQUIP_BDD)){ // Boucle sur chaque �quipement     

if($Boucle_EQUIP['EQUIP_Type'] == 'pile'){			// Si c'est une pile de commutateur

	if (in_array('-p',$argv)){	// BASH Maj propri�t�s des ports
		$Verif_PORT = ""; // vide $Verif_PORT
		$Verif_PORT = MajPORT($Boucle_EQUIP['EQUIP_Nom'],$Boucle_EQUIP['EQUIP_Communaute']); // Lancement de la fonction MajPORT	et mise du retour dans $Verif
		echo $Verif_PORT."\n";
	} // BASH FIN Maj propri�t�s des ports

	if (in_array('-m',$argv)){	// BASH Maj adresses MAC
		$Verif_MAC = ""; // vide $Verif_MAC
		$Verif_MAC = MajMAC($Boucle_EQUIP['EQUIP_Nom'],$Boucle_EQUIP['EQUIP_Communaute']); // Lancement de la fonction MajMAC
		echo $Verif_MAC."\n";
	} // BASH FIN adresses MAC

}else{		//Si ce n'est pas une pile de commutateur

	if (in_array('-i',$argv)){	// BASH Maj adresses IP - DNS
		$Verif_IPDNS = ""; // vide $Verif_MAC
		$Verif_IPDNS =MajIPDNS($Boucle_EQUIP['EQUIP_Nom'],$Boucle_EQUIP['EQUIP_Communaute']); // Lancement de la fonction MajIPDNS
		echo $Verif_IPDNS."\n";
	} // BASH FIN adresses IP - DNS

}	// FIN v�rification Type �quipement


	if (in_array('-s',$argv)){	// Maj SysUpTime
		$Verif_SYSUP = ""; // vide $Verif_MAC
		$Verif_SYSUP =MajSysup($Boucle_EQUIP['EQUIP_Nom'],$Boucle_EQUIP['EQUIP_Communaute']); // Lancement de la fonction MajSysup
		echo $Verif_SYSUP."\n";
	}			// BASH FIN Maj SysUpTime
	
echo "\n"; // Saut de ligne entre les �quipements
} 		// FIN de la boucle while EQUIP
}	// FIN des Maj

   mysql_close($bdd); // fermeture de la base de donn�es

$datefin = date("d-m-Y");
$heurefin = date("H:i:s");

?>

D�couverte termin�e, merci pour votre patience. Fin du script �  <?php echo $heurefin; ?> le <?php echo $datefin; ?> ...

<?php
}	// BASH FIN  v�rifications options correctes

?>
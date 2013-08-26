<?php
/*
Planning Biblio, Plugin Congés Version 1.1
Licence GNU/GPL (version 2 et au dela)
Voir les fichiers README.txt et COPYING.txt
Copyright (C) 2013 - Jérôme Combes

Fichier : plugins/conges/install.php
Création : 24 juillet 2013
Dernière modification : 26 août 2013
Auteur : Jérôme Combes, jerome@planningbilbio.fr

Description :
Fichier permettant l'installation du plugin Congés. Ajoute les informations nécessaires dans la base de données
*/

session_start();

// Sécurité
if($_SESSION['login_id']!=1){
  echo "<br/><br/><h3>Vous devez vous connecter au planning<br/>avec le login \"admin\" pour pouvoir installer ce plugin.</h3>\n";
  echo "<a href='../../index.php'>Retour au planning</a>\n";
  exit;
}

$version="1.1";
include_once "../../include/config.php";

$sql=array();

// Droits d'accès
$sql[]="INSERT INTO `{$dbprefix}acces` (`nom`,`groupe_id`,`page`) VALUES ('Congés - Index','100','plugins/conges/index.php');";
$sql[]="INSERT INTO `{$dbprefix}acces` (`nom`,`groupe_id`,`page`) VALUES ('Congés - Liste','100','plugins/conges/voir.php');";
$sql[]="INSERT INTO `{$dbprefix}acces` (`nom`,`groupe_id`,`page`) VALUES ('Congés - Enregistrer','100','plugins/conges/enregistrer.php');";
$sql[]="INSERT INTO `{$dbprefix}acces` (`nom`,`groupe_id`,`page`) VALUES ('Congés - Modifier','100','plugins/conges/modif.php');";
$sql[]="INSERT INTO `{$dbprefix}acces` (`nom`,`groupe_id`,`page`) VALUES ('Congés - CalculCredit','100','plugins/conges/ajax.calculCredit.php');";
$sql[]="INSERT INTO `{$dbprefix}acces` (`nom`,`groupe_id`,`groupe`,`page`) VALUES ('Congés - Infos','2','Gestion des congés','plugins/conges/infos.php');";

// Création de la table conges
$sql[]="CREATE TABLE `{$dbprefix}conges` (`id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `perso_id` INT(11) NOT NULL, `debut` DATETIME NOT NULL, `fin` DATETIME NOT NULL, `commentaires` TEXT, `refus` TEXT, `heures` VARCHAR(20), `debit` VARCHAR(20), `saisie` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `modif` INT(11) NOT NULL DEFAULT '0',`modification` TIMESTAMP, `valide` INT(11) NOT NULL DEFAULT '0',`validation` TIMESTAMP);";

// Création de la table conges_infos
$sql[]="CREATE TABLE `{$dbprefix}conges_infos` (`id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `debut` DATE NULL, `fin` DATE NULL, `texte` TEXT NULL, `saisie` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP);";

// Menu
$sql[]="INSERT INTO `{$dbprefix}menu` (`niveau1`,`niveau2`,`titre`,`url`) VALUES (15,0,'Congés','plugins/conges/index.php');";
$sql[]="INSERT INTO `{$dbprefix}menu` (`niveau1`,`niveau2`,`titre`,`url`) VALUES (15,10,'Liste des congés','plugins/conges/voir.php');";
$sql[]="INSERT INTO `{$dbprefix}menu` (`niveau1`,`niveau2`,`titre`,`url`) VALUES (15,20,'Poser des congés','plugins/conges/enregistrer.php');";
$sql[]="INSERT INTO `{$dbprefix}menu` (`niveau1`,`niveau2`,`titre`,`url`) VALUES (15,30,'Informations','plugins/conges/infos.php');";

// Modification de la table personnel
$sql[]="ALTER TABLE `{$dbprefix}personnel` ADD `congesCredit` VARCHAR(10), ADD `congesReliquat` VARCHAR(10), ADD `congesAnticipation` VARCHAR(10);";
$sql[]="ALTER TABLE `{$dbprefix}personnel` ADD `recupSamedi` VARCHAR(10);";
$sql[]="ALTER TABLE `{$dbprefix}personnel` ADD `congesAnnuel` VARCHAR(10);";

// Ajout des taches planifiées
$sql[]="INSERT INTO `{$dbprefix}cron` (m,h,dom,mon,dow,command,comments) VALUES (0,0,1,1,'*','plugins/conges/cron.jan1.php','Cron Congés 1er Janvier');";
$sql[]="INSERT INTO `{$dbprefix}cron` (m,h,dom,mon,dow,command,comments) VALUES (0,0,1,1,'*','plugins/conges/cron.sept1.php','Cron Congés 1er Septembre');";

//	Inscription du plugin Congés dans la base
$sql[]="INSERT INTO `{$dbprefix}plugins` (`nom`) VALUES ('conges');";

?>
<!-- Entête HTML -->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>Plugin Congés - Installation</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>

<?php
// Execution des requêtes
foreach($sql as $elem){
  $db=new db();
  $db->query($elem);
  if(!$db->error)
    echo "$elem : <font style='color:green;'>OK</font><br/>\n";
  else
    echo "$elem : <font style='color:red;'>Erreur</font><br/>\n";
}

echo "<br/><br/><a href='../../index.php'>Retour au planning</a>\n";
?>

</body>
</html>
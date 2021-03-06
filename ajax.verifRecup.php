<?php
/*
Planning Biblio, Plugin Congés Version 1.5.1
Licence GNU/GPL (version 2 et au dela)
Voir les fichiers README.md et LICENSE
Copyright (C) 2013-2014 - Jérôme Combes

Fichier : plugins/conges/ajax.verifRecup.php
Création : 18 septembre 2013
Dernière modification : 3 juin 2014
Auteur : Jérôme Combes, jerome@planningbilbio.fr

Description :
Vérifie si le jour demandé à déjà fait l'objet d'une demande de récuperation.
Appelé en arrière plan par la fonction JS verifRecup()
*/

session_start();
ini_set('display_errors',0);
ini_set('error_reporting',E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

$version="1.5.1";
include "../../include/config.php";
include "../../personnel/class.personnel.php";
include "class.conges.php";

$date=dateFr($_GET['date']);
$perso_id=is_numeric($_GET['perso_id'])?$_GET['perso_id']:$_SESSION['login_id'];

$db=new db();
$db->select("recuperations",null,"`perso_id`='$perso_id' AND (`date`='$date' OR `date2`='$date')");
if($db->result){
  echo "###Demande###";
}
?>
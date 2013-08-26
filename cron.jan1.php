<?php
/*
Planning Biblio, Plugin Conges Version 1.0
Licence GNU/GPL (version 2 et au dela)
Voir les fichiers README.txt et COPYING.txt
Copyright (C) 2013 - Jérôme Combes

Fichier : plugins/conges/cron.jan1.php
Création : 13 août 2013
Dernière modification : 13 août 2013
Auteur : Jérôme Combes, jerome@planningbilbio.fr

Description :
Fichier executant des taches planifiées au 1er janvier pour le plugin Conges.
Page appelée par le fichier include/cron.php
*/

require_once "class.conges.php";

$db=new db();
$db->update("personnel","congesReliquat='0.00'");
?>
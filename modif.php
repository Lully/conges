<?php
/*
Planning Biblio, Plugin Congés Version 1.5
Licence GNU/GPL (version 2 et au dela)
Voir les fichiers README.md et LICENSE
Copyright (C) 2013-2014 - Jérôme Combes

Fichier : plugins/conges/modif.php
Création : 1er août 2013
Dernière modification : 4 avril 2014
Auteur : Jérôme Combes, jerome@planningbilbio.fr

Description :
Fichier permettant voir ou de modifier un congé
Accessible par la page plugins/conges/voir.php
Inclus dans le fichier index.php
*/

require_once "class.conges.php";

// Initialisation des variables
$id=$_GET['id'];
$menu=isset($_GET['menu'])?$_GET['menu']:null;
$debut=isset($_GET['debut'])?$_GET['debut']:null;
$fin=isset($_GET['fin'])?$_GET['fin']:null;
$quartDHeure=$config['heuresPrecision']=="quart d&apos;heure"?true:false;

// Elements du congé demandé
$c=new conges();
$c->id=$id;
$c->fetch();
if(!array_key_exists(0,$c->elements)){
  echo "<h3>Congés</h3>\n";
  echo "<div id='acces_refuse'>Accès refusé</div>\n";
  include "include/footer.php";
  exit;
}
$data=$c->elements[0];

$perso_id=isset($_GET['perso_id'])?$_GET['perso_id']:$data['perso_id'];
if(!in_array(2,$droits) and $perso_id!=$_SESSION['login_id']){
  echo "<h3>Congés</h3>\n";
  echo "<div id='acces_refuse'>Accès refusé</div>\n";
  include "include/footer.php";
  exit;
}

if($config['Multisites-nombre']>1){
  $p=new personnel();
  $p->fetchById($perso_id);
  // $droitsConges = droits nécessaires pour adminN2 si multisites
  $droitsConges=array();
  if(is_array($p->elements[0]['sites'])){
    foreach($p->elements[0]['sites'] as $site){
      $droitsConges[]=400+$site;
    }
  }
}
else{
  $droitsConges=array(2);
}
$admin=in_array(7,$droits)?true:false;
$admin=in_array(2,$droits)?true:$admin;
$adminN2=false;
foreach($droitsConges as $elem){
  $adminN2=in_array($elem,$droits)?true:$adminN2;
}

if(isset($_GET['confirm'])){
  $fin=$fin?$fin:$debut;
  $debutSQL=dateSQL($debut);
  $finSQL=dateSQL($fin);
  $hre_debut=$_GET['hre_debut']?$_GET['hre_debut']:"00:00:00";
  $hre_fin=$_GET['hre_fin']?$_GET['hre_fin']:"23:59:59";
  $commentaires=htmlentities($_GET['commentaires'],ENT_QUOTES|ENT_IGNORE,"UTF-8",false);
  $refus=isset($_GET['refus'])?htmlentities($_GET['refus'],ENT_QUOTES|ENT_IGNORE,"UTF-8",false):null;

  // Enregistre la modification du congés
  $c=new conges();
  $c->update($_GET);

  // Envoi d'une notification par email
  // Récupération des adresses e-mails de l'agent et des responsables pour m'envoi des alertes
  $c=new conges();
  $c->getResponsables($debutSQL,$finSQL,$perso_id);
  $responsables=$c->responsables;

  $db_perso=new db();
  $db_perso->query("select nom,prenom,mail,mailResponsable from {$dbprefix}personnel where id=$perso_id;");
  $nom=$db_perso->result[0]['nom'];
  $prenom=$db_perso->result[0]['prenom'];
  $mail=$db_perso->result[0]['mail'];
  $mailResponsable=$db_perso->result[0]['mailResponsable'];

  // Choix du sujet et des destinataires en fonction du degré de validation
  switch($_GET['valide']){
    // Modification sans validation
    case 0 : 
      $sujet="Modification de congés";
      $notifications=$config['Absences-notifications2'];
      break;
    // Validations Niveau 2
    case 1 : 
      $sujet="Validation de congés";
      $notifications=$config['Absences-notifications4'];
      break;
    case -1 :
      $sujet="Refus de congés";
      $notifications=$config['Absences-notifications4'];
      break;
    // Validations Niveau 1
    case 2 :
      $sujet="Congés en attente de validation hiérarchique";
      $notifications=$config['Absences-notifications3'];
      break;
    case -2 :
      $sujet="Congés en attente de validation hiérarchique";
      $notifications=$config['Absences-notifications3'];
      break;
  }

  // Choix des destinataires en fonction de la configuration
  $a=new absences();
  $a->getRecipients($notifications,$responsables,$mail,$mailResponsable);
  $destinataires=$a->recipients;

  // Message qui sera envoyé par email
  $message="$sujet : <br/>$prenom $nom<br/>Début : $debut";
  if($hre_debut!="00:00:00")
    $message.=" ".heure3($hre_debut);
  $message.="<br/>Fin : $fin";
  if($hre_fin!="23:59:59")
    $message.=" ".heure3($hre_fin);
  if($commentaires)
    $message.="<br/><br/>Commentaires :<br/>$commentaires<br/>";
  if($refus and $_GET['valide']==-1){
    $message.="<br/>Motif du refus :<br/>$refus<br/>";
  }

  // ajout d'un lien permettant de rebondir sur la demande
  $url=createURL("plugins/conges/modif.php&id=$id");
  $message.="<br/><br/>Lien vers la demande de cong&eacute; :<br/><a href='$url'>$url</a><br/><br/>";

  sendmail($sujet,$message,$destinataires);
  if($menu=="off"){
    echo "<script type=text/JavaScript>parent.document.location.reload(false);</script>\n";
    echo "<script type=text/JavaScript>popup_closed();</script>\n";
  }
  else{
    echo "<script type='text/JavaScript'>document.location.href=\"index.php?page=plugins/conges/voir.php&information=Le congé à été modifié avec succés\"</script>\n";
  }
}

else{	// Formulaire
  $valide=$data['valide']>0?true:false;
  $selectAccept[0]=$data['valide']>0?"selected='selected'":null;
  $selectAccept[1]=$data['valide']<0?"selected='selected'":null;
  $selectAccept[2]=($data['valideN1']>0 and $data['valide']==0)?"selected='selected'":null;
  $selectAccept[3]=($data['valideN1']<0 and $data['valide']==0)?"selected='selected'":null;
  $displayRefus=$data['valide']>=0?"display:none;":null;
  $displayRefus=($data['valideN1']<0 and $admin)?null:$displayRefus;
  $perso_id=$data['perso_id'];
  $debut=dateFr(substr($data['debut'],0,10));
  $fin=dateFr(substr($data['fin'],0,10));
  $hre_debut=substr($data['debut'],-8);
  $hre_fin=substr($data['fin'],-8);
  $allday=null;
  $displayHeures=null;
  if($hre_debut=="00:00:00" and $hre_fin=="23:59:59"){
    $allday="checked='checked'";
    $displayHeures="style='display:none;'";
  }
  $jours=number_format(($data['heures']/7),2,"."," ");
  $tmp=explode(".",$data['heures']);
  $heures=$tmp[0];
  $minutes=$tmp[1];
  $select25=$minutes=="25"?"selected='selected'":null;
  $select50=$minutes=="50"?"selected='selected'":null;
  $select75=$minutes=="75"?"selected='selected'":null;
  $selectRecup=$data['debit']=="recuperation"?"selected='selected'":null;
  $selectCredit=$data['debit']=="credit"?"selected='selected'":null;

  // Crédits
  $p=new personnel();
  $p->fetchById($perso_id);
  $nom=$p->elements[0]['nom'];
  $prenom=$p->elements[0]['prenom'];
  $credit=number_format($p->elements[0]['congesCredit'], 2, '.', ' ');
  $reliquat=number_format($p->elements[0]['congesReliquat'], 2, '.', ' ');
  $anticipation=number_format($p->elements[0]['congesAnticipation'], 2, '.', ' ');
  $credit2=str_replace(array(".00",".25",".50",".75"),array("h00","h15","h30","h45"),$credit);
  $reliquat2=str_replace(array(".00",".25",".50",".75"),array("h00","h15","h30","h45"),$reliquat);
  $anticipation2=str_replace(array(".00",".25",".50",".75"),array("h00","h15","h30","h45"),$anticipation);
  $recuperation=number_format($p->elements[0]['recupSamedi'], 2, '.', ' ');
  $recuperation2=heure4($recuperation);

  // Affichage du formulaire
  echo "<h3>Congés</h3>\n";
  echo "<form name='form' action='index.php' method='get' id='form'>\n";
  echo "<input type='hidden' name='page' value='plugins/conges/modif.php' />\n";
  echo "<input type='hidden' name='menu' value='$menu' />\n";
  echo "<input type='hidden' name='confirm' value='confirm' />\n";
  echo "<input type='hidden' name='reliquat' value='$reliquat' />\n";
  echo "<input type='hidden' name='recuperation' value='$recuperation' />\n";
  echo "<input type='hidden' name='credit' value='$credit' />\n";
  echo "<input type='hidden' name='anticipation' value='$anticipation' />\n";
  echo "<input type='hidden' name='id' value='$id' id='id' />\n";
  echo "<input type='hidden' name='valide' value='0' />\n";
  echo "<table border='0'>\n";
  echo "<tr><td style='width:300px;'>\n";
  echo "Nom, prénom : \n";
  echo "</td><td>\n";
  if($admin){
    $db_perso=new db();
    $db_perso->query("select * from {$dbprefix}personnel where actif='Actif' order by nom,prenom;");
    echo "<select name='perso_id' id='perso_id' style='width:98%;'>\n";
    foreach($db_perso->result as $elem){
      if($perso_id==$elem['id']){
	echo "<option value='".$elem['id']."' selected='selected'>".$elem['nom']." ".$elem['prenom']."</option>\n";
      }
      else{
	echo "<option value='".$elem['id']."'>".$elem['nom']." ".$elem['prenom']."</option>\n";
      }
    }
    echo "</select>\n";
  }
  else{
    echo "<input type='hidden' name='perso_id' id='perso_id' value='{$_SESSION['login_id']}' />\n";
    echo $_SESSION['login_nom']." ".$_SESSION['login_prenom'];
  }
  echo "</td></tr>\n";
  echo "<tr><td style='padding-top:15px;'>\n";
  echo "Journée(s) entière(s) : \n";
  echo "</td><td style='padding-top:15px;'>\n";
  echo "<input type='checkbox' name='allday' $allday onclick='all_day();'/>\n";
  echo "</td></tr>\n";
  echo "<tr><td>\n";
  echo "Date de début : \n";
  echo "</td><td>";
  echo "<input type='text' name='debut' id='debut' value='$debut' class='datepicker' style='width:97%;'/>\n";
  echo "</td></tr>\n";
  echo "<tr id='hre_debut' $displayHeures ><td>\n";
  echo "Heure de début : \n";
  echo "</td><td>\n";
  echo "<select name='hre_debut' id='hre_debut_select' style='width:98%;'>\n";
  selectHeure(7,23,true,$quartDHeure,$hre_debut);
  echo "</select>\n";
  echo "</td></tr>\n";
  echo "<tr><td>\n";
  echo "Date de fin : \n";
  echo "</td><td>";
  echo "<input type='text' name='fin' id='fin' value='$fin'  class='datepicker' style='width:97%;'/>\n";
  echo "</td></tr>\n";
  echo "<tr id='hre_fin' $displayHeures ><td>\n";
  echo "Heure de fin : \n";
  echo "</td><td>\n";
  echo "<select name='hre_fin' id='hre_fin_select' style='width:98%;'>\n";
  selectHeure(7,23,true,$quartDHeure,$hre_fin);
  echo "</select>\n";
  echo "</td></tr>\n";

  echo <<<EOD
    <tr><td style='padding-top:15px;'>Nombre d'heures : </td>
      <td style='padding-top:15px;'>
      <select name='heures' style='width:30%;' onchange='calculRestes();'>
EOD;
      for($i=0;$i<1000;$i++){
	$selected=$heures==$i?"selected='selected'":null;
	echo "<option value='$i' $selected>$i</option>\n";
      }

  echo <<<EOD
	</select>
      h
      <select name='minutes' style='width:30%;' onchange='calculRestes();'>
	<option value='00'>00</option>
	<option value='25' $select25 >15</option>
	<option value='50' $select50 >30</option>
	<option value='75' $select75 >45</option>
	</select>
      <input type='button' value='Calculer' onclick='calculCredit();' style='width:30%;'></td></tr>

  <tr><td>Nombre de jours (7h/jour) : </td>
    <td id='nbJours'>$jours</td></tr>

  <tr><td colspan='2' style='padding-top:20px;'>
EOD;
  if($reliquat){
    echo "Ces heures seront débitées sur le réliquat de l'année précédente puis sur : ";
  }
  else{
    echo "Ces heures seront débitées sur : ";
  }
  echo <<<EOD
    </td></tr>
    <tr><td>&nbsp;</td>
    <td><select name='debit' style='width:98%;' onchange='calculRestes();'>
    <option value='recuperation' $selectRecup >Le crédit de récupérations</option>
    <option value='credit' $selectCredit >Le crédit de congés de l'année en cours</option>
    </select></td></tr>
EOD;
  if(!$valide){
    echo <<<EOD
    <tr><td colspan='2'>
      <table border='0'>
	<tr><td style='width:298px;'>Reliquat : </td><td style='width:130px;'>$reliquat2</td><td>(après débit : <font id='reliquat4'>$reliquat2</font>)</td></tr>
	<tr><td>Crédit de récupérations : </td><td>$recuperation2</td><td><font id='recup3'>(après débit : <font id='recup4'>$recuperation2</font>)</font></td></tr>
	<tr><td>Crédit de congés: </td><td>$credit2</td><td><font id='credit3'>(après débit : <font id='credit4'>$credit2</font>)</font></td></tr>
	<tr><td>Solde débiteur : </td><td>$anticipation2</td><td><font id='anticipation3'>(après débit : <font id='anticipation4'>$anticipation2</font>)</font></td></tr>
      </table>
    </td></tr>
EOD;
  }

  echo "<tr valign='top'><td style='padding-top:15px;'>\n";
  echo "Commentaires : \n";
  echo "</td><td style='padding-top:15px;'>\n";
  echo "<textarea name='commentaires' cols='16' rows='5' style='width:97%;'>{$data['commentaires']}</textarea>\n";
  echo "</td></tr><tr><td>&nbsp;\n";

  echo "<tr style='vertical-align:top;'><td style='padding-top:15px;padding-bottom:15px;'>\n";
  echo "Demande : \n";
  echo "</td><td style='padding-top:15px;padding-bottom:15px;'>\n";
  echo dateFr($data['saisie'],true);
  if($data['saisie_par'] and $data['saisie_par']!=$data['perso_id']){
    echo " par ".nom($data['saisie_par']);
  }
  echo "</td></tr>\n";

  echo "<tr><td>Validation</td>\n";
  // Affichage de l'état de validation dans un menu déroulant si l'agent a le droit de le modifié et si le congé n'est pas validé
  if(($adminN2 and !$valide) or ($admin and $data['valide']==0)){
    echo "<td><select name='valide' style='width:98%;' onchange='afficheRefus(this);'>\n";
    echo "<option value='0'>&nbsp;</option>\n";
    echo "<option value='2' {$selectAccept[2]}>Accept&eacute; (En attente de validation hi&eacute;rarchique)</option>\n";
    echo "<option value='-2' {$selectAccept[3]}>Refus&eacute; (En attente de validation hi&eacute;rarchique)</option>\n";
    if($adminN2){
      echo "<option value='1' {$selectAccept[0]}>Accept&eacute;</option>\n";
      echo "<option value='-1' {$selectAccept[1]}>Refus&eacute;</option>\n";
    }
    echo "</select></td>\n";
    }
  // Affichage simple de l'état de validation si l'agent n'a pas le droit de le modifié ou si le congé est validé
  else{
    if($data['valide']<0){
      echo "<td>Refusé</td>";
    }
    elseif($data['valide']>0){
      echo "<td>Validé</td>";
    }
    elseif($data['valideN1']){
      echo "<td>En attente de validation hi&eacute;rarchique</td>";
    }
    else{
      echo "<td>Demand&eacute;</td>";
    }
  }
  echo "</tr>\n";
  echo "<tr id='tr_refus' style='vertical-align:top;$displayRefus'><td>Motif du refus :</td>\n";
    echo "<td><textarea name='refus' cols='16' rows='5' style='width:100%;'>{$data['refus']}</textarea></td></tr>\n";
  echo "<tr><td>&nbsp;</td></tr>\n";

  echo "</td></tr><tr><td colspan='2' style='text-align:center;'>\n";
  if($menu=="off"){
    echo "<input type='button' value='Annuler' onclick='popup_closed();' class='ui-button'/>";
  }
  else{
    echo "<input type='button' value='Annuler' onclick='document.location.href=\"index.php?page=plugins/conges/voir.php\";' class='ui-button'/>";
  }

  if((!$valide and $admin) or ($data['valide']==0 and $data['valideN1']==0)){
    echo "<input type='button' value='Enregistrer les modifications' style='margin-left:20px;' class='ui-button' onclick='verifConges();'/>\n";
  }

  if($admin){
    echo "<input type='button' value='Supprimer' style='margin-left:20px;' onclick='supprimeConges()' class='ui-button'/>\n";
  }
  
  $debuttransf=str_replace('-','',substr($data['debut'],0,10))."T".str_replace(':','',substr($data['debut'],-8));
   $debuttransf=str_replace('T000000','',$debuttransf);
   $fintransf=str_replace('-','',substr($data['fin'],0,10))."T".str_replace(':','',substr($data['fin'],-8));
   $fintransf=str_replace('T235959','',$fintransf);
   echo "<a style='margin-left: 40px;' target='_blank' id='Inscrire_dans_mon_agenda_Google_$id' title='Ajouter dans mon agenda Google' href='https://www.google.com/calendar/event?action=TEMPLATE&hl=fr&text=Congés  $prenom $nom&dates=$debuttransf/$fintransf&location=Nice&ctz=Europe%2FParis&amp;details='>
<img width='20' height='20' style='border: none; text-align: right' src='img/gcal.jpg' alt='Ajouter dans mon agenda Google'/>
</a>\n"; 

  echo "</td></tr></table>\n";
  echo "</form>\n";

  // Calcul des crédits restant au chargement de la page
  echo "<script type='text/JavaScript'>calculRestes();</script>\n";
}
?>

<?php
/*
Planning Biblio, Plugin Congés Version 1.5.2
Licence GNU/GPL (version 2 et au dela)
Voir les fichiers README.md et LICENSE
Copyright (C) 2013-2014 - Jérôme Combes

Fichier : plugins/conges/cet.php
Création : 6 mars 2014
Dernière modification : 12 juin 2014
Auteur : Jérôme Combes, jerome@planningbilbio.fr

Description :
Fichier permettant de voir les CET
*/

include_once "class.conges.php";
include_once "personnel/class.personnel.php";

// Initialisation des variables
$adminN1=in_array(7,$droits)?1:0;
$adminN2=in_array(2,$droits)?1:0;
$displayValidation=$adminN1?null:"style='display:none;'";
$displayValidationN2=$adminN2?null:"style='display:none;'";
$agent=isset($_GET['agent'])?$_GET['agent']:null;
$tri=isset($_GET['tri'])?$_GET['tri']:"`debut`,`fin`,`nom`,`prenom`";
$annee=isset($_GET['annee'])?$_GET['annee']:(isset($_SESSION['oups']['recup_annee'])?$_SESSION['oups']['recup_annee']:(date("m")<9?date("Y")-1:date("Y")));
if($adminN1){
  $perso_id=isset($_GET['perso_id'])?$_GET['perso_id']:(isset($_SESSION['oups']['recup_perso_id'])?$_SESSION['oups']['recup_perso_id']:$_SESSION['login_id']);
}
else{
  $perso_id=$_SESSION['login_id'];
}
if(isset($_GET['reset'])){
  $annee=date("m")<9?date("Y")-1:date("Y");
  $perso_id=$_SESSION['login_id'];
}
$_SESSION['oups']['recup_annee']=$annee;
$_SESSION['oups']['recup_perso_id']=$perso_id;

$debut=$annee."-09-01";
$fin=($annee+1)."-08-31";
$message=null;

// Recherche des demandes de récupérations enregistrées
$c=new conges();
$c->admin=$adminN1;
$c->debut=$debut;
$c->fin=$fin;
if($perso_id!=0){
  $c->perso_id=$perso_id;
}
$c->getCET();
$cet=$c->elements;

// Recherche des agents
$p=new personnel();
$p->fetch();
$agents=$p->elements;

// Années universitaires
$annees=array();
for($d=date("Y")+2;$d>date("Y")-11;$d--){
  $annees[]=array($d,$d."-".($d+1));
}

// Notifications
if(isset($_GET['message'])){
  switch($_GET['message']){
    case "Demande-OK" : $message="Votre demande a été enregistrée"; $type="highlight";	break;
    case "Demande-Erreur" : $message="Une erreur est survenue lors de l'enregitrement de votre demande."; $type="error"; break;
    case "OK" : $message="Vos modifications ont été enregistrées"; $type="highlight";	break;
    case "Erreur" : $message="Une erreur est survenue lors de la validation de vos modifications."; $type="error"; break;
    case "Refus" : $message="Accès refusé."; $type="error"; break;
  }
  if($message){
    echo "<script type='text/JavaScript'>information('$message','$type',70);</script>\n";
  }
}

// Affichage
echo "<h3 class='print_only'>Liste des demandes de CET de ".nom($perso_id,"prenom nom").", année $annee-".($annee+1)."</h3>\n";
echo <<<EOD
<h3 class='noprint'>Compte &Eacute;pargne Temps</h3>

<div id='liste'>
<h4 class='noprint'>Liste des demandes</h4>
<form name='form' method='get' action='index.php' class='noprint'>
<p>
<input type='hidden' name='page' value='plugins/conges/cet.php' />
<input type='hidden' id='adminN1' value='$adminN1' />
<input type='hidden' id='adminN2' value='$adminN2' />
Ann&eacute;e : <select name='annee'>
EOD;
foreach($annees as $elem){
  $selected=$annee==$elem[0]?"selected='selected'":null;
  echo "<option value='{$elem[0]}' $selected >{$elem[1]}</option>";
}
echo "</select>\n";

if($adminN1){
  echo "&nbsp;&nbsp;Agent : ";
  echo "<select name='perso_id' id='perso_id'>";
  $selected=$perso_id==0?"selected='selected'":null;
  echo "<option value='0' $selected >Tous</option>";
  foreach($agents as $agent){
    $selected=$agent['id']==$perso_id?"selected='selected'":null;
    echo "<option value='{$agent['id']}' $selected >{$agent['nom']} {$agent['prenom']}</option>";
  }
  echo "</select>\n";
}else{
  echo "<input type='hidden' name='perso_id' id='perso_id' value='$perso_id' />\n";
}
echo <<<EOD
&nbsp;&nbsp;<input type='submit' value='OK' id='button-OK' class='ui-button'/>
&nbsp;&nbsp;<input type='button' value='Reset' id='button-Effacer' class='ui-button' onclick='location.href="index.php?page=plugins/conges/recuperations.php&reset"' />
</p>
</form>
<table id='tableCET'>
<thead>
<tr><th>&nbsp;</th>
EOD;
if($adminN1){
  echo "<th>Agent</th>";
}
echo "<th>Jours</th><th>Demande</th><th>Commentaires</th><th>Validation</th><th>Crédits</th></tr>\n";
echo "</thead>\n";
echo "<tbody>\n";

foreach($cet as $elem){
  $validation="Demand&eacute;e, ".dateFr($elem['saisie'],true);
  $validationStyle="font-weight:bold;";
  if($elem['saisie_par'] and $elem['saisie_par']!=$elem['perso_id']){
    $validation.=" par ".nom($elem['saisie_par']);
  }
  $credits=null;
  if($elem['valideN2']>0){
    $validation=nom($elem['valideN2']).", ".dateFr($elem['validation'],true);
    $validationStyle=null;
    if($elem['solde_prec']!=null and $elem['solde_actuel']!=null){
      $credits=heure4($elem['solde_prec'])." &rarr; ".heure4($elem['solde_actuel']);
    }

  }
  elseif($elem['valideN2']<0){
    $validation="Refus&eacute;, ".nom(-$elem['valideN2']).", ".dateFr($elem['validation'],true);
    $validationStyle="color:red;font-weight:bold;";
  }

  echo "<tr>";
  echo "<td><a href='javascript:getCET({$elem['id']});'><img src='themes/default/images/modif.png' alt='Modifier' /></a></td>\n";
  if($adminN1){
    echo "<td>".nom($elem['perso_id'])."</td>";
  }
  echo "<td>{$elem['jours']}</td><td>".dateFr($elem['saisie'])."</td>";
  echo "<td>".str_replace("\n","<br/>",$elem['commentaires'])."</td><td style='$validationStyle'>$validation</td><td>$credits</td></tr>\n";
}

$button=$adminN1?"Alimenter un CET":"Alimenter mon CET";
echo <<<EOD
</tbody>
</table>
</div> <!-- liste -->

<div class='noprint'>
<br/><button id='cet-dialog-button' class='ui-button'>$button</button>
</div>

<div id="cet-dialog-form" title="Compte &Eacute;pargne Temps" class='noprint' style='display:none;'>
  <p class="validateTips">Veuillez choisir le nombre de jours à verser sur le Compte &Eacute;pargne Temps.</p>
  <form>
  <input type='hidden' name='id' id='cet-id' />
  <fieldset>
    <table class='tableauFiches'>
EOD;
if($adminN1){
  echo <<<EOD
    <tr><td><label for="agent">Agent</label></td>
    <td><select id='cet-agent' name='agent' style='text-align:center;'>
      <option value=''>&nbsp;</option>
EOD;
  foreach($agents as $elem){
    $selected=$elem['id']==$perso_id?"selected='selected'":null;
    echo "<option value='{$elem['id']}' $selected >".nom($elem['id'])."</option>\n";
  }
  echo "</select></td></tr>\n";
}

echo <<<EOD
    <tr><td>Reliquat disponible</td>
    <td><label id='cet-reliquat'></label></td></tr>
EOD;

echo <<<EOD
    <tr><td><label for="jours">Nombre de jours à verser</label></td>
    <td><select id='cet-jours' name='jours' style='text-align:center;'>
      </select></td></tr>
    <tr $displayValidation ><td>Validation</td>
      <td><select id='cet-validation'>
	<option value='0'>&nbsp;</option>
	<option value='1' >Accept&eacute; (En attente de validation hi&eacute;rarchique)</option>
	<option value='-1' >Refus&eacute; (En attente de validation hi&eacute;rarchique)</option>
	<option value='2'  $displayValidationN2 >Accept&eacute;</option>
	<option value='-2'  $displayValidationN2 >Refus&eacute;</option>
      </select></td></tr>
    <tr><td><label for="commentaires">Commentaire</label></td>
      <td><textarea name="commentaires" id="cet-commentaires" ></textarea></td></tr>
    </table>
  </fieldset>
  </form>
</div>
EOD;
?>
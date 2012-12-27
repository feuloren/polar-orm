<?php

require_once 'PolarObject.class.php';

class Utilisateur extends PolarObject {
    public static $table = 'polar_utilisateurs';
    protected static $attrs = array(
        'IPClient' => T_STR,
        'DateCreation' => T_STR,
        'Login' => T_STR,
        'MotDePasse' => T_STR,
        'Email' => T_STR,
        'Staff' => T_BOOL,
        'Nom' => T_STR,
        'Bureau' => T_BOOL,
        'Ancien' => T_BOOL,
        'Responsable' => T_INT,
        'Poste' => T_STR,
        'Presentation' => T_STR,
        'Prenom' => T_STR,
        'Sexe' => array('m', 'f'),
        'Telephone' => T_STR,
        'Newsletter' => T_STR);
    protected static $nulls = array(
        'Responsable', 'Poste',
        'Sexe', 'Telephone');

    public function load_from_ginger() {
      
    }

    public function send_mail($from, $from_nom, $sujet, $html, $pj=array()) {
      sendMail($from, $from_nom, $this->Email, $sujet, $html, $pj);
    }
}

<?php

require_once 'PolarObject.class.php';

class Utilisateur extends PolarObject {
    public static $table = 'polar_utilisateurs';
    protected static $attrs = array(
        'IPClient' => T_IP,
        'DateCreation' => T_DATE,
        'Login' => T_STR,
        'MotDePasse' => T_STR,
        'Email' => T_MAIL,
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
}

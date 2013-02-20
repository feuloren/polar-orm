<?php

require_once 'PolarObject.class.php';

class MouvementCoffre extends PolarObject {
    public static $table = 'polar_caisse_coffre';
    protected static $attrs = array(
        'User' => 'Utilisateur',
        'Date' => T_STR,
        'Billets' => T_INT,
        'Pieces' => T_FLOAT,
        'Commentaire' => T_STR);
    protected static $nulls = array();

}
?>

<?php

require_once 'PolarObject.class.php';

class Evenement extends PolarObject {
    public static $table = 'polar_evenementiel';
    protected static $attrs = array(
        'Date' => T_STR,
        'Lieu' => T_STR,
        'Titre' => T_STR,
        'Description' => T_STR,
        'Auteur' => 'Utilisateur',
        'Creation' => T_STR,
        'Photo' => T_STR);
    protected static $nulls = array();

    private $participants;
}
?>

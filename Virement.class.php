<?php

require_once 'PolarObject.class.php';

class Virement extends PolarObject {
    public static $table = 'polar_caisse_virement';
    protected static $attrs = array(
        'Date' => T_STR,
        'Emetteur' => T_STR,
        'Destinataire' => T_STR,
        'Montant' => T_FLOAT,
        'Effectue' => T_BOOL);
    protected static $nulls = array();

}
?>

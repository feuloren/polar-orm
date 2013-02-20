<?php

require_once 'PolarObject.class.php';

class Cheque extends PolarObject {
    public static $table = 'polar_caisse_cheques';
    protected static $attrs = array(
        'NumVente' => T_INT,
        'Date' => T_STR,
        'Numero' => T_STR,
        'Banque' => T_STR,
        'Montant' => T_STR,
        'Emetteur' => T_STR,
        'Ordre' => T_STR,
        'PEC' => T_BOOL,
        'DateEncaissement' => T_STR,
        'Motif' => T_STR);
    protected static $nulls = array(
        'Numero', 'DateEncaissement', 'Motif');

}
?>

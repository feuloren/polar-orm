<?php

require_once 'PolarObject.class.php';

class FactureAsso extends PolarObject {
    public static $table = 'polar_assos_factures';
    protected static $attrs = array(
        'Asso' => 'Asso',
        'Date' => T_STR,
        'Montant' => T_FLOAT,
        'Encaisse' => T_BOOL,
        'Cheque' => T_INT);
    protected static $nulls = array();

}
?>

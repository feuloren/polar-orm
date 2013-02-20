<?php

require_once 'PolarObject.class.php';

class PaiementAsso extends PolarObject {
    public static $table = 'polar_assos_paiement';
    protected static $attrs = array(
        'Asso' => 'Asso',
        'Date' => T_STR,
        'Convention' => 'ConventionAsso',
        'But' => T_STR,
        'Montant' => T_FLOAT,
        'Detail' => T_STR,
        'TypeSignataire' => T_STR,
        'Signataire' => T_STR,
        'Cheque' => T_INT,
        'DateVirement' => T_STR);
    protected static $nulls = array(
        'DateVirement');

}
?>

<?php

require_once 'PolarObject.class.php';

class ConventionAsso extends PolarObject {
    public static $table = 'polar_assos_conventions';
    protected static $attrs = array(
        'Asso' => 'Asso',
        'Date' => T_STR,
        'But' => T_STR,
        'PolarSengage' => T_STR,
        'AssoSengage' => T_STR,
        'TypeSignataire' => T_STR,
        'NomSignataire' => T_STR,
        'Attestation' => T_BOOL);
    protected static $nulls = array();

}
?>

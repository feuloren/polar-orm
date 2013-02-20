<?php

require_once 'PolarObject.class.php';

class Secteur extends PolarObject {
    public static $table = 'polar_secteurs';
    protected static $attrs = array(
        'Secteur' => T_STR,
        'Code' => T_INT);
    protected static $nulls = array();
}
?>

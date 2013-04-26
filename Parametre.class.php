<?php

require_once 'PolarObject.class.php';

class Parametre extends PolarObject {
    public static $table = 'polar_parametres';
    private static $cache = array();

    public static function get($nom) {
        if (in_array($nom, self::$cache)) {
            return self::$cache[$nom];
        } else {
            $r = PolarObject::$db->q_select('Parametre')
                ->select('Valeur')->where('Nom LIKE ?', $nom)
                ->rawExecute();
            $value = $r->fetchColumn();

            if ($value != False)
                self::$cache[$nom] = $value;

            return $value;
        } 
    }
}
<?php

require_once 'PolarObject.class.php';

class News extends PolarObject {
  public static $table = 'polar_news';
    protected static $attrs = array(
        'Auteur' => 'Utilisateur',
        'Titre' => T_STR,
        'News' => T_STR,
        'Date' => T_STR,
        'Etat' => array('online', 'offline'));
    protected static $nulls = array();

}
?>

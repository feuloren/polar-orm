<?php

require_once 'PolarObject.class.php';

class Article extends PolarObject {
    public static $table = 'polar_caisse_articles';
    protected static $attrs = array(
        "CodeCaisse" => T_INT,
        "EnVente" => T_BOOL,
        "Actif" => T_BOOL,
        "Nom" => T_STR,
        "Secteur" => T_INT,
        "PrixAchat" => T_FLOAT,
        "PrixVente" => T_FLOAT,
        "PrixVenteAsso" => T_FLOAT,
        "TVA" => T_FLOAT, #TODO différencier tva achat et vente (ex bouquins)
        "Palier1" => T_INT,
        "Remise1" => T_FLOAT,
        "Palier2" => T_INT,
        "Remise2" => T_FLOAT,
        "StockInitial" => T_INT,
        "Stock" => T_INT,
        "SeuilAlerte" => T_INT,
        "CodeJS" => T_STR,
        "Photo" =>  T_STR,
        "EAN13" => T_STR,
        "Auteur" => 'Utilisateur',
        "Date" => T_DATE);
    protected static $nulls = array(
        'StockInitial', 'Stock', 'SeuilAlerte',
        'EAN13', 'Auteur');
}

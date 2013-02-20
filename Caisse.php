<?php

require_once 'PolarObject.class.php';

class Caisse extends PolarObject {
  public static $table = 'polar_caisses';
  protected static $attrs = array(
      'Mouvement' => 'MouvementCoffre',
      'Details' => 'DetailsCaisse',
      'TheoriqueCB' => T_FLOAT,
      'ReelCB' => T_FLOAT,
      'TheoriqueMoneo' => T_FLOAT,
      'ReelMoneo' => T_FLOAT,
      'TheoriqueEspeces' => T_FLOAT,
      'ReelEspeces' => T_FLOAT,
      'DernierIDVente' => T_INT,
      'TotalAvant' => T_FLOAT,
      'TotalAprès' => T_FLOAT,
      'Commentaire' => T_STR);
  protected static $nulls = array();

}
?>

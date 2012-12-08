<?php

require_once 'PolarObject.class.php';

class Vente extends PolarObject {
    public static $table = 'polar_caisse_ventes';
    protected static $attrs = array(
        'IDVente' => T_INT,
        'Article' => 'Article',
        'Date' => T_DATE,
        'Tarif' => array('asso', 'normal'),
        'Finalise' => T_BOOL,
        'Asso' => 'Asso',
        'Client' => T_STR,
        'Facture' => T_INT,
        'PrixFacture' => T_FLOAT,
        'MontantTVA' => T_FLOAT,
        'MoyenPaiement' => array('cb', 'moneo', 'cheque', 'especes', 'asso', 'payutc'),
        'Quantite' => T_INT,
        'Permanencier' => 'Utilisateur');
    protected static $nulls = array(
        'Asso');
        
    public function __construct($article, $qte, $paiement, $permanencier,
                                $tarif='normal', $asso=NULL, $client='',
                                $idvente=NULL) {
        if ($qte instanceof PolarDB) {
            parent::__construct($article, $qte);
        }
        else {
            if ($idvente==NULL) $idvente = self::generate_idvente();
            
            if (!($article instanceof Article))
                throw new InvalidValue('$article n\'est pas un article');
            if ($qte <= 0)
                throw new InvalidValue('Quantite <= 0');
            
            $attrs = array('IDVente' => $idvente,
                           'Article' => $article,
                           'Date' => 'NOW()',
                           'Finalise' => 0,
                           'Asso' => $asso,
                           'Client' => $client,
                           'Facture' => 0, #TODO
                           'Tarif' => $tarif,
                           'MoyenPaiement' => $paiement,
                           'Quantite' => $qte,
                           'Permanencier' => $permanencier);
            parent::__construct($attrs);
        }
    }
    
    public function __set($attr, $value) {
        if ($attr === 'Quantite' and is_int($value)) {
            $this->values[$attr] = $value;
            $prix = $this->calcule_prix();
            $this->PrixFacture = $prix[0];
            $this->MontantTVA = $prix[1];
        } else {
            parent::__set($attr, $value);
        }
    }
    
    private function calcule_prix() {
        //if ($this->
        // On gère la remise sur quantité
        // et les tarifs asso #TODO
        $a = $this->Article;
        $q = $this->Quantite;
        if ($this->Tarif === 'normal') {
            $prix = $q * $a->PrixVente;
            if ($q >= $a->Palier1) {
                if ($a->Palier2 != NULL and $a->Palier2 > 0 and $q >= $a->Palier2)
                    $prix *= (1-$a->Remise2);
                else
                    $prix *= (1-$a->Remise1);
            }
        }
        else {
            $prix = $q * $a->PrixVenteAsso;
        }
        $tva = ($prix - ($q * $a->PrixAchat)) * $a->TVA;
        if ($tva < 0) $tva = 0;
        return array($prix, $tva);
    }
    
    public function create_similaire($article, $qte) {
        return new Vente($article, $qte, $this->MoyenPaiement,
                         $this->Permanencier, $this->Tarif, $this->Asso,
                         $this->Client, $this->IDVente);
    }
    
    static function generate_idvente() {
        return 1;
    }
}

class OldVente extends Vente {
    public static $table = 'polar_caisse_ventes_global';
}
?>

<?php

require_once 'PolarObject.class.php';

class Commande extends PolarObject {
    public static $table = 'polar_commandes';
    protected static $attrs = array(
        'Type' => 'TypeCommande',
        'Nom' => T_STR,
        'Prenom' => T_STR,
        'Mail' => T_STR,
        'Asso' => 'Asso',
        'DateCommande' => T_STR,
        'IPCommande' => T_STR,
        'DatePaiement' => T_STR,
        'IDVente' => 'Vente',
        'DatePrete' => T_STR,
        'IDPreparateur' => 'User',
        'DateRetrait' => T_STR,
        'IDRetrait' => 'Utilisateur',
        'DateRetour' => T_STR,
        'IDRetour' => 'Utilisateur',
        'Termine' => T_BOOL);
    protected static $nulls = array(
        'Nom', 'Prenom', 'Mail', 'Asso',
        'DateCommande', 'IPCommande',
        'DatePaiement', 'IDVente',
        'DatePrete', 'IDPreparateur',
        'DateRetrait', 'IDRetrait',
        'DateRetour', 'IDRetour');
    private $details;

    function __construct($data=NULL) {
        parent::__construct($data);
        $this->details = $this->db->fetchAll('ContenuCommande',
                                             'IDCommande = '.$this->get_id());
    }

    function set_payee($idvente) {
        $this->DatePaiement = 'NOW()';
        $this->IDVente = $idvente;
    }
        
    function set_prete($preparateur) {
        $this->IDPreparateur = $preparateur;
        $this->DatePrete = "NOW()";
    }
    
    function set_retiree($preparateur, $termine=0) {
        $this->IDRetrait = $preparateur;
        $this->DateRetrait = "NOW()";
    }
    
    function set_retour($preparateur, $termine=1) {
        $this->IDRetour = $preparateur;
        $this->DateRetour = "NOW()";
    }

    function get_details() {
        return $this->details;
    }

    function add_detail($type, $qte=NULL) {

    }
}

class CommandeProjecteur extends Commande {
    public function __construct($data=NULL, $db=NULL, $type='vp-etu') {

    }
}
?>

<?php

require_once 'PolarObject.class.php';

class Asso extends PolarObject {
    public static $table = 'polar_assos';
    protected static $attrs = array(
        'MailAsso' => T_STR,
        'Asso' => T_STR,
        'President' => T_STR,
        'MailPresident' => T_STR,
        'TelPresident' => T_STR,
        'Tresorier' => T_STR,
        'MailTresorier' => T_STR,
        'TelTresorier' => T_STR,
        'MotDePasse' => T_STR,
        'DateCreation' => T_STR,
        'Etat' => array('Actif', 'DefautPaiement',
                        'AttenteActivation', 'Clos',
                        'Supprime'));
    protected static $nulls = array(
        'MailPresident', 'TelPresident',
        'MailTresorier', 'TelTresorier');

    public function sendMail($from, $from_nom, $sujet, $html, $pj=array()) {
        $recipients = array($this->MailAsso);
        if ($this->MailTresorier !== NULL)
            $recipients[] = $this->MailTresorier;
        if ($this->MailPresident !== NULL)
            $recipients[] = $this->MailPresident;
        
        sendMail($from, $from_nom, $recipients, $sujet, $html, $pj);
    }
}
?>

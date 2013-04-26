<?php

require_once 'PolarObject.class.php';

class Utilisateur extends PolarObject {
    public static $table = 'polar_utilisateurs';
    protected static $attrs = array(
        'IPClient' => T_STR,
        'DateCreation' => T_STR,
        'Login' => T_STR,
        'MotDePasse' => T_STR,
        'Email' => T_STR,
        'Staff' => T_BOOL,
        'Nom' => T_STR,
        'Bureau' => T_BOOL,
        'Ancien' => T_BOOL,
        'Responsable' => T_INT,
        'Poste' => T_STR,
        'Presentation' => T_STR,
        'Prenom' => T_STR,
        'Sexe' => array('m', 'f'),
        'Telephone' => T_STR,
        'Newsletter' => T_STR);
    protected static $nulls = array(
        'Responsable', 'Poste',
        'Sexe', 'Telephone');

    public static function create_from_ginger($ginger, $login) {
        try {
            $user = $ginger->getUser($login);
        } catch (ApiException $e) {
            return false;
        }
        // on vérifie si l'utilisateur avec ce login existe déjà
        //if (Utilisateur::$db->query("SELECT COUNT(*) FROM polar_utilisateurs WHERE Login LIKE '$login'")->fetchColum() == 0) {
            // On crée un nouvel Utilisateur avec les valeurs
            // par défaut et celles obtenues depuis ginger
            return new Utilisateur(array('IPClient' => get_ip(),
                                         'DateCreation' => 'NOW()',
                                         'Login' => $login,
                                         'MotDePasse' => md5(genererAleatoire(8)),
                                         'Email' => $user->mail,
                                         'Presentation' => 'Ce membre n&rsquo;a pas encore de pr&eacute;sentation.',
                                         'Nom' => $user->nom,
                                         'Prenom' => $user->prenom,
                                         'Newsletter' => 1));
            //} else {
            //return false;
            //}
    }

    public function send_mail($from, $from_nom, $sujet, $html, $pj=array()) {
      //sendMail($from, $from_nom, $this->Email, $sujet, $html, $pj);
    }
}

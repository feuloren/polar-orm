<?php

require_once 'PolarObject.class.php';
require_once 'PolarAssociation.class.php';

class Page extends PolarObject {
    public static $table = 'polar_securite_pages';
    protected static $attrs = array(
        'Titre' => T_STR,
        'Menu' => T_STR,
        'Acces' => array('public', 'member', 'staff', 'private'),
        'Module' => T_STR,
        'Section' => T_STR,
        'Postit' => array('module', 'section'));
    protected static $nulls = array(
        'Menu', 'Module', 'Section', 'Postit');
        
    public $droits = NULL;
    
    public function __construct($data=NULL) {
        parent::__construct($data);
        $this->droits = new DroitsPage($this::$db, $this->get_id());
    }
    
    public function add_authorisation($user) {
        if ($this->Acces === 'private') {
            $this->droits->add($user);
        }
    }
    
    public function remove_authorisation($user) {
        if ($this->Acces === 'private') {
            $this->droits->remove($user);
        }
    }
    
    public function set_id($id) {
        parent::set_id($id);
        $this->droits->set_id($id);
    }
    
    public function get_dependants() {
        return array($this->droits);
    }
}

class DroitsPage extends PolarAssociation {
    public function __construct($db, $id=NULL) {
        parent::__construct('polar_securite_droits', 'Page', 'ID',
                            'Utilisateur', 'User', $db, $id);
    }
}

?>

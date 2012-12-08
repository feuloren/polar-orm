<?php

require_once 'PolarObject.class.php';

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
        
    private $droits;
    
    public function __construct($data=NULL, $db=NULL) {
        $this->droits = new DroitsPage($data, $db);
        parent::__construct($data, $db);
    }
    
    public function add_authorisation($user) {
        if ($this->Access === 'private') {
            $this->droit->add($user);
        }
    }
    
    public function remove_authorisation($user) {
        if ($this->Access === 'private') {
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

class DroitsPage implements PolarSaveable {
    public static $table = 'polar_securite_droits';
    public $id;
    public $droits;
    public $to_remove;
    public $to_add;

    public function __construct($page=NULL, $db=NULL) {
        $this->id = $page;
        $this->droits = array();
        $this->to_add = array();
        $this->to_remove = array();
    }

    public function save() {
        $returns = array();
        
        if (!empty($this->to_add)) {
            $tmp = array();
            foreach ($this->to_add as $user_id) {
                $this->droits[] = $user_id;
                $tmp[] = '('.$this->id.','.$user_id.')';
            }
            $this->to_add = array();
            $returns[] = "INSERT INTO ".$this::$table." VALUES ".implode($tmp, ',');
        }
        
        if (!empty($this->to_remove)) {
            $tmp = array();
            foreach ($this->to_remove as $user_id) {
                $this->droits = array_diff($this->droits, array($user_id)); // PHP sucks
                $tmp[] = "User=$user_id";
            }
            $this->to_remove = array();
            $returns[] = "DELETE FROM ".$this::$table." WHERE ID=".$this->id." AND (".implode($tmp, ' OR ').")";
        }
        
        return $returns;
    }
    
    public function get_necessaires() {
        return array_merge($this->to_add, $this->to_remove);
    }
    
    public function get_dependants() {
        return array();
    }
    
    public function add($user) {
        if (!in_array($user, $this->droits) AND !in_array($user, $this->to_add)) {
            $this->to_add[] = $user;
        }
    }
    
    public function remove($user) {
        if (in_array($user, $this->droits) AND !in_array($user, $this->to_remove)) {
            $this->to_remove[] = $user;
        }
        if (in_array($user, $this->to_add)) {
            $this->to_add = array_diff($this->to_add, array($user));
        }
    }
    
    public function set_id($id) {
        if ($this->id === NULL) {
            $this->id = $id;
        }
    }
    
    public function get_id() {
        return $this->id;
    }
}
?>

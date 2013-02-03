<?php

require_once 'PolarObject.class.php';

/** PolarAssociation
 * Représente une partie de la table de jointure entre les objets $start et $destination
 * 
 * On donne l'ID de l'objet de départ et PolarAssociation permet d'ajouter ou de supprimer des objets $destination liés à cet objet
 *
 * Ex : si $start = 'Page' et $destination = 'Utilisateur' et
 *         $table = 'polar_securite_droits'
 *      alors l'association représentera la liste des utilisateur qui ont le droit d'accèder à cette page
 */
class PolarAssociation implements PolarSaveable {
    public $table;
    private $id;
    private $db;
    protected $start; // object to which you associate the others $destination $object
    protected $startName; // column name for the start object
    protected $destination;
    protected $destName; // column name for the destination objects
    public $list;
    public $to_remove;
    public $to_add;
   
    public function __construct($table, $start, $startName, $destination,
                                $destName, $db, $id=NULL) {
        $this->id = $id;
        $this->db = $db;
        $this->table = $table;
        $this->start = $start;
        $this->startName = $startName;
        $this->destination = $destination;
        $this->destName = $destName;
        $this->list = array();
        $this->to_add = array();
        $this->to_remove = array();
        $this->load_from_db();
    }

    public function load_from_db() {
        if ($this->id != NULL) {
            $query = 'SELECT `'. $this->destName .'` FROM '.
                $this->table . ' WHERE ' .
                $this->startName .' = '.
                $this->id;
            $result = $this->db->query('SELECT `'. $this->destName .'` FROM '.
                                       $this->table. ' WHERE ' .
                                       $this->startName .' = '.
                                       $this->id);
            foreach ($result as $dest)
                $this->list[] = $dest[$this->destName];
        }
    }

    public function save() {
        $returns = array();
        
        if (!empty($this->to_add)) {
            $tmp = array();
            foreach ($this->to_add as $object) {
                $this->list[] = $object;
                $tmp[] = '('.$this->id.','.format_attr($object).')';
            }
            $this->to_add = array();
            $returns[] = "INSERT INTO ".$this->table." VALUES ".implode($tmp, ',');
        }
        
        if (!empty($this->to_remove)) {
            $tmp = array();
            foreach ($this->to_remove as $object) {
                $this->list = array_diff($this->list, array($object)); // PHP sucks
                $tmp[] = $this->destName.'='.format_attr($object);
            }
            $this->to_remove = array();
            $returns[] = 'DELETE FROM '.$this->table.' WHERE '.$this->startName.'='.$this->id.' AND ('.implode($tmp, ' OR ').')';
        }
        
        return $returns;
    }
    
    public function get_necessaires() {
        return array_merge($this->to_add, $this->to_remove);
    }
    
    public function get_dependants() {
        return array();
    }
    
    /** check_destination
     * Vérifie si l'objet passé est bien du type $destination
     * ou si l'entier $dest est un identifiant valide dans la table de destination
     */
    public function check_destination($dest) {
        return ($dest instanceof $this->destination) or
            (is_int($dest) and $this->db->validObject($this->destination,
                                                      $dest));
    }

    public function add($dest) {
        if (!$this->check_destination($dest))
            throw new InvalidValue('$dest must be or a '.$this->destination.' valid ID in the table'.$this->destination->$table);

        if (!in_array($dest, $this->list) AND !in_array($dest, $this->to_add)) {
            $this->to_add[] = $dest;
        }
    }
    
    public function remove($dest) {
        if (!$this->check_destination($dest))
            throw new InvalidValue('$dest must be or a '.$this->destination.' valid ID in the table'.$this->destination->$table);

        if (in_array($dest, $this->list) AND !in_array($dest, $this->to_remove)) {
            $this->to_remove[] = $dest;
        }
        if (in_array($dest, $this->to_add)) {
            $this->to_add = array_diff($this->to_add, array($dest));
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
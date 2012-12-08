<?php

const T_STR = 'string';
const T_INT = 'integer';
const T_FLOAT = 'double';
const T_BOOL = 'boolean';
const T_DATE = 'date';
const T_MAIL = 'mail';
const T_IP = 'ip';
$STATIC_TYPES = array(T_STR, T_INT, T_FLOAT, T_BOOL, T_DATE, T_MAIL, T_IP);

class InvalidAttribute extends Exception { }
class InvalidType extends Exception {
    function __construct($attr, $obj_type, $expected) {
        parent::__construct("Expected '$expected' but got '$obj_type' for attribute '$attr'");
    }}
class NotNullable extends Exception { }
class InvalidValue extends Exception { }
class InvalidModel extends Exception { }
class AttributesNotComplete extends Exception { }

function type_is_object($type) {
    global $STATIC_TYPES;
    return !(in_array($type, $STATIC_TYPES) or is_array($type));
}

function format_attr($value) {
    switch (gettype($value)) {
    case 'string':
        return '"'.$value.'"';
    case 'integer':
    case 'double':
        return (string) $value;
    case 'boolean':
        return ($value ? "1" : "0");
    case 'object':
        return (string) $value->get_id();
    }
}

/*
Interface pour les objets sauvegardable dans la base de données:

Les fonctions get_necessaires() et get_dependants() doivent renvoyer respectivement
les objets dont l'id est nécessaire à la sauvegarde de l'objet actuel et les objets
dont l'ID de l'objet actuel est nécessaires à la sauvegarde.

La fonction save peut renvoyer une requête sql sous forme de chaine de caractères
ou un array de de requêtes qui seront exécutées en une transaction.

Lors de la sauvegarde les fonctions sont exécutées dans cet ordre
get_necessaires()
save()
get_dependants()

Voir le code de PolarDB::save pour plus de détails
*/
interface PolarSaveable
{
    public function save();
    public function get_necessaires();
    public function get_dependants();
}

/*
PolarObject

Représente un objet dans la base de donnée;
c'est à dire toute ligne qui possède un champ ID primaire

Pour chaque table avec un champ ID primaire,
 il faut créer une classe dérivée en modifiant les attributs
 $attrs, $nulls et $table
On peut aussi définir des fonctions relatives à l'objet représenté
 qui permettent de manipuler plus facilement cet objet.
*/
abstract class PolarObject implements PolarSaveable {
    private $id = NULL;
    private $db = NULL;
    protected static $attrs = array();
    protected static $nulls = array();
    protected $values;
    protected $modified;
    protected static $table;

    public function __construct($data=NULL, $db=NULL) {
        if ($db === NULL)
            $this->__construct_from_data($data);
        else
            $this->__construct_from_db($data, $db);
    }

    /*
    Crée un nouvel objet à partir d'un tableau clé => valeur
    La validité des clés et des valeurs est vérifiée et une exception sera renvoyé
     en cas de problème.
    L'identifiant de l'objet est NULL, il faut sauvegarder l'objet en utilisant
     PolarDB::save pour l'enregistrer et lui attribuer un ID
    */
    private function __construct_from_data($data=NULL) {
        $this->values = array();
        if ($data != NULL) {
            foreach ($data as $key => $value) {
                $this->__set($key, $value);
            }
        }
    }
    
    /*
    Crée un nouvel objet à partir de la base de donnée
    Les objets liés sont automatiquement chargés
    */
    private function __construct_from_db($data, $db) {
        $this->set_db($db);
        
        //var_dump($data);
        foreach ($this::$attrs as $key => $type) {
            if (!array_key_exists($key, $data))
                throw new InvalidModel("Key '$key' expected but not present in the database");
            if (type_is_object($type) and $data[$key] !== NULL and $data[$key] != 0) {
                $object = $db->fetchOne($type, $data[$key]);
                $this->__set($key, $object);
            } else {
                $this->__set($key, $data[$key]);
            }
        }
        $this->set_id($data['ID']);
    }
    
    public function __get($attr) {
        if (!array_key_exists($attr, $this::$attrs))
            throw new InvalidAttribute($attr);

        return $this->values[$attr];
    }
    
    public function __set($attr, $value) {
        if (!array_key_exists($attr, $this::$attrs))
            throw new InvalidAttribute($attr);

        if ($value === NULL) {
            if (in_array($attr, $this::$nulls))
                $this->values[$attr] = $value;
            else
                throw new NotNullable($attr);
        }
        else if (is_array($this::$attrs[$attr])) {
            if (in_array($value, $this::$attrs[$attr]))
                $this->values[$attr] = $value;
            else
                throw new InvalidValue("$value not in allowed values for $attr");
        }
        else {
            $val_type = gettype($value);
            $expected = $this::$attrs[$attr];
            switch ($val_type) {
            case 'integer':
                if ($expected === T_FLOAT or $expected === T_INT)
                    $this->values[$attr] = $value;
                elseif ($expected === T_BOOL)
                    $this->values[$attr] = (bool) $value;
                else
                    throw new InvalidType($attr, $val_type, $expected);
                break;
            case 'boolean':
                if ($expected === T_BOOL)
                    $this->values[$attr] = $value;
                else 
                    throw new InvalidType($attr, $val_type, $expected);
                break;
            case 'double':
                if ($expected === T_FLOAT)
                    $this->values[$attr] = $value;
                else 
                    throw new InvalidType($attr, $val_type, $expected);
                break;
            case 'string':
                if ($expected == T_STR)
                    $this->values[$attr] = $value;
                elseif ($expected === T_IP and
                        preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/i", $value))
                    $this->values[$attr] = $value;
                elseif ($expected === T_MAIL and
                        preg_match("/.*@.*\..*/", $value))
                    $this->values[$attr] = $value;
                elseif ($expected === T_DATE)
                    $this->values[$attr] = $value;
                elseif ($expected === T_BOOL)
                    $this->values[$attr] = (bool) $value;
                elseif ($expected === T_INT)
                    $this->values[$attr] = intval($value);
                elseif ($expected === T_FLOAT)
                    $this->values[$attr] = (double) $value;
                else
                    throw new InvalidType($attr, $val_type, $expected);
                break;
            case 'object':
                $obj_type = get_class($value);
                if ($obj_type === $expected)
                    $this->values[$attr] = $value;
                else
                    throw new InvalidType($attr, $obj_type, $expected);
                break;
            default:
                throw new InvalidType($attr, $val_type, $expected);
            }
        }
        if ($this->id !== NULL)
            $this->modified[$attr] = 1;
    }
    
    public function set_db($db) {
        $this->db = $db;
    }
    
    public function get_id() {
        return $this->id;
    }
    
    public function set_id($id) {
        if ($this->id == NULL)
            $this->id = $id;
    }
    
    public function get_necessaires() {
        $objs = array();
        
        foreach ($this->values as $obj) {
            if ($obj instanceof PolarSaveable)
                $objs[] = $obj;
        }
        return $objs;
    }
    
    public function get_dependants() {
        return array();
    }
    
    public function save() {
        if (empty($this->modified))
            return "";
    
        $query = 'UPDATE '.$this::$table.' SET ';
        foreach ($this->modified as $attr => $thing) {
            $value = $this->values[$attr];
            $query .= " `$attr`=".format_attr($value).",";
        }
        $query = substr($query, 0, -1);
        $query .= ' WHERE ID='.$this->id;
        
        $this->modified = array();
        
        return $query;
    }
}
?>

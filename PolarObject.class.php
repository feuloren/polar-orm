<?php

const T_STR = 'string';
const T_INT = 'integer';
const T_FLOAT = 'double';
const T_BOOL = 'boolean';
$STATIC_TYPES = array(T_STR, T_INT, T_FLOAT, T_BOOL);

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
        // un peu violent mais facilite la vie
        if ($value === 'NOW()')
            return $value;
        else
            return '"'.$value.'"';
    case 'integer':
    case 'double':
        return (string) $value;
    case 'boolean':
        return ($value ? "1" : "0");
    case 'object':
        return (string) $value->get_id();
    default:
        return "NULL";
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
    protected $db = NULL;
    protected static $attrs = array();
    protected static $nulls = array();
    protected $values;
    protected $modified;
    protected static $table;

    public function __construct(array $data=NULL, PolarDB $db=NULL) {
        if ($db == NULL)
            $this->db = new FakeDB();
        else
            $this->db = $db;

        if ($data === NULL or !isset($data['ID']))
            $this->__construct_from_data($data);
        else
            $this->__construct_from_db($data);
    }

    /*
    Crée un nouvel objet à partir d'un tableau clé => valeur
    La validité des clés et des valeurs est vérifiée et une exception sera renvoyé
     en cas de problème.
    L'identifiant de l'objet est NULL, il faut sauvegarder l'objet en utilisant
     PolarDB::save pour l'enregistrer et lui attribuer un ID
    */
    private function __construct_from_data(array $data) {
        $this->values = array();
        if ($data !== NULL) {
            foreach ($data as $key => $value) {
                $this->__set($key, $value);
            }
        }
    }

    /*
    Crée un nouvel objet à partir de la base de donnée
    Les objets liés sont automatiquement chargés
    */
    private function __construct_from_db(array $data) {
        foreach ($this::$attrs as $key => $type) {
            if (!array_key_exists($key, $data))
                throw new InvalidModel("Key '$key' expected but not present in the database");
            if (type_is_object($type) and $data[$key] !== NULL and $data[$key] != 0) {
                $this->__set($key, intval($data[$key]));
            } else {
                $this->__set($key, $data[$key]);
            }
        }
        $this->set_id((int) $data['ID']);
    }

    public function __get($attr) {
        if (!array_key_exists($attr, $this::$attrs))
            throw new InvalidAttribute($attr);

        if (!array_key_exists($attr, $this->values))
            return NULL;

        if (type_is_object($this::$attrs[$attr]) and
            is_int($this->values[$attr])) {
            $o = $this->db->fetchOne($this::$attrs[$attr],
                                     $this->values[$attr]);
            $this->values[$attr] = $o;
        }
        return $this->values[$attr];
    }

    public function __set($attr, $value) {
        if (!array_key_exists($attr, $this::$attrs))
            throw new InvalidAttribute($attr);

        $expected = $this::$attrs[$attr];
        // 1er cas : si la value est null
        // et qu'on a pas le droit : on renvoie une exception
        if ($value === NULL) {
            if (in_array($attr, $this::$nulls))
                $this->values[$attr] = $value;
            else
                throw new NotNullable($attr);
        }
        // Deuxième cas : si on a un enum
        // On teste si la valeur est autorisée sinon exception
        else if (is_array($expected)) {
            if (in_array($value, $expected))
                $this->values[$attr] = $value;
            else
                throw new InvalidValue("$value not in allowed values for $attr");
        }
        // 3eme cas : on attend un objet,
        // Si on reçoie un objet de la bonne classe c'est cool
        // Si c'est un int on vérifie que la ligne existe bien dans la base
        // Sinon exception
        else if (type_is_object($expected)) {
            if (is_object($value) and get_class($value) === $expected)
                $this->values[$attr] = $value;
            else if (is_int($value)) {
                if($this->db->validObject($expected, $value))
                    $this->values[$attr] = $value;
                else
                    throw new InvalidValue('No object \''.$expected
                                           .'\' has id '.$value);
            }
            else
                throw new InvalidType($attr, gettype($value), $expected);
        }
        // Finalement on va tenter de convertir la valeur reçue vers la type attendu
        // En cas d'échec on balance une exception
        else {
            $val_type = gettype($value);
            if (settype($value, $expected))
                $this->values[$attr] = $value;
            else
                throw new InvalidValue("Could not convert $value from $val_type to $expected");
        }
        if ($this->id !== NULL)
            $this->modified[$attr] = 1;
    }

    public function set_db(PolarDB $db) {
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
        if ($this->id === NULL)
            return $this->initial_insert();

        if (empty($this->modified))
            return ";";

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

    private function initial_insert() {
        if ($this->id !== NULL)
            return "";

        $fields = array();
        $values = array();
        foreach ($this::$attrs as $key => $value) {
            $fields[] = $key;
            $values[] = format_attr($this->values[$key]);
        }
        $values = implode($values, ',');
        $fields = implode($fields, ',');
        $r = 'INSERT INTO '.$this::$table.' ('.$fields.') VALUES ('.$values.')';
        return $r;
    }
}

class FakeDB {
    public function save($obj) {
    }

    public function fetchOne($type, $query) {
        return $query;
    }

    public function fetchAll($type, $query='1', $limit=NULL) {
        return $query;
    }

    public function validObject($type, $query) {
        return False;
    }
}
?>

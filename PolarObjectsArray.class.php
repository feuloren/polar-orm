<?php

require_once 'PolarObject.class.php';

class InvalidMethod extends Exception { }

/* PolarObjectsArray
 * 
 * Représente un ensemble d'objets issus de la base de donnée
 * Les objets peuvent être récupérés un par un comme un itérateur
 * On peut aussi appeler une méthode définie par la classe d'objets stockés
 *  La méthode sera appelée sur tous les objets stockés avec les paramètres
 *  passés
 */
class PolarObjectsArray implements PolarSaveable, ArrayAccess, Countable, Iterator {
    private $objs;
    private $position;
    private $type;
    public static $db;

    public function __construct($type, $lazy=false) {
        $this->objs = array();
        $this->position = 0;
        $this->type = $type;
        $this->lazy = $lazy;
    }

    // Implémentation de PolarSaveable
    function get_necessaires() {
        return array();
    }

    function save() {
        return ";";
    }

    function get_dependants() {
        return $this->objs;
    }

    // Implémentation de ArrayAccess, Coutable et Iterable
    function rewind() {
        $this->position = 0;
    }

    function current() {
        return $this->getObj($this->position);
    }

    function key() {
        return $this->position;
    }

    function next() {
        ++$this->position;
    }

    function valid() {
        return isset($this->objs[$this->position]);
    }

    public function offsetSet($offset, $value) {
        if (!($value instanceof $this->type or is_int($value))) {
            throw new InvalidValue("Object must a ".$this->type);
        }

        if (is_null($offset))
            $this->objs[] = $value;
        else
            $this->objs[$offset] = $value;
    }

    public function offsetExists($offset) {
        return isset($this->objs[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->objs[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->objs[$offset]) ? $this->getObj($offset) : null;
    }
    
    public function count() {
        return count($this->objs);
    }

    private function getObj($offset) {
        if (!isset($this->objs[$offset]))
            return null;

        $o = $this->objs[$offset];
        if (is_int($o)) {
            $new_o = $this::$db->fetchOne($this->type, $o);
            $this->objs[$offset] = $new_o;
            return $new_o;
        } else {
            return $o;
        }
                
    }

    /**
     * __call
     * Appelle la méthode demandée sur tous les objets de l'array
     *   et renvoie les réponses dans un tableau
     */
    public function __call($func, $args) {
        if (count($this->objs) == 0)
            return array();
        
        if (!method_exists($this->objs[0], $func))
            throw new InvalidMethod("No method $func for object "
                                     .get_class($this->objs[0]));

        $result = array();
        foreach ($this->objs as $obj)
            $result[] = call_user_func_array(array($obj, $func), $args);

        return $result;
    }

    public function to_json($attributes = NULL) {

    }
}
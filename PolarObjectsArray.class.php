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
class PolarObjectsArray implements ArrayAccess, Countable, Iterator {
    private $objs;
    private $position;

    public function __construct() {
        $this->objs = array();
        $this->position = 0;
    }

    function rewind() {
        $this->position = 0;
    }

    function current() {
        return $this->objs[$this->position];
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
        return isset($this->objs[$offset]) ? $this->objs[$offset] : null;
    }
    
    public function count() {
        return count($this->objs);
    }

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
}
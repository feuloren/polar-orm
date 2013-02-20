<?php

require_once 'PolarObject.class.php';
require_once 'PolarObjectsArray.class.php';

class PolarDB {
    private $db;
    private $select_req;
    private $objects_store;

    public function __construct($server, $base, $user, $pass) {
            /* Connexion à une base ODBC avec l'invocation de pilote */
        #global $CONF;
        $dsn = "mysql:dbname=$base;host=$server";

        //try {
            $this->db = new PDO($dsn, $user, $pass,
                                array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
        //} catch (PDOException $e) {
            //echo 'Connexion échouée : ' . $e->getMessage();
        //}
    }

    public function save() {
        $this->saving = array();
        call_user_func_array(array($this, 'iter_save'), func_get_args());
        $this->saving = array();
    }

    private function iter_save() {
        foreach (func_get_args() as $obj) {
            if ($obj instanceof PolarSaveable && !(in_array($obj, $this->saving))) {
                $this->saving[] = $obj;
                call_user_func_array(array($this, 'iter_save'), $obj->get_necessaires());
                $this->do_save($obj);
                call_user_func_array(array($this, 'iter_save'), $obj->get_dependants());
            }
        }
    }

    public function fetchOne($type, $query) {
        // if(in_array($this->objects_store, $query)) ...
        // Si la requête est un entier, on considère que c'est l'ID de l'objet
        if (intval($query) !== 0)
            $query = "ID=$query";
        // On récupère le 1 er élément
        return $this->fetchAll($type, $query, 1)->current();
    }

    public function fetchAll($type, $query='1', $limit=NULL, $lazy=false) {
        if ($lazy)
            $req = "SELECT ID";
        else
            $req = "SELECT *";
        $req .= " FROM ".$type::$table." WHERE $query";
        if ($limit != NULL) $req.= " LIMIT $limit";

        $result = $this->query($req);

        $objects = new PolarObjectsArray($type, $lazy);
        foreach ($result as $ligne) {
            if ($lazy)
                $objects[] = intval($ligne['ID']);
            else {
                $obj = new $type($ligne);
                $objects[] = $obj;
            }
            //$this->objects_store[$type][$] = $obj;
        }

        return $objects;
    }

    public function validObject($type, $id) {
        $r = $this->query('SELECT COUNT(*) FROM '.
                          $type::$table.
                          ' WHERE ID='.$id);

        return $r->fetchColumn() > 0;
    }

    public function query($query) {
        return $this->db->query($query);
    }

    public function delete() {
        foreach (func_get_args() as $obj) {
          if ($obj instanceof PolarObject and !is_null($obj->get_id())) {
              $this->db->exec('DELETE FROM '.$obj::$table.
                              ' WHERE ID='.$obj->get_id());
            }
        }
    }

    ### Private functions

    private function do_save($obj) {
        $todo = $obj->save();

        // La méthode save peut retourner une commande
        // ou une liste de commande à éxécuter
        // Si on n'a qu'une seule commande et que l'objet n'est pas encore
        // sauvegardé, on donne le dernier id généré par mysql à l'objet

        if (is_array($todo)) {
            $this->db->beginTransaction();
            foreach ($todo as $query) {
                $this->db->exec($query);
            }
            $this->db->commit();
        }
        else {
            $this->db->exec($todo);
            if ($obj instanceof PolarObject and $obj->get_id() === NULL) {
                $new_id = $this->db->lastInsertId();
                $obj->set_id($new_id);
                //$this->objects_store[get_class($obj)][$new_id] = $obj;
            }
        }
    }
}
?>

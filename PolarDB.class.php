<?php

require_once 'PolarObject.class.php';
require_once 'PolarObjectsArray.class.php';
require_once 'PolarQuery.class.php';

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
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        //} catch (PDOException $e) {
            //echo 'Connexion échouée : ' . $e->getMessage();
        //}
    }

    /* save
     * save one or multiple objects
     */
    public function save() {
        $this->saving = array();
        call_user_func_array(array($this, 'iter_save'), func_get_args());
        $this->saving = array();
    }

    /* iter_save
     * recursively get and save objets from the initial objects list
     */
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

    /* create_query
     * return a PolarQuery with the right $db reference
     * supported types are QUERY_INSERT, QUERY_SELECT, QUERY_UPDATE, QUERY_SELECT
     */
    public function create_query($type, $class) {
        return new PolarQuery($this, $type, $class);
    }

    /* create_insert_query
     */
    public function create_insert_query($class) {
        return $this->create_query(QUERY_INSERT, $class);
    }

    /* create_select_query
     */
    public function create_select_query($class) {
        return $this->create_query(QUERY_SELECT, $class)->select("$class.*");
    }

    /* create_delete_query
     */
    public function create_delete_query($class) {
        return $this->create_query(QUERY_DELETE, $class);
    }

    /* create_update_query
     */
    public function create_update_query($class) {
        return $this->create_query(QUERY_UPDATE, $class);
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
                $obj = new $type(false);
                $obj->hydrate($ligne);
                $objects[] = $obj;
            }
            //$this->objects_store[$type][$] = $obj;
        }

        return $objects;
    }

    /* validObject
     * Returns true if the row pointed by id 
     */
    public function validObject($type, $id) {
        $r = $this->create_select_query($type)->select('COUNT(*)', 'C')->where('ID=?', $id)->rawExecute();

        return $r->fetchColumn() > 0;
    }

    public function query($query) {
        return $this->db->query($query);
    }

    public function delete() {
        foreach (func_get_args() as $obj) {
          if ($obj instanceof PolarObject and !is_null($obj->get_id())) {
              $obj->delete();
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

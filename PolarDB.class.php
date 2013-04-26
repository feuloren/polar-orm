<?php

require_once 'PolarObject.class.php';
require_once 'PolarObjectsArray.class.php';
require_once 'PolarQuery.class.php';

class PolarDB {
    private $db;
    private $select_req;
    private $objects_store;
    private $nb_query = 0;

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
                $obj->save();
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
    public function q_insert($class) {
        return $this->create_query(QUERY_INSERT, $class);
    }

    /* create_select_query
     */
    public function q_select($class) {
        return $this->create_query(QUERY_SELECT, $class);
    }

    /* create_delete_query
     */
    public function q_delete($class) {
        return $this->create_query(QUERY_DELETE, $class);
    }

    /* create_update_query
     */
    public function q_update($class) {
        return $this->create_query(QUERY_UPDATE, $class);
    }

    /* fetchOne
     * DEPRECATED
     */
    public function fetchOne($type, $query) {
        // if(in_array($this->objects_store, $query)) ...
        // Si la requête est un entier, on considère que c'est l'ID de l'objet
        if (intval($query) !== 0)
            $query = "ID=$query";
        // On récupère le 1 er élément
        return $this->fetchAll($type, $query, 1)->current();
    }

    /* fetchAll
     * DEPRECATED
     */
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
        $r = $this->q_select($type)->select('COUNT(*)')->where('ID=?', $id)->rawExecute();

        return $r->fetchColumn() > 0;
    }

    /* delete
     * delete all the objects passed
     */
    public function delete() {
        foreach (func_get_args() as $obj) {
          if ($obj instanceof PolarObject and !is_null($obj->get_id())) {
              $obj->delete();
            }
        }
    }

    public function get_nb_queries() {
        return $this->nb_query;
    }

    // Access underlying PDO methods

    public function query($query) {
        $this->nb_query++;
        return $this->db->query($query);
    }

    public function lastInsertId() {
        return $this->db->lastInsertId();
    }

    public function beginTransaction() {
        $this->db->beginTransaction();
    }

    public function commit() {
        $this->db->commit();
    }

    public function rollBack() {
        $this->db->rollBack();
    }

    public function inTransaction() {
        return $this->db->inTransaction();
    }
}
?>

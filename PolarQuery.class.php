<?php

define('QUERY_SELECT', 0);
define('QUERY_INSERT', 1);
define('QUERY_DELETE', 2);
define('QUERY_UPDATE', 3);

class WrongValuesNumber extends Exception {};

class PolarQuery {
    private $db;
    protected $type;
    protected $class;
    public $wheres = array();
    public $selects = array(); // alias.nom => as ...
    public $joins = array(); // 
    public $fields = array();
    public $values = array();
    public $orders = array();
    public $groups = array();
    public $limit = NULL;
    public $offset = NULL;

    private $tables = array();

    public function __construct($db, $type, $class) {
        $this->db = $db;

        if (!in_array($type, array(QUERY_SELECT, QUERY_INSERT, QUERY_DELETE, QUERY_UPDATE)))
            throw new InvalidTruc();

        $this->type = $type;
        
        $this->class = $class;
        $this->tables[make_alias($class::$table)] = $class;
    }

    
    public function select($req, $alias=NULL) {
        if ($this->type != QUERY_SELECT)
            throw new InvalidQueryType();

        if ($req instanceof PolarQuery) { //subqueries
            if ($alias == NULL)
                throw new Exception('Must give array for subquery');
            else
                $this->selects[] = array($req, $alias);
        } else { // normal field select
            if ($alias == NULL) {
                $this->selects[] = array($req, NULL);
            } else { 
                $this->selects[] = array($req, $alias);
            } 
        }

        return $this;
    }

    public function where($where) {
        $values = func_get_args();
        array_shift($values);
        $pieces = explode('?', $where);
        if ((count($pieces) - 1) != count($values)) {
            throw new WrongValuesNumber(sprintf("expected %d but got %d", (count($pieces) - 1), count($values)));
        } else {
            $real_where = current($pieces);
            foreach ($values as $val) {
                $real_where .= format_attr($val) . next($pieces);
            }
            $this->wheres[] = $real_where;
        }
        return $this;
    }

    public function order($ordre) {
        if ($this->type != QUERY_SELECT)
            throw new InvalidQueryType();

        $this->orders[] = $ordre;

        return $this;
    }

    /* groupBy
     * Ajoute une colonne de regroupement
     */
    public function groupBy($group) {
        if ($this->type != QUERY_SELECT)
            throw new InvalidQueryType();

        $this->groups[] = $group;

        return $this;
    }

    /* set_value
     * Définit la valeur d'un champ pour INSERT ou UPDATE
     */
    public function set_value($field, $value) {
        $this->fields[] = $field;
        $this->values[] = $value;
        return $this;
    }

    /* join
     * Définit une jointure
     */
    public function join($class, $condition) { // + objets à sélectionner
        $alias = make_alias($class::$table);
        $this->tables[$alias] = $class;
        $this->joins[] = array($class, $alias, $condition);
        return $this;
    }

    public function limit($limit, $offset=NULL) {
        $this->limit = $limit;
        $this->offset = $offset;
    }

    public function get_sql() {
        $sql = '';
        switch($this->type) {
        case QUERY_SELECT:
            $sql = $this->get_sql_select();
            break;
        case QUERY_UPDATE:
            $sql = $this->get_sql_update();
            break;
        case QUERY_DELETE:
            $sql = $this->get_sql_delete();
            break;
        case QUERY_INSERT:
            $sql = $this->get_sql_insert();
            break;
        }
        return $this->replace_classname_by_alias($sql);
    }

    // Select
    protected function get_sql_select() {
        $req = 'SELECT %selects% FROM %main_table% %joins% WHERE %wheres% %group% %order% %limit%';
        $class = reset($this->tables);
        return str_replace(array('%selects%',
                                 '%main_table%',
                                 '%joins%',
                                 '%wheres%',
                                 '%group%',
                                 '%order%',
                                 '%limit%'),
                           array($this->format_selects(),
                                 $class::$table . ' ' . key($this->tables),
                                 $this->format_joins(),
                                 $this->format_wheres(),
                                 $this->format_groups(),
                                 $this->format_order(),
                                 $this->format_limit()),
                           $req);
    }

    public function format_selects() {
        reset($this->tables);
        if (empty($this->selects))
            return key($this->tables).'.*';

        $selects = '';
        foreach($this->selects as $select) {
            if ($select[0] instanceof PolarQuery) {
                $field = '('.$select[0]->get_sql().')';
            } else {
                $field = $select[0];
            }
            if ($select[1] == NULL) {
                $selects .= $field . ', ';
            } else {
                $selects .= $field . ' AS ' . $select[1] . ', ';
            }
        }
        return substr($selects, 0, -2);
    }

    public function format_wheres() {
        if (empty($this->wheres))
            return '1';

        $wheres = '';
        foreach($this->wheres as $where) {
            $wheres .= $where . ' AND ';
        }
        return substr($wheres, 0, -5);
    }

    public function format_order() {
        if (empty($this->orders))
            return '';
        else
            return ' ORDER BY ' . implode(',', $this->orders);
    }

    public function format_groups() {
        if (empty($this->groups))
            return '';
        else
            return ' GROUP BY ' . implode(',', $this->groups);
    }

    public function format_limit() {
        if ($this->limit == NULL) {
            return '';
        } else {
            if ($this->offset == NULL) {
                return ' LIMIT '.$this->limit;
            } else {
                return ' LIMIT '.$this->offset.','.$this->limit;
            }
        }
    }

    protected function replace_classname_by_alias($string) {
        $search = array();
        $replace = array();
        foreach ($this->tables as $alias => $class) {
            $search[] = $class.'.';
            $replace[] = $alias.'.';
        }
        return str_replace($search, $replace, $string);
    }

    public function format_joins() {
        $joins = '';

        foreach($this->joins as $join) {
            $class = $join[0];
            $joins .= ' JOIN '.$class::$table.' '.$join[1].' ON '.$join[2];
        }
        return $joins;
    }

    // Update
    protected function get_sql_update() {
        $req = 'UPDATE %table% SET %sets% WHERE %wheres%';
        $class = reset($this->tables);
        return str_replace(array('%table%',
                                 '%sets%',
                                 '%wheres%'),
                           array($class::$table .' '. key($this->tables),
                                 $this->format_sets(),
                                 $this->format_wheres()),
                           $req);
    }

    public function format_sets() {
        if (count($this->fields) != count($this->values))
            throw new WrongValuesNumber('Use set_value !!!');

        $sets = '';
        foreach($this->fields as $index => $field) {
            $sets .= '`'.$field . '`='.$this->values[$index].', ';
        }
        return substr($sets, 0, -2);
    }

    // Delete
    protected function get_sql_delete() {
        $req = 'DELETE FROM %table% WHERE %wheres%';
        $class = reset($this->tables);
        return str_replace(array('%table%',
                                 '%wheres%'),
                           array($class::$table,
                                 $this->format_wheres()),
                           $req);
    }

    // Insert
    protected function get_sql_insert() {
        $req = 'INSERT INTO %table% (%fields%) VALUES(%values%)';
        $class = reset($this->tables);
        return str_replace(array('%table%',
                                 '%fields%',
                                 '%values%'),
                           array($class::$table,
                                 implode($this->fields, ','),
                                 implode($this->values, ',')),
                           $req);
    }

    /* execute
     * Exécute la requête crée
     * Retourne un objet $class correctement hydraté
     * Ou un PolarObjectsArray contenant tous les objets
     * retournés par la requête
     */
    public function execute() {
        $data = $this->db->query($this->get_sql());

        if ($this->type != QUERY_SELECT)
            return $data;

        $objects = new PolarObjectsArray($this->class);
        foreach ($data as $ligne) {
            $obj = new $this->class(false);
            $obj->hydrate($ligne);
            $objects[] = $obj;
        }
        if (count($objects) == 1)
            return $objects[0];
        else
            return $objects;
    }

    /* rawExecute
     * Execute la requête crée
     * Retourne l'objet PDOStatement résulant sans traitement
     */
    public function rawExecute() {
        return $this->db->query($this->get_sql());
    }
}

function make_alias($table_name) {
    $alias = '';
    foreach (explode('_', $table_name) as $mot)
        $alias .= $mot[0];
    return $alias;
}
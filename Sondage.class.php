<?php

require_once 'PolarObject.class.php';
require_once 'PolarObjectsArray.class.php';
require_once 'QuestionSondage.class.php';

class Sondage extends PolarObject {
    public static $table = 'polar_sondages_v2';
    protected static $attrs = array(
        'Titre' => T_STR,
        'Date' => T_STR,
        'Createur' => 'Utilisateur');
    protected static $nulls = array();

    public $questions = NULL;
    
    public function __construct($data=NULL) {
        parent::__construct($data);
        if ($this->get_id() === NULL)
            $this->questions = new PolarObjectsArray();
        else
            $this->questions = $this::$db->fetchAll('QuestionSondage', '`Sondage` = '.$this->get_id());
    }

    public function add_question($question) {
        $this->questions[] = $question;
    }

    public function create_question($question, $type) {
        $q = new QuestionSondage(array('Question'=>$question,
                                       'Sondage'=>$this,
                                       'type'=>$type));
        $this->add_question($q);
        return $q;
    }

    public function get_dependants() {
        return array($this->questions);
    }
}

?>

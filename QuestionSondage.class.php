<?php

require_once 'PolarObject.class.php';

class QuestionSondage extends PolarObject {
    public static $table = 'polar_sondages_questions_v2';
    protected static $attrs = array(
        'Question' => T_STR,
        'Sondage' => 'Sondage',
        'Type' => array('unique', 'libre', 'multiple'));
    protected static $nulls = array();

    public $reponses = NULL;

    public function __construct($data=NULL) {
        parent::__construct($data);
        if ($this->get_id() === NULL)
            $this->reponses = new PolarObjectsArray();
        else
            $this->reponses = $this::$db->fetchAll('ReponseSondage', '`Question` = '.$this->get_id(), NULL, true);
    }

    public function add_reponse($question) {
        $this->reponses[] = $question;
    }

    public function create_reponse($reponse, $image) {
        $r = new ReponseSondage(array('Question'=>$this,
                                       'Sondage'=>$reponse,
                                       'type'=>$type));
        $this->add_reponse($r);
        return $r;
    }

    public function get_dependants() {
        return array($this->reponses);
    }
}
?>

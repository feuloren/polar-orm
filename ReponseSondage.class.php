<?php

require_once 'PolarObject.class.php';
require_once 'PolarAssociation.class.php';

class ReponseSondage extends PolarObject {
    public static $table = 'polar_sondages_reponses_v2';
    protected static $attrs = array(
        'Question' => 'QuestionSondage',
        'Reponse' => T_STR,
        'Image' => T_STR);
    protected static $nulls = array(
        'Image');

    public $votes = NULL;

    public function __construct($data) {
        parent::__construct($data);
        $this->votes = new PolarAssociation('polar_sondages_votes', 'Reponse', 'Reponse', 'Utilisateur', 'User', $this::$db, $this->get_id());
    }

    public function set_id($id) {
        parent::set_id($id);
        $this->votes->set_id($id);
    }

    public function get_dependants() {
        return array($this->votes);
    }
}
?>

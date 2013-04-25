<?php
require_once 'Page.class.php';
require_once 'Utilisateur.class.php';
require_once 'PolarDB.class.php';
require_once 'Article.class.php';
require_once 'Vente.class.php';
require_once 'PolarAssociation.class.php';

class TestObject extends PolarObject {
    public static $table = 'tests';
    protected static $attrs = array(
        'Texte' => T_STR,
        'Nombre' => T_INT,
        'Liste' => array('a', 'b', 'c', 'd'),
        'Flottant' => T_FLOAT,
        'Booleen' => T_BOOL,
        'Objet' => 'TestObject');
    protected static $nulls = array('Objet');
}

class PolarORMTest extends PHPUnit_Framework_TestCase
{
    public function setUp() {
        $this->db = new PolarDB("localhost", "polar", "root", "root");
        PolarObject::$db = $this->db;
    }

    public function testTypeDetection() {
        $this->assertFalse(type_is_object(T_STR));
        $this->assertFalse(type_is_object(T_INT));
        $this->assertFalse(type_is_object(T_BOOL));
        $this->assertFalse(type_is_object(T_FLOAT));
        $this->assertFalse(type_is_object(array('member', 'private', 'public')));
        $this->assertTrue(type_is_object('Utilisateur'));
        $this->assertTrue(type_is_object('Page'));
    }

    public function testPolarObject() {
        $o = new TestObject(array("Texte" => "Bonjour",
                                  "Nombre" => 42,
                                  "Liste" => "b",
                                  "Booleen" => True,
                                  "Flottant" => 9.456,
                                  "Objet" => NULL));
        $this->assertSame("Bonjour", $o->Texte);
        $this->assertSame(42, $o->Nombre);
        $this->assertSame("b", $o->Liste);
        $this->assertSame(9.456, $o->Flottant);
        $this->assertTrue($o->Booleen);
        $o->Flottant = "coucou";
        $this->assertSame(0.0, $o->Flottant);
        $o->Nombre = "truc";
        $this->assertSame(0, $o->Nombre);
        $o->Booleen = "faux";
        $this->assertSame(True, $o->Booleen);

        $p = new TestObject(array("Texte" => "A",
                                  "Nombre" => 0,
                                  "Liste" => "a",
                                  "Flottant" => 9,
                                  "Booleen" => False,
                                  "Objet" => $o));
        $this->assertFalse($p->Booleen);
        $this->assertSame($o, $p->Objet);
        return $p;
    }

    /**
     * @expectedException InvalidValue
     * @depends testPolarObject
     */
    public function testInvalidValueListe($o) {
        $o->Liste = "x";
    }

    public function testMakeAlias() {
        $this->assertEquals('t', make_alias('tests'));
        $this->assertEquals('pca', make_alias('polar_caisse_articles'));
        $this->assertEquals('pcvg', make_alias('polar_caisse_ventes_global'));
    }

    public function testPolarQuerySelect() {
        $q = new PolarQuery($this->db, QUERY_SELECT, 'TestObject');
        
        $this->assertEquals('t.*', $q->format_selects());
        $this->assertEquals($q, $q->select('t.ID'));
        $this->assertEquals('t.ID', $q->format_selects());
        $q->select('Nom');
        $this->assertEquals('t.ID, Nom', $q->format_selects());

        $this->assertEquals('1', $q->format_wheres());
        $this->assertEquals($q, $q->where('t.ID = 3'));
        $this->assertEquals('t.ID = 3', $q->format_wheres());
        $q->where('t.Texte = \'THEVENET\'');
        $this->assertEquals('t.ID = 3 AND t.Texte = \'THEVENET\'', $q->format_wheres());
        $q->wheres = array(); // reset_wheres()
        $q->where('t.ID = ?', 4);
        $this->assertEquals('t.ID = 4', $q->format_wheres());
        $q->where('t.Texte = ?', 'Florent');
        $this->assertEquals('t.ID = 4 AND t.Texte = "Florent"', $q->format_wheres());
    }

    /*
    public function testAssociation()
    {
        $id = 1;
        $start = 'ID';
        $dest = 'Other';
        $as = new PolarAssociation('polar_nothing', 'TestObject', );
        $this->assertEquals(NULL, $as->get_id());
        $as->set_id($page);
        $this->assertEquals($page, $as->id);
        $as->set_id($page+1);
        $this->assertEquals($page, $as->id);
        $this->assertEmpty($as->list);
        $this->assertEmpty($as->to_remove);
        $this->assertEmpty($as->to_add);
        $as->add(3);
        $as->add(4);
        $result = $as->save();
        $this->assertEquals($result[0], "INSERT INTO polar_securite_droits VALUES ($page,3),($page,4)");
        $this->assertEquals(array(3,4), $as->list);
        $this->assertEmpty($as->to_remove);
        $this->assertEmpty($as->to_add);
        $as->add(4);
        $as->remove(3);
        $result = $as->save();
        $this->assertCount(1, $result);
        $this->assertEquals($result[0], "DELETE FROM polar_securite_droits WHERE ID=$page AND (User=3)");
        $this->assertEmpty(array_diff(array(4), $as->list));
        $this->assertEmpty($as->to_remove);
        $this->assertEmpty($as->to_add);
        $this->assertEmpty($as->get_necessaires());
    }
*/

    public function testUtilisateur() {
        $u = new Utilisateur(array('Nom' => 'Thévenet'));
        $this->assertEquals('Thévenet', $u->Nom);
        return $u;
    }

    /**
     * @expectedException InvalidValue
     * @depends testUtilisateur
     */
    public function testInvalidVal($u) {
        $u->Sexe = 'b';
    }

    /**
     * @expectedException InvalidAttribute
     * @depends testUtilisateur
     */
    public function testInvalidAttr($u) {
        $u->Bonjour = NULL;
    }

    /**
     * @expectedException NotNullable
     * @depends testUtilisateur
     */
    public function testNotNullable($u) {
        $u->Email = NULL;
    }

    /**
     * @expectedException PDOException
     */
    public function testConnexion() {
        $a = new PolarDB("localhost", "polar", "choux", "fleur");
    }

    /**
     * @depends testTypeDetection
     */
    public function testLoadFromDB() {
        //$result = $this->db->query("SELECT * FROM polar_utilisateurs WHERE ID=913");
        //$u = new Utilisateur($result->fetch(), $this->db);
        $u = $this->db->fetchOne('Utilisateur', 'Telephone LIKE "0663469246" ');
        $this->assertEquals($u->get_id(), 913);
        $this->assertEquals($u->Login, 'fthevene');
        $this->assertEquals($u->Email, 'fthevene@etu.utc.fr');
        $this->assertTrue($u->Staff);
        $this->assertTrue($u->Bureau);
        $this->assertEquals($u->Nom, 'THEVENET');
        $this->assertEquals($u->Sexe, 'm');
        $this->assertEquals($u->Telephone, '0663469246');
        $this->assertCount(0, $u->get_dependants());
        $this->assertCount(0, $u->get_necessaires());

        $u->Nom = "Moimoi";
        $this->assertEquals("Moimoi", $u->Nom);
        $u->Sexe = "f";
        $this->assertEquals("f", $u->Sexe);
        $u->Staff = False;
        $this->assertFalse($u->Staff);
        $this->assertEquals("UPDATE polar_utilisateurs SET  `Nom`=\"Moimoi\", `Sexe`=\"f\", `Staff`=0 WHERE ID=913",
            $u->save());
        $this->assertEquals(";", $u->save());

        $bureau = $this->db->fetchAll('Utilisateur', 'Bureau=1');
        $this->assertCount(6, $bureau);
        return $u;
    }

    /**
     * @depends testLoadFromDB
     */
    public function testFormatAttr($u) {
        $this->assertEquals('"bonjour"', format_attr('bonjour'));
        $this->assertEquals("3", format_attr(3));
        $this->assertEquals("1.4567864456556", format_attr(1.4567864456556));
        $this->assertEquals("913", format_attr($u));
        $this->assertEquals("NULL", format_attr(NULL));
    }

    /**
     * @depends testLoadFromDB
     */
    public function testVente($u) {
        $article1 = $this->db->fetchOne('Article', 557);
        $this->assertEquals(913, $article1->Auteur->get_id());
        $article2 = $this->db->fetchOne('Article', 802);
        $v = array();
        $v[] = new Vente($article1, 2, 'cb', $u);
        $this->assertTrue(in_array($u, $v[0]->get_necessaires()));
        $this->assertCount(0, $v[0]->get_dependants());
        $this->assertEquals(2, $v[0]->Quantite);
        $this->assertEquals('cb', $v[0]->MoyenPaiement);
        $this->assertEquals('normal', $v[0]->Tarif);
        $this->assertEquals(NULL, $v[0]->Asso);
        $this->assertEquals(10, $v[0]->PrixFacture);
        $this->assertEquals(10*0.196, $v[0]->MontantTVA);
        $v[] = $v[0]->create_similaire($article1, 12);
        $this->assertEquals(12, $v[1]->Quantite);
        $this->assertEquals('cb', $v[1]->MoyenPaiement);
        $this->assertEquals('normal', $v[1]->Tarif);
        $this->assertEquals(NULL, $v[1]->Asso);
        $this->assertEquals(54, $v[1]->PrixFacture);
        $this->assertEquals(54*0.196, $v[1]->MontantTVA);
        $v[] = $v[0]->create_similaire($article2, 30);
        $v[] = $v[0]->create_similaire($article2, 150);
        $v[] = $v[0]->create_similaire($article2, 300);
    }

    /**
     * @depends testLoadFromDB
     * @depends testAssociation
     */
    public function testPage() {
        $membres = $this->db->fetchOne('Page', 7);
        $manuels = $this->db->fetchOne('Page', 13);
        $this->assertEquals('public', $membres->Acces);
        $this->assertEquals('private', $manuels->Acces);
    }

    public function testInitialSave() {
        $u = new Utilisateur(array(
                                'IPClient' => '127.0.0.1',
                                'DateCreation' => 'NOW()',
                                'Login' => 'abcdefgh',
                                'MotDePasse' => '******',
                                'Email' => 'abcdefgh@etu.utc.fr',
                                'Staff' => 1,
                                'Bureau' => 0,
                                'Ancien' => 0,
                                'Responsable' => NULL,
                                'Poste' => NULL,
                                'Presentation' => '',
                                'Nom' => 'Super',
                                'Prenom' => 'Man',
                                'Sexe' => 'm',
                                'Telephone' => NULL,
                                'Newsletter' => 0));
        $this->db->save($u);
        $this->assertNotNull($u->get_id());
        return $u;
    }

    /**
     * @depends testInitialSave
     */
    public function testIncrementalSave($u) {
        $u->Telephone = "0663469246";
        $this->assertEquals("UPDATE polar_utilisateurs SET  `Telephone`=\"0663469246\" WHERE ID=".$u->get_id(), $u->save());
        $this->assertEquals(";", $u->save());
        return $u;
    }

    public function testValidObject() {
        $this->assertTrue($this->db->validObject('Utilisateur', 913));
        $this->assertFalse($this->db->validObject('Utilisateur', 0));
    }

    /**
     * @depends testIncrementalSave
     * @depends testValidObject
     */
    function testDelete($u) {
        $id = $u->get_id();
        $this->assertTrue($this->db->validObject('Utilisateur', $id));
        $this->db->delete($u);
        $this->assertFalse($this->db->validObject('Utilisateur', $id));
    }

}
?>

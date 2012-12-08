<?php
require_once 'Page.class.php';
require_once 'Utilisateur.class.php';
require_once 'PolarDB.class.php';
require_once 'Article.class.php';
require_once 'Vente.class.php';

class PolarORMTest extends PHPUnit_Framework_TestCase
{
    public function setUp() {
        $this->db = new PolarDB("localhost", "polar", "root", "root");
    }

    public function testTypeDetection() {
        $this->assertFalse(type_is_object(T_STR));
        $this->assertFalse(type_is_object(T_IP));
        $this->assertFalse(type_is_object(T_DATE));
        $this->assertFalse(type_is_object(T_INT));
        $this->assertFalse(type_is_object(T_BOOL));
        $this->assertFalse(type_is_object(T_FLOAT));
        $this->assertFalse(type_is_object(T_MAIL));
        $this->assertFalse(type_is_object(array('member', 'private', 'public')));
        $this->assertTrue(type_is_object('Utilisateur'));
        $this->assertTrue(type_is_object('Page'));
    }

    public function testDroitsPage()
    {
        $page = 1;
        $droits = new DroitsPage(NULL, NULL);
        $this->assertEquals(NULL, $droits->id);
        $droits->set_id($page);
        $this->assertEquals($page, $droits->id);
        $droits->set_id($page+1);
        $this->assertEquals($page, $droits->id);
        $this->assertEmpty($droits->droits);
        $this->assertEmpty($droits->to_remove);
        $this->assertEmpty($droits->to_add);
        $droits->add(3);
        $droits->add(4);
        $result = $droits->save();
        $this->assertEquals($result[0], "INSERT INTO polar_securite_droits VALUES ($page,3),($page,4)");
        $this->assertEquals(array(3,4), $droits->droits);
        $this->assertEmpty($droits->to_remove);
        $this->assertEmpty($droits->to_add);
        $droits->add(4);
        $droits->remove(3);
        $result = $droits->save();
        $this->assertCount(1, $result);
        $this->assertEquals($result[0], "DELETE FROM polar_securite_droits WHERE ID=$page AND (User=3)");
        $this->assertEmpty(array_diff(array(4), $droits->droits));
        $this->assertEmpty($droits->to_remove);
        $this->assertEmpty($droits->to_add);
        $this->assertEmpty($droits->get_necessaires()); 
    }
    
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
     * @expectedException InvalidType
     * @depends testUtilisateur
     */
    public function testInvalidType($u) {
        $u->Email = array();
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
        $this->assertEquals("", $u->save());
        
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
        $this->assertEquals((10-6)*0.196, $v[0]->MontantTVA);
        $v[] = $v[0]->create_similaire($article1, 12);
        $this->assertEquals(12, $v[1]->Quantite);
        $this->assertEquals('cb', $v[1]->MoyenPaiement);
        $this->assertEquals('normal', $v[1]->Tarif);
        $this->assertEquals(NULL, $v[1]->Asso);
        $this->assertEquals(54, $v[1]->PrixFacture);
        $this->assertEquals((54-36)*0.196, $v[1]->MontantTVA);
        $v[] = $v[0]->create_similaire($article2, 30);
        $v[] = $v[0]->create_similaire($article2, 150);
        $v[] = $v[0]->create_similaire($article2, 300);
    }
    
    /**
     * @depends testLoadFromDB
     * @depends testDroitsPage
     */
    public function testPage() {
        $membres = $this->db->fetchOne('Page', 7);
        $manuels = $this->db->fetchOne('Page', 13);
        $this->assertEquals('public', $membres->Acces);
        $this->assertEquals('private', $manuels->Acces);
    }
}
?>

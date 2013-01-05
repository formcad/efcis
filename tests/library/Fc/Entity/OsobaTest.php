<?php

namespace Fc\Entity;

/**
 * Description of UserTest
 *
 * @author jon
 */
class OsobaTest
    extends \ModelTestCase
{
    public function testCanCreateUser()
    {
        $this->assertInstanceOf('Fc\Entity\Osoba',new Osoba());
    }

    public function testCanSaveTextikAndRetrieveIt()
    {

        $em = $this->doctrineContainer->getEntityManager();
        $em->persist($this->getTestOsoba());
        $em->flush();

        $users = $em->createQuery('select textik from Fc\Entity\Osoba textik')->execute();
        $this->assertEquals(1,count($users));

        $this->assertEquals('John',$users[0]->textik);
    }

    /**
     *
     * @return User
     */
    private function getTestOsoba()
    {
        $u = new Osoba();
        $u->textik = "John";
        return $u;
    }

}
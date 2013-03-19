<?php

/**
 * Získávání identity uživatelů pro vytvoření záznamu v Zend_Auth
 */
class Application_Model_UserIdentity extends Fc_Model_DatabaseAbstract
{      
    /**
     * Uživtelské jméno
     * @var string 
     */
    protected $_username = null;

    /**
     * Kód výrobní nebo dvoustrojové karty
     * @var integer
     */
    protected $_kodKarty = null;

    /**
     * Kompletní získání uživatelské identity
     * @return object
     * @throws Exception
     */
    public function getUserIdentity() 
    {
        if ($this->_username == null) {
            throw new Exception('Chybí uživatelské jméno');
        }
        else {
            
            $identity = null;
            $data = $this->_getUserData();
            $roles = $this->_getUserRoles($data['id_osoby']);
                       
            $identity->id = $data['id_osoby'];
            $identity->jmeno = $data['jmeno'];
            $identity->prijmeni = $data['prijmeni'];
            $identity->oznaceni = $this->_username;
            $identity->roles = $roles;
            
            return $identity;
        }  
    }
    
    /**
     * Ze zadaného kódu karty zaměstnance vrátí ID typu karty a ID zaměstnance
     * @return array
     * @throws Exception
     */
    public function dekodujKartuZamestance()
    {
        if ($this->_kodKarty == null) {
            throw new Exception('Chybí kód karty');
        }
        else {
            
            $select = self::$_adapter->select();
            $select->from('identifikace_osoby',
                    array('idKarty' => 'id_typu','idZamestnance' => 'id_osoby'))
                ->where('hodnota = ?', $this->_kodKarty);
            
            return self::$_adapter->fetchRow($select);
        }
    }
    
    /**
     * Řádek uživatelských dat z tabulky uživatelů
     * @return array
     */
    protected function _getUserData()
    {    
        $select = self::$_adapter->select()
            ->from( 'osoby',
                    array('id_osoby', 'jmeno', 'prijmeni') )
            ->where('oznaceni = ?', $this->_username);

        return self::$_adapter->fetchRow($select);         
    }
    
    /**
     * Pole uživatelových rolí v jednotlivých modulech
     * @var integer $idUser
     * @return array
     */
    protected function _getUserRoles($idUser)
    {
        $select = self::$_adapter->select()
            ->from( array('r' => 'role'),
                    array('role' => 'nazev') )
            ->join( array('rm' => 'role_osob'),
                    'r.id_role = rm.id_role' )
            ->join( array('m' => 'moduly'),
                    'rm.id_modulu = m.id_modulu',
                    array('modul' => 'nazev'))
            ->where( 'rm.id_osoby = ?', $idUser);
        
        $roles = self::$_adapter->fetchAll($select);
                                
        if (!empty($roles)) {
            foreach ($roles as $role) {                
                $roleArray[$role['modul']] = $role['role'];                 
            }   
        }
        return $roleArray;        
    }
    
    
    public function setUsername($username)
    {
        $this->_username = $username;
    }
    
    public function setKodKarty($kodKarty) {
        $this->_kodKarty = $kodKarty;
    }    
        
}
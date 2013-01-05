<?php

/**
 * Získávání seznamu zaměstnanců
 */
class Application_Model_UserList extends Fc_Model_DatabaseAbstract
{      

    /**
     * Role uživatele
     * @var string
     */
    protected $_role = 'guest';
    
    /**
     * ID uživatele
     * @var integer
     */
    protected $_id = null;
        
    /**
     * Na základě role uživatele vypíše seznam uživatelů
     * 
     * @return array Seznam uživatelů s přiřazenými docházkovými čipy
     */
    public function getUsers() 
    {                    
        // obecný dotaz
        $select = $this->_adapter->select()
            ->from( array( 'o' => 'osoby'),
                    array( 'id' => 'id_osoby', 'jmeno', 'prijmeni') )
            ->join( array('co' => 'cipy_osob'),
                    'o.id_osoby = co.id_osoby',
                    array( 'id_cipu'))    
            ->join( array('c' => 'cipy'),
                    'co.id_cipu = c.id_cipu',
                    array( 'nazev'))   
            ->where( 'o.aktivni IS TRUE')
            ->order( array( 'prijmeni', 'jmeno', 'nazev'));        
        
        // dotaz na základě role dokončíme
        switch ($this->_role) {           
            
            case 'employee':                
                $select->where('o.id_osoby = ?', $this->_id);
                break;
            
            case 'user':
                $select->where('o.id_osoby = ?', $this->_id);                
                break;
            
            case 'admin':
                break;
            
            default:
                return array();
                break;
        }  
        return $this->_adapter->fetchAll($select);  
    }    
    
    /**
     * Na základě role uživatele vypíše seznam uživatelů ve výrobě
     * 
     * @return array Seznam uživatelů s přiřazenými výrobními kartami
     */
    public function getVyrobaUsers()
    {              
        return $this->_getKartyUzivatelu('vyroba');
    }     
    
    /**
     * Na základě role uživatele vypíše seznam uživatelů s režijní identifikací
     * 
     * @return array Seznam uživatelů s přiřazenými režijními kartami
     */
    public function getRezieUsers() 
    {                   
        return $this->_getKartyUzivatelu('rezie');
    }      
    
    /**
     * 
     * @param string $typ rezie nebo vyroba podle toho, co je potřeba
     * @return array
     */
    private function _getKartyUzivatelu($typ)
    {
        // obecný dotaz
        $select = $this->_adapter->select()
            ->from( array( 'o' => 'osoby'),
                    array( 'id' => 'id_osoby', 'jmeno', 'prijmeni') )
            ->join( array('io' => 'identifikace_osoby'),
                    'o.id_osoby = io.id_osoby',
                    array( 'hodnota'))    
            ->join( array('t' => 'typy_prace'),
                    'io.id_typu = t.id_typu',
                    array( 'nazev' => 'nazev_typu', 'typKarty' => 'id_typu'))   
            ->where( 'o.aktivni IS TRUE')
            ->order( array( 'prijmeni', 'jmeno', 'typKarty'));        
        
        // dotaz na základě role upravíme
        switch ($this->_role) {           
            
            case 'employee':                
                $select->where('o.id_osoby = ?', $this->_id);
                break;
            
            case 'user':
                $select->where('o.id_osoby = ?', $this->_id);                
                break;
            
            case 'admin':
                break;
            
            default:
                return array();
                break;
        }  
        
        // filtrujeme karty na základě vstupního parametru
        switch ($typ) {
            case 'vyroba':
                $select->where('io.id_typu <> 3');
                break;
            case 'rezie':
                $select->where('io.id_typu = 3');
                break;
        }
        
        return $this->_adapter->fetchAll($select);
    }
    
    public function getRole() {
        return $this->_role;
    }

    public function setRole($role) {
        $this->_role = $role;
    }

    public function getId() {
        return $this->_id;
    }

    public function setId($id) {
        $this->_id = $id;
    }
    
}
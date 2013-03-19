<?php

/**
 * Získávání informací o zaměstnancích
 */
class Application_Model_UserData extends Fc_Model_DatabaseAbstract
{      

    /**
     * Id uživatele
     * @var string
     */
    protected $_userId = null;
    
    /**
     * Role uživatele
     * @var string
     */
    protected $_userRole = 'guest';
    
    /**
     * Id uživatele, kterého prověřujeme
     * @var integer
     */
    protected $_dataUserId = null;
       
    /**
     * Hash uživatelského hesla
     * @var string
     */
    protected $_hashHesla = null;

    /**
     * Zjištění hashe hesla konkrétího uživatele
     * 
     * @return string
     */
    public function getHeslo()
    {
        // základní ošetření bezpečnosti
        switch ($this->_userRole) {
            // guest nic nemůže
            case 'guest':
                throw new Exception('Nedostatečné oprávnění');        
                break;
            // employee vidí jenom sebe
            case 'employee':
                if ($this->_userId <> $this->_dataUserId) {
                    throw new Exception('Nedostatečné oprávnění');
                }
                break;                        
        }
        
        $select = self::$_adapter->select()
            ->from(array('o'=>'osoby'),
                array('heslo'))
            ->where('o.id_osoby = ?',$this->_dataUserId);
        
        $row = self::$_adapter->fetchRow($select);
        
        return $row['heslo'];
    }
    
    /**
     * Změna hashe hesla konkrétního uživatele
     */
    public function zmenaHesla()
    {
        // základní ošetření bezpečnosti
        switch ($this->_userRole) {
            // guest nic nemůže
            case 'guest':
                throw new Exception('Nedostatečné oprávnění');        
                break;
            // employee mění heslo jenom sám sobě
            case 'employee':
                if ($this->_userId <> $this->_dataUserId) {
                    throw new Exception('Nedostatečné oprávnění');
                }
                break;                        
        }
        
        self::$_adapter->update(
            'osoby',
            array(
                'heslo' => $this->_hashHesla
            ),
            array(
                'id_osoby = ?' => $this->_dataUserId
            ));       
    }
    
    public function getUserId() {
        return $this->_userId;
    }

    public function setUserId($userId) {
        $this->_userId = $userId;
    }

    public function getUserRole() {
        return $this->_userRole;
    }

    public function setUserRole($userRole) {
        $this->_userRole = $userRole;
    }

    public function getDataUserId() {
        return $this->_dataUserId;
    }

    public function setDataUserId($dataUserId) {
        $this->_dataUserId = $dataUserId;
    }

    public function getHashHesla() {
        return $this->_hashHesla;
    }

    public function setHashHesla($hashHesla) {
        $this->_hashHesla = $hashHesla;
    }  
}
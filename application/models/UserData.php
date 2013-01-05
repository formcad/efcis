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
     * Zjištění hashe hesla konkrétího uživatele
     * 
     * @return string
     */
    public function getHashHesla()
    {
        // základní ošetření bezpečnosti
        switch ($this->_userRole) {
            // guest nic nevidí
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
        
        $select = $this->_adapter->select()
            ->from(array('o'=>'osoby'),
                array('heslo'))
            ->where('o.id_osoby = ?',$this->_dataUserId);
        
        $row = $this->_adapter->fetchRow($select);
        
        return $row['heslo'];
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
}
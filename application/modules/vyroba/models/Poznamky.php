<?php

class Vyroba_Model_Poznamky extends Fc_Model_DatabaseAbstract
{
    /**
     * ID poznámky
     * @var integer
     */
    protected $_id = null;
    
    /**
     * ID uživatele
     * @var integer 
     */
    protected $_idUser = null;
    
    /**
     * ID pozice
     * @var integer 
     */
    protected $_idPozice = null;
    
    /**
     * Text poznámky
     * @var string 
     */
    protected $_text = null;

    public function addPoznamka() 
    {
        self::$_adapter->insert( 'vyrobni_poznamky', array(
            'id_pozice' => $this->_idPozice,
            'id_osoby' => $this->_idUser,
            'ulozeno' => date('Y-m-d H:i:s'),
            'text' => $this->_text
        ));        
    }
    
    /**
     * Z DB vrátí jednu konkrétné poznámku 
     */
    public function getPoznamka() 
    {
        $select = self::$_adapter->select()
            ->from( 'vyrobni_poznamky',
                    array('text','id_pozice'))
            ->where( 'id_poznamky = ?',$this->_id);
        
        return self::$_adapter->fetchRow($select);
    }
    
    /**
     * Změní již zapsanou poznámku 
     */
    public function changePoznamka()
    {
        self::$_adapter->update(
            'vyrobni_poznamky',
            array(
                'ulozeno' => date('Y-m-d H:i:s'),
                'text' => $this->_text,
                'id_pozice' => $this->_idPozice
                ),
            array(
                'id_poznamky = ?' => $this->_id
        ));   
    }
    
    /**
     * Pro daného člověka získá poseldních 5 výrobních poznámek
     * @return array 
     */
    public function getNedavnePoznamky() 
    {
        $select = self::$_adapter->select()
            ->from( array('vp' => 'vyrobni_poznamky'),
                    array('ulozeno','id'=>'id_poznamky','text'))
            ->joinLeft( array('pz' => 'pozice'),
                    'vp.id_pozice = pz.id_pozice',
                    array('pozice'=>'nazev') )    
            ->joinLeft( array('pl' => 'polozky'),
                    'pz.id_polozky = pl.id_polozky',
                    array('zakazka'=>'cislo_zakazky') )                    
            ->where( 'vp.id_osoby = ?', $this->_idUser)
            ->order( array('vp.id_poznamky DESC') )
            ->limit( 5 );          
        
        return self::$_adapter->fetchAll($select);               
    }


    public function getId() {
        return $this->_id;
    }

    public function setId($id) {
        $this->_id = $id;
    }

    public function getIdUser() {
        return $this->_idUser;
    }

    public function setIdUser($idUser) {
        $this->_idUser = $idUser;
    }

    public function getIdPozice() {
        return $this->_idPozice;
    }

    public function setIdPozice($idPozice) {
        $this->_idPozice = $idPozice;
    }

    public function getText() {
        return $this->_text;
    }

    public function setText($text) {
        $this->_text = $text;
    }  
 
}

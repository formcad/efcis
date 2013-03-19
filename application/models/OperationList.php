<?php

/**
 * Získávání seznamu operací
 */
class Application_Model_OperationList extends Fc_Model_DatabaseAbstract
{      
    /**
     * Možnosti řazení technologií
     * @var string možnosti jsou ID z tabulky poradi
     */
    protected $_vyrobaOrder = null;
        
    /**
     * Na základě vstupních parametrů vrátí seznam operací
     * 
     * @return array Seznam operací
     */
    public function getVyrobniOperace() 
    {                   
        $select = self::$_adapter->select()
            ->from( array( 't' => 'technologie'),
                    array( 'id' => 'cislo_technologie', 
                        'nazev' => 'nazev_technologie',
                        'zkratka' => 'zkratka_technologie',
                        'popis' => 'popis_nakladu') )
            ->join( array('pt' => 'poradi_technologii'),
                    't.cislo_technologie = pt.cislo_technologie' )      
            ->where( 'pt.id_poradi = ?', $this->_vyrobaOrder)
            ->where( 'pt.platnost_od >= (?)', new Zend_Db_Expr(
                        self::$_adapter->select()
                            ->from('poradi_technologii',array(
                                'max(platnost_od)'))
                            ->where('cislo_technologie = t.cislo_technologie')
                            ->where('id_poradi = ?', $this->_vyrobaOrder)) )
            ->where( 'pt.poradi IS NOT NULL' )
            ->order( 'pt.poradi' );         

        return self::$_adapter->fetchAll($select);  
    }    
    
    /**
     * Vrátí seznam režijních operací
     * 
     * @return array Seznam operací
     */
    public function getRezijniOperace()
    {
        $select = self::$_adapter->select()
            ->from( array( 't' => 'rezijni_technologie'),
                    array( 'id' => 'id_operace', 
                        'nazev' => 'nazev_operace') )                       
            ->where( 't.poradi IS NOT NULL' )
            ->where( 't.viditelne IS TRUE' )
            ->order( 't.poradi' );         

        return self::$_adapter->fetchAll($select);          
    }
    
    public function setVyrobaOrder($vyrobaOrder) {
        $this->_vyrobaOrder = $vyrobaOrder;
    }

}
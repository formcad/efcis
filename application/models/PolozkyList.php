<?php

/**
 * Získávání seznamu zakázek a poptávek
 */
class Application_Model_PolozkyList extends Fc_Model_DatabaseAbstract
{      

    /**
     * Role uživatele
     * @var string
     */
    protected $_role = 'guest';
    
    /**
     * Funkce vypíše seznam aktivních zakázek
     * @return type
     */
    public function getAktivniZakazky()
    {
        $subquery = $this->_adapter->select()
                    ->from(array('s'=>'stav_polozky'),
                        array('id'=>'id_polozky','stav'=>'id_stavu'))
                    ->where('id_typu = 201')
                    ->where('datum_prirazeni = (?)', new Zend_Db_Expr($this->_adapter->select()
                            ->from(array('st'=>'stav_polozky'), array('max(datum_prirazeni)'))
                            ->where('st.id_polozky = s.id_polozky')));
                
        $select = $this->_adapter->select()
            ->from(array('z'=>'polozky'),
                array('id'=>'id_polozky','cislo'=>'cislo_zakazky'))
            ->join(array('f' => new Zend_Db_Expr('('.$subquery.')')), 'f.id = z.id_polozky', array())
            ->where('f.stav = 15')
            ->order(array(
                new Zend_Db_Expr( "CAST(split_part(z.cislo_zakazky, '-', 3) AS INTEGER) DESC" ) ,
                new Zend_Db_Expr( "CAST(split_part(z.cislo_zakazky, '-', 1) AS INTEGER) DESC" )
            ));
        
        return $this->_adapter->fetchAll($select);
    }
    
    public function setRole($_role) {
        $this->_role = $_role;
    }    
}
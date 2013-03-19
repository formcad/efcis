<?php

/**
 * Získávání seznamu pozic
 */
class Application_Model_PoziceList extends Fc_Model_DatabaseAbstract
{      
    /**
     * Role uživatele
     * @var string
     */
    protected $_role = 'guest';
    
    /**
     * ID položky
     * @var integer
     */
    protected $_idPolozky = null;
    
    /**
     * Funkce v dané položce vypíše nesmazané pozice
     * @return array
     */
    public function getPlnohodnotnePozice()
    {
        if ($this->_idPolozky == null) {
            throw ('Není zadané ID položky');
        } else {        
            /**** SUBSELECT  **************************************************/

            $subSubselect = self::$_adapter->select()
                ->from('stav_pozice',
                    array('max(datum_prirazeni)'))
                ->where('id_pozice = st.id_pozice');

            $subselect = self::$_adapter->select()
                ->from(array('st'=>'stav_pozice'),
                        array('id'=>'id_pozice','stav'=>'id_stavu'))
                ->where( 'datum_prirazeni = ?', new Zend_Db_Expr('('.$subSubselect.')'));

            /**** SELECT ******************************************************/

            $select = self::$_adapter->select()
                ->from(array('p'=>'pozice'),
                    array('id'=>'id_pozice','nazev','idTypu'=>'id_typu',))
                ->join(array('s'=>new Zend_Db_Expr('('.$subselect.')')),'p.id_pozice = s.id',array())            
                ->where('s.stav NOT IN (19)')
                ->where('p.id_polozky = ?', $this->_idPolozky)
                ->where('p.id_rodice IS NULL')
                ->order('p.nazev');

            return self::$_adapter->fetchAll($select);
        }
    }

    public function setRole($_role) {
        $this->_role = $_role;
    }    
    
    public function setIdPolozky($idPolozky) {
        $this->_idPolozky = $idPolozky;
    }    
}
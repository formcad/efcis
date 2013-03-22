<?php

class Application_Model_Kalendar extends Fc_Model_DatabaseAbstract
{
    /**
     * Počáteční datum
     * @var String 
     */
    protected $_datumOd = '2011-01-01';
    
    /**
     * Koncové datum
     * @var String 
     */
    protected $_datumDo = '2111-01-01';        
    
    /**
     * Konstruktor třídy
     * 
     * @param  String $datumOd
     * @param  String $datumDo
     */
    public function __construct($datumOd = null, $datumDo = null) 
    {
        $this->setDatumOd($datumOd);
        $this->setDatumDo($datumDo);
    }
    
    /**
     * Na základě nastaveného _datumOd a _datumDo vrátí kalendář
     * @return Array
     */
    public function ziskejKalendar()
    {
        $select = self::$_adapter->select()
            ->from( 'kalendar',
                    array('datum', 'svatek') )     
            ->where( 'datum >= ?', $this->_datumOd)
            ->where( 'datum <= ?', $this->_datumDo)
            ->order( array('datum') );          
        
        return self::$_adapter->fetchAll($select);          
    }    
    
    /**
     * Na základě nastaveného _datumOd a _datumDo vrátí kalendář včetně svátků
     * a víkendů
     * @return Array
     */
    public function ziskejVikendovyKalendar() 
    {
        $kalendar = array();
        $dny = $this->ziskejKalendar();
        
        foreach ($dny as $den) {
            
            $cisloDne = date('w',strtotime($den['datum']));
            if ($cisloDne == 6 or $cisloDne == 0) {
                $vikend = true;
            }
            else {
                $vikend = false;
            }
            $kalendar[] = array(
                'datum' => $den['datum'],
                'svatek' => $den['svatek'],
                'vikend' => $vikend
            );
        } 
        return $kalendar;
    }
    
    public function setDatumOd($datumOd) {
        $this->_datumOd = $datumOd;
    }

    public function setDatumDo($datumDo) {
        $this->_datumDo = $datumDo;
    }
    
}

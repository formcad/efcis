<?php

class Application_Model_Kalendar extends Fc_Model_DatabaseAbstract
{
    /**
     * Počáteční datum
     * @var string 
     */
    protected $_dateFrom = '2011-01-01';
    
    /**
     * Koncové datum
     * @var string 
     */
    protected $_dateTo = '2111-01-01';        
    
    public function getKalendar()
    {
        $select = $this->_adapter->select()
            ->from( 'kalendar',
                    array('datum', 'svatek') )     
            ->where( 'datum >= ?', $this->_dateFrom)
            ->where( 'datum <= ?', $this->_dateTo)
            ->order( array('datum') );          
        
        return $this->_adapter->fetchAll($select);          
    }    
    
    public function getVikendovyKalendar() {

        $kalendar = array();
        $dny = $this->getKalendar();
        
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
    
    public function setDateFrom($dateFrom) {
        $this->_dateFrom = $dateFrom;
    }

    public function setDateTo($dateTo) {
        $this->_dateTo = $dateTo;
    }
    
}

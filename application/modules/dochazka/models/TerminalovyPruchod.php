<?php

/**
 * Třída nahrává průchody z terminálu a zajišťuje jejich integritu 
 */
class Dochazka_Model_TerminalovyPruchod extends Fc_Model_DatabaseAbstract
{
    /**
     * ID akce
     * @var integer 
     */
    protected $_idAkce = null;
    
    /**
     * Kód Dallas čipu
     * @var string 
     */
    protected $_kodCipu = null;
    
    /**
     * ID osoby
     * @var integer 
     */
    protected $_idOsoby = null;
   
    /**
     * ID čipu
     * @var integer 
     */
    protected $_idCipu = null;
    
    /**
     * Datum směny
     * @var string 
     */
    protected $_datumSmeny = null;
    
    /**
     * Z čísla čipu je vráceno ID uživatele a ID čipu, zároveň je nastaveno zde;
     * Logika databáze zaručuje, že pro jednoho člověka existuje pouze jedna 
     * kombinace ID uživatele a ID čipu
     */
    public function dekodujCip() 
    { 
        $select = self::$_adapter->select()
            ->from( 'cipy_osob',
                    array('idCipu'=>'id_cipu','idOsoby'=>'id_osoby'))
            ->where( 'kod = ?', $this->_kodCipu)
            ->where( 'aktivni = TRUE');

        $data = self::$_adapter->fetchRow($select);
        
        $this->_idCipu = $data['idCipu'];
        $this->_idOsoby = $data['idOsoby'];
        return $data;
    }
    
    /**
     * Zkontroluje a zajistí integritu tabulky dochazka_temp z hlediska 
     * příchodů daného zaměstnance. Aby byl výsledek integritní, nesmí být 
     * v temp tabulce žádný záznam o příchodu zaměstnance. Pokud je, je přesunut 
     * do error tabulky a je vrácena informace, jak byl záznam starý.
     * Z logiky plyne, že temp příchod bude pouze jeden
     * 
     * @return array
     */
    public function integritaTempPrichod() 
    {       
        $data = $this->vyberTempPrichod();
  
        // vše je OK
        if (empty($data)) {
            
            $result = array( 
                'integritni' => true, 
                'casChyby' => null,
                'datumSmeny' => null
            );                            
        }
        // není to v pořádku
        else {
            // získáme záznam o neintegritních datech
            $result = array(
                'integritni' => false,
                'casChyby' => date('Y-m-d H:i:s',strtotime($data['cas_akce'])),
                'datumSmeny' => date('Y-m-d',strtotime($data['datum']))
            );
  
            // přesunume neintegritní data do error tabulky
            $this->moveTempToError();
        }    
        return $result;  
    }    
    
    /**
     * Data z tabulky dochazka_temp přesune do dochaka_error a záznam v tempu
     * smaže
     */
    public function moveTempToError() {
        
        $data = $this->vyberTempPrichod();
        
        self::$_adapter->insert( 'dochazka_error', array(
            'id_cipu' => $this->_idCipu,
            'id_osoby' => $this->_idOsoby,
            'id_akce' => $data['id_akce'],
            'datum' => $data['datum'],
            'cas_akce' => $data['cas_akce']
        ));            
                
        $this->smazTempPrichod();
    }
    
    /**
     * Do tabulky dochazka_temp se uloží informace o příchdou zaměstnance
     */
    public function ulozPrichod() 
    {
        self::$_adapter->insert( 'dochazka_temp', array(
            'id_cipu' => $this->_idCipu,
            'id_osoby' => $this->_idOsoby,
            'id_akce' => $this->_idAkce,
            'datum' => $this->_datumSmeny,
            'cas_akce' => date('Y-m-d H:i:s')
        ));          
    }
    
    /**
     * Do tabulky dochazka_error uloží informace o chybném zaznamenání času příchodu 
     */
    public function ulozError()
    {   
        self::$_adapter->insert( 'dochazka_error', array(
            'id_cipu' => $this->_idCipu,
            'id_osoby' => $this->_idOsoby,
            'id_akce' => $this->_idAkce,
            'datum' => $this->_datumSmeny,
            'cas_akce' => date('Y-m-d H:i:s')
        ));            
    }
    
    /**
     * Získání informace o příchodu v temp tabulce. Z povahy věci tento temp
     * příchod může pro danou kombinaci id osoby a id čipu existovat jenom jeden
     * 
     * @return array
     */
    public function vyberTempPrichod() 
    { 
        $select = self::$_adapter->select()
            ->from( 'dochazka_temp',
                    array('id_zaznamu','datum','id_akce','cas_akce','id_osoby',
                        'id_cipu'))
            ->where( 'id_osoby = ?', $this->_idOsoby)
            ->where( 'id_cipu = ?', $this->_idCipu);
        
        $data = self::$_adapter->fetchRow($select);
        
        // řádek se vrátí pouze v případě, že vůbec nějaký je
        if ($data)
            return $data;
        else
            return null;     
    }
    
    /**
     * Odstranění vybraného temp příchodu z DB
     */
    public function smazTempPrichod() 
    {
        self::$_adapter->delete( 'dochazka_temp', array(
            'id_cipu = ?' => $this->_idCipu,
            'id_osoby = ?' => $this->_idOsoby
        ));
    }
    
    /**
     * Vyhledá poslední záznam v tabulce dochazka_pruchody pro danou kombinaci 
     * ID člověka a ID čipu. Pokud je to příchod, je vrácen záznam
     */
    public function vyberPermPrichod()
    {
        $select = self::$_adapter->select()
            ->from( array('pr' => 'dochazka_pruchody'),
                    array('datum','cas_akce','id_akce'))
            ->join( array('d' => 'dochazka'),
                    'd.id_akce = pr.id_akce',
                    array('id_typu'))
            ->where( 'pr.id_osoby = ?', $this->_idOsoby)
            ->where( 'pr.id_cipu = ?', $this->_idCipu)
            ->where( 'pr.smazano IS FALSE')
            ->order( array('pr.cas_akce DESC'));
        
        $data = self::$_adapter->fetchRow($select);

        // pokud v DB vůbec nějaký záznanm existue
        if (!empty($data)) {
            // pokud jde o příchod, je vrácen záznam
            if ($data['id_typu'] == 1) {
                return $data;
            }
            else {
                return null;
            }            
        }
        // žádný záznam neexistuje - nový zaměstnanec
        else {
            return null;
        }
    }
    
    
    public function getIdAkce() {
        return $this->_idAkce;
    }

    public function setIdAkce($_idAkce) {
        $this->_idAkce = $_idAkce;
    }

    public function getKodCipu() {
        return $this->_kodCipu;
    }

    public function setKodCipu($_kodCipu) {
        $this->_kodCipu = $_kodCipu;
    }

    public function getIdOsoby() {
        return $this->_idOsoby;
    }

    public function setIdOsoby($_idOsoby) {
        $this->_idOsoby = $_idOsoby;
    }

    public function getIdCipu() {
        return $this->_idCipu;
    }

    public function setIdCipu($_idCipu) {
        $this->_idCipu = $_idCipu;
    }

    public function getDatumSmeny() {
        return $this->_datumSmeny;
    }

    public function setDatumSmeny($_datumSmeny) {
        $this->_datumSmeny = $_datumSmeny;
    }

}
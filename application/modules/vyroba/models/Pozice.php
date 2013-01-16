<?php

class Vyroba_Model_Pozice extends Fc_Model_DatabaseAbstract
{
    /**
     * ID pozice
     * @var integer
     */
    private $_id;
    
    /**
     * Název pozice
     * @var string
     */
    private $_nazevPozice;
    
    /**
     * Ověří existenci konkrétní pozice na základě jejího ID
     * @return boolean true = existuje, false = neexistuje
     */
    public function overExistenci()
    {
        $select = $this->_adapter->select()
            ->from('pozice',array('pocet' => 'count(id_pozice)'))
            ->where('id_pozice = ?', $this->_id);
        
        $data = $this->_adapter->fetchRow($select);
        
        if ( $data['pocet'] == 1)
            return true;
        else 
            return false;
    }
    
    /**
     * Nalezení nesmazané pozice z nestornované zakázky podle názvu pozice
     * @return array Id pozice, název pozice, název zakázky
     */
    public function najdiPodleNazvu()
    {
        /**** Stav pozice *****************************************************/
        
        // pomocný subselect pro $subselectStavPozice
        // výběr data poslední změny stavu pozice
        $subselectDatumStavuPozice = $this->_adapter->select()
            ->from(array('st_poz'=>'stav_pozice'),
                array('max(datum_prirazeni)'))
            ->where('st_poz.id_pozice = s_poz.id_pozice');
        
        // pomocný subselect pro $select
        // zjištění aktuálního stavu pozice
        $subselectStavPozice = $this->_adapter->select()
            ->from(array('s_poz'=>'stav_pozice'),
                array('id_pozice','id_stavu'))
            ->where('datum_prirazeni = (?)',new Zend_Db_Expr($subselectDatumStavuPozice));  // pouze nejaktuálnější stav
        
        /**** Stav položky ****************************************************/
        
        // pomocný subselect pro $subselectStavPolozky
        // výběr data poslední změny stavu položky
        $subselectDatumStavuPolozky = $this->_adapter->select()
            ->from(array('st_pol'=>'stav_polozky'),
                array('max(datum_prirazeni)'))
            ->where('st_pol.id_polozky = s_pol.id_polozky');
        
        // pomocný subselect pro $select
        // zjištění aktuálního stavu zakázky
        $subselectStavPolozky = $this->_adapter->select()
            ->from(array('s_pol'=>'stav_polozky'),
                array('id_polozky','id_stavu'))
            ->where('id_typu = ?', 201)                                                      // typ položky je zakázka
            ->where('datum_prirazeni = (?)',new Zend_Db_Expr($subselectDatumStavuPolozky));  // pouze nejaktuálnější stav
                
        /**** Celkový select **************************************************/
        
        $select = $this->_adapter->select()
            ->from(array('pz'=>'pozice'),
                array('id'=>'id_pozice', 'nazev'))
            ->join(array('po'=>'polozky'),
                'pz.id_polozky = po.id_polozky',
                array('cisloZakazky'=>'cislo_zakazky'))
            ->join(array('stav_pozice'=>new Zend_Db_Expr('('.$subselectStavPozice.')')),
                'stav_pozice.id_pozice = pz.id_pozice',
                array())
            ->join(array('stav_polozky'=>new Zend_Db_Expr('('.$subselectStavPolozky.')')),
                'stav_polozky.id_polozky = pz.id_polozky',
                array())
            ->where('upper(nazev) LIKE ?',$this->_nazevPozice) // hledaný výraz
            ->where('stav_polozky.id_stavu NOT IN (18)')       // jakékoliv zakázky kromě stornovaných
            ->where('stav_pozice.id_stavu NOT IN (19)')        // jakékoliv pozice kromě smazaných
            ->order(array(
                new Zend_Db_Expr( "CAST(split_part(po.cislo_zakazky, '-', 3) AS INTEGER) DESC" ),  // seřaď sestupně podle let
                new Zend_Db_Expr( "CAST(split_part(po.cislo_zakazky, '-', 1) AS INTEGER) DESC" )   // seřaď sestupně podle čísla zakázky
            ));
        
        return $this->_adapter->fetchAll($select);
    }

    /**
     * Pro danou pozici (podle nastaveného ID pozice) zjistíme, jakými technologiemi
     * se pozice skutečně vyráběla
     * 
     * @return array
     * 
     * @todo Jde vylepšit seřazování technologií podle pořadí, i když tady by mohl
     * vzniknout problém při vyhledávání u starých pozic, jejichž některé technolgie
     * už ani žádné pořadí mít nemusí
     */
    public function zjistiSkutecneTechnologie()
    {
        $select = $this->_adapter->select()
            ->from(array('t'=>'technologie'),
                array('id'=>'cislo_technologie','nazev'=>'nazev_technologie'))
            ->join(array('oc'=>'odpracovane_casy'),
                'oc.cislo_technologie = t.cislo_technologie',
                array())
            ->where('oc.id_pozice = ?',$this->_id)
            ->group('t.cislo_technologie')
            ->order('t.cislo_technologie');
        
        return $this->_adapter->fetchAll($select);
    }

    public function getId() {
        return $this->_id;
    }

    public function setId($id) {
        $this->_id = $id;
    }

    public function getNazevPozice() {
        return $this->_nazevPozice;
    }

    public function setNazevPozice($nazevPozice) {
        $this->_nazevPozice = $nazevPozice;
    }
}
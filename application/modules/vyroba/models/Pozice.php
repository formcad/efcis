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
        $select = self::$_adapter->select()
            ->from('pozice',array('pocet' => 'count(id_pozice)'))
            ->where('id_pozice = ?', $this->_id);
        
        $data = self::$_adapter->fetchRow($select);
        
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
        $subselectDatumStavuPozice = self::$_adapter->select()
            ->from(array('st_poz'=>'stav_pozice'),
                array('max(datum_prirazeni)'))
            ->where('st_poz.id_pozice = s_poz.id_pozice');
        
        // pomocný subselect pro $select
        // zjištění aktuálního stavu pozice
        $subselectStavPozice = self::$_adapter->select()
            ->from(array('s_poz'=>'stav_pozice'),
                array('id_pozice','id_stavu'))
            ->where('datum_prirazeni = (?)',new Zend_Db_Expr($subselectDatumStavuPozice));  // pouze nejaktuálnější stav
        
        /**** Stav položky ****************************************************/
        
        // pomocný subselect pro $subselectStavPolozky
        // výběr data poslední změny stavu položky
        $subselectDatumStavuPolozky = self::$_adapter->select()
            ->from(array('st_pol'=>'stav_polozky'),
                array('max(datum_prirazeni)'))
            ->where('st_pol.id_polozky = s_pol.id_polozky');
        
        // pomocný subselect pro $select
        // zjištění aktuálního stavu zakázky
        $subselectStavPolozky = self::$_adapter->select()
            ->from(array('s_pol'=>'stav_polozky'),
                array('id_polozky','id_stavu'))
            ->where('id_typu = ?', 201)                                                      // typ položky je zakázka
            ->where('datum_prirazeni = (?)',new Zend_Db_Expr($subselectDatumStavuPolozky));  // pouze nejaktuálnější stav
                
        /**** Celkový select **************************************************/
        
        $select = self::$_adapter->select()
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
        
        return self::$_adapter->fetchAll($select);
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
        $select = self::$_adapter->select()
            ->from(array('t'=>'technologie'),
                array('id'=>'cislo_technologie','nazev'=>'nazev_technologie'))
            ->join(array('oc'=>'odpracovane_casy'),
                'oc.cislo_technologie = t.cislo_technologie',
                array())
            ->where('oc.id_pozice = ?',$this->_id)
            ->group('t.cislo_technologie')
            ->order('t.cislo_technologie');
        
        return self::$_adapter->fetchAll($select);
    }
    
    /**
     * Pro danou pozici (podle nastaveného ID pozice) zjistíme všechny úkonoy
     * výroby této pozice
     * 
     * @return array
     */
    public function zjistiSkutecnouVyrobu()
    {
        $select = self::$_adapter->select()
            ->from(array('oc'=>'odpracovane_casy'),
                array('id'=>'id_zaznamu','start'=>'time_start','end'=>'time_end',
                    'update'=>'time_update'))
            ->join(array('os'=>'osoby'),
                'os.id_osoby = oc.id_osoby',
                array('vyrabiJmeno'=>'jmeno','vyrabiPrijmeni'=>'prijmeni'))
            ->joinLeft(array('om'=>'osoby'),
                'om.id_osoby = oc.id_naposled_upravil',
                array('meniJmeno'=>'jmeno','meniPrijmeni'=>'prijmeni'))
            ->join(array('t'=>'technologie'),
                't.cislo_technologie = oc.cislo_technologie',
                array('technologie'=>'nazev_technologie'))
            ->join(array('pz'=>'pozice'),
                'pz.id_pozice = oc.id_pozice',
                array('nazevPozice'=>'nazev'))
            ->join(array('po'=>'polozky'),
                'pz.id_polozky = po.id_polozky',
                array('zakazka'=>'cislo_zakazky'))
            ->where('oc.id_pozice = ?',$this->_id)
            ->order(array('oc.time_start'));
        
        $data = self::$_adapter->fetchAll($select);
        
        // dopočítáme trvání operací
        if (count($data)>0) {
            foreach ($data as $index => $zaznam) {
                
                $trvani = strtotime($zaznam['end']) - strtotime($zaznam['start']);             
                $trvani < 60 ? $trvani = 0 : $trvani = round($trvani/3600,2);
                
                $timeStart = strtotime($zaznam['start']);
                // pokud byl záznam vložen ručně, vypíšeme pouze datum vložení
                if (date('H:i:s', $timeStart) == '00:00:00') {
                    $zacatek = date('d. m. Y', $timeStart); 
                } 
                // jinak vypíšeme celé datum včetně času
                else {
                    $zacatek =  date('d. m. Y H:i', $timeStart); 
                }         
                
                if ($zaznam['update'] == null) {
                    $updateRow = '';
                } else {
                    $updateRow = date('d. m. Y, H:i', strtotime($zaznam['update'])).', '
                    .$zaznam['meniJmeno'].' '.$zaznam['meniPrijmeni'];
                } 

                $data[$index]['delkaOperace'] = $trvani;
                $data[$index]['zacatekPrace'] = $zacatek;
                $data[$index]['zaznamUpdate'] = $updateRow;
            }
        }
        
        return $data;
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
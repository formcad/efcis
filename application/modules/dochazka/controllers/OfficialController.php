<?php

class Dochazka_OfficialController extends Zend_Controller_Action
{
    /**
     * Session namespace formcad
     * @var
     */
    private static $_session = null;

    /**
     * Uložení identity uživatele
     * @var
     */
    private static $_identity = null;
    
    /**
     * Proměnná, kam se ukládá pauza vznikající při redkukci pole záznamů
     * příchodů a odchodů
     * @var float
     */
    private $_tempPreruseni;
    
    public function init()
    {
        self::$_session = new Zend_Session_Namespace('formcad');
        self::$_identity = Zend_Auth::getInstance()->getIdentity();     
    }
    
    public function indexAction()
    {
        // action body
    }

    public function novyVykazAction()
    {   
        // do view přidáme monthpicker JS
        $this->view->headScript()->appendFile(
            '/js/jquery/jquery-ui-monthpicker.js',
            'text/javascript'
        );        
                
        // měsíce, pro které lze vytvořit výkaz docházky
        $this->view->form = new Dochazka_Form_MesicOficialniDochazky();
        

        /**** DATA DO VIEW ****************************************************/
 
        $url = $this->_helper->url;
        $this->view->leftNavigation = array(
            array(
                'img' => 'modul-dochazka.png',
                'url' => $url->url(array('module' => 'dochazka',
                                         'controller' => 'show',
                                         'action' => 'index')),
                'text' => 'Docházka')
        );
        $this->view->uzivatel = self::$_session->uzivatel;  
    }

    public function tvorbaVykazuAction()
    {
        $datum = $this->getRequest()->getPost('mesicDochazky');
        $mesic = substr($datum,0,strpos($datum,'/'));
        $rok = substr($datum,strpos($datum,'/')+1);
        
        /*** VÝJIMKY **********************************************************/
        
        try {
            // zjistíme, zda pro tento měsíc už výkaz není vytvořen
            $dochazka = new Dochazka_Model_DochazkaOficialni();
            $dochazka->setOsoba(self::$_session->idOsoby);
            $dochazka->setCip(self::$_session->idCipu);
            $dochazka->setUzivatel(self::$_identity->id);
            $dochazka->setMesic($mesic);
            $dochazka->setRok($rok);

            // výjimka v případě, že už je pro měsíc vytvořená oficiální docházka
            if ($dochazka->overExistenci() == true) {
                throw new Exception('Pro daný měsíc je už oficiální docházka zaměstnance vytvořená');        
            }

            // podíváme se na chyby v docházce (tabulka chyb)        
            $akce = new Dochazka_Model_AkceDochazky();
            $akce->setIdUser(self::$_session->idOsoby);
            $akce->setIdChip(self::$_session->idCipu);   
            $akce->setDateFrom('01-'.$mesic.'-'.$rok.' 00:00:00');
            $akce->setDateTo(cal_days_in_month(CAL_GREGORIAN, $mesic, $rok).'-'.$mesic.'-'.$rok.' 23:59:59');
        
            $poleError = $akce->getErrorAkce(); 
            if (!empty($poleError)) {
                throw new Exception('V tabulce chyb jsou v daném měsíci zaznamenané chyby - nejprve je odstraňte'); 
            }

            // podíváme se na chyby do struktury dat
            $poleZaznamu = $akce->getAkce();

            if (count($poleZaznamu) > 0) {

                $souborIntegrity = $this->_helper->integrityCheck($poleZaznamu);            
                foreach ($souborIntegrity as $zaznam) {
                    // v případě, že je tam neintegritní vstup, jde o chybu
                    if ($zaznam == false) {
                        throw new Exception('V tabulce záznamů jsou v daném měsíci zaznamenané chyby (červené řádky) - nejprve je odstraňte'); 
                        break;
                    }
                }
            }
        }
        catch (Exception $e) {
            $this->view->exceptionMessage = $e->getMessage();            
        }
        
        /**** ZPRACOVÁNÍ DAT **************************************************/       

        $data = array();
        if (count($poleZaznamu) > 0) {
        foreach ($poleZaznamu as $index => $den) {
            
            /**** INICIALIZACE PROMĚNNÝCH *************************************/
            
            $data[$index]['polePruchodu'] = array();    // výsledné pole průchodů
            $tempPruchody = array();                    // průběžné pole průchodů
            $data[$index]['sumaPreruseni'] = 0;         // výsledná suma přerušení   
            $sumaPreruseni = 0;                         // průběžná suma přerušení        
            $this->_tempPreruseni = 0;                  // přenos přerušení z funce redukující docházku
            
            /**** PRŮCHODY ****************************************************/

            $pocetPruchodu = count($den['pruchody']);

            // nejprve si uděláme pole průchodů
            $i = 0;
            for ($i == 0; $i < $pocetPruchodu; $i = $i+2 ) {

                // za předpokladu, že záznam je více než 3 minuty ho budeme dál zpracovávat
                if (strtotime($den['pruchody'][$i+1]['timestamp']) - strtotime($den['pruchody'][$i]['timestamp']) > 180) {                    
                    $tempPruchody[] = array(
                        'prichod' => $den['pruchody'][$i]['dbTimestamp'],
                        'odchod' => $den['pruchody'][$i+1]['dbTimestamp']
                    );
                }                        
            }  
            
            // pokud je dvojic příchod-odchod víc, zkusíme jejich počet zredukovat
            if (count($tempPruchody) > 1) {
                do {                
                    $puvodniPocet = count($tempPruchody);
                    $tempPruchody = $this->_redukujPole($tempPruchody);
                    $novyPocet = count($tempPruchody);
                }
                while ($puvodniPocet > $novyPocet);               
            }
            
            // vzniklé výsledné pole si uložíme 
            foreach ($tempPruchody as $pruchod) {
                $data[$index]['polePruchodu'][] = array(
                    'prichod' => $pruchod['prichod'],
                    'odchod' => $pruchod['odchod']
                );
            }
            
            /**** PŘERUŠENÍ ***************************************************/
            
            foreach ($den['preruseni'] as $preruseni) {                        
                $sumaPreruseni += $preruseni['delka'];
            }    
            // k sumě přerušení přičteme přerušení vzniklá redukcí průchodů
            $sumaPreruseni += ($this->_tempPreruseni / 3600); 
   
            $data[$index]['sumaPreruseni'] = round($sumaPreruseni,2); 
            
            /**** PŘÍPLATKY ***************************************************/
            
            foreach ($den['priplatky'] as $priplatek) {
                
                $data[$index]['sumaPriplatku'][$priplatek['idPriplatku']] += $priplatek['delka'];              
            }
            
            /**** OSTATNÍ *****************************************************/
            
            $data[$index]['datum'] = $den['datum'];
        }
       
        /**** ULOŽENÍ DO DATABÁZE *********************************************/
        
        // zapíšeme do oficialni_dochazka založení docházky
        $idDochazky = $dochazka->zalozDochazku();        
        
        // do modelu přehrajeme data
        $dochazka->setNovaOficialniData($data);
        
        // uložíme oficiální průchody
        $dochazka->ulozNoveOficialniPruchody();
        
        // uložíme oficiální přerušení
        $dochazka->ulozNoveOficialniPreruseni();
        
        // uložíme oficiální příplatky
        $dochazka->ulozNoveOficialniPriplatky();
        
        // a přesměrujeme pryč
        $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector');
        $redirector->gotoUrlAndExit('/dochazka/official/zmena-vykazu/id/'.$idDochazky);        
        
        }
        // jinak se vytvoří view 
        else {
        
            /**** DATA DO VIEW ****************************************************/

            $url = $this->_helper->url;
            $this->view->leftNavigation = array(
                array(
                    'img' => 'modul-dochazka.png',
                    'url' => $url->url(array('module' => 'dochazka',
                                             'controller' => 'show',
                                             'action' => 'index')),
                    'text' => 'Docházka')
            );
            $this->view->uzivatel = self::$_session->uzivatel;     
        
        }
    }
    
    public function zmenaVykazuAction()
    {
        // zjistíme ID výkazu
        $idVykazu = $this->getRequest()->getParam('id');
        
        // získáme rok a měsíc výkazu
        $dochazkaOficialni = new Dochazka_Model_DochazkaOficialni();
        $dochazkaOficialni->setOsoba(self::$_session->idOsoby);
        $dochazkaOficialni->setCip(self::$_session->idCipu);
        $dochazkaOficialni->setIdDochazky($idVykazu);
        
        $rozsah = $dochazkaOficialni->getRozsahDochazky();
        
        // máme časový rozsah, získáme data oficiální docházky
        $dochazkaOficialni->setDatumOd($rozsah['rok'].'-'.$rozsah['mesic'].'-01');
        $dochazkaOficialni->setDatumDo($rozsah['rok'].'-'.$rozsah['mesic'].'-'.cal_days_in_month(CAL_GREGORIAN,$rozsah['mesic'],$rozsah['rok']) );
        
        // získáme pole oficiální docházky
        $poleZaznamu = $dochazkaOficialni->getAkce();
        
        
        /**** DATA DO VIEW ****************************************************/
 
        $url = $this->_helper->url;
        $this->view->leftNavigation = array(
            array(
                'img' => 'modul-dochazka.png',
                'url' => $url->url(array('module' => 'dochazka',
                                         'controller' => 'show',
                                         'action' => 'index'),null,true),
                'text' => 'Docházka')
        );    
        $this->view->uzivatel = self::$_session->uzivatel;  
    }

    
    public function vyberVykazuAction()
    {
        $poleDochazky = array();
        
        $dochazka = new Dochazka_Model_DochazkaOficialni();
        $dochazka->setOsoba(self::$_session->idOsoby);
        $dochazka->setCip(self::$_session->idCipu);
        
        $poleZaznamu = $dochazka->getOficialniDochazka();
        
        // pole záznamů musíme přeskupit do jiné podoby
        foreach ($poleZaznamu as $zaznam) {
            $poleDochazky[$zaznam['rok']][] = array(
                'mesic' => $zaznam['mesic'].'/'.$zaznam['rok'],
                'id' => $zaznam['id']
            );
        }        
        $this->view->poleDochazky = $poleDochazky;
        
        /**** DATA DO VIEW ****************************************************/
 
        $url = $this->_helper->url;
        $this->view->leftNavigation = array(
            array(
                'img' => 'modul-dochazka.png',
                'url' => $url->url(array('module' => 'dochazka',
                                         'controller' => 'show',
                                         'action' => 'index')),
                'text' => 'Docházka')
        );    
        $this->view->uzivatel = self::$_session->uzivatel;          
    }
    
    
    
    /**
     * Relativně hloupá, ale užitečná funkce. Pokud rozdíl příchodu a odchodu
     * v jednotlivých záznamech vstupního pole je < 1 hodina, potom se tyto dva 
     * záznamy spojí. Funkce tak zredukuje jeden řádek pole záznamů. Aby byla
     * redukce úplná, je potřeba z Controlleru volat funkci dokud nebude počet
     * vstupních záznamů pole roven počtu výstupů z pole. Pauza, která vznikne
     * redukcí záznamů se ukládá do proměnné třídy, odkud k ní přistupuje
     * controller.
     * 
     * @param array $poleDochazky
     * @return array 
     */
    protected function _redukujPole($poleDochazky) 
    {
        $vyslednePole = array();              
        $hotovo = false;
        
        // pro každý řádek pole docházky
        $j = 0; 
        for ($j == 0; $j < count($poleDochazky); $j++ ) {
             
            $odchod = strtotime($poleDochazky[$j]['odchod']);
            $prichod = strtotime($poleDochazky[$j+1]['prichod']);          
            
            // řádek pole docházky kopírujeme rovnou do výsledného pole, když:
            // A. už se provedlo zredukování pole o jeden řádek
            // B. jsme v posledním cyklu a není tak možné určit příchod $j+1
            // C. když mezi příchodem a odchodem uplynula víc než hodina
            if ( $hotovo or $j == (count($poleDochazky)-1) or ($prichod - $odchod) > 3600 ) {
                $vyslednePole[] = $poleDochazky[$j];
            }            
            // pokud mezi odchodem a příchodem uplynula méně než hodina,
            // zaznamená se to jako pauza a docházka je o řádek redukovaná          
            else {
                
                $this->_tempPreruseni += $prichod - $odchod;
                              
                // spojíme původní pole a redukované pole a to tak, aby na sebe 
                // záznamy časově navazovaly --> bude scházet akorát jeden index
                $vyslednePole[] = array(
                    'prichod' => $poleDochazky[$j]['prichod'],
                    'odchod' => $poleDochazky[$j+1]['odchod']
                );
                
                // zredukovaný řádek neprocházíme
                $j++;
                // a kvůli nechtěnému nepořádku v indexech další řádky neredukujeme
                $hotovo = true;
            } 
        } 
        return $vyslednePole;   
    }
}

<?php

/**
 * Controller pro zápis režijních a výrobních časů
 */

class Vyroba_ZapisController extends Zend_Controller_Action
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

    public function init()
    {
        self::$_session = new Zend_Session_Namespace('formcad');
        self::$_identity = Zend_Auth::getInstance()->getIdentity();     
    }

    public function indexAction()
    {
        
    }

    public function addAction()
    { 
        $url = $this->_helper->url;
        
        if ($this->getRequest()->getParam('display') == 'rezie') {
            $display = 'rezie';
        } else {
            $display = 'vyroba';
        }
        
        $akceVyroby = new Vyroba_Model_AkceVyroby();
        $akceRezie = new Vyroba_Model_AkceRezie();
        
        $zamestnanci = new Application_Model_UserList();
        $zamestnanci->setId(self::$_identity->id);
        $zamestnanci->setRole(self::$_identity->roles['default']);      
        
        // pro forumuláře potřebujeme pole zaměstnanců
        $poleUzivatelu = $zamestnanci->getVyrobaUsers();
        $poleRezie = $zamestnanci->getRezieUsers();
        
        // výrobní formulář
        $vyrobaForm = $this->_initVyrobaForm($poleUzivatelu,false);
        $newElement = $vyrobaForm->addIdInput('0','',10,true);
        $vyrobaForm->addElement($newElement); 
        $vyrobaForm->getDisplayGroup('dily')->addElement($newElement);
        
        // režijní formulář
        $rezieForm = $this->_initRezieForm($poleRezie,false);

        /**** ZPRACOVÁNÍ OBSAHU ODESLANÉHO VÝROBNÍHO FORMULÁŘE ****************/
        
        // pokud je formulář odeslaný, přímo uložíme záznam bez standardního 
        // ověření, protože bylo provedeno ajaxovou akcí při odeslání formuláře
        $request = $this->getRequest();
        if ($request->isPost() and $request->getParam('pocetRadku') !== null) {
            
            $vyroba = new Vyroba_Model_ZaznamyVyroby();
            $uzivatele = new Application_Model_UserIdentity();
            $filter = new Zend_Filter_LocalizedToNormalized();
            
            // dekódování zaměstnance a jeho karty
            $uzivatele->setKodKarty($request->getParam('zamestnanec'));
            $user = $uzivatele->dekodujKartuZamestance();
            $time = strtotime(str_replace(" ","",$request->getParam('datum')));
            
            
            // získání pomocných časových hodnot
            $zacatek = mktime(0,0,0,date('m', $time),
                                    date('d', $time),
                                    date('Y', $time));
            $delka = $filter->filter($request->getParam('delka'))* 3600;
        
            // nastavení hodnot v modelu
            $vyroba->setIdUzivatele(self::$_identity->id);
            $vyroba->setIdKarty($user['idKarty']);
            $vyroba->setIdZamestnance($user['idZamestnance']);
            $vyroba->setIdOperace($request->getParam('operace'));
            $vyroba->setTimeStart(date('Y-m-d 00:00:00', $time));
            $vyroba->setTimeEnd(date('Y-m-d H:i:s', ($zacatek + $delka)));
            
            foreach ($request->getParam('id') as $idPozice){
                $vyroba->setIdPozice($idPozice);
                $vyroba->ulozZaznam();
            }
            
            self::$_session->params = array(
                'zamestnanecVyroba' => $request->getPost("zamestnanec"),
                'datumVyroba' => $request->getPost("datum"),
                'operaceVyroba' => $request->getPost("operace"),                
            );
            
            $this->_helper->redirector('add', 'zapis', 'vyroba',array('display' => 'vyroba'));          
        }     
        
        /**** ZPRACOVÁNÍ OBSAHU ODESLANÉHO REŽIJNÍHO FORMULÁŘE ****************/
        
        elseif ($request->isPost() and $request->getParam('pocetRadku') == null) {
            
            $rezie = new Vyroba_Model_ZaznamyRezie();
            $uzivatele = new Application_Model_UserIdentity();
            $filter = new Zend_Filter_LocalizedToNormalized();
            
            // dekódování zaměstnance a jeho karty
            $uzivatele->setKodKarty($request->getParam('zamestnanecRezie'));
            $user = $uzivatele->dekodujKartuZamestance();
            $time = strtotime(str_replace(" ","",$request->getParam('datumRezie')));
            
            
            // získání pomocných časových hodnot
            $zacatek = mktime(0,0,0,date('m', $time),
                                    date('d', $time),
                                    date('Y', $time));
            $delka = $filter->filter($request->getParam('delkaRezie'))* 3600;
        
            // nastavení hodnot v modelu
            $rezie->setIdUzivatele(self::$_identity->id);
            $rezie->setIdZamestnance($user['idZamestnance']);
            $rezie->setIdOperace($request->getParam('operaceRezie'));
            $rezie->setTimeStart(date('Y-m-d 00:00:00', $time));
            $rezie->setTimeEnd(date('Y-m-d H:i:s', ($zacatek + $delka)));
            $rezie->setTimeUpdate(date('Y-m-d H:i:s'));
            
            if (strlen($request->getParam('poznamkaRezie'))>0)
                $rezie->setPoznamka($request->getParam('poznamkaRezie'));
            
            $rezie->ulozZaznam();
            
            self::$_session->params = array(
                'zamestnanecRezie' => $request->getPost("zamestnanecRezie"),
                'datumRezie' => $request->getPost("datumRezie"),
                'operaceRezie' => $request->getPost("operaceRezie")          
            );          
                    
            $this->_helper->redirector('add', 'zapis', 'vyroba',array('display' => 'rezie')); 
        }
        
        /**** TABULKY SE ZÁZNAMY **********************************************/
        
        foreach ($akceVyroby->getPosledniZaznamy() as $zaznam) {

            $vyrobniZaznamy[] = array(
                'idZaznamu' => $zaznam['idZaznamu'],
                'idPozice' => $zaznam['idPozice'],
                'vyrobil' => $zaznam['userJmeno'].' '.$zaznam['userPrijmeni'],
                'dil' => $zaznam['nazevPozice'],
                'zakazka' => $zaznam['cisloZakazky'],
                'operace' => $zaznam['technologie'],
                'datum' => date('d. m. Y', strtotime($zaznam['start'])),
                'casVyroby' => $this->_getUpravenyCas($zaznam['start'], $zaznam['end']),
                'zapsal' => $zaznam['zmenilJmeno'].' '.$zaznam['zmenilPrijmeni'],
                'zmena' => $this->_getZmenaZaznamu($zaznam,
                                self::$_identity->id,
                                self::$_identity->roles['vyroba'])
            );
        }
        
        foreach ($akceRezie->getPosledniZaznamy() as $zaznam) {
            
            $rezijniZaznamy[] = array(
                'idZaznamu' => $zaznam['idZaznamu'],                
                'vyrobil' => $zaznam['userJmeno'].' '.$zaznam['userPrijmeni'],
                'operace' => $zaznam['technologie'],
                'datum' => date('d. m. Y', strtotime($zaznam['start'])),
                'casTrvani' => $this->_getUpravenyCas($zaznam['start'], $zaznam['end']),
                'zapsal' => $zaznam['zmenilJmeno'].' '.$zaznam['zmenilPrijmeni'],
                'poznamka' => $zaznam['poznamka'],
                'zmena' => $this->_getZmenaZaznamu($zaznam,
                                self::$_identity->id,
                                self::$_identity->roles['vyroba'])
            );
        }
        
        /**** DATA DO VIEW ****************************************************/ 
            
        $this->view->vyrobaForm = $vyrobaForm;
        $this->view->rezieForm = $rezieForm;
        
        $this->view->display = $display;
        $this->view->vyrobniZaznamy = $vyrobniZaznamy;
        $this->view->rezijniZaznamy = $rezijniZaznamy;
        $this->view->naseptavacPozice = $this->_initNaseptavacForm(null);
        
        // návratová stránka je závislá na uživatelském oprávnění
        if (self::$_identity->roles['default'] == 'employee') {
            $this->view->leftNavigation = array(
                array(
                    'img' => 'home.png',
                    'url' => $url->url(array('module' => 'default',
                                             'controller' => 'employee',
                                             'action' => 'index'),
                                       null, true),
                    'text' => 'Domů')
            );             
        } 
        // jinak se skočí na docházku
        else {
            $this->view->leftNavigation = array(
                array(
                    'img' => 'modul-dochazka.png',
                    'url' => $url->url(array('module' => 'dochazka',
                                             'controller' => 'show',
                                             'action' => 'index'),
                                       null, true),
                    'text' => 'Docházka')
            );               
        }        
    }
    
    public function editVyrobaAction()
    {
        $url = $this->_helper->url;
        
        $vyroba = new Vyroba_Model_ZaznamyVyroby();
        $filter = new Zend_Filter_NormalizedToLocalized();
        
        $zamestnanci = new Application_Model_UserList();
        $zamestnanci->setId(self::$_identity->id);
        $zamestnanci->setRole(self::$_identity->roles['default']);      
        
        // pro forumuláře potřebujeme pole zaměstnanců
        $poleUzivatelu = $zamestnanci->getVyrobaUsers();
        
        // výrobní formulář
        $vyrobaForm = $this->_initVyrobaForm($poleUzivatelu,true);
        $vyrobaForm->removeElement('pridejRadek');
        $newElement = $vyrobaForm->addIdInput('0','',10,true);
        $vyrobaForm->addElement($newElement); 
        $vyrobaForm->getDisplayGroup('dily')->addElement($newElement);

        /**** ZPRACOVÁNÍ OBSAHU ODESLANÉHO VÝROBNÍHO FORMULÁŘE ****************/
        
        // pokud je formulář odeslaný, přímo uložíme záznam bez standardního 
        // ověření, protože bylo provedeno ajaxovou akcí při odeslání formuláře
        $request = $this->getRequest();
        if ($request->isPost()) {
            
            $uzivatele = new Application_Model_UserIdentity();
            $filter = new Zend_Filter_LocalizedToNormalized();
            
            // dekódování zaměstnance a jeho karty
            $uzivatele->setKodKarty($request->getParam('zamestnanec'));
            $user = $uzivatele->dekodujKartuZamestance();
            $time = strtotime(str_replace(" ","",$request->getParam('datum')));
            
            
            // získání pomocných časových hodnot
            $zacatek = mktime(0,0,0,date('m', $time),
                                    date('d', $time),
                                    date('Y', $time));
            $delka = $filter->filter($request->getParam('delka'))* 3600;
        
            // nastavení hodnot v modelu
            $vyroba->setIdUzivatele(self::$_identity->id);
            $vyroba->setIdZaznamu($request->getParam('idZaznamu'));
            $vyroba->setIdKarty($user['idKarty']);
            $vyroba->setIdZamestnance($user['idZamestnance']);
            $vyroba->setIdOperace($request->getParam('operace'));
            $vyroba->setTimeStart(date('Y-m-d 00:00:00', $time));
            $vyroba->setTimeEnd(date('Y-m-d H:i:s', ($zacatek + $delka)));
            
            $id = $request->getParam('id');
                $vyroba->setIdPozice($id[0]);
                $vyroba->zmenZaznam();
                        
            self::$_session->params = array(
                'zamestnanecVyroba' => $request->getPost("zamestnanec"),
                'datumVyroba' => $request->getPost("datum"),
                'operaceVyroba' => $request->getPost("operace"),                
            );
            
            switch($request->getParam('navrat')) {
                
                case 'show-kontrola-vyroby':
                    $this->_helper->redirector('show-kontrola-vyroby', 'time', 'vyroba');  
                    break;
                
                case 'show-prace':
                    $this->_helper->redirector('show-prace', 'time', 'vyroba');  
                    break;
                
                case 'add':
                    $this->_helper->redirector('add','zapis','vyroba',array('display'=>'vyroba'));  
                    break;
            }
        } 
        // jinak musíme vyplnit formulář
        else {
            $vyroba->setIdZaznamu($this->getRequest()->getParam('zmena'));
            $data = $vyroba->getZaznam();          
          
            $formData = array(
                'pocetRadku' => 1,
                'idZaznamu' => $data['idZaznamu'],
                'navrat' => $this->getRequest()->getParam('navrat'),
                'id' => array( '0' => $data['idPozice']),
                'zamestnanec' => $data['vyrobniKarta'],
                'datum' => date('d. m. Y',strtotime($data['start'])),
                'delka' => $filter->filter($this->_getUpravenyCas($data['start'], $data['end'])),
                'operace' => $data['operace']                
            );
            $vyrobaForm->populate($formData);
        }
        
        /**** DATA DO VIEW ****************************************************/ 
         
        $this->view->vyrobaForm = $vyrobaForm;
        $this->view->naseptavacPozice = $this->_initNaseptavacForm(null);
         
        // návratová stránka je závislá na uživatelském oprávnění
        if (self::$_identity->roles['default'] == 'employee') {
            $this->view->leftNavigation = array(
                array(
                    'img' => 'home.png',
                    'url' => $url->url(array('module' => 'default',
                                             'controller' => 'employee',
                                             'action' => 'index'),
                                       null, true),
                    'text' => 'Domů')
            );             
        } 
        // jinak se skočí na docházku
        else {
            $this->view->leftNavigation = array(
                array(
                    'img' => 'modul-dochazka.png',
                    'url' => $url->url(array('module' => 'dochazka',
                                             'controller' => 'show',
                                             'action' => 'index'),
                                       null, true),
                    'text' => 'Docházka')
            );               
        }             
    }
    
    public function editRezieAction()
    {
        $url = $this->_helper->url;
        
        $rezie = new Vyroba_Model_ZaznamyRezie();
        $filter = new Zend_Filter_NormalizedToLocalized();
        
        $zamestnanci = new Application_Model_UserList();
        $zamestnanci->setId(self::$_identity->id);
        $zamestnanci->setRole(self::$_identity->roles['default']);      
        
        // pro forumuláře potřebujeme pole zaměstnanců
        $poleRezie = $zamestnanci->getRezieUsers();
        
        // režijní formulář
        $rezieForm = $this->_initRezieForm($poleRezie,true);

        /**** ZPRACOVÁNÍ OBSAHU ODESLANÉHO VÝROBNÍHO FORMULÁŘE ****************/
        
        // pokud je formulář odeslaný, přímo uložíme záznam bez standardního 
        // ověření, protože bylo provedeno ajaxovou akcí při odeslání formuláře
        $request = $this->getRequest();
        if ($request->isPost()) {
            
            $uzivatele = new Application_Model_UserIdentity();
            $filter = new Zend_Filter_LocalizedToNormalized();
            
            // dekódování zaměstnance a jeho karty
            $uzivatele->setKodKarty($request->getParam('zamestnanecRezie'));
            $user = $uzivatele->dekodujKartuZamestance();
            $time = strtotime(str_replace(" ","",$request->getParam('datumRezie')));
            
            
            // získání pomocných časových hodnot
            $zacatek = mktime(0,0,0,date('m', $time),
                                    date('d', $time),
                                    date('Y', $time));
            $delka = $filter->filter($request->getParam('delkaRezie'))* 3600;
        
            // nastavení hodnot v modelu
            $rezie->setIdZaznamu($request->getParam('idZaznamu'));
            $rezie->setIdUzivatele(self::$_identity->id);
            $rezie->setIdZamestnance($user['idZamestnance']);
            $rezie->setIdOperace($request->getParam('operaceRezie'));
            $rezie->setTimeStart(date('Y-m-d 00:00:00', $time));
            $rezie->setTimeEnd(date('Y-m-d H:i:s', ($zacatek + $delka)));
            $rezie->setTimeUpdate(date('Y-m-d H:i:s'));
            
            if (strlen($request->getParam('poznamkaRezie'))>0)
                $rezie->setPoznamka($request->getParam('poznamkaRezie'));
            
            $rezie->zmenZaznam();
                        
            self::$_session->params = array(
                'zamestnanecRezie' => $request->getPost("zamestnanecRezie"),
                'datumRezie' => $request->getPost("datumRezie"),
                'operaceRezie' => $request->getPost("operaceRezie")          
            );        

            switch($request->getParam('navrat')) {
                
                case 'show-kontrola-rezie':
                    $this->_helper->redirector('show-kontrola-rezie', 'time', 'vyroba');  
                    break;
                
                case 'show-prace':
                    $this->_helper->redirector('show-prace', 'time', 'vyroba');  
                    break;
                
                case 'add':
                    $this->_helper->redirector('add','zapis','vyroba',array('display'=>'rezie'));  
                    break;
            }                                   
        } 
        // jinak musíme vyplnit formulář
        else {
            $rezie->setIdZaznamu($this->getRequest()->getParam('zmena'));
            $data = $rezie->getZaznam();          
          
            $formData = array(        
                'idZaznamu' => $data['idZaznamu'],
                'navrat' => $this->getRequest()->getParam('navrat'),
                'zamestnanecRezie' => $data['rezijniKarta'],
                'datumRezie' => date('d. m. Y',strtotime($data['start'])),
                'delkaRezie' => $filter->filter($this->_getUpravenyCas($data['start'], $data['end'])),
                'operaceRezie' => $data['operace'],
                'poznamkaRezie' => $data['poznamka']
            );
            $rezieForm->populate($formData);
        }
        
        /**** DATA DO VIEW ****************************************************/ 
            
        $this->view->rezieForm = $rezieForm;   
        
        // návratová stránka je závislá na uživatelském oprávnění
        if (self::$_identity->roles['default'] == 'employee') {
            $this->view->leftNavigation = array(
                array(
                    'img' => 'home.png',
                    'url' => $url->url(array('module' => 'default',
                                             'controller' => 'employee',
                                             'action' => 'index'),
                                       null, true),
                    'text' => 'Domů')
            );             
        } 
        // jinak se skočí na docházku
        else {
            $this->view->leftNavigation = array(
                array(
                    'img' => 'modul-dochazka.png',
                    'url' => $url->url(array('module' => 'dochazka',
                                             'controller' => 'show',
                                             'action' => 'index'),
                                       null, true),
                    'text' => 'Docházka')
            );               
        }                     
    }
    

    /**
     * Získání formuláře zápisu režie
     * 
     * @var array $uzivatele 
     * @var boolean $zmena
     * @return ZendX_JQuery_Form
     */
    protected function _initVyrobaForm($uzivatele,$zmena)
    {
        $url = $this->_helper->url;
        
        // nastavení defaultních parametrů formuláře
        $params = self::$_session->params;
        
        if ($params['zamestnanecVyroba'] == null) { $zamestnanec = self::$_identity->id; } 
        else { $zamestnanec = $params['zamestnanecVyroba']; }
            
        if ($params['datumVyroba'] == null) { $datum = date('d. m. Y');} 
        else { $datum = $params['datumVyroba']; }            
            
        if ($params['operaceVyroba'] == null) { $operace = 2; } 
        else { $operace = $params['operaceVyroba']; }            
                        
        $data = array(
            'zamestnanec' => $zamestnanec,
            'datum' => $datum,
            'operace' => $operace );
                
        // pro formulář potřebujeme seznam výrobních operací
        $operace = new Application_Model_OperationList();
        $operace->setVyrobaOrder(2);
        
        $seznamOperaci = $operace->getVyrobniOperace();
       
        $formClass = Vyroba_Form_Vyroba;
        $formClass::$zamestnanci = $uzivatele;
        $formClass::$operace = $seznamOperaci;
        $formClass::$default = $data;
        
        $form = new $formClass();                        
        
        switch ($zmena) {
            case true:
                $action = $url->url(array('module' => 'vyroba',
                                          'controller' => 'zapis', 
                                          'action' => 'edit-vyroba'),
                                    null, true);                     
                break;
            case false:
                $action = $url->url(array('module' => 'vyroba',
                                          'controller' => 'zapis', 
                                          'action' => 'add'),
                                    null, true);                     
                break;
        }        
        $form->setAction($action);    
              
        return $form;
    }
    
    /**
     * Získání formuláře zápisu výroby
     * 
     * @var array $uzivatele 
     * @return ZendX_JQuery_Form
     */
    protected function _initRezieForm($uzivatele,$zmena)
    {
        $url = $this->_helper->url;
        
        // nastavení defaultních parametrů formuláře
        $params = self::$_session->params;
        
        if ($params['zamestnanecRezie'] == null) { $zamestnanec = self::$_identity->id; } 
        else { $zamestnanec = $params['zamestnanecRezie']; }
            
        if ($params['datumRezie'] == null) { $datum = date('d. m. Y');} 
        else { $datum = $params['datumRezie']; }            
            
        if ($params['operaceRezie'] == null) { $operace = 2; } 
        else { $operace = $params['operaceRezie']; }            
                        
        $data = array(
            'zamestnanec' => $zamestnanec,
            'datum' => $datum,
            'operace' => $operace );
        
        // pro formulář potřebujeme seznam výrobních operací
        $operace = new Application_Model_OperationList();
        
        $seznamOperaci = $operace->getRezijniOperace();
       
        $formClass = Vyroba_Form_Rezie;
        $formClass::$zamestnanci = $uzivatele;
        $formClass::$operace = $seznamOperaci;
        $formClass::$default = $data;
        
        $form = new $formClass();                        
                
        switch ($zmena) {
            case true:
                $action = $url->url(array('module' => 'vyroba',
                                          'controller' => 'zapis', 
                                          'action' => 'edit-rezie'),
                            null, true);                        
                break;
            case false:
                $action = $url->url(array('module' => 'vyroba',
                                          'controller' => 'zapis', 
                                          'action' => 'add'),
                                    null, true);                    
                break;
        }         
        
        $form->setAction($action);    
              
        return $form;
    }
    
    /**
     * Zjistí a správně zaokrouhlí čas výroby/režie ze zadaných časových mezí,
     * časové údaje menší než 1 minuta se nepočítají
     * 
     * @param string $start datum (a čas) v rozumném formátu - použití pro strtotime()
     * @param type $end datum (a čas) v rozumném formátu - použití pro strtotime()
     * @return float 
     * @throws exception 
     */
    protected function _getUpravenyCas($start,$end)
    {
        if ($start == null or $end == null)
        {
            throw ('Chybné časové meze v záznamu výroby');
        } else {
            
            $rawTime = strtotime($end) - strtotime($start);

            if ($rawTime < 60) { $time = 0; }
            else { $time = (ceil(($rawTime * 100) / 3600)) / 100; }            
            
            return $time;
        } 
    }

    /**
     * Získání formuláře našeptávače s defaultně zadaným ID položky
     * 
     * @param integer|null $idPolozky
     * @return \Vyroba_Form_Naseptavac
     */
    protected function _initNaseptavacForm($idPolozky)
    {
        $zakazky = new Application_Model_PolozkyList();
        $zakazky->setRole(self::$_identity->roles['default']);     
        $poleZakazek = $zakazky->getAktivniZakazky();
        
        switch ($idPolozky) {
            case null: $id = $poleZakazek[0]['id']; break;
            default: $id = $idPolozky;
        }        
        
        $pozice = new Application_Model_PoziceList();
        $pozice->setRole(self::$_identity->roles['default']);
        $pozice->setIdPolozky($id);
        
        Vyroba_Form_Naseptavac::$poleZakazek = $poleZakazek;    
        Vyroba_Form_Naseptavac::$polePozic = $pozice->getPlnohodnotnePozice();
        Vyroba_Form_Naseptavac::$idPolozky = $id;
        
        return new Vyroba_Form_Naseptavac;
    }
    
    /**
     * Doplnění seznamu pozic do formuláře našeptávače pro zjištění ID pozice
     */
    public function ajaxNaseptavacAction()
    {
        $this->_helper->getHelper('layout')->disableLayout();     
        
        $idPolozky = $this->_getParam('id');
        $this->view->form = $this->_initNaseptavacForm($idPolozky);
    }


    /**
     * Ajaxové přidání řádku pro ID pozice do výrobního formuláře
     */
    public function ajaxNovyRadekAction()
    {
        $this->_helper->getHelper('layout')->disableLayout();     

        $form = new Vyroba_Form_Vyroba();           
        $order = 10 + $this->_getParam('pocet');
        $input = $form->addIdInput($this->_getParam('pocet'),'',$order,false);        

        $this->view->element = $input->__toString();
    }
    
    /**
     * Ajaxová kontrola prováděná při odesílání výrobního formuláře
     */
    public function ajaxFormVyrobaCheckAction()
    {
        $this->_helper->getHelper('layout')->disableLayout();
        
        $pozice = new Vyroba_Model_Pozice();

        $chybaArray = array();

        $filterTrim = new Zend_Filter_StringTrim();            

        $validatorDelka = new Zend_Validate();
        $validatorDelka->addValidator(new Zend_Validate_NotEmpty)
            ->addValidator(new Zend_Validate_Float(Zend_Registry::get('Zend_Locale')));

        $validatorPozice = new Zend_Validate();
        $validatorPozice->addValidator(new Zend_Validate_Between(array('min' => 0,'max' => 1000000)))
            ->addValidator(new Zend_Validate_Int());            

        $validatorDatum = new Zend_Validate();
        $validatorDatum->addValidator(new Zend_Validate_NotEmpty())
            ->addValidator(new Zend_Validate_Date(array('format' => 'dd. mm. yy')));            

        // ověříme formát délky            
        $delkaTrim = $filterTrim->filter($this->_getParam('delka'));       
        if (!$validatorDelka->isValid($delkaTrim)) {
            $chybaArray[] = "Doba trvání operace není platné číslo";
        }

        // ověříme formát data
        $datumTrim = $filterTrim->filter($this->_getParam("datum"));
        if (!$validatorDatum->isValid($datumTrim)) {
            $chybaArray[] = "Datum není ve správném formátu";
        }

        // ověříme, zda ID dílů skutečně existují
        foreach ($this->_getParam('id') as $idPozice) {

            $filterId = $filterTrim->filter($idPozice);
            $pozice->setId($filterId);

            // ověření správnosti ID pozice a existence pozice v databázi
            if (!$validatorPozice->isValid($filterId)){
                $chybaArray[] = "ID pozice '$idPozice' není ve správném číselném formátu";
            }
            else {            
                if (!$pozice->overExistenci()) {
                    $chybaArray[] = "Pozice '$idPozice' není v databázi";
                }                                        
            }
        }           
        
        $this->view->chyby = $chybaArray;
    }
    
    /**
     * Ajaxová kontrola prováděná při odesílání režijního formuláře
     */
    public function ajaxFormRezieCheckAction()
    {
        $this->_helper->getHelper('layout')->disableLayout();
        
        $chybaArray = array();

        $filterTrim = new Zend_Filter_StringTrim();                  

        $validatorDelka = new Zend_Validate();
        $validatorDelka->addValidator(new Zend_Validate_NotEmpty)
            ->addValidator(new Zend_Validate_Float(Zend_Registry::get('Zend_Locale')));        
       
        $validatorDatum = new Zend_Validate();
        $validatorDatum->addValidator(new Zend_Validate_NotEmpty())
            ->addValidator(new Zend_Validate_Date(array('format' => 'dd. mm. yy')));         
        
        // ověříme formát délky            
        $delkaTrim = $filterTrim->filter($this->_getParam('delkaRezie'));       
        if (!$validatorDelka->isValid($delkaTrim)) {
            $chybaArray[] = "Doba trvání operace není platné číslo";
        }                          

        // ověříme formát data
        $datumTrim = $filterTrim->filter($this->_getParam("datumRezie"));
        if (!$validatorDatum->isValid($datumTrim)) {
            $chybaArray[] = "Datum není ve správném formátu";
        }
            
        $this->view->chyby = $chybaArray;        
    }
    
    /**
     * Funkce otestuje, zda zadaný uživatel může měnit konkrétní záznam výroby
     * 
     * @param array $zaznam Načtený záznam výroby
     * @param integer $userId Id uživatele
     * @param integer $userRole Role uživatele v modulu výroba
     */
    private function _getZmenaZaznamu($zaznam,$userId,$userRole) 
    { 
        switch ($userRole) {
            case 'employee':
                if ($zaznam['userId'] == $userId) {$zmena = true;}
                else  {$zmena = false;}
                break;
            case 'user':
                if ($zaznam['userId'] == $userId) {$zmena = true;}
                else  {$zmena = false;}
                break;
            case 'admin':
                 $zmena = true;
                break;
            default: $zmena = false;
        }
        return $zmena;
    }
}

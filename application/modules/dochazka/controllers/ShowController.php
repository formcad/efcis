<?php

class Dochazka_ShowController extends Zend_Controller_Action
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
        $identity = Zend_Auth::getInstance()->getIdentity();     
        $osobyDochazky = new Application_Model_UserList();
        $osobyDochazky->setRole($identity->roles['dochazka']);
        $osobyDochazky->setId($identity->id);        
        
        Dochazka_Form_ParametryDochazky::$users = $osobyDochazky->getUsers(); 
        $form = new Dochazka_Form_ParametryDochazky();
 
        $request = $this->getRequest();
        if ($request->isPost()) {
          
            // v případě odeslaného a zvalidovaného formuláře vybereme data
            if ($form->isValid($request->getPost())) {               
                               
                $uzivatel = explode(',',$request->getPost('osoba'));
               
                // uložíme data do session
                $datum = new Fc_Date_Format();
                         
                self::$_session->idOsoby = $uzivatel[0];
                self::$_session->idCipu = $uzivatel[1];
                self::$_session->od = $datum->dbDateFormat($request->getPost('od'));
                self::$_session->do = $datum->dbDateFormat($request->getPost('do'));
                
                $podrobnosti = new Dochazka_Model_Osoby();
                $podrobnosti->setId($uzivatel[0]);
                $user = $podrobnosti->getUserName();
                
                // v závislosti na ID čipu uložíme uživatelovo jméno
                switch (self::$_session->idCipu) {
                    case 1:
                        self::$_session->uzivatel = $user["jmeno"]." ".$user["prijmeni"];
                        self::$_session->uzivatelJmeno = $user["jmeno"];
                        self::$_session->uzivatelPrijmeni = $user["prijmeni"];
                        self::$_session->uzivatelCip = null;
                        break;
                    
                    case 2:
                        self::$_session->uzivatel = $user["jmeno"]." ".$user["prijmeni"].", přesčasy";
                        self::$_session->uzivatelJmeno = $user["jmeno"];
                        self::$_session->uzivatelPrijmeni = $user["prijmeni"];
                        self::$_session->uzivatelCip = '(B)';
                        break;
                }                
            }                  
        }  
        else {

            // vyplníme formulářová data, pokud už jsou náhodou v session
            if (isset(self::$_session->idOsoby)) {     
                
                $datumOd = $form->getElement('od');
                $datumOd->setValue(date('d. m. Y', strtotime(self::$_session->od)));     
                
                $datumDo = $form->getElement('do');
                $datumDo->setValue(date('d. m. Y', strtotime(self::$_session->do)));     
                
                $osoba = $form->getElement('osoba');
                $osoba->setValue(self::$_session->idOsoby.','.self::$_session->idCipu);
            }            
        }
        
        $url = $this->_helper->url;
        $this->view->leftNavigation = array(
            array(
                'img' => 'modul-dochazka.png',
                'url' => $url->url(array('module' => 'dochazka',
                                         'controller' => 'index',
                                         'action' => 'index')),
                'text' => 'Docházka')
        );        
        
        $this->view->form = $form;
        $this->view->navigace = (isset(self::$_session->idOsoby)) ? true : false;
        $this->view->uzivatel = self::$_session->uzivatel;    
    }

    public function chybyAction() 
    {
        $chyby = new Dochazka_Model_ErroryDochazky();        
        $this->view->poleChyb = $chyby->getAllErrors();        
        
        $url = $this->_helper->url;
        $this->view->leftNavigation = array(
            array(
                'img' => 'modul-dochazka.png',
                'url' => $url->url(array('module' => 'dochazka',
                                         'controller' => 'index',
                                         'action' => 'index')),
                'text' => 'Docházka')
        );                
    }
    
    /**
     * Funkce pro zjištění seznamu zaměstnanců přítomných v práci
     */
    public function pritomniAction()
    {
        $identity = Zend_Auth::getInstance()->getIdentity();  
        
        $pruchody = new Dochazka_Model_TempPruchod();
        $pruchody->setRole($identity->roles['dochazka']);
        
        $pruchody->setRole(self::$_session->idOsoby);
        
        $this->view->pritomniLide = $pruchody->zjistiPritomne();
        
        $url = $this->_helper->url;
        $this->view->leftNavigation = array(
            array(
                'img' => 'modul-dochazka.png',
                'url' => $url->url(array('module' => 'dochazka',
                                         'controller' => 'index',
                                         'action' => 'index')),
                'text' => 'Docházka')
        );          
    }
        

}

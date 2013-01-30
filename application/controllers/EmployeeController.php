<?php

/**
 * Controller používaný pro obsluhu požadavků uživatelů systému, kteří
 * mají pro
 * defalut module nastavené oprávnění "employee". Ve Formcadu tak jde o lidi
 * z výroby.
 */

class EmployeeController extends Zend_Controller_Action
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
        self::$_identity = Zend_Auth::getInstance()->getIdentity();             
        self::$_session = new Zend_Session_Namespace('formcad');
        
        if (!isset(self::$_session->idOsoby))
            self::$_session->idOsoby = self::$_identity->id;
        if (!isset(self::$_session->uzivatel))
            self::$_session->uzivatel = self::$_identity->jmeno." ".self::$_identity->prijmeni;
        if (!isset(self::$_session->od))
            self::$_session->od = date('Y-m-d', strtotime('-2 days'));
        if (!isset(self::$_session->do))
            self::$_session->do = date('Y-m-d');
    }
    
    /**
     * Index Action - základní rozhraní informačního systému pro zaměstnance,
     * tedy mnoho informací na jedné stránce (a v jedné akci controlleru)
     */
    public function indexAction()
    {
        /**** Do jakého listu se dostaneme při návratu ************************/

        if (null !== $this->getRequest()->getParam('tab')) {
            $tab = $this->getRequest()->getParam('tab');
        } else {
            $tab = '0';
        }        

        /**** Formulář časových mezí ******************************************/
        
        $form = new Application_Form_TimeLimits();

        $from = $form->getElement('limitFrom');
        $from->setValue(date('d. m. Y', strtotime(self::$_session->od)));
        
        $to = $form->getElement('limitTo');
        $to->setValue(date('d. m. Y', strtotime(self::$_session->do)));        

        /**** Záznamy o rozpracované práci ************************************/
        
        $vyroba = new Vyroba_Model_AkceVyroby();
        $vyroba->setIdUser(self::$_session->idOsoby);
        $aktualniPrace = $vyroba->getAktualniZaznamy();
               
        /**** Výrobní poznámky ************************************************/
        
        $poznamky = new Vyroba_Model_Poznamky();
        $poznamky->setIdUser(self::$_session->idOsoby);
        $nedavnePoznamky = $poznamky->getNedavnePoznamky();
        
        /**** Hledání podobných pozic *****************************************/
        
        $hledaciForm = new Application_Form_HledaniVyrazu();       
        
        /**** Hledání podrobností o výrobě konkrétních pozic ******************/

        $prehledovyFrom = new Application_Form_HledaniPozice();

        /**** Našeptávač ID pozice při vybraném názvu pozice ******************/
        
        $naseptavacForm = $this->_helper->
            naseptavacPozice(null,self::$_identity->roles['vyroba']);
        
        /**** Data do view ****************************************************/
        
        $this->view->idUzivatele = self::$_identity->id;
        $this->view->tab = $tab;
        $this->view->limitForm = $form;
        $this->view->hledaciForm = $hledaciForm;
        $this->view->prehledovyFrom = $prehledovyFrom;
        $this->view->naseptavacForm = $naseptavacForm;
        $this->view->pracovniZaznamy = $aktualniPrace;
        $this->view->poznamky = $nedavnePoznamky;
    }

    /**
     * Změna hesla uživatele do systému
     */
    public function zmenaHeslaAction()
    {
        $form = new Application_Form_PasswordChange;
        $url = $this->_helper->url;
        $identity = Zend_Auth::getInstance()->getIdentity();     
                
        $request = $this->getRequest();
        if ($request->isPost()) {         
                     
            // v případě odeslaného a zvalidovaného formuláře
            if ($form->isValid($request->getPost())) {
                
                // shoduje se heslo s hashem z databáze
                $authModel = new Application_Model_UserData();
                $authModel->setUserId($identity->id);
                $authModel->setUserRole($identity->roles['dochazka']);
                $authModel->setDataUserId(self::$_identity->id);               
         
                try {
                    if (sha1($request->getPost('currentPassword')) !== $authModel->getHashHesla()) {
                        throw new Exception('Špatně vyplněné současné heslo');
                    }

                    // shoduje se heslo a potvrzení hesla
                    if ($request->getPost('newPassword') !== $request->getPost('confirmPasswor')) {
                        throw new Exception('Nově zadané heslo se neshoduje s potvrzením hesla');
                    }
                } catch (Exception $e) {
                    $this->view->exceptionMessage = $e->getMessage();
                    $this->view->form = $form;
                    $exception = true;
                }
                
                if (!$exception) {
                    // nic nebrání změně hesla
                    $authModel->setHashHesla(sha1($request->getPost('newPassword')));
                    $authModel->zmenaHesla();

                    // uživatele za trest odhlásíme
                    $this->_helper->redirector('logout', 'auth', 'default');
                }
            } 
            // případně vrátíme formulář s vypsanou chybovou hláškou
            else {
                $this->view->form = $form;
            }    
        }     
        else {           
            $this->view->form = $form;
        }        

        $this->view->leftNavigation = array(
            array(
                'img' => 'home.png',
                'url' => $url->url(array('module' => 'default',
                                         'controller' => 'employee',
                                         'action' => 'index')),
                'text' => 'Domovská stránka')
        );        
    }    
    
    /**
     * Ajaxové nastavování data směny
     */
    public function ajaxDatumAction()
    {
        $this->_helper->getHelper('layout')->disableLayout();
        $datum = new Fc_Date_Format();
        
        switch ($this->getRequest()->getPost('typ')) {
         
            case 'od':
                self::$_session->od = $datum->dbDateFormat($this->getRequest()->getPost('datum'));
                break;
            
            case 'do':
                self::$_session->do = $datum->dbDateFormat($this->getRequest()->getPost('datum'));
                break;
        
        }
    }
    
    /**
     * Doplnění seznamu pozic do formuláře našeptávače pro zjištění ID pozice
     */
    public function ajaxNaseptavacAction()
    {
        $this->_helper->getHelper('layout')->disableLayout();     
        
        $idPolozky = $this->_getParam('id');
        $this->view->form = $this->_helper->naseptavacPozice($idPolozky,self::$_identity->roles['vyroba']);
    }    

}


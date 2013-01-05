<?php

/**
 * Controller použitý pro autentizaci uživatelů
 */
class AuthController extends Zend_Controller_Action
{
       
    public function init()
    {
       
    }

    public function indexAction()
    {   
        $form = new Application_Form_Login();
        $request = $this->getRequest();
        
        if ($request->isPost()) { 
            if ($form->isValid($request->getPost())) {                 
                if ($this->_process($form->getValues())) {
                    
                    // jsme přihlášení, přesměrujeme podle role v default modulu
                    switch(Zend_Auth::getInstance()->getIdentity()->roles['default']) {
                        
                        case 'employee':
                            $this->_helper->redirector('index', 'employee', 'default');
                            break;
                        
                        default:
                            $this->_helper->redirector('index', 'index', 'dochazka');
                            break;
                    }  
                } 
                else  {
                    $this->view->error = 'Chybné uživatelské jméno nebo heslo!';
                }
            }
        }        
        $this->view->form = $form;       
    }


    public function logoutAction()
    {
        Zend_Auth::getInstance()->clearIdentity();
        Zend_Session::destroy(); 
        $this->_helper->redirector('index'); // zpátky na login stránku
    }
    
    /**
     * Provádí přihlášení, o úspěchu nebo neúspěchu informuje v návratové
     * hodnotě, ovšem už bez upřesnění konkrétní chyby
     * 
     * @param post $values Formulářová data
     * @return boolean 
     */
    protected function _process($values)
    { 
        // parametry autetnitizačního adaptéru
        $authAdapter = $this->_getAuthAdapter();     
        $authAdapter->setCredential(sha1($values['password']))
            ->setIdentity($values['username']); 

        // provedení atuentizace
        $auth = Zend_Auth::getInstance();
        $result = $auth->authenticate($authAdapter);
 
        // autentizace úspěšná - zapíšeme identitu
        if ($result->isValid()) {
            
            $this->_writeIdentity($result->getIdentity());
            return true;
        }
        return false;        
    }

    /**
     * Vytvoření autentizačního adaptéru
     * @return \Zend_Auth_Adapter_DbTable
     */
    protected function _getAuthAdapter()
    {
        $dbAdapter = Zend_Db_Table::getDefaultAdapter();
        $authAdapter = new Zend_Auth_Adapter_DbTable($dbAdapter);

        $authAdapter->setTableName('osoby')
            ->setIdentityColumn('oznaceni')
            ->setCredentialColumn('heslo')                
            ->setCredentialTreatment('(?) AND aktivni IS TRUE');
        
        return $authAdapter;
    }
    
    /**
     * Zápis uživatelovy identity pomocí Zend_Auth
     * @param string $username
     */
    protected function _writeIdentity($username)
    {
        $model = new Application_Model_UserIdentity();
        $model->setUsername($username);
        
        $identity = $model->getUserIdentity();
        
        $auth = Zend_Auth::getInstance();  
        $auth->getStorage()->write($identity);        
    }
    
}
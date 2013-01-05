<?php

class Application_Form_Login extends Zend_Form
{

    public function init()
    {
        $url = new Zend_View_Helper_Url();
        $action = $url->url(array('controller'=>'auth', 'action'=>'index'),null, true);
        
        $this->setName("login");
        $this->setAttrib("id", "form-login");
        $this->setMethod('post');
        $this->setAction($action);            

        $username = new Zend_Form_Element_Text('username');
        $username->setLabel('Uživatelské jméno')
            ->setRequired(true)
            ->addFilters(array('StringTrim', 'StringToLower'))
            ->addValidator(
                  'NotEmpty', 
                  true, 
                  array('messages' => array('isEmpty' => 'Nezadali jste uživatelské jméno!'))
              );
     
        $password = new Zend_Form_Element_Password('password');
        $password->setLabel('Heslo')
            ->setRequired(true)
            ->addFilters(array('StringTrim'))
            ->addValidator(
                  'NotEmpty', 
                  true, 
                  array('messages' => array('isEmpty' => 'Nezadali jste heslo!'))
              );
        
        $submit = new Zend_Form_Element_Submit('submit_login');
        $submit->setRequired(true)
            ->setIgnore(true)
            ->setLabel('Přihlásit');
        
        $this->addElements(array($username,$password,$submit));

    }

}


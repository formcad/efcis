<?php

class Application_Form_PasswordChange extends Zend_Form
{

    public function init()
    {
        $this->setName("passwordChange");
        $this->setAttrib("id", "form-passwordChange");
        $this->setMethod('post');
          
        $soucasneHeslo = new Zend_Form_Element_Password('currentPassword');
        $soucasneHeslo->setLabel('Současné heslo')
            ->setRequired(true)
            ->addFilters(array('StringTrim'))
            ->addValidator(
                  'NotEmpty', 
                  true, 
                  array('messages' => array('isEmpty' => 'Nezadali jste heslo!'))
              );
        
        $noveHeslo = new Zend_Form_Element_Password('newPassword');
        $noveHeslo->setLabel('Nové heslo')
            ->setRequired(true)
            ->addFilters(array('StringTrim'))
            ->addValidator(
                  'NotEmpty', 
                  true, 
                  array('messages' => array('isEmpty' => 'Nezadali jste heslo!'))
              );
        
        $potvrzeniHesla = new Zend_Form_Element_Password('confirmPassword');
        $potvrzeniHesla->setLabel('Potvrzení hesla')
            ->setRequired(true)
            ->addFilters(array('StringTrim'))
            ->addValidator(
                  'NotEmpty', 
                  true, 
                  array('messages' => array('isEmpty' => 'Nezadali jste heslo!'))
              );
        
        $submit = new Zend_Form_Element_Submit('submit_password');
        $submit->setRequired(true)
            ->setIgnore(true)
            ->setLabel('Změnit heslo');        

        $this->addElements(array($soucasneHeslo,$noveHeslo,$potvrzeniHesla,$submit));      
    }
}

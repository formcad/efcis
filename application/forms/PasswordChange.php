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
            ->addFilters(array('StringTrim'))
            ->addValidator(
                  'NotEmpty', 
                  true, 
                  array('messages' => array('isEmpty' => 'Nezadali jste heslo!')))
            ->setRequired(true);
        
        $noveHeslo = new Zend_Form_Element_Password('newPassword');
        $noveHeslo->setLabel('Nové heslo')            
            ->addFilters(array('StringTrim'))
            ->addValidator(
                  'NotEmpty', 
                  true, 
                  array('messages' => array('isEmpty' => 'Nezadali jste heslo!')))
            ->setRequired(true);
        
        $potvrzeniHesla = new Zend_Form_Element_Password('confirmPassword');
        $potvrzeniHesla->setLabel('Potvrzení hesla')
            ->addFilters(array('StringTrim'))
            ->addValidator(
                  'NotEmpty', 
                  true, 
                  array('messages' => array('isEmpty' => 'Nezadali jste heslo!')))
            ->setRequired(true);     
        
        $submit = new Zend_Form_Element_Submit('submit_password');
        $submit->setRequired(true)
            ->setIgnore(true)
            ->setLabel('Změnit heslo');        

        $this->addElements(array($soucasneHeslo,$noveHeslo,$potvrzeniHesla,$submit));      
    }
}

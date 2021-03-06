<?php

class Application_Form_HledaniVyrazu extends Zend_Form
{

    public function init()
    {        
        $this->setName("login");
        $this->setAttrib("id", "form-hledaniVyrazu");  

        $text = new Zend_Form_Element_Text('hledanyVyraz');
        $text->setRequired(true)
            ->setAttrib('id','form-hledaniVyrazu-hledanyVyraz')
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty);
        
        $button = new Zend_Form_Element_Submit('vyhledej');
        $button->setLabel('Najít');
        
        $this->addElements(array($text,$button));

    }
}

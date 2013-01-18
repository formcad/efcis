<?php

class Application_Form_HledaniVyrazu extends Zend_Form
{

    public function init()
    {        
        $this->setName("login");
        $this->setAttrib("id", "form-hledani-pozic");  

        $text = new Zend_Form_Element_Text('hledanyVyraz');
        $text->setRequired(true)
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty);
        
        $button = new Zend_Form_Element_Button('vyhledej');
        $button->setRequired(true)
            ->setLabel('Najít');
        
        $this->addElements(array($text,$button));

    }
}

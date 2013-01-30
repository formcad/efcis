<?php

class Application_Form_HledaniPozice extends Zend_Form
{

    public function init()
    {        
        $url = new Zend_View_Helper_Url();
        $action = $url->url(array('module' => 'vyroba',
                                  'controller'=>'pozice', 
                                  'action'=>'vyrobni-zaznamy'),
                            null, true);  
        
        $this->setName("login");
        $this->setAttrib("id", "form-hledaniPozice");  
        $this->setMethod('post');
        $this->setAction($action);  

        $pozice = new Zend_Form_Element_Button('najdiPozici');
        $pozice->setRequired(true)
            ->setAttrib('id','form-hledaniPozice-najdiPozici')
            ->setIgnore(true)
            ->setLabel('Neznám ID pozice');
        
        $text = new Zend_Form_Element_Text('hledanyVyraz');
        $text->setRequired(true)
            ->setAttrib('id','form-hledaniPozice-hledanyVyraz')
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty);
        
        $button = new Zend_Form_Element_Submit('vyhledej');
        $button->setRequired(true)
            ->setIgnore(true)
            ->setLabel('Najít');
        
        $this->addElements(array($pozice,$text,$button));

    }
}

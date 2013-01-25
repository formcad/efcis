<?php

/**
 * Formulář pro nastavení parametrů při zaokrouhlování časů oficiální docházky
 */
class Dochazka_Form_ZaokrouhleniCasu extends ZendX_JQuery_Form
{

    public function init()
    {
        
        /**** Inicializace formuláře ******************************************/
        
        $this->setName("parametrySummary");
        $this->setAttrib("id", "form-dochazka-zaokrouhleniCasu");
        $this->setMethod("post");

        /**** Elementy - Ranní ************************************************/
        
        $ranniOd = new Zend_Form_Element_Text('ranniOd');
        $ranniOd->setLabel('Ranní od')
            ->setRequired(true)
            ->setValue('5:45')
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty)
            ->addValidator(new Zend_Validate_Date(array("format" => 'H:i')));
        
        $ranniDo = new Zend_Form_Element_Text('ranniDo');
        $ranniDo->setLabel('Ranní do')
            ->setRequired(true)
            ->setValue('6:00')
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty)
            ->addValidator(new Zend_Validate_Date(array("format" => 'H:i')));   
        
        $ranniCil = new Zend_Form_Element_Text('ranniCil');
        $ranniCil->setLabel('Zaokrouhlit na')
            ->setRequired(true)
            ->setValue('6:00')
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty)
            ->addValidator(new Zend_Validate_Date(array("format" => 'H:i')));
                
        /**** Elementy - Odpolední ********************************************/
        
        $odpoOd = new Zend_Form_Element_Text('odpoledniOd');
        $odpoOd->setLabel('Odpolední od')
            ->setRequired(true)
            ->setValue('13:45')
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty)
            ->addValidator(new Zend_Validate_Date(array("format" => 'H:i')));
        
        $odpoDo = new Zend_Form_Element_Text('odpoledniDo');
        $odpoDo->setLabel('Odpolední do')
            ->setRequired(true)
            ->setValue('14:00')
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty)
            ->addValidator(new Zend_Validate_Date(array("format" => 'H:i')));
        
        $odpoCil = new Zend_Form_Element_Text('odpoledniCil');
        $odpoCil->setLabel('Zaokrouhlit na')
            ->setRequired(true)
            ->setValue('14:00')
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty)
            ->addValidator(new Zend_Validate_Date(array("format" => 'H:i')));
        
        /**** Elementy - Noční ************************************************/
        
        $nocOd = new Zend_Form_Element_Text('nocniOd');
        $nocOd->setLabel('Noční od')
            ->setRequired(true)
            ->setValue('21:45')
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty)
            ->addValidator(new Zend_Validate_Date(array("format" => 'H:i')));
        
        $nocDo = new Zend_Form_Element_Text('nocniDo');
        $nocDo->setLabel('Noční do')
            ->setRequired(true)
            ->setValue('22:00')
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty)
            ->addValidator(new Zend_Validate_Date(array("format" => 'H:i')));        
        
        $nocCil = new Zend_Form_Element_Text('nocniCil');
        $nocCil->setLabel('Zaokrouhlit na')
            ->setRequired(true)
            ->setValue('22:00')
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty)
            ->addValidator(new Zend_Validate_Date(array("format" => 'H:i')));
                
        /**** Elementy - zákazové button, funkcionalita řešena přes JS ********/
        
        $zakazatRanni = new Zend_Form_Element_Button('zakazRanni');
        $zakazatRanni->setLabel('Zakázat/povolit filtr ranní směny')
            ->setAttrib('class', 'form-dochazka-zaokrouhleniCasu-filtr');
        
        $zakazatOdpoledni = new Zend_Form_Element_Button('zakazOdpoledni');
        $zakazatOdpoledni->setLabel('Zakázat/povolit filtr odpolední směny')
            ->setAttrib('class', 'form-dochazka-zaokrouhleniCasu-filtr');
        
        $zakazatNocni = new Zend_Form_Element_Button('zakazNocni');        
        $zakazatNocni->setLabel('Zakázat/povolit filtr noční směny')
            ->setAttrib('class', 'form-dochazka-zaokrouhleniCasu-filtr');
        
        /**** Element - odeslání formuláře ************************************/
        
        $submit = new Zend_Form_Element_Submit('submitZaokrouhleni');
        $submit->setRequired(true)
               ->setIgnore(true)
               ->setLabel('Provést');        
        
        /**** Přidání prvků ***************************************************/
        
        $this->addDisplayGroup(array($zakazatRanni,$ranniOd,$ranniDo,$ranniCil),'ranni');  
        $this->addDisplayGroup(array($zakazatOdpoledni,$odpoOd,$odpoDo,$odpoCil),'odpoledni');  
        $this->addDisplayGroup(array($zakazatNocni,$nocOd,$nocDo,$nocCil),'nocni');  
        $this->addDisplayGroup(array($submit),'submit'); 
    }
}


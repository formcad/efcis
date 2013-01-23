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
        $this->setAction('#');
     
        /**** Hidden element ID docházky **************************************/
        
        $idDochazky = new Zend_Form_Element_Hidden('idDochazky');
        
        /**** Elementy - Ranní ************************************************/
        
        $ranniOd = new Zend_Form_Element_Text('ranniOd');
        $ranniOd->setLabel('Ranní od')
            ->setRequired(true)
            ->setValue('5:45')
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty);
        
        $ranniDo = new Zend_Form_Element_Text('ranniDo');
        $ranniDo->setLabel('Ranní do')
            ->setRequired(true)
            ->setValue('6:00')
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty);        
        
        $ranniCil = new Zend_Form_Element_Text('ranniCil');
        $ranniCil->setLabel('Zaokrouhlit na')
            ->setRequired(true)
            ->setValue('6:00')
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty);      
                
        /**** Elementy - Odpolední ********************************************/
        
        $odpoOd = new Zend_Form_Element_Text('odpoledniOd');
        $odpoOd->setLabel('Odpolední od')
            ->setRequired(true)
            ->setValue('13:45')
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty);
        
        $odpoDo = new Zend_Form_Element_Text('odpoledniDo');
        $odpoDo->setLabel('Odpolední do')
            ->setRequired(true)
            ->setValue('14:00')
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty);        
        
        $odpoCil = new Zend_Form_Element_Text('odpoledniCil');
        $odpoCil->setLabel('Zaokrouhlit na')
            ->setRequired(true)
            ->setValue('14:00')
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty);              
        
        /**** Elementy - Noční ************************************************/
        
        $nocOd = new Zend_Form_Element_Text('nocniOd');
        $nocOd->setLabel('Noční od')
            ->setRequired(true)
            ->setValue('21:45')
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty);
        
        $nocDo = new Zend_Form_Element_Text('nocniDo');
        $nocDo->setLabel('Noční do')
            ->setRequired(true)
            ->setValue('22:00')
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty);        
        
        $nocCil = new Zend_Form_Element_Text('nocniCil');
        $nocCil->setLabel('Zaokrouhlit na')
            ->setRequired(true)
            ->setValue('22:00')
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty);              
                
        
        /**** Přidání prvků ***************************************************/
        
        $this->addDisplayGroup(array($ranniOd,$ranniDo,$ranniCil),'ranni');  
        $this->addDisplayGroup(array($odpoOd,$odpoDo,$odpoCil),'odpoledni');  
        $this->addDisplayGroup(array($nocOd,$nocDo,$nocCil,$idDochazky),'nocni');  
        
    }
}


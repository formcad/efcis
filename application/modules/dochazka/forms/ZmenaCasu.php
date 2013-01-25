<?php

/**
 * Formulář pro nastavení parametrů při změně časů průchodů oficiální docházky
 */
class Dochazka_Form_ZmenaCasu extends ZendX_JQuery_Form
{
    public function init()
    {
        
        /**** Inicializace formuláře ******************************************/
        
        $this->setName("zmenaCasuPruchodu");
        $this->setAttrib("id", "form-dochazka-zmenaCasu");
        $this->setMethod("post");
        $this->setAction('#');
     
        /**** Hidden element ID záznamu ***************************************/
        
        $idZaznamu = new Zend_Form_Element_Hidden('idZaznamu');
        $idZaznamu->setAttrib('id','form-dochazka-zmenaCasu-idZaznamu');
        
        /**** Element - datum směny *******************************************/
                
        $datumSmeny = new Fc_JQuery_Form_Element_DatePicker('datumSmeny');
        $datumSmeny->setLabel('Datum směny')
           ->setJQueryParam('defaultDate', date('d. m. Y'))               
           ->setRequired(true)
           ->setAttrib('id','form-dochazka-zmenaCasu-datumSmeny')
           ->addFilters(array('StringTrim'))
           ->addValidator(new Zend_Validate_NotEmpty)             
           ->addValidator(new Zend_Validate_Date(array('format' => 'dd. mm. yy')));        
        
        /**** Elementy - Příchod **********************************************/
        
        $prichodDen = new Fc_JQuery_Form_Element_DatePicker('prichodDen');
        $prichodDen->setLabel('Datum příchodu')
           ->setJQueryParam('defaultDate', date('d. m. Y'))     
           ->setRequired(true)
           ->setAttrib('id','form-dochazka-zmenaCasu-prichodDen')
           ->addFilters(array('StringTrim'))
           ->addValidator(new Zend_Validate_NotEmpty)             
           ->addValidator(new Zend_Validate_Date(array('format' => 'dd. mm. yy')));
        
        $prichodCas = new Zend_Form_Element_Text('prichodCas');
        $prichodCas->setLabel('Čas příchodu')
            ->setRequired(true)
            ->setAttrib('id','form-dochazka-zmenaCasu-prichodCas')
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty)
            ->addValidator(new Zend_Validate_Date(array('format' => 'H:i')));;
        
        /**** Elementy - Odchod ***********************************************/
        
        $odchodDen = new Fc_JQuery_Form_Element_DatePicker('odchodDen');
        $odchodDen->setLabel('Datum odchdou')
           ->setJQueryParam('defaultDate', date('d. m. Y'))     
           ->setRequired(true)
           ->setAttrib('id','form-dochazka-zmenaCasu-odchodDen')
           ->addFilters(array('StringTrim'))
           ->addValidator(new Zend_Validate_NotEmpty)             
           ->addValidator(new Zend_Validate_Date(array('format' => 'dd. mm. yy')));
        
        
        $odchodCas = new Zend_Form_Element_Text('odchodCas');
        $odchodCas->setLabel('Čas odchodu')
            ->setRequired(true)
            ->setAttrib('id','form-dochazka-zmenaCasu-odchodCas')
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty)
            ->addValidator(new Zend_Validate_Date(array('format' => 'H:i')));;        
        
        
      
        
        /**** Přidání prvků ***************************************************/
        
        $this->addDisplayGroup(array($datumSmeny),'datum');  
        $this->addDisplayGroup(array($prichodDen,$prichodCas),'prichod');  
        $this->addDisplayGroup(array($odchodDen,$odchodCas,$idZaznamu),'odchod');  
        
    }
}


<?php

/**
 * Formulář pro nastavení parametrů docházky
 */
class Dochazka_Form_SummaryParameters extends ZendX_JQuery_Form
{

    public function init()
    {
        
        /**** Inicializace formuláře ******************************************/
        
        $url = new Zend_View_Helper_Url();
        $action = $url->url(array("module" => "dochazka",
                                  "controller" =>"summary", 
                                  "action" => "view"), null, true);
           
        $this->setName("parametrySummary");
        $this->setAttrib("id", "form-dochazka-parametrySummary");
        $this->setMethod("post");
        $this->setAction($action);            
     
        /**** Element - datum *************************************************/
        
        $datum = new Fc_JQuery_Form_Element_DatePicker('datum');
        $datum->setLabel('Datum směny')
           ->setJQueryParam('defaultDate', date('d. m. Y')) 
           ->setValue(date('d. m. Y'))
           ->setRequired(true)
           ->addFilters(array('StringTrim'))
           ->addValidator(new Zend_Validate_NotEmpty)             
           ->addValidator(new Zend_Validate_Date(array('format' => 'dd. mm. yy')));    
        
        /**** Element - diference *********************************************/
        
        $diference = new Zend_Form_Element_Text('diference');
        $diference->setLabel('Odchylka (%)')
            ->setRequired(true)
            ->setValue(10)
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty);
        
        /**** Submit element **************************************************/
        
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setRequired(true)
               ->setLabel('Zobrazit');
         
        
        /**** Přidání prvků ***************************************************/
        
        $this->addElements(array($datum,$diference,$submit));  
       
    }
}


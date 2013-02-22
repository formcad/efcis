<?php

/**
 * Formulář pro nastavení parametrů docházky
 */
class Dochazka_Form_ParametryDochazky extends ZendX_JQuery_Form
{
    /**
     * Uživatelé docházky
     * @var array
     */
    public static $users;   
    
    public function init()
    {
        /**** Inicializace formuláře ******************************************/
        
        $url = new Zend_View_Helper_Url();
        $action = $url->url(array("module" => "dochazka",
                                  "controller" =>"show", 
                                  "action" => "index"), null, true);
           
        $this->setName("parametryDochazky");
        $this->setAttrib("id", "form-dochazka-parametryDochazky");
        $this->setMethod("post");
        $this->setAction($action);            

        /**** Element - osoby *************************************************/
        
        $osoba = new Zend_Form_Element_Select('osoba');
        $osoba->setLabel('Zaměstnanec')
              ->setRequired(true);
               
        if (!empty(self::$users)) {
            foreach (self::$users as $user){            

                // název docházkového čipu nebudeme zobrazovat
                $user["id_cipu"] == 1 ? $nazev = '' : $nazev = ' - '.$user["nazev"];                
                
                // přidáme formulářový prvek Option
                $osoba->addMultiOption($user["id"].','.$user["id_cipu"], 
                        $user["prijmeni"].' '.$user["jmeno"].$nazev);                          
            } 
        }
     
        /**** Element - datum od **********************************************/
        
        $od = new Fc_JQuery_Form_Element_DatePicker('od');
        $od->setLabel('Docházka od')
           ->setJQueryParam('defaultDate', date('d. m. Y', strtotime('-2 days')))     
           ->setValue(date('d. m. Y', strtotime('-2 days')))
           ->setRequired(true)
           ->addFilters(array('StringTrim'))
           ->addValidator(new Zend_Validate_NotEmpty)             
           ->addValidator(new Zend_Validate_Date(array('format' => 'dd. mm. yy')));    
        
        /**** Element - datum do **********************************************/
        
        $do = new Fc_JQuery_Form_Element_DatePicker('do');
        $do->setLabel('Docházka do')
           ->setJQueryParam('defaultDate', date('d. m. Y'))     
           ->setValue(date('d. m. Y'))
           ->setRequired(true)
           ->addFilters(array('StringTrim'))
           ->addValidators(array(
               new Zend_Validate_NotEmpty,
               new Zend_Validate_Date(array('format' => 'dd. mm. yy'))
           ));
        
        /**** Element - odeslání formuláře ************************************/
        
        $submit = new Zend_Form_Element_Submit('submitParametry');
        $submit->setRequired(true)
               ->setIgnore(true)
               ->setLabel('Nastavit parametry');
         
        
        /**** Přidání prvků ***************************************************/
        
        $this->addElements(array($osoba,$od,$do,$submit));  
       
    }
}


<?php

/**
 * Výběr měsíce při tvorbě oficiální docházky
 */

class Dochazka_Form_MesicOficialniDochazky extends Zend_Form
{

    public function init()
    {
        
        /**** Inicializace formuláře ******************************************/
        
        $this->setName("mesicOficialniDochazkyForm");
        $this->setAttrib("id", "form-dochazka-mesicOficialniDochazky");
        $this->setMethod('post');            
        
        $url = new Zend_Controller_Action_Helper_Url;
        $action = $url->url(array('module' => 'dochazka',
                                  'controller'=>'official', 
                                  'action'=>'tvorba-vykazu'),null, true); 
        $this->setAction($action);        
        
        /**** Element - měsíc *************************************************/
        
        $monthPicker = new Zend_Form_Element_Text('mesicDochazky');
        $monthPicker->setAttrib('class', 'monthpicker')
                ->setRequired(true)                
                ->setValue(date('m/Y', strtotime('last month')))
                ->addFilters(array('StringTrim'))
                ->addValidator(new Zend_Validate_NotEmpty);         
                
        /**** Element - odeslání formuláře ************************************/
        
        $submit = new Zend_Form_Element_Submit('submitVybrat');
        $submit->setRequired(true)
               ->setLabel('Potvrdit');            
        
        $this->addElements(array($monthPicker,$submit));
    }


}

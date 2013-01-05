<?php

/**
 * Formulář pro přidání nebo změnu poznámky ve výrobě
 */
class Vyroba_Form_Poznamka extends Zend_Form
{   
    /**
     * Pole pozic
     * @var array
     */
    public static $polePozic;
    
    public function init()
    {             
        /**** Inicializace formuláře ******************************************/
        
        $this->setName("poznamkaForm");
        $this->setAttrib("id", "form-vyroba-poznamkaForm");
        $this->setMethod("post");                       
        
        /**** Element - Pole pozic ********************************************/
       
        $pozice = new Zend_Form_Element_Select('pozice');
        $pozice->setLabel('Díl');
               
        $pozice->addMultiOption('','Poznámka se nevztahuje k dílu');
        
        if (!empty(self::$polePozic)) {
            foreach (self::$polePozic as $dil) {

                // přidáme formulářový prvek Option
                $pozice->addMultiOption($dil["idPozice"],$dil["nazevPozcie"]." (".$dil['cisloZakazky'].")");                
            }            
        }
        
        /**** Element - text poznámky *****************************************/
        
        $poznamka = new Zend_Form_Element_Text('text');
        $poznamka->setLabel('Poznámka')
                 ->setRequired(true)
                 ->addFilter('StringTrim')
                 ->addValidator(new Zend_Validate_NotEmpty);
         
        /**** Element - id poznámky *******************************************/
        
        $id = new Zend_Form_Element_Hidden('idPoznamky');
        
        /**** Element - odeslání formuláře ************************************/
        
        $submit = new Zend_Form_Element_Submit('submitPoznamka');
        $submit->setRequired(true)
               ->setLabel('Zapsat');        
        
        /**** Přidání prvků ***************************************************/
        
        $this->addElements(array($pozice,$poznamka,$id,$submit));       
    }
}
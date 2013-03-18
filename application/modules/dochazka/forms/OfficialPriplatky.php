<?php

/**
 * Formulář pro přidání nebo změnu poznámky u konkrétního dne oficiální docházky
 */
class Dochazka_Form_OfficialPriplatky extends Zend_Form
{
    /**
     * Proměnná, ve které jsou uložené jednotlivé typy příplatků - pro každý
     * prvek pole přípaltků se vytvoří element formuláře
     * 
     * @var array
     */
    public static $polePriplatku;
    
    public function init()
    {        
        /**** Inicializace formuláře ******************************************/
        
        $this->setName("parametrySummary");
        $this->setAttrib("id", "form-dochazka-oficialniPriplatky");
        $this->setMethod("post");      
        
        /**** Element - příplatky *********************************************/
        
        if (null !== self::$polePriplatku) {
            foreach (self::$polePriplatku as $priplatek) {
           
                $element = new Zend_Form_Element_Text("'".$priplatek['id']."'");
                $element->setLabel($priplatek['nazev'])
                    ->setBelongsTo('priplatek')
                    ->setAttrib('class', 'priplatekElement')
                    ->addFilters(array('StringTrim'))
                    ->addValidator('Float', false, array(Zend_Registry::get('Zend_Locale')));             
                
                $this->addElement($element);
            }
        }
    }
}

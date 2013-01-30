<?php

/**
 * Formulář pro vyhledání zakázky a pozice
 */
class Application_Form_Naseptavac extends Zend_Form
{
    /**
     * Pole zakázek
     * @var array 
     */
    public static $poleZakazek;
    
    /**
     * Pole pozic
     * @var array
     */
    public static $polePozic;
    
    /**
     * Defaultně vybraná položka
     * @var integer
     */
    public static $idPolozky;
    
    public function init()
    {             
        /**** Inicializace formuláře ******************************************/
 
        $this->setName("naseptavacZakazekForm");
        $this->setAttrib("id", "form-vyroba-naseptavacZakazekForm");
        $this->setMethod("post");                       
        
        /**** Element - Pole zakázek ******************************************/
       
        $zakazky = new Zend_Form_Element_Select('naseptavacZakazka');
        $zakazky->setLabel('Zakázka')
            ->setValue(self::$idPolozky);
        
        if (!empty(self::$poleZakazek)) {
            foreach (self::$poleZakazek as $zakazka) {

                // přidáme formulářový prvek Option
                $zakazky->addMultiOption($zakazka["id"],$zakazka["cislo"]);
            }            
        }
        
        /**** Element - pole pozice *******************************************/
        
        $pozice = new Zend_Form_Element_Select('naspetavacPozice');
        $pozice->setLabel('Pozice');


        if (!empty(self::$polePozic)) {
            foreach (self::$polePozic as $dil) {

                // přidáme formulářový prvek Option
                $pozice->addMultiOption($dil["id"],$dil["nazev"]);
            }            
        }
        
        /**** Přidání prvků ***************************************************/
        
        $this->addElements(array($zakazky,$pozice));               
    }
}
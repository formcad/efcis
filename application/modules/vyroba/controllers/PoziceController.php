<?php

/**
 * Controller pro pozice, neboli díly zakázek
 */

class Vyroba_PoziceController extends Zend_Controller_Action
{  
    /**
     * Session namespace formcad
     * @var
     */
    private static $_session = null;

    /**
     * Uložení identity uživatele
     * @var
     */
    private static $_identity = null;    

    /**
     * Funkce pro zobrazení výsledků ajaxového hledání pozic podle jechich názvů.
     * Kromě zakázek, kde se dané pozice vyskytují vrací i přehled operací, které
     * se při výrobě na pozicích provedly
     */
    public function ajaxHledaniPoziceAction()
    {
        $this->_helper->getHelper('layout')->disableLayout();
        $nazev = $this->getRequest()->getParam('nazevDilu');
        
        $model = new Vyroba_Model_Pozice();
        $model->setNazevPozice($nazev);
        
        // získáme ID pozice, jejich název a název zakázky
        $data = $model->najdiPodleNazvu();
        
        // pro jednotlivé pozice ještě zjistíme technologie, kterými se vyráběly
        foreach ($data as $index=>$pozice) {
            
            $model->setId($pozice['id']);
            $technologieArray = $model->zjistiSkutecneTechnologie();
            
            foreach ($technologieArray as $technologie) {
                $data[$index]['technologie'][] = $technologie['nazev'];
            }
        }
        $this->view->data = $data;
    }
}

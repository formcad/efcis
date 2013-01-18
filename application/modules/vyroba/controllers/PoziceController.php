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
    
    public function vyrobniZaznamyAction() 
    {
        $url = $this->_helper->url;
        
        $idPozice = $this->getRequest()->getParam('hledanyVyraz');
        
        $pozice = new Vyroba_Model_Pozice();
        $pozice->setId($idPozice);
        
        // kontroly
        try {
            // kontrola, zda máme něco vyplněného
            if (strlen($idPozice) == 0) {
                throw new Exception('Není zadané ID pozice');
            }
            // kontrola, zda je vůbec záznam v databázi            
            if (!$pozice->overExistenci()) {
                throw new Exception('Chybně zadané ID pozice');
            }
        } catch (Exception $e) {
            $this->view->exceptionMessage = $e->getMessage();
            $exception = true;
        }     
        
        if (!$exception) {
            // zjistíme výrobní úkony u konkrétní pozice
            $this->view->data = $pozice->zjistiSkutecnouVyrobu();            
        }
        
        $this->view->leftNavigation = array(
            array(
                'img' => 'home.png',
                'url' => $url->url(array('module' => 'default',
                                         'controller' => 'employee',
                                         'action' => 'index')),
                'text' => 'Domovská stránka')
        );           
    }
}

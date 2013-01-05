<?php

class Dochazka_TerminalController extends Zend_Controller_Action
{ 
    public function init()
    {
        
    }

    public function indexAction()
    {
        $flashMessenger = $this->_helper->getHelper('FlashMessenger');   
        
        $typyPruchodu = new Dochazka_Model_TypyPruchodu();
       
        Dochazka_Form_PruchodyDochazky::$typyPruchodu = $typyPruchodu->getTypy();        
        $form = new Dochazka_Form_PruchodyDochazky();
        
        $request = $this->getRequest();
        if ($request->isPost()) {
            
            // v případě odeslaného a zvalidovaného formuláře uložíme data
            if ($form->isValid($request->getPost())) { 
                                
                // získáme potřebné hodnoty
                $idAkce = $this->getRequest()->getPost('zvolenaAkce');
                $kodCipu = $this->getRequest()->getPost('kodCipu');                        
              
                $akce = new Dochazka_Model_AkceDochazky();
                $akce->setIdAction($idAkce);

                $terminalModel = new Dochazka_Model_TerminalovyPruchod();
                $terminalModel->setIdAkce($idAkce);
                $terminalModel->setKodCipu($kodCipu);

                // z kódu čipu získáme ID osoby a ID čipu
                $osoba = $terminalModel->dekodujCip();
              
                // došlo k chybě při dekódování čipu
                if (null == $osoba["idOsoby"]) {
                    $flashMessenger->addMessage('Došlo k chybě - neznámý čip');
                }                                
                else {
                    
                    // postup záleží na tom, zda jde o příchod nebo o odchod
                    $action = $akce->getPodrobnostiAkce();
                    switch ($action['idTypu']) {

                        /**** PŘÍCHOD *****************************************/

                        case 1:                                        

                            /**
                             * Princip zjištění data směny
                             * 
                             * 1. v tempu nebyl zapomenutý příchod
                             * 1.1. v DB je nějaký záznam o odchodu
                             * 1.1.1. čas odchodu je před více než 8 hodinami
                             *        -> datum směny je dnešní datum
                             * 1.1.2. čas odchodu je před méně než 8 hodinami, ale víc jak před půl hodinou
                             *        -> datum směny je datum směny odchodu, pauzu neřešíme
                             * 1.1.3. čas odchodu je méně než před půl hodinou
                             *        -> datum směny je datum směny odchodu; pokud byl při odchodu 
                             *           požadavek na automatické doplnění pauzy, doplníme ji
                             * 1.2. v DB není žádný záznam o odchodu
                             *      -> datum směny je dnešní datum, pauzu neřešíme
                             * 
                             * 2. v tempu byl zapomenutý příchod (temp není integritní)
                             *    -> pauzu neřešíme, protože byla vyřešena při integritním příchodu
                             * 
                             * 2.1. v DB je nějaký záznam o odchodu
                             * 2.1.1. skutečně zapomenutý odchod
                             * 2.1.1.1. čas neintegritního příchodu je před více než 10 hodinami
                             *        -> datum směny je dnešní datum
                             * 2.1.1.2. čas neintegritního příchodu je před méně než 10 hodinami
                             *        -> datum směny je datum směny neintegritního příchodu
                             * 2.1.2. po zapomenutém odchodu ručně doplněný odchod do dochazka_pruchody
                             *        -> pauzu neřešíme
                             * 2.1.2.1. čas ručně doplněného odchodu je před více než 8 hodinami
                             *          -> datum směny je dnešní datum
                             * 2.1.2.2. čas ručně doplněného odchodu je před méně než 8 hodinami
                             *          -> datum směny je datum směny ručně doplněného odchodu
                             * 
                             * 2.2. v DB není žádný záznam o odchodu (např. nový zaměstnanec)
                             *      -> datum směny je dnešní datum, pauzu neřešíme
                             */                            
                            
                            // získej z dochazka_pruchody na poslední odchod, který je v minulosti
                            $posledniOdchod = $this->_getPosledniOdchod($osoba['idOsoby'],$osoba['idCipu']);                           
                            
                            // zajištění konzistence dat tabulky dochazka_temp
                            $integrita = $terminalModel->integritaTempPrichod();                            

                            // 1. v tempu nebyl zapomenutý příchod
                            if ($integrita['integritni']) {

                                // 1.1. v DB je nějaký záznam o odchodu
                                if (!empty($posledniOdchod)){
                                    $casOdchodu = strtotime($posledniOdchod['cas_akce']);

                                    // 1.1.1. čas odchodu je před více než 8 hodinami
                                    if ((time() - $casOdchodu) > 28800) {
                                        // datum směny je dnešní datum
                                        $datumSmeny = date('Y-m-d');
                                    }
                                    //  1.1.2. čas odchodu je před méně než 8 hodinami, ale víc jak před půl hodinou 
                                    elseif ((time() - $casOdchodu) > 1800) {
                                        // datum směny je datum směny odchodu, pauzu neřešíme
                                        $datumSmeny = $posledniOdchod['datum'];
                                    }
                                    // 1.1.3. čas odchodu je méně než před půl hodinou
                                    else {
                                        // datum směny je datum směny odchodu
                                        $datumSmeny = $posledniOdchod['datum'];
                                        
                                        // pokud byl při odchodu požadavek na automatické doplnění pauzy, doplníme ji
                                        if ($posledniOdchod['id_akce'] == 7) {
                                            
                                            $delkaPauzy = round((1800 - (time() - $casOdchodu))/3600,2);
                                            $this->_doplnPauzu($osoba['idOsoby'],$osoba['idCipu'],$datumSmeny, $delkaPauzy);
                                        }
                                    }
                                }
                                // 1.2. v DB není žádný záznam o odchodu
                                else {
                                    // datum směny je dnešní datum, pauzu neřešíme
                                    $datumSmeny = date('Y-m-d');
                                }
                            }
                            
                            // 2. v tempu byl zapomenutý příchod (temp není integritní)
                            else {                                
                                // 2.1. v DB je nějaký záznam o odchodu
                                if (!empty($posledniOdchod)) {
                                
                                    $neintegritniCas = strtotime($integrita['casChyby']);
                                    $casOdchodu = strtotime($posledniOdchod['cas_akce']);

                                    // 2.1.1. skutečně zapomenutý odchod
                                    if ($casOdchodu < $neintegritniCas) {

                                        // 2.1.1.1. čas neintegritního příchodu je před více než 10 hodinami
                                        if ((time() - $neintegritniCas) > 36000) {
                                            // datum směny je dnešní datum
                                            $datumSmeny = date('Y-m-d');                                        
                                        }
                                        // 2.1.1.2. čas neintegritního příchodu je před méně než 10 hodinami
                                        else {
                                            // datum směny je datum směny neintegritního příchodu
                                            $datumSmeny = $integrita['datumSmeny'];
                                        }
                                    }
                                    // 2.1.2. po zapomenutém odchodu ručně doplněný odchod do dochazka_pruchody
                                    else {

                                        // čas ručně doplněného odchodu je před více než 8 hodinami
                                        if ((time() - $casOdchodu) > 28800) {
                                            // datum směny je dnešní datum
                                            $datumSmeny = date('Y-m-d');                                           
                                        }
                                        else {
                                            // datum směny je datum směny ručně doplněného odchodu
                                            $datumSmeny = $posledniOdchod['datum'];
                                        }
                                    }                                
                                }
                                // 2.2. v DB není žádný záznam o odchodu (např. nový zaměstnanec)
                                else {
                                    // datum směny je dnešní datum
                                    $datumSmeny = date('Y-m-d');                                    
                                }
                            }
                                       
                            $terminalModel->setDatumSmeny($datumSmeny);
                            $terminalModel->ulozPrichod();
                            $flashMessenger->addMessage('Příchod byl uložen');                    
                            break;

                        /**** ODCHOD ******************************************/

                        case 2:
                            
                            /**** VYBEREME SPRÁVNÝ PŘÍCHOD ********************/
                            
                            // podíváme se do dochazka_temp, zda tam je vložený příchod
                            $tempPrichod = $terminalModel->vyberTempPrichod();
                           
                            // podíváme se ještě do dochakza_pruchody, zda tam není ručně vložený příchod
                            $permPrichod = $terminalModel->vyberPermPrichod();
                            
                            // v tempu je příchod a ručně vložený je taky příchod
                            if ($tempPrichod and $permPrichod) {
                                
                                // pokud čas posledního zapsaného příchodu (perm příchodu) je v budoucnu
                                if (strtotime($permPrichod['cas_akce']) > time()) {
                                    // platným příchodem je TEMP příchod a zapsaný PERM příchod dál neřešíme
                                    $prichod = $tempPrichod;
                                    $denSmeny = $tempPrichod['datum'];
                                    $tempIntegrita = true;
                                }                                
                                // příchod z tempu bude přesunut do erroru a dál pracujeme jenom s perm příchodem
                                else {
                                    $terminalModel->moveTempToError();                                    
                                    $prichod = $permPrichod;
                                    $denSmeny = $permPrichod['datum'];
                                    $permIntegrita = true;
                                }
                            } 
                            elseif ($tempPrichod) {
                                $prichod = $tempPrichod;
                                $denSmeny = $tempPrichod['datum'];
                                $tempIntegrita = true;
                            }
                            elseif ($permPrichod) {
                                $prichod = $permPrichod;
                                $denSmeny = $permPrichod['datum'];
                                $permIntegrita = true;
                            }

                            /**** POKUD MÁME PŘÍCHOD, POKRAČUJEME *************/
                     
                            if ($prichod) {
                                          
                                /**** ULOŽENÍ ZÁZNAMU *************************************/

                            
                                // pokud jsou temporary akce, zpracujeme je
                                if ($tempIntegrita) {
 
                                    // uložení TEMP příchodu 
                                    $this->_ulozPruchod($prichod['cas_akce'],$denSmeny,
                                            $prichod['id_akce'],$prichod['id_osoby'],$prichod['id_cipu']);
                                    
                                    // smazání TEMP příchodu 
                                    $terminalModel->smazTempPrichod();
                                }                                                                     

                                // odchod uložíme vždy
                                $this->_ulozPruchod(date('Y-m-d H:i:s'),$denSmeny,
                                        $idAkce,$osoba['idOsoby'],$osoba['idCipu']);             
                                
                                /**** ULOŽÍME PAUZU ***************************/
                                
                                $timePrichod = strtotime($prichod['cas_akce']);
                                $timeOdchod = mktime();
                                $pauza = $timeOdchod - $timePrichod;                                
                                
                                // méně než 6 hodin
                                if ( $pauza < 21600) {
                                    $delkaPauzy = null;
                                }
                                // 6 až 12 hodin
                                elseif ( $pauza > 21600 and $pauza < 43200 ) {
                                    $delkaPauzy = 0.5;
                                }
                                // nad 12 hodin
                                else {
                                    $delkaPauzy = 1;
                                }                                                 
                                
                                if ($delkaPauzy != null) {
                                    $this->_doplnPauzu($osoba['idOsoby'], $osoba['idCipu'], $denSmeny, $delkaPauzy);                         
                                }
                                $flashMessenger->addMessage('Byl uložen záznam: '.$action['nazev']);                                
                            }
                            // data nebyla integritní
                            else {
                                // data o odchodu uložíme do error tabulky 
                                $terminalModel->ulozError();

                                // upozorníme uživatele
                                $flashMessenger->addMessage('Došlo k chybě - chybí záznam příchodu');
                            }
                            break;
                    }             
                }
            }                        
        }          

        $this->view->messages = array_merge(
            $flashMessenger->getMessages(),
            $flashMessenger->getCurrentMessages()
        );
        
        $flashMessenger->clearCurrentMessages();
        
        $this->view->form = $form;        
    }

    /**
     * Ajaxová akce pro zjištění názvu terminálové akce ze vstupního ID akce
     */
    public function ajaxAkceAction()
    {
        $this->_helper->getHelper('layout')->disableLayout();

        $akce = new Dochazka_Model_AkceDochazky();
        $akce->setIdAction($this->getRequest()->getPost('idAkce'));
        
        $podrobnosti = $akce->getPodrobnostiAkce();
        $this->view->akce = $podrobnosti["nazev"];
    }
    
    /**
     * Získá z dochazka_pruchody na poslední odchod, který je v minulosti
     * pro kombinaci ID uživatele a ID čipu
     * 
     * @param integer $idOsoby
     * @param integer $idCipu
     * @return array
     */
    protected function _getPosledniOdchod($idOsoby,$idCipu) 
    {
        $pruchody = new Dochazka_Model_PruchodyDochazky();
        $pruchody->setIdOsoby($idOsoby);
        $pruchody->setIdCipu($idCipu);
        $pruchody->setIdTypu(2);
                          
        return $pruchody->getPosledniAkce();
    }
    
    /**
     * Automatické doplnění pauzy do příslušného dne docházky
     * 
     * @param integer $idOsoby
     * @param integer $idCipu
     * @param string $den
     * @param float $delkaPauzy
     */
    protected function _doplnPauzu($idOsoby,$idCipu,$den,$delkaPauzy)
    {
        $preruseniModel = new Dochazka_Model_PreruseniDochazky();
        
        $preruseniModel->setIdPreruseni(1); // 1 = pravidelná pauza
        $preruseniModel->setDatum($den);     
        $preruseniModel->setDelka($delkaPauzy);
        $preruseniModel->setIdOsoby($idOsoby); 
        $preruseniModel->setIdCipu($idCipu); 
        
        $preruseniModel->addPreruseni();        
    }
    
    /**
     * Uložení průchodu do dochazka_pruchody
     * 
     * @param string $casAkce
     * @param string $datum
     * @param integer $idAkce
     * @param integer $idOsoby
     * @param integer $idCipu
     */
    protected function _ulozPruchod($casAkce,$datum,$idAkce,$idOsoby,$idCipu)
    {
        $dochazkaModel = new Dochazka_Model_PruchodyDochazky();
        
        $dochazkaModel->setCasAkce($casAkce);
        $dochazkaModel->setDatum($datum);
        $dochazkaModel->setIdAkce($idAkce);
        $dochazkaModel->setIdOsoby($idOsoby);
        $dochazkaModel->setIdCipu($idCipu);                                    
        $dochazkaModel->addPruchod();        
    }
}

<?php

/**
 * Kosik
 *
 * @author     Marden
 */

namespace FrontModule;

use Nette\Application\UI\Form,
    Nette\Utils\Html,
    Nette\Database\SqlLiteral,
    Nette\Mail\Message;

class CartPresenter extends BasePresenter {

    private $shippingPerson = 0;
    private $shippingSave = 50;
    private $shippingDPD = 138;
    private $shippingDpd = 89;
    private $shippingDpdCod = 158;
    private $shippingDel = 39;
    private $noShippingLimit = 2500;

    /** @persistent */
    public $orderStep;

    /** String */
    public $customerInformation;

    /** String */
    public $customerAddress;

    public function getAllCartIds($cart) {
        if (isset($cart)) {
            $allCartIds = Array();
            foreach ($cart as $key => $value) {
                $allCartIds[] = $key;
            }
            return $allCartIds;
        } else {
            return 0;
        }
    }

    public function getCorrectShipping($payment, $shippingCash, $shippingBank, $shippingCod, $totalPrice = 0) {
        if ($totalPrice >= $this->noShippingLimit) {
            return 0;
        } else {
            if ($payment == 'BANK') {
                if ($shippingBank == 'DPD') {
                    return $this->shippingDPD;
                }
                if ($shippingBank == 'PERSON') {
                    return $this->shippingPerson;
                }
                if ($shippingBank == 'SAVE') {
                    return $this->shippingSave;
                }
            }
            if ($payment == 'COD') {
                if ($shippingCod == 'DPD') {
                    return $this->shippingDpdCod;
                }
            }

            if ($payment == 'CASH') {
                if ($shippingCash == 'PERSON') {
                    return $this->shippingPerson;
                }
                if ($shippingCash == 'SAVE') {
                    return $this->shippingSave;
                }
            }
        }
    }

    public function getCorrectShippingAndPaymentName($payment, $shippingCash, $shippingBank, $shippingCod, $type) {
        $shippingTable = $this->context->createReference()->getReference('shipping');
        $paymentTable = $this->context->createReference()->getReference('payment');

        if ($type == 'PAYMENT') {
            $paymentTypeRow = $paymentTable->where('name_nm', $payment)->fetch();
            return $paymentTypeRow->name_full;
        }

        if ($type == 'SHIPPING') {
            if ($payment == 'CASH') {
                $shippingTypeRow = $shippingTable->where('name_nm', $shippingCash)->fetch();
                return $shippingTypeRow->name_full;
            }
            if ($payment == 'COD') {
                $shippingTypeRow = $shippingTable->where('name_nm', $shippingCod)->fetch();
                return $shippingTypeRow->name_full;
            }
            if ($payment == 'BANK') {
                $shippingTypeRow = $shippingTable->where('name_nm', $shippingBank)->fetch();
                return $shippingTypeRow->name_full;
            }
        }
    }

    public function getCorrectShippingAndPaymentNm($payment, $shippingCash, $shippingBank, $shippingCod, $type) {
        $shippingTable = $this->context->createReference()->getReference('shipping');
        $paymentTable = $this->context->createReference()->getReference('payment');

        if ($type == 'PAYMENT') {
            $paymentTypeRow = $paymentTable->where('name_nm', $payment)->fetch();
            return $paymentTypeRow->name_nm;
        }

        if ($type == 'SHIPPING') {
            if ($payment == 'CASH') {
                $shippingTypeRow = $shippingTable->where('name_nm', $shippingCash)->fetch();
                return $shippingTypeRow->name_nm;
            }
            if ($payment == 'COD') {
                $shippingTypeRow = $shippingTable->where('name_nm', $shippingCod)->fetch();
                return $shippingTypeRow->name_nm;
            }
            if ($payment == 'BANK') {
                $shippingTypeRow = $shippingTable->where('name_nm', $shippingBank)->fetch();
                return $shippingTypeRow->name_nm;
            }
        }
    }

    public function getCorrectShippingAndPaymentId($payment, $shippingCash, $shippingBank, $shippingCod, $type) {
        $shippingTable = $this->context->createReference()->getReference('shipping');
        $paymentTable = $this->context->createReference()->getReference('payment');

        if ($type == 'PAYMENT') {
            $paymentTypeRow = $paymentTable->where('name_nm', $payment)->fetch();
            return $paymentTypeRow->id;
        }

        if ($type == 'SHIPPING') {
            if ($payment == 'CASH') {
                $shippingTypeRow = $shippingTable->where('name_nm', $shippingCash)->fetch();
                return $shippingTypeRow->id;
            }
            if ($payment == 'COD') {
                $shippingTypeRow = $shippingTable->where('name_nm', $shippingCod)->fetch();
                return $shippingTypeRow->id;
            }
            if ($payment == 'BANK') {
                $shippingTypeRow = $shippingTable->where('name_nm', $shippingBank)->fetch();
                return $shippingTypeRow->id;
            }
        }
    }

    public function getCorrectSaveNm($id) {
        $saveRow = $this->context->createReference()->getReference('save')->where('id',$id)->fetch();
        return $saveRow->name_full;
    }
    
    public function actionDefault() {
        /*
         * obsluha noveho kosiku
         */
        /*priprava dat*/
        $categoryService = $this->context->createProductsList();
        
        $cartItemsNew = $this->context->createProduct()->getProduct()->where('id', $this->cart->getIds())->order('product_type DESC')->order('name_full');        
        /*priprava formularu pro odebirani zbozi*/
        foreach ($cartItemsNew as $item) {
            $cartItem = $this->cart->getItemById($item->id);
                if ($cartItem->rowType == \CartItem::ITEM_NORMAL){
                    $this->createRemoveFromBasketFormNew($item);
                }
                if ($cartItem->rowType == \CartItem::ITEM_BONUS){
                    $this->createRemoveBFromBasketNewForm($item);
                }                
                $subCategoryNameNew[$cartItem->productId] = $this->context->createProductsList()->getWeb($item->category_id);
                $categoryNameNew[$cartItem->productId] = $this->context->createProductsList()->getWeb($categoryService->getParentId($item->category_id));            
            
        }
        
        /*posilani do template*/
        if ($this->cart->amount > 0){
            $this->template->cartItemsNew = $cartItemsNew;
            $this->template->subCategoryNameNew = $subCategoryNameNew;
            $this->template->categoryNameNew = $categoryNameNew;  
            
        }
        
    }

    public function actionOrder() {
        /*
         * obsluha noveho kosiku
         */        
        $this->template->orderStep = $this->orderStep;
        //nacteni totalprice pro pocitani postovneho
        $totalPrice = $this->cart->getTotalPrice(\CartItem::ITEM_NORMAL);
        
        if ($this->getUser()->isLoggedIn()) {
            $customerRow = $this->context->createCustomer()->getCustomer()->where('id', $this->getUser()->id)->fetch();
            $customerInformation = Array(
                'name' => $customerRow->name,
                'surname' => $customerRow->surname,
                'email' => $customerRow->email,
                'phone' => $customerRow->phone
            );
            $this->customerInformation = $customerInformation;
            $customerAddressRow = $this->context->createCustomer()->getCustomerAddress()->where('id', $customerRow->shippingaddress_id)->fetch();
            if (!empty($customerAddressRow)) {
                $customerAddress = Array();
                $customerAddress['street'] = $customerAddressRow->street;
                $customerAddress['city'] = $customerAddressRow->city;
                $customerAddress['zipcode'] = $customerAddressRow->zipcode;
                $customerAddress['country_id'] = $customerAddressRow->country_id;
                $this->customerAddress = $customerAddress;
                $this->template->customerAddress = $customerAddress;
            }
        }
        if ($this->orderStep == 'orderSummary') {
            $orderSession = $this->getSession('order');
            $this['finishOrderForm']->setDefaults(Array(
                'name' => $orderSession->name,
                'surname' => $orderSession->surname,
                'phone' => $orderSession->phone,
                'email' => $orderSession->email,
                'street' => $orderSession->street,
                'city' => $orderSession->city,
                'note' => $orderSession->note,
                'save' => $orderSession->save,
                'zipcode' => $orderSession->zipcode,
                'country_id' => $orderSession->country_id,
                'payment_id' => $this->getCorrectShippingAndPaymentId($orderSession->payment, $orderSession->shippingCash, $orderSession->shippingBank, $orderSession->shippingCod, 'PAYMENT'),
                'shipping_id' => $this->getCorrectShippingAndPaymentId($orderSession->payment, $orderSession->shippingCash, $orderSession->shippingBank, $orderSession->shippingCod, 'SHIPPING'),
                'shipping_price' => $this->getCorrectShipping($orderSession->payment, $orderSession->shippingCash, $orderSession->shippingBank, $orderSession->shippingCod, $totalPrice)
            ));
            $orderSession->shipping = $this->getCorrectShippingAndPaymentNm($orderSession->payment, $orderSession->shippingCash, $orderSession->shippingBank, $orderSession->shippingCod, 'SHIPPING');
            $this->template->orderInformation = $orderSession;
            $this->template->shippingPrice = $this->getCorrectShipping($orderSession->payment, $orderSession->shippingCash, $orderSession->shippingBank, $orderSession->shippingCod, $totalPrice);
            $this->template->shippingName = $this->getCorrectShippingAndPaymentName($orderSession->payment, $orderSession->shippingCash, $orderSession->shippingBank, $orderSession->shippingCod, 'SHIPPING');
            $this->template->paymentName = $this->getCorrectShippingAndPaymentName($orderSession->payment, $orderSession->shippingCash, $orderSession->shippingBank, $orderSession->shippingCod, 'PAYMENT');
            if($orderSession->save >=1){
                $this->template->saveName = $this->getCorrectSaveNm($orderSession->save);  
            }          
        }
        /*priprava dat*/
        $categoryService = $this->context->createProductsList();
        
        $cartItemsNew = $this->context->createProduct()->getProduct()->where('id', $this->cart->getIds())->order('product_type DESC')->order('name_full');        
        /*priprava formularu pro odebirani zbozi*/
        foreach ($cartItemsNew as $item) {
            $cartItem = $this->cart->getItemById($item->id);
                if ($cartItem->rowType == \CartItem::ITEM_NORMAL){
                    $this->createRemoveFromBasketFormNew($item);
                }
                if ($cartItem->rowType == \CartItem::ITEM_BONUS){
                    $this->createRemoveBFromBasketNewForm($item);
                }                
                $subCategoryNameNew[$cartItem->productId] = $this->context->createProductsList()->getWeb($item->category_id);
                $categoryNameNew[$cartItem->productId] = $this->context->createProductsList()->getWeb($categoryService->getParentId($item->category_id));            
            
        }
        
        /*posilani do template*/
        if ($this->cart->amount > 0){
            $this->template->cartItemsNew = $cartItemsNew;
            $this->template->subCategoryNameNew = $subCategoryNameNew;
            $this->template->categoryNameNew = $categoryNameNew; 
            $this->template->noShippingLimit = $this->noShippingLimit;
        }
    }

    protected function createRemoveFromBasketForm($item) {
        // pomoci parametru konstruktoru pripnu komponentu k Presenteru
        $form = new Form($this, 'removeProduct' . $item->id);

        $form->addHidden('id', $item->id);

        $form->addText('amount', 'ks')
                ->setDefaultValue(1)
                ->addRule(Form::FILLED, 'Zadejte prosím počet kusů.')
                ->addRule(Form::INTEGER, 'Počet kusů musí být číslo.');

        //$form->addSubmit('add', 'Odebrat z košíku');
        $form->addSubmit('add', 'Odebrat')
                ->setAttribute('class', 'button floatLeft');

        $form->onSuccess[] = callback($this, 'basketFormSubmitted');
    }
    
    protected function createRemoveFromBasketFormNew($item) {
        // pomoci parametru konstruktoru pripnu komponentu k Presenteru
        $form = new Form($this, 'removeProductNew' . $item->id);

        $form->addHidden('id', $item->id);

        $form->addText('amount', 'ks')
                ->setDefaultValue(1)
                ->addRule(Form::FILLED, 'Zadejte prosím počet kusů.')
                ->addRule(Form::INTEGER, 'Počet kusů musí být číslo.');

        //$form->addSubmit('add', 'Odebrat z košíku');
        $form->addSubmit('add', 'Odebrat')
                ->setAttribute('class', 'button floatLeft');

        $form->onSuccess[] = callback($this, 'basketFormSubmitted');
    }

    protected function createRemoveBFromBasketForm($item) {
        // pomoci parametru konstruktoru pripnu komponentu k Presenteru
        $form = new Form($this, 'removeBProduct' . $item->id);

        $form->addHidden('id', $item->id);

        $form->addText('amount', 'ks')
                ->setDefaultValue(1)
                ->addRule(Form::FILLED, 'Zadejte prosím počet kusů.')
                ->addRule(Form::INTEGER, 'Počet kusů musí být číslo.');

        //$form->addSubmit('add', 'Odebrat z košíku');
        $form->addSubmit('add', 'Odebrat')
                ->setAttribute('class', 'basket');

        $form->onSuccess[] = callback($this, 'basketBFormSubmitted');
    }
    
    protected function createRemoveBFromBasketNewForm($item) {
        // pomoci parametru konstruktoru pripnu komponentu k Presenteru
        $form = new Form($this, 'removeBProductNew' . $item->id);

        $form->addHidden('id', $item->id);

        $form->addText('amount', 'ks')
                ->setDefaultValue(1)
                ->addRule(Form::FILLED, 'Zadejte prosím počet kusů.')
                ->addRule(Form::INTEGER, 'Počet kusů musí být číslo.');

        //$form->addSubmit('add', 'Odebrat z košíku');
        $form->addSubmit('add', 'Odebrat')
                ->setAttribute('class', 'basket');

        $form->onSuccess[] = callback($this, 'basketBFormSubmitted');
    }

    public function emptyCart() {
        //unset($this->session->getSection('cart')->cart);
        unset($this->session->getSection('bonus')->cart);        
        /*
         * obsluha noveho kosiku
         */
        $this->cart->emptyCart();
        
        $this->flashMessage('Obsah košíku byl vysypán.', 'info');
        $this->redirect('this');
    }


    function createComponentConfirmForm() {
        $form = new ConfirmationDialog();
        $form->addConfirmer(
                'empty', array($this, 'emptyCart'), 'Opravdu chcete vysypat košík?'
        );
        return $form;
    }

    public function createComponentPaymentAndShippingForm() {
        //$countries = $this->context->createReference()->getReference('country')->where('hidden', 0)->order('id')->fetchPairs('id', 'name_full');
        $totalPrice = $this->cart->getTotalPrice(\CartItem::ITEM_NORMAL);
        if($totalPrice >=$this->noShippingLimit){
            $paymentType = $this->context->createReference()->getReference('payment')->where('name_nm', Array('BANK'))->order('name_full')->fetchPairs('name_nm', 'name_full');
            $shippingTypeBank = $this->context->createReference()->getReference('shipping')->where('name_nm', Array('PERSON', 'SAVE','DPD'))->order('name_full')->fetchPairs('name_nm', 'name_full');
            $shippingTypeCash = $this->context->createReference()->getReference('shipping')->where('name_nm', Array())->fetchPairs('name_nm', 'name_full');
            $shippingTypeCod = $this->context->createReference()->getReference('shipping')->where('name_nm', Array())->fetchPairs('name_nm', 'name_full');
        }
        else{
            $paymentType = $this->context->createReference()->getReference('payment')->where('name_nm', Array('CASH', 'BANK', 'COD'))->order('name_full')->fetchPairs('name_nm', 'name_full');
            $shippingTypeCash = $this->context->createReference()->getReference('shipping')->where('name_nm', Array('PERSON', 'SAVE'))->fetchPairs('name_nm', 'name_full');
            $shippingTypeBank = $this->context->createReference()->getReference('shipping')->where('name_nm', Array('PERSON', 'SAVE','DPD'))->order('name_full')->fetchPairs('name_nm', 'name_full');
            $shippingTypeCod = $this->context->createReference()->getReference('shipping')->where('name_nm', Array('DPD'))->fetchPairs('name_nm', 'name_full');     
        }
        $saveList = $this->context->createReference()->getReference('save')->where('active', 1)->fetchPairs('id', 'name_full');

        $form = new Form();
        $form->addGroup('Způsob platby');
        $form->addRadioList('payment', 'Způsob platby', $paymentType)
                ->addCondition(Form::EQUAL, 'CASH')
                ->toggle('CASH')
                ->addCondition(Form::EQUAL, 'BANK')
                ->toggle('BANK')
                ->addCondition(Form::EQUAL, 'COD')
                ->toggle('COD');
        
        $form->addGroup('Doprava')->setOption('container', Html::el('fieldset')->id("CASH")->style("display:none"));
        
            $form->addRadioList('shippingCash', 'Způsob dopravy', $shippingTypeCash);
        
            $form->addSelect('saveListCash', 'Pobočka uloženky', $saveList)
                ->setPrompt('- Vyberte pobočku -');

        $form->addGroup('Doprava')->setOption('container', Html::el('fieldset')->id("BANK")->style("display:none"));
        $form->addRadioList('shippingBank', 'Způsob dopravy', $shippingTypeBank);

            $form->addSelect('saveListBank', 'Pobočka uloženky', $saveList)
                ->setPrompt('- Vyberte pobočku -');

        $form->addGroup('Doprava')->setOption('container', Html::el('fieldset')->id("COD")->style("display:none"));
        $form->addRadioList('shippingCod', 'Způsob dopravy', $shippingTypeCod);

        $form->addGroup('Doručovací údaje');
        $form->addText('name', 'Jméno:*', 40, 100)
                ->addRule(Form::FILLED, 'Je nutné zadat jméno.')->setAttribute('class', 'text');
        $form->addText('surname', 'Příjmení:*', 40, 100)
                ->addRule(Form::FILLED, 'Je nutné zadat příjmení.')->setAttribute('class', 'text');
        $form->addText('phone', 'Telefonní číslo:*', 40, 100)
                ->addRule(Form::FILLED, 'Je nutné zadat telefon.')->setAttribute('class', 'text')
                ->addRule(Form::INTEGER, 'Telefon musí být složen jen z čísel.');
        $form->addText('email', 'E-mail:*', 40, 100)
                ->addRule(Form::FILLED, 'Je nutné zadat e-mail.')->setAttribute('class', 'text')
                ->addRule(Form::EMAIL, 'Pole email musí obsahovat platnou emailovou adresu.');        
        $form->addText('street', 'Ulice:*', 40, 100)
                ->addRule(Form::FILLED, 'Je nutné zadat ulici.')->setAttribute('class', 'text');
        $form->addText('city', 'Město:*', 40, 100)
                ->addRule(Form::FILLED, 'Je nutné zadat město.')->setAttribute('class', 'text');
        $form->addText('zipcode', 'PSČ*:', 40, 100)
                ->addRule(Form::FILLED, 'Je nutné zadat PSČ.')->setAttribute('class', 'text');
        
        $form->addGroup('Poznámka');
        $form->addTextArea('note', 'Poznámka k objednávce', 100, 7);

        if ($this->getUser()->isLoggedIn()) {
            $form->setDefaults(Array('payment' => 'CASH',
                'shippingCash' => 'PERSON',
                'shippingBank' => 'DPD',
                'shippingCod' => 'SAVE',
                'name' => $this->customerInformation['name'],
                'surname' => $this->customerInformation['surname'],
                'phone' => $this->customerInformation['phone'],
                'email' => $this->customerInformation['email'],
                'street' => $this->customerAddress['street'],
                'city' => $this->customerAddress['city'],
                'zipcode' => $this->customerAddress['zipcode'],
                'country_id' => 1
            ));
        } else {
            $form->setDefaults(Array('payment' => 'CASH',
                'shippingCash' => 'PERSON',
                'shippingBank' => 'DPD',
                'shippingCod' => 'SAVE'
            ));
        }
        if($totalPrice >=$this->noShippingLimit){
            $form->setDefaults(Array('payment' => 'BANK',
                'shippingBank' => 'PERSON'
            ));  
        }        
        $form->setCurrentGroup(null);
        $form->addSubmit('submit', 'Potvrdit')
                ->setAttribute('class','button floatLeft')
                ->setAttribute('title','Potvrdit');


        $form->onSuccess[] = callback($this, 'paymentShippingFormSubmitted');
        return $form;
    }

    public function paymentShippingFormSubmitted(Form $form) {
        $this->orderStep = 'orderSummary';
        $orderSession = $this->session->getSection('order');
        $orderSession->payment = $form->values->payment;
        $orderSession->shippingCash = $form->values->shippingCash;
        $orderSession->shippingBank = $form->values->shippingBank;
        $orderSession->shippingCod = $form->values->shippingCod;
        $orderSession->name = $form->values->name;
        $orderSession->surname = $form->values->surname;
        $orderSession->phone = $form->values->phone;
        $orderSession->email = $form->values->email;
        $orderSession->street = $form->values->street;
        $orderSession->city = $form->values->city;
        $orderSession->zipcode = $form->values->zipcode;
        $orderSession->note = $form->values->note;
        $orderSession->country_id = 1; //$form->values->country_id;      
        
        if($form->values->saveListBank!==NULL){
            $orderSession->save = $form->values->saveListBank;       
        }
        elseif($form->values->saveListCash!==NULL){
            $orderSession->save = $form->values->saveListCash;
        }
        else{
            $orderSession->save = NULL;
        }
        
        $this->redirect('Cart:order');
    }

}

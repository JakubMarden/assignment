<?php

/**
 * Prehledy (homepage admin casti aplikace)
 *
 * @author     Marden
 */

namespace AdminModule;

use Nette\Application\UI\Form;
use \DateTime;
use Nette\Mail\Message;
use Nette\Diagnostics\Debugger;

class OverviewPresenter extends SecuredPresenter
{
    public $orderDate;
    public $note;
    
    public function renderDefault(){
    }
    
    public function renderCountdown(){         
    }
    
    public function createComponentOrderDateForm() {
        $orderDateService = $this->context->createOrderDate();
        $this->orderDate = $orderDateService->getOrderDates()->where('active',1)->order('orderdate')->limit(1)->fetch();
        
        if($this->orderDate ==false){
            $id = NULL;
            $orderdate = NULL;           
        }
        else {
            $id = $this->orderDate->id;
            $orderdate = $this->orderDate->orderdate; 
            
        }
        $form = new Form();
        $form->addHidden('id')
                ->setDefaultValue($id);
        $form->addDatePicker('orderdate','Datum uzavírky:')
                ->addRule(Form::FILLED, 'Datum musí být vyplněno!')
                ->addRule(Form::VALID, 'Zadané datum není platné!')
                ->setDefaultValue($orderdate);
        $form->addSubmit('send','Uložit datum uzavírky');
        $form->onSuccess[] = callback($this, 'orderDateFormSubmitted');        
        return $form;   
    }
    
    public function orderDateFormSubmitted(Form $form){
        $orderDateService = $this->context->createOrderDate();

    if ( $form->values->id !=null){ 
         $values = Array (
            'id' => $form->values->id,
            'orderdate' => $form->values->orderdate,
            'active' => 1 );
            $date = new DateTime($values['orderdate']->format('Y-m-d H:i:s'));
            $date->setTime(18,0); 
            $values['orderdate'] = $date;
            $orderDateService->getOrderDates()->where('id',$form->values->id)->update($values);
            $this->flashMessage('Datum upraven.', 'info');
        } else {
          $values = Array (
            'orderdate' => $form->values->orderdate,
            'active' => 1 );
          $date = new DateTime($values['orderdate']->format('Y-m-d H:i:s'));
          $date->setTime(18,0); 
          $values['orderdate'] = $date;
          $orderDateService->getOrderDates()->insert($values);
          $this->flashMessage('Datum uložen.', 'info');
        };
        $this->redirect('this');
    }
    
    public function renderNotehp($noteId){      
    }
    
    public function createComponentNotehpForm(){
        
        $httpRequest = $this->context->httpRequest;
        $orderId = $httpRequest->getQuery('noteId');
        $this->note = $this->context->createNote()->getNote()->where('active', 1)->where('order', $orderId)->limit(1)->order('id DESC')->fetch();
        
        if($this->note ==false){
            $id = NULL;
            $heading = NULL; 
            $title = NULL;
            $order = $orderId;
            $text = NULL;
        }
        else {
            $id = $this->note->id;
            $heading = $this->note->heading;
            $title = $this->note->title;
            $order = $orderId;
            $text =$this->note->text;
        }
        
        $form = new Form();
        $form->addHidden('id')
                ->setDefaultValue($id);
        $form->addHidden('order')
                ->setDefaultValue($order);
        $form->addText('heading', '*Nadpis: ', 40)
                ->addRule(Form::FILLED, 'Nadpis musí být vyplněn!')
                ->setDefaultValue($heading);
        $form->addText('title', '*Titul: ', 100)
                ->addRule(Form::FILLED, 'Titul musí být vyplněn!')
                ->setDefaultValue($title);
        $form->addTextArea('text', '*Text: ', 76, 10)
                ->addRule(Form::FILLED, 'Text musí být vyplněn!')
                ->setDefaultValue($text);
        $form->addCheckbox('active', 'Viditelný')
                ->setDefaultValue(true);
        $form->addSubmit('send','Uložit zprávu');
        $form->onSuccess[] = callback($this, 'notehpFormSubmitted');        
        return $form;   
    }
    
    public function notehpFormSubmitted($form){
         $noteService = $this->context->createNote();        
            if ( $form->values->id !=null){  
                 $values = Array (
                    'id' => $form->values->id,
                    'title' => $form->values->title,
                    'text' => $form->values->text,
                    'heading' => $form->values->heading,
                    'order' => $form->values->order,
                    'active' => $form->values->active);

                    $noteService->getNote()->where('id',$form->values->id)->update($values);
                    $this->flashMessage('Zpráva upravena.', 'info');
                } else {
            
                $values = Array (
                  'title' => $form->values->title,
                  'text' => $form->values->text,
                  'heading' => $form->values->heading,
                  'order' => $form->values->order,
                  'active' => 1 );

                $noteService->getNote()->insert($values);
                $this->flashMessage('Zpráva uložena.', 'info');
              };
       $noteId = $values["order"];      
        $this->redirect('Overview:notehp', $noteId);       
    }
    
        public function renderActionEmail(){
            
        }
        
        public function createComponentActionEmailForm(){
        
            $form = new Form();
            $form->addSubmit('send','Rozeslat emaily');
            $form->onSuccess[] = callback($this, 'actinEmailsubmitted');        
            return $form; 
        }
        
        public function actinEmailsubmitted(){
            $recievers = $this->context->createCustomer()->getCustomer()->where('newsletter', '1')->where('active', "1");
            $products = $this->context->createProduct()->getProduct()->where('action', "1")->order('category_id');
            $orderDate = $this->context->createOrderDate()->getOrderDates()->where('active',1)->order('orderdate')->limit(1)->fetch();
            
            $template = new \Nette\Templating\FileTemplate(APP_DIR . '/AdminModule/templates/Overview/_actionEmail.latte');
            $template->registerFilter(new \Nette\Latte\Engine);
            $template->registerHelperLoader('\Nette\Templating\DefaultHelpers::loader');    
            $template->products = $products;

            if(!empty($orderDate)){ 
                    $template->orderdate = $orderDate->orderdate->format('d.m. H:i');
            } else {
                   $template->orderdate = NULL;
            }
            $template->render(); // vykreslí šablonu
            foreach($recievers as $reciever){
                try {
                $mail = new Message;
                $mail->setFrom('dovoz-nemecko.cz <info@dovoz-nemecko.cz>')
                        ->addTo($reciever->email)                             
                        ->addTo($this->context->parameters['orderEmail'])
                        ->setSubject('Dovoz Německo - nabídka akčního zboží')
                        ->addTo($this->context->parameters['adminEmail']) 
                        ->setHtmlBody($template)
                        ->send();
                }
                catch (\Exception $e){
                    Debugger::log($e);
                    $this->flashMessage("Email odebírateli $reciever->email se nepovedl odeslat", 'error');
                }
            }
            $this->flashMessage('Emaily úspěšně odeslány', 'info');
       }
       
       public function renderUserView(){
           $users = $this->context->createCustomer()->getCustomer();
           $adresses = $this->context->createCustomer()->getCustomerAddress();
           foreach($users as $user){
               $user->billingaddress = "";
               $user->shippingaddress = "";
               foreach($adresses as $address){
                   if(isset($user->billingaddress_id) and ($user->billingaddress_id == $address->id)){
                       $user->billingaddress = "$address->street\n" .$address->city ."\n$address->zipcode";
                   }
                   if(isset($user->shippingaddress_id) and($user->shippingaddress_id == $address->id)){
                       $user->shippingaddress = "$address->street\n" .$address->city ."\n$address->zipcode";
                   }
               }
           }
           $this->template->users = $users;
       }
       
        public function renderMultiplier(){
            
        }
        
        public function createComponentMultiplierForm(){
        
            $form = new Form();
            $form->addText('multiplier', 'Násobený kurz',10)
                    ->addCondition(Form::FILLED)
                    ->addRule(Form::FLOAT, 'Zadejte číselnou hodnotu.');
            $form->addSubmit('send','Přepočítat ceny');
            $form->onSuccess[] = callback($this, 'multipliersubmitted');        
            return $form; 
        }
        
        public function multipliersubmitted($form){
            $productService = $this->context->createProduct();
            $products = $productService->getProduct();
            $multiplier = $form->values->multiplier;
            set_time_limit(100);
            foreach ($products as $product){
                $product->price = round($product->price * $multiplier);
                $product->undiscounted_price = round($product->undiscounted_price * $multiplier);
                $productService->editProduct($product);
            }
            $this->flashMessage('Ceny přepočítány', 'info');
       }
        public function renderWeschel(){
            
        }
        
        public function createComponentWeschelForm(){
            $weschelService = $this->context->createWeschel();
            $value = $weschelService->getWeschel()->limit(1)->order('id DESC')->fetch();
            $form = new Form();
            $form->addText('weschel', 'Kurz za 1 Euro',10)
                    ->setDefaultValue($value->weschel)
                    ->addCondition(Form::FILLED)
                    ->addRule(Form::FLOAT, 'Zadejte číselnou hodnotu.');
            $form->addSubmit('send','Uložit kurz');
            $form->onSuccess[] = callback($this, 'weschelsubmitted');        
            return $form; 
        }
        
        public function weschelsubmitted($form){
           $weschelService = $this->context->createWeschel();        
           $values = Array (
                  'weschel' => $form->values->weschel);
           $weschelService->getWeschel()->insert($values);
           $this->flashMessage('Kurz uložen', 'info');
       }       
}
<?php

/**
 * Description of Message
 *
 * @author Marden
 */
class Calc
{
    private $formula;
    private $formula_array;
    private $result = false;
    private $info;
    
    public function __construct()
    {  
        session_start();
        $this->init();
    }
        
    private function init(){
        $data = filter_input_array(INPUT_POST);
        if(isset($data["formula"]))
        {
            if ((isset($_SESSION["csrf_token"])) and intval($data["csrf_token"]) === $_SESSION["csrf_token"])
            {
                $this->formula_array = $this->formulaPreparation($data["formula"]); // upravi priklad do formatu pole a zkontroluje ho
                
                if(!$this->result){
                    $this->bracketFunction();                                       //vypocet vyrazu uvnitr zavorek
                }
                
                if(!$this->result){
                   $this->result = $this->getResult($this->formula_array);      //vypocet celkoveho prikladu s mezivysledky zavorek (pokud byly)
                   $this->result = round($this->result,4);                  
                }
                
                if(!$this->info and ($this->result or $this->result === 0.0)){                                               //paklize neprobehla chyba, vypisuje vysledek                    
                    $this->info = "Výsledek příkladu $this->formula je: $this->result\n";
                }
                elseif (!$this->info and !$this->result){
                    $this->info = "Výsledek příkladu $this->formula se nepodařilo zjistit, prosím zkontrolujte zadání a případně zadejte znovu.";
                }
            }
            else //neplatny token csrf
            {
               $this->info = $data["csrf_token"]."Neplatný token, odešlete prosím formulář znovu.\n". $_SESSION["csrf_token"]; 
            }
            
        }
        else //nedorazila hodnota vyplneneho formulare..to by se snad stat nemelo
        {
           $this->info = $data['formula'] ."Váš příklad nedorazil v pořádku, odešlete prosím formulář znovu.\n"; 
        }

        $_SESSION["info"] = $this->info;
        header('Location: index.php');
        exit;
    }
    
    /**
    *  oddeli jednotlive komponenty prikladu do formy pole vcetne detekce a upravy zapornych hodnot a kontroly spravnosti
    * @param    array   $formula
    * @return   $array
    */
    private function formulaPreparation($formula){ 
        $this->formula = $formula;
        $search = array("–",",","--","-+","+-","+","-","*","/","(",")","^","ˆ","  ");
        $replace = array("-","."," + "," - "," - "," + "," - "," * "," / "," ( "," ) "," ^ "," ˆ "," ");
        $formula_preparation = str_replace ($search, $replace, $this->formula); // prida mezery vsude mimo cisla
        $this->formula_array = explode(" ", $formula_preparation);              //rozpadne priklad na jednotlive komponenty skrze mezery
        
        $left_bracket_count = substr_count($this->formula, '(');
        $right_bracket_count = substr_count($this->formula, ')');
        
        if($left_bracket_count > $right_bracket_count)                          //kontrola poctu zavorek
        {
            $this->info = "Nebyla nalezena oteviraci zavorka";
            $this->result = true;
        }
        elseif($left_bracket_count < $right_bracket_count)
        {
            $this->info = "Nebyla nalezena uzaviraci zavorka";
            $this->result = true;
        }
        $minus_indicators = array("(","*","/","^","ˆ","+","-");
        
        for($i = 0; $i < count($this->formula_array);$i++)
        {
            if($this->formula_array[$i]==="")                                   //maze prazdna pole
            {
                unset($this->formula_array[$i]);
                $this->formula_array = array_values($this->formula_array);
            }
            
            if(($this->formula_array[$i]==="-" or $this->formula_array[$i]==="-") //detekuje cisla mensi nez nula a upravi pole tak, aby cisla u sebe mela znamenka minus
                and (is_numeric($this->formula_array[$i+1]))
                and ($i === 0 or in_array($this->formula_array[$i-1], $minus_indicators)))
            {
               $this->formula_array[$i] = "-".$this->formula_array[$i+1];
               unset($this->formula_array[$i+1]);
               $this->formula_array = array_values($this->formula_array);
            } 
            
        }
        
        return  $this->formula_array;      
    }
    
    /**
    * detekuje zavorky a iniciuje vypocet hodnot uvnitr zavorek
    */
    private function bracketFunction()
    {   
        while(in_array(")", $this->formula_array,true)){
            $right_bracket_key = array_search(")",$this->formula_array);
                    
                    for($i=$right_bracket_key-1;$i>=0;$i--)
                    {
                        if($this->formula_array[$i] === "(")
                        {
                            $left_bracket_key = $i;
                            break;
                        }    
                    }
                    
                    for($k = $left_bracket_key + 1; $k < $right_bracket_key; $k++)
                    {                       
                        $bracket_array[$k] = $this->formula_array[$k];
                        unset($this->formula_array[$k]);
                    }
                    
                    unset($this->formula_array[$right_bracket_key]);  
                    $this->formula_array[$left_bracket_key] = $this->getResult($bracket_array);
                    
                    if($this->formula_array[$left_bracket_key] === false){
                        break;
                    }
                    
                    $this->formula_array = array_values($this->formula_array);                    
                    unset($bracket_array);
        }
    }
    
    /**
    * spocita vysledek hodnot uvnitr pole bez zavorek
    * @param    array   $array
    * @return   float
    */
    private function getResult($array){
        $result = floatval(implode("", $array));                                  //vysledek v pripade, ze jiz nejsou zadne operatory v poli
        $arrayCheck = $this->arrayCheck($array);
        
        while($arrayCheck and ((in_array("^", $array,true)) or (in_array("ˆ", $array,true)) or (in_array("*", $array,true)) or (in_array("/", $array,true)) or (in_array("+", $array,true)) or (in_array("-", $array,true)))){
            $array = array_values($array);
           
            if(in_array("^", $array,true)){
                $operator_key = array_search("^",$array,true);
                $array[$operator_key - 1] = $result = $this->getExponential($array,$operator_key);
            }
            elseif(in_array("ˆ", $array,true)){
                $operator_key = array_search("ˆ",$array,true);
                $array[$operator_key - 1]  = $result = $this->getExponential($array,$operator_key);
            }
            elseif(in_array("*", $array,true)){
                $operator_key = array_search("*",$array,true);
                $array[$operator_key - 1]  = $result = $this->getMultiplicate($array,$operator_key);
            }
            elseif(in_array("/", $array,true)){
                $operator_key = array_search("/",$array,true);
                $array[$operator_key - 1]  = $result = $this->getDivide($array,$operator_key);
            }
            elseif(in_array("+", $array,true) or in_array("-", $array,true)){
                $operator_key_plus = array_search("+",$array,true);
                $operator_key_minus = array_search("-",$array,true);
                
                if(($operator_key_plus !== false) and ($operator_key_plus < $operator_key_minus or $operator_key_minus === false))  //algoritmus resici souslednost sum operaci
                {
                    $operator_key = $operator_key_plus;
                    $array[$operator_key_plus - 1]  = $result = $this->getPlus($array,$operator_key_plus);  
                } else {
                    $operator_key = $operator_key_minus;
                    $array[$operator_key_minus - 1]  = $result = $this->getMinus($array,$operator_key_minus);  
                }  
            }
            
            if($this->result === true or $result === null){ 
                break;
            }

            unset($array[$operator_key]);
            unset($array[$operator_key + 1]);   
            
        }
        return $result;
    }
    
    /**
    * spocita mocninu hodnot uvnitr pole bez zavorek
    * @param    array   $array
    * @param    int   $exponential_key
    * @return   int
    */
    private function getExponential($array,$exponential_key){                         
        $base = floatval($array[$exponential_key - 1]);                        //mocnenec
        $exponent = floatval($array[$exponential_key + 1]);                    //mocnitel

        if($exponent === 0)
        {
            $result = 1;
        }
        elseif($exponent === 1)
        {
            $result = $base;
        }
        elseif($exponent > 1){
            $result = $base;
            for($i=2; $i <= $exponent; $i++){
                $result = $result*$base;
            }
        } 
        elseif($exponent === -1)
        {
            $array = array("1", "/",$base);
            $result = $this->getDivide($array,1);
        }
        elseif($exponent < -1)
        {
            $exponent = $exponent * (-1);
            $semiresult = $base;
            for($i=2; $i <= $exponent; $i++){
                $semiresult = $semiresult*$base;
            } 
            $array = array("1", "/",$semiresult);
            $result = $this->getDivide($array,1);
        }
        
        return $result;
    }
    /**
    * spocita nasobek hodnot uvnitr pole bez zavorek
    * @param    array   $array
    * @param    int   $multiplication_key
    * @return   int
    */
    private function getMultiplicate($array,$multiplication_key){
       $first = floatval($array[$multiplication_key - 1]);   //prvni clen nasobeni
       $second = floatval($array[$multiplication_key + 1]);  //druhy clen nasoeni
       return $result = $first * $second;
    }
    
    /**
    * spocita deleni hodnot uvnitr pole bez zavorek
    * @param    array   $array
    * @param    int   $divide_key
    * @return   float
    */
    private function getDivide($array,$divide_key){
        $first = floatval($array[$divide_key - 1]);   //delenec
        $second = floatval($array[$divide_key + 1]);  //delitel
        
        if(intval($second) === 0){
            $this->info= "Deleni nulou";
            $this->result = true;
        } else {
            return $result = $first / $second;
            //return $result = bcdiv($first ,$second, 4);   pro pripad deleni na presne 4 desetinna mista, pak by bylo potreb pretypovat promennou result
        }
    }
    
    /**
    * spocita soucet hodnot uvnitr pole bez zavorek
    * @param    array   $array
    * @param    int   $plus_key
    * @return   int
    */
    private function getPlus($array,$plus_key){
        $first = floatval($array[$plus_key - 1]);   //prvni clen scitani
        $second = floatval($array[$plus_key + 1]);  //druhy clen scitani
       
        if($array[$plus_key - 2]=== "-" and is_numeric($array)){           //resi nekonzistenci vypoctu pri scitani a odecitani v jednom poli
            $array[$plus_key - 2]= "+";
            return $result = $second - $first;
        } else {
            return $result = $first + $second;      //standardni vypocet
        }    
    }
    
    /**
    * spocita odecet hodnot uvnitr pole bez zavorek
    * @param    array   $array
    * @param    int   $minus_key
    * @return   int
    */
    private function getMinus($array,$minus_key){
       $first = floatval($array[$minus_key - 1]);   //prvni clen odcitani
       $second = floatval($array[$minus_key + 1]);  //druhy clen odcitani
       return $result = $first - $second;
    }
    
    /**
    * kontroluje strukturu pole
    * @param    array   $array
    * @return   boolean
    */
    private function arrayCheck($array){
        $odd = true;
        $result = true;
        foreach ($array as $value) {
            if((!is_numeric($value) and $odd ===true) or (is_numeric($value) and $odd ===false)){
                $this->info = "Výsledek příkladu $this->formula se nepodařilo zjistit, prosím zkontrolujte zadání a případně zadejte znovu.";
                $this->result = true;
                $result = false;
                break;
            } elseif(is_numeric($value) and $odd ===true){
                    $odd =false;
            } elseif(!is_numeric($value) and $odd ===false){
                    $odd =true;
            }
        }    
        return $result;
    }
}

new Calc();
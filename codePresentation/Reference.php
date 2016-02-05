<?php
/**
 * Třída modelu ciselniku
 */
class Reference extends Nette\Object
{
	/** @var Nette\Database\Connection */
	public $database;

	public function __construct(Nette\Database\Connection $database)
	{
		$this->database = $database;
	}

    public function getProduct()
    {
        return $this->database->table('product');
    }
    
    public function getNameNmById($referenceTable,$id)
    {
        $referenceTableSelection = $this->database->table($referenceTable);
        
        $nameNmRow = $referenceTableSelection->where('id',$id)->fetch();
        return $nameNmRow->name_nm;
    }
    
    /**
    * Získá tabulku číselníků
    * @return Nette\Database\Table\Selection
    */
    public function getReference($referenceName)
    {
        return $this->database->table($referenceName)
                               ->order('ordering');
    }
}
<?php


class LifeFrancaCSVHandler extends SQLIImportAbstractHandler implements ISQLIImportHandler
{

	const STATE_GROUP = 'csv_import';

	const STATUS_PENDING = 'non_elaborato';

	const STATUS_DONE = 'elaborato_correttamente';

	const STATUS_INVALID = 'non_elaborabile';

	const STATUS_ERROR = 'errori_in_elaborazione';

	const STATUS_DOING = 'in_elaborazione';

	protected $rowIndex = 0;
	
	protected $rowCount;
	
	protected $currentGUID;

	protected $language;

	protected $dataSource = array();

	protected $opereRootNode;

	protected $eventiRootNode;

	protected $opereRootNodeChildren;

	protected $currentFileNode;

	protected $currentFileHasError;

	protected $currentRowId;

	protected $currentFileLog = array();

	protected $states = array();

	protected $repositories = array();

	protected $relationsCache = array();

	/**
	* Constructor
	*/
	public function __construct(SQLIImportHandlerOptions $options = null)
	{
		parent::__construct($options);
		$this->remoteIDPrefix = $this->getHandlerIdentifier().'-';
		$this->language = 'ita-IT';
	}

	public function initialize()
	{
		$stateGroup = eZContentObjectStateGroup::fetchByIdentifier(self::STATE_GROUP);
		if ($stateGroup instanceof eZContentObjectStateGroup){
			$stateId = false;
			$this->states = $stateGroup->states();
			foreach ($this->states as $state) {
				if ($state->attribute('identifier') == self::STATUS_PENDING){
					$stateId = $state->attribute('id');
				}
			}
			$rootObject = eZContentObject::fetchByRemoteID('csv-import-opere-repository');
			if ($rootObject instanceof eZContentObject && $stateId){
				$rootNode = $rootObject->attribute('main_node');
				$this->dataSource = $rootNode->subtree(array(
					'AttributeFilter' => array(array('state', '=', $stateId))
				));
				$this->repositories['opera'] = $rootNode->attribute('node_id');
			}
			$rootObject = eZContentObject::fetchByRemoteID('csv-import-eventi-repository');
			if ($rootObject instanceof eZContentObject && $stateId){
				$rootNode = $rootObject->attribute('main_node');
				$this->dataSource = array_merge(
					$this->dataSource, 
					$rootNode->subtree(array(
						'AttributeFilter' => array(array('state', '=', $stateId))
					))
				);
				$this->repositories['evento'] = $rootNode->attribute('node_id');
			}
		}

		$opereRoot = eZContentObject::fetchByRemoteID('opere');
		if ($opereRoot instanceof eZContentObject){
			$this->opereRootNode = $opereRoot->attribute('main_node');
			$this->opereRootNodeChildren = $this->opereRootNode->children();
		}else{
			throw new Exception("Opere root node not found", 1);			
		}

		$eventiRoot = eZContentObject::fetchByRemoteID('eventi');
		if ($eventiRoot instanceof eZContentObject){
			$this->eventiRootNode = $eventiRoot->attribute('main_node');			
		}else{
			throw new Exception("Eventi root node not found", 1);			
		}
	}

	public function getProcessLength()
	{
		if (!isset($this->rowCount)) {
			$this->rowCount = count($this->dataSource);
		}

		return $this->rowCount;
	}


	public function getNextRow()
	{
		if ($this->rowIndex < $this->rowCount) {
		      $row = $this->dataSource[$this->rowIndex];
		      $this->rowIndex++;
		} else {
		  	$row = false;
		}

		return $row;
	}

	public function cleanup()
	{
		return;
	}

	public function process($row)
	{	
		$this->currentFileNode = $row;
		$this->currentFileHasError = false;
		$this->currentGUID = $this->currentFileNode->attribute('name');

		$filePath = null;
		$dataMap = $this->currentFileNode->dataMap();
		if (isset($dataMap['file']) && $dataMap['file']->hasContent() && $dataMap['file']->attribute('data_type_string') == eZBinaryFileType::DATA_TYPE_STRING){
			$file = $dataMap['file']->content();
			$filePath = $file->filePath();
		}
		if ($filePath){
			$fileHandler = eZClusterFileHandler::instance($filePath);
			if ($fileHandler->exists()){
				$fileHandler->fetch();
				
				if ($this->currentFileNode->attribute('parent_node_id') == $this->repositories['opera'])
					return $this->processOperaFile($filePath);	

				if ($this->currentFileNode->attribute('parent_node_id') == $this->repositories['evento'])
					return $this->processEventoFile($filePath);	
			}			
		}

		$this->setFileNodeStatus(self::STATUS_INVALID);
		$this->appendFileNodeLog("File non trovato");
		$this->storeFileNodeLog();
	}

	private function processOperaFile($filePath)
	{
		$this->setFileNodeStatus(self::STATUS_DOING);
		$csvOptions = new SQLICSVOptions( array(
            'csv_path'         => $filePath,
            'delimiter'        => ',',
            'enclosure'        => '"'
        ) );
        $doc = new SQLICSVDoc( $csvOptions );
        $doc->parse();
        $dataSource = $doc->rows;
        
        if ($this->validateOperaHeaders($dataSource->getHeaders())){
        	foreach ($dataSource as $item) {
        		try{
	        		$this->importOpera($item);	        		
	        	}catch(Exception $e){
	        		$this->appendFileNodeLog($e->getMessage());   
	        	}
        	}
        }   
        if ($this->currentFileHasError)
        	$this->setFileNodeStatus(self::STATUS_ERROR);
        else
        	$this->setFileNodeStatus(self::STATUS_DONE);

        $this->storeFileNodeLog();   
	}

	private function importOpera($item)
	{
		//$this->cli->output($item->obid);
		
		$this->currentRowId = (string)$item->obid;
		$contentOptions = new SQLIContentOptions(array(
            'class_identifier' => 'opera',
            'remote_id' => 'opera_' . $item->obid,
        ));

        $parentNodeID = $this->findOperaParentNodeId($item->tipoOpera);

        $content = SQLIContent::create($contentOptions);
        $content->fields->obid = $item->obid;
        $content->fields->gps = "1|#{$item->y}|#{$item->x}|#";
        $content->fields->tipoopera = $this->findTipoOpera($item->tipoOpera);
        $content->fields->anno = (string)$item->annoRealizzazione != '' ? (int)$item->annoRealizzazione : null;
        $content->fields->bacinoprincipale = $this->findRelation($item->bacinoPrincipale, 'bacino');
        $content->fields->sottobacini = $this->findRelation($item->sottobacino, 'bacino');
        $content->fields->comune = $this->findRelation($item->comune, 'comune');
        $content->fields->comunita = $this->findRelation($item->comunitDiValle, 'comunita');
        $content->fields->materiale = $item->materiale;
        $content->fields->quantita = $item->quantit;
        $content->fields->unita_misura = $item->unitMisura;
        $content->fields->area_drenata = $item->areaDrenata;
        $content->fields->pendenza_monte = $item->pendenzaMonte;
        
        $content->addLocation(SQLILocation::fromNodeID($parentNodeID));
        $publisher = SQLIContentPublisher::getInstance();
        $publisher->publish($content);
        $newID = $content->getRawContentObject()->attribute( 'id' );
		unset($content);
		//$this->appendFileNodeLog("Pubblicato/Aggiornato contenuto #{$newID}");     
		$this->currentRowId = null;
	}

	private function findRelation($string, $classIdentifier)
	{
		$string = trim($string);

		// eccezioni e smartellamenti
		if ($classIdentifier == 'bacino'){
			$string = str_replace('-MERDAR', '- MERDAR', $string);
			$string = str_replace('STRIGNO-OSPONDAEDALETTO', 'STRIGNO-OSPEDALETTO', $string);
			$string = str_replace('RIO SPONDAOREGGIO', 'RIO SPOREGGIO', $string);
			$string = str_replace('CASTELNUOVO-GRIGNO VERSANTE DX', 'CASTELNUOVO-GRIGNO VS. DX', $string);
			$string = str_replace('TORRENTE LENO', 'TORR. LENO', $string);
			$string = str_replace('TORRENTE RABBIES', 'TORR. RABBIES', $string);
			$string = str_replace('TORRENTE PESCARA', 'TORR. PESCARA', $string);
			$string = str_replace('TORRENTE NOVELLA', 'TORR. NOVELLA', $string);
			$string = str_replace('TORRENTE VERMIGLIANA', 'TORR. VERMIGLIANA', $string);
			$string = str_replace('TORRENTE ALA', 'TORR. ALA', $string);
			$string = str_replace('SPONDAONDA', 'SPONDA', $string);
			$string = str_replace('S. VALENTINO-VALLE CIPRIANA', 'S. VALENTINO-V. CIPRIANA', $string);
			$string = str_replace('SPONDA. SX RENDENA', 'SP. SX RENDENA', $string);
			$string = str_replace('SPONDA. SINISTRA RENDENA', 'SP. SX RENDENA', $string);
		}

		if ($classIdentifier == 'comune'){
			$string = str_replace('SAN JAN DI FASSA', 'SÈN JAN DI FASSA', $string);
			$string = str_replace("E'", 'È', $string);
			$string = str_replace("A'", 'À', $string);
			$string = str_replace("O'", 'Ò', $string);
		}

		if(isset($this->relationsCache[$classIdentifier][$string])){
			return $this->relationsCache[$classIdentifier][$string];
		}

		$searchResult = eZSearch::search(
            trim($string),
            array(
                'SearchContentClassID' => array($classIdentifier),
                'SearchLimit' => 1,
                'Filter' => array('attr_name_s:"' . $string . '"')
            )
        );
        if ( $searchResult['SearchCount'] > 0 ){
            $this->relationsCache[$classIdentifier][$string] = $searchResult['SearchResult'][0]->attribute( 'contentobject_id' );
            
            return $this->relationsCache[$classIdentifier][$string]; 
        }

        $this->appendFileNodeLog("Relazione $string di classe $classIdentifier non trovata");
        $this->currentFileHasError = true;
        
        return null;
	}

	private function findTipoOpera($tipoOpera)
	{
		$mapping = array(
			'tombinatura' => 'Tombinatura',
			'briglia di consolidamento' => 'Briglia di consolidamento',
			'briglia di trattenuta' => 'Briglia di trattenuta',
			'drenaggi' => 'Drenaggio',
			'cunettone' => 'Cunettone',
			'rafforzamento arginale' => 'Rafforzamento arginale',
			'interventi_di ingegneria naturalistica' => 'Intervento di Ingegneria Naturalistica',
			'opera spondale' => 'Opera spondale',
			'opere consolidamento' => 'Opera di consolidamento',
			'piazza e vasche di deposito' => 'Piazza di deposito',
			'vallitomo' => 'Vallo Tomo',
			'rivestimenti in alveo' => 'Rivestimento Alveo',
			'repellente' => 'Repellente',
			'rilevato arginale' => 'Rilevato arginale',
		);

		if (isset($mapping[$tipoOpera])){
			return $mapping[$tipoOpera];
		}
		$this->appendFileNodeLog("Tipo opera $tipoOpera non mappato");
		$this->currentFileHasError = true;
		return ucwords($tipoOpera);
	}

	private function findOperaParentNodeId($tipoOpera)
	{
		foreach ($this->opereRootNodeChildren as $item) {
			if ($this->findTipoOpera($tipoOpera) == $item->attribute('name')){
				return $item->attribute('node_id');
			}
		}

		$contentOptions = new SQLIContentOptions(array(
            'class_identifier' => 'pagina_sito',
            'remote_id' => $tipoOpera,
        ));
        $content = SQLIContent::create($contentOptions);
        $content->fields->name = ucwords($tipoOpera);

        $content->addLocation(SQLILocation::fromNodeID($this->opereRootNode->attribute('node_id')));
        $publisher = SQLIContentPublisher::getInstance();
        $publisher->publish($content);
        $newNodeID = $content->getRawContentObject()->attribute( 'main_node_id' );
        unset($content);

		return $newNodeID;
	}

	private function validateOperaHeaders($headers)
	{
		$validHeaders = array(
			'obid',
			'tipoOpera',
			'annoRealizzazione',
			'materiale',
			'quantit',
			'unitMisura',
			'areaDrenata',
			'pendenzaMonte',
			'bacinoPrincipale',
			'sottobacino',
			'comunitDiValle',
			'comune',
			'x',
			'y',
		);

		foreach ($validHeaders as $header) {
			if(!in_array($header, $headers)){
				$this->setFileNodeStatus(self::STATUS_INVALID);
				$this->currentFileHasError = true;
				$this->appendFileNodeLog("Intestazione csv $header non trovata");

				return false;
			}
		}

		return true;
	}

	private function processEventoFile($filePath)
	{
		$this->setFileNodeStatus(self::STATUS_DOING);
		$csvOptions = new SQLICSVOptions( array(
            'csv_path'         => $filePath,
            'delimiter'        => ';',
            'enclosure'        => '"'
        ) );
        $doc = new SQLICSVDoc( $csvOptions );
        $doc->parse();
        $dataSource = $doc->rows;
        
        if ($this->validateEventoHeaders($dataSource->getHeaders())){
        	foreach ($dataSource as $item) {
        		try{
	        		$this->importEvento($item);	        		
	        	}catch(Exception $e){
	        		$this->appendFileNodeLog($e->getMessage());   
	        	}
        	}
        }   
        if ($this->currentFileHasError)
        	$this->setFileNodeStatus(self::STATUS_ERROR);
        else
        	$this->setFileNodeStatus(self::STATUS_DONE);

        $this->storeFileNodeLog(); 
	}

	private function validateEventoHeaders($headers)
	{
		$validHeaders = array(
			'obid',
			'tipoEvento',
			'dataEvento',
			'localita',
			'descrizione',
			'danni',
			'corsoAcqua',
			'fonte',
			'bacinoPrincipale',
			'sottobacino',
			'comunitaDiValle',
			'comune',
			'x',
			'y',
		);

		foreach ($validHeaders as $header) {
			if(!in_array($header, $headers)){
				$this->setFileNodeStatus(self::STATUS_INVALID);
				$this->currentFileHasError = true;
				$this->appendFileNodeLog("Intestazione csv $header non trovata");

				return false;
			}
		}

		return true;
	}

	private function importEvento($item)
	{
		$this->currentRowId = (string)$item->obid;
		$contentOptions = new SQLIContentOptions(array(
            'class_identifier' => 'historical_event',
            'remote_id' => 'historical_event_' . $item->obid,
        ));

        $parentNodeID = $this->eventiRootNode->attribute('node_id');

        $content = SQLIContent::create($contentOptions);
        $content->fields->obid = $item->obid;
        $content->fields->gps = "1|#{$item->y}|#{$item->x}|#";
        $content->fields->tipoevento = $item->tipoEvento;
        $content->fields->data = $item->dataEvento;
        $content->fields->bacinoprincipale = $this->findRelation(strtoupper($item->bacinoPrincipale), 'bacino');
        $content->fields->sottobacini = $this->findRelation(strtoupper($item->sottobacino), 'bacino');
        $content->fields->comune = $this->findRelation(strtoupper($item->comune), 'comune');
        $content->fields->comunita = $this->findRelation(strtoupper($item->comunitaDiValle), 'comunita');
        $content->fields->localita = $item->localita;
        $content->fields->descrizione = $item->descrizione;
        $content->fields->danni = $item->danni;
        $content->fields->corso_acqua = $item->corsoAcqua;
        $content->fields->fonte = $item->fonte;
        
        $content->addLocation(SQLILocation::fromNodeID($parentNodeID));
        $publisher = SQLIContentPublisher::getInstance();
        $publisher->publish($content);
        $newID = $content->getRawContentObject()->attribute( 'id' );
		unset($content);
		//$this->appendFileNodeLog("Pubblicato/Aggiornato contenuto #{$newID}");     
		$this->currentRowId = null;
	}

	private function appendFileNodeLog($message)
	{
		if(!isset($this->currentFileLog[$this->currentFileNode->attribute('node_id')])){
			$this->currentFileLog[$this->currentFileNode->attribute('node_id')] = array();
		}
		
		$context = '';
		if ($this->currentRowId){
			$context = " (obid " . $this->currentRowId . ')';
		}
		$content = "<p>{$message} " . $context . "</p>";			
		$this->currentFileLog[$this->currentFileNode->attribute('node_id')][] = $content;
	}

	private function storeFileNodeLog()
	{
		$dataMap = $this->currentFileNode->dataMap();
		if (isset($dataMap['description'])){
			$time = strftime( "%b %d %Y %H:%M:%S", strtotime( "now" ) );
			$content = "<p>{$time}</p>" . implode("\n", $this->currentFileLog[$this->currentFileNode->attribute('node_id')]);
			$dataMap['description']->fromString($this->getRichContent($content));
			$dataMap['description']->store();
		}
	}

	private function setFileNodeStatus($status)
	{
		$object = $this->currentFileNode->attribute('object');
		foreach ($this->states as $state) {
			if ($state->attribute('identifier') == $status){
				$object->assignState($state);
				eZContentCacheManager::clearContentCache($object->attribute('id'));
			}
		}
	}	

	public function getHandlerIdentifier()
	{
		return 'lifefrancacsvhandler';
	}

	public function getHandlerName()
	{
		return 'LifeFranca CSV Handler';
	}

	public function getProgressionNotes()
	{
		return 'Currently importing : ' . $this->currentGUID;
	}

}


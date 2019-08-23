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

	protected $comuniRootNode;

	protected $comunitaRootNode;

	protected $bacini1RootNode;

	protected $bacini2RootNode;

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

			$remotes = array(
				'csv-import-opere-repository' => 'opera',
				'csv-import-eventi-repository' => 'evento',
				'json-import-comuni-repository' => 'comuni',
				'json-import-comunita-repository' => 'comunita',
				'json-import-bacini1-repository' => 'bacini1',
				'json-import-bacini2-repository' => 'bacini2',
			);
			$this->dataSource = array();

			foreach ($remotes as $remote => $repo) {
				$rootObject = eZContentObject::fetchByRemoteID($remote);
				if ($rootObject instanceof eZContentObject && $stateId){
					$rootNode = $rootObject->attribute('main_node');
					$this->dataSource = array_merge(
						$this->dataSource, 
						$rootNode->subtree(array(
							'AttributeFilter' => array(array('state', '=', $stateId))
						))
					);
					$this->repositories[$repo] = $rootNode->attribute('node_id');
				}
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

		$comuniRoot = eZContentObject::fetchByRemoteID('comuni');
		if ($comuniRoot instanceof eZContentObject){
			$this->comuniRootNode = $comuniRoot->attribute('main_node');			
		}else{
			throw new Exception("Comuni root node not found", 1);			
		}

		$comunitaRoot = eZContentObject::fetchByRemoteID('comunita');
		if ($comunitaRoot instanceof eZContentObject){
			$this->comunitaRootNode = $comunitaRoot->attribute('main_node');			
		}else{
			throw new Exception("Comunita root node not found", 1);			
		}

		$bacini1Root = eZContentObject::fetchByRemoteID('bacini-1');
		if ($bacini1Root instanceof eZContentObject){
			$this->bacini1RootNode = $bacini1Root->attribute('main_node');			
		}else{
			throw new Exception("Bacini 1 root node not found", 1);			
		}
		
		$bacini2Root = eZContentObject::fetchByRemoteID('bacini-2');
		if ($bacini2Root instanceof eZContentObject){
			$this->bacini2RootNode = $bacini2Root->attribute('main_node');			
		}else{
			throw new Exception("Bacini 2 root node not found", 1);			
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
		DataHandlerLifeFranca::clearCache();
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

				if ($this->currentFileNode->attribute('parent_node_id') == $this->repositories['comuni'])
					return $this->processComuneFile($filePath);	

				if ($this->currentFileNode->attribute('parent_node_id') == $this->repositories['comunita'])
					return $this->processComunitaFile($filePath);	

				if ($this->currentFileNode->attribute('parent_node_id') == $this->repositories['bacini1'])
					return $this->processBacino1File($filePath);	

				if ($this->currentFileNode->attribute('parent_node_id') == $this->repositories['bacini2'])
					return $this->processBacino2File($filePath);	
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
            'delimiter'        => ';',
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
		//$this->cli->output($item->classid);
		
		$this->currentRowId = (string)$item->classid;
		$contentOptions = new SQLIContentOptions(array(
            'class_identifier' => 'opera',
            'remote_id' => 'opera_' . $item->classid,
        ));

        $parentNodeID = $this->findOperaParentNodeId($item->tipoOpera);

        $content = SQLIContent::create($contentOptions);
        $content->fields->classid = $item->classid;
        $content->fields->gps = "1|#{$item->y}|#{$item->x}|#";
        $content->fields->tipoopera = $this->findTipoOpera($item->tipoOpera);
        $content->fields->anno = (string)$item->annoRealizzazione != '' ? (int)$item->annoRealizzazione : null;
        $content->fields->bacinoprincipale = $this->findRelation($item->bacinoPrincipale, 'bacino');
        $content->fields->sottobacini = $this->findRelation($item->sottobacino, 'bacino');
        $content->fields->comune = $this->findRelation($item->comune, 'comune');
        $content->fields->comunita = $this->findRelation($item->comunitaDiValle, 'comunita');
        if(isset($item->materiale)){
        	$content->fields->materiale = $item->materiale;
        }
        $content->fields->quantita = $item->quantita;
        $content->fields->unita_misura = $item->unitaMisura;        
        if(isset($item->areaDrenata)){
        	$content->fields->area_drenata = $item->areaDrenata;
        }
        if(isset($item->pendenzaMonte)){
        	$content->fields->pendenza_monte = $item->pendenzaMonte;
        }
        
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
		$string = str_replace(' ', '_', $string);

		// eccezioni
		if ($classIdentifier == 'bacino'){
			
		}

		if ($classIdentifier == 'comune'){

			// if (in_array($string, ['AMB003_169', 'AMB003_2'])){
			// 	$string = 'SÈN JAN DI FASSA'; //@todo
			// }
		}

		if(isset($this->relationsCache[$classIdentifier][$string])){
			return $this->relationsCache[$classIdentifier][$string];
		}

		$relation = eZContentObject::fetchByRemoteID($string);
        if ($relation instanceof eZContentObject){
            $this->relationsCache[$classIdentifier][$string] = $relation->attribute( 'id' );
            
            return $this->relationsCache[$classIdentifier][$string]; 
        }

        $this->appendFileNodeLog("$classIdentifier \"$string\" non trovato");
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
			'interventi di ingegneria naturalistica' => 'Intervento di Ingegneria Naturalistica',
			'interventi_di ingegneria naturalistica' => 'Intervento di Ingegneria Naturalistica',
			'opera spondale' => 'Opera spondale',
			'opere di consolidamento' => 'Opera di consolidamento',
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
			'classid',
			'tipoOpera',
			'annoRealizzazione',
			//'materiale',
			'quantita',
			'unitaMisura',
			//'areaDrenata',
			//'pendenzaMonte',
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
			// 'descrizione',
			// 'danni',
			// 'corsoAcqua',
			// 'fonte',
			'bacinoPrincipale',
			'sottobacino',
			'comunitaDiValle',
			'comuni',
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
        $content->fields->comune = $this->findRelation(strtoupper($item->comuni), 'comune');
        $content->fields->comunita = $this->findRelation(strtoupper($item->comunitaDiValle), 'comunita');
        $content->fields->localita = $item->localita;
        // $content->fields->descrizione = $item->descrizione;
        // $content->fields->danni = $item->danni;
        // $content->fields->corso_acqua = $item->corsoAcqua;
        // $content->fields->fonte = $item->fonte;
        
        $content->addLocation(SQLILocation::fromNodeID($parentNodeID));
        $publisher = SQLIContentPublisher::getInstance();
        $publisher->publish($content);
        $newID = $content->getRawContentObject()->attribute( 'id' );
		unset($content);
		//$this->appendFileNodeLog("Pubblicato/Aggiornato contenuto #{$newID}");     
		$this->currentRowId = null;
	}

	private function processComuneFile($filePath)
	{		
		$this->setFileNodeStatus(self::STATUS_DOING);

		$dataSource = json_decode(file_get_contents($filePath), true);
		
		foreach ($dataSource['features'] as $item) {    		
    		try{
        		$this->importComune($item);	        		
        	}catch(Exception $e){
        		$this->appendFileNodeLog($e->getMessage());   
        	}
    	}

		if ($this->currentFileHasError)
        	$this->setFileNodeStatus(self::STATUS_ERROR);
        else
        	$this->setFileNodeStatus(self::STATUS_DONE);

        $this->storeFileNodeLog();
	}

	private function importComune($item, &$importIdList)
	{		
		$properties = $item['properties'];

		if (!isset($properties['classid']) || !isset($properties['comune'])){
			throw new Exception("Proprietà comune o classid non trovata", 1);				
		}

        $map = array(
            'type' => '',
            'color' => '',
            'source' => '',
            'geo_json' => json_encode(array(
                "type" => "FeatureCollection",
                "features" => array($item)
            )),            
        );

		$this->currentRowId = (string)$properties['classid'];
        if(!eZContentObject::fetchByRemoteID($properties['classid'])){
			$contentOptions = new SQLIContentOptions(array(
	            'class_identifier' => 'comune',
	            'remote_id' => $properties['classid'],
	        ));

	        $parentNodeID = $this->comuniRootNode->attribute('node_id');

	        $content = SQLIContent::create($contentOptions);
	        $content->fields->name = strtoupper($properties['comune']);
	        $content->fields->map = json_encode($map);
	        
	        $content->addLocation(SQLILocation::fromNodeID($parentNodeID));
	        $publisher = SQLIContentPublisher::getInstance();
	        $publisher->publish($content);	        
			unset($content);
        }
        $this->currentRowId = null;
	}

	private function processComunitaFile($filePath)
	{
		$this->setFileNodeStatus(self::STATUS_DOING);

		$dataSource = json_decode(file_get_contents($filePath), true);
		foreach ($dataSource['features'] as $item) {
    		try{
        		$this->importComunita($item);	        		
        	}catch(Exception $e){
        		$this->appendFileNodeLog($e->getMessage());   
        	}
    	}

		if ($this->currentFileHasError)
        	$this->setFileNodeStatus(self::STATUS_ERROR);
        else
        	$this->setFileNodeStatus(self::STATUS_DONE);

        $this->storeFileNodeLog(); 
	}

	private function importComunita($item)
	{
		$properties = $item['properties'];

		if (!isset($properties['classid']) || !isset($properties['comunita_di_valle'])){
			throw new Exception("Proprietà comunita_di_valle o classid non trovata", 1);				
		}

        $map = array(
            'type' => '',
            'color' => '',
            'source' => '',
            'geo_json' => json_encode(array(
                "type" => "FeatureCollection",
                "features" => array($item)
            )),            
        );

		$this->currentRowId = (string)$properties['classid'];
        if(!eZContentObject::fetchByRemoteID($properties['classid'])){
			$contentOptions = new SQLIContentOptions(array(
	            'class_identifier' => 'comunita',
	            'remote_id' => $properties['classid'],
	        ));

	        $parentNodeID = $this->comunitaRootNode->attribute('node_id');

	        $content = SQLIContent::create($contentOptions);
	        $content->fields->name = strtoupper($properties['comunita_di_valle']);
	        $content->fields->map = json_encode($map);
	        
	        $content->addLocation(SQLILocation::fromNodeID($parentNodeID));
	        $publisher = SQLIContentPublisher::getInstance();
	        $publisher->publish($content);	        
			unset($content);
        }
        $this->currentRowId = null;
	}

	private function processBacino1File($filePath)
	{
		$this->setFileNodeStatus(self::STATUS_DOING);

		$dataSource = json_decode(file_get_contents($filePath), true);
		foreach ($dataSource['features'] as $item) {
    		try{
        		$this->importBacino1($item);	        		
        	}catch(Exception $e){
        		$this->appendFileNodeLog($e->getMessage());   
        	}
    	}

		if ($this->currentFileHasError)
        	$this->setFileNodeStatus(self::STATUS_ERROR);
        else
        	$this->setFileNodeStatus(self::STATUS_DONE);

        $this->storeFileNodeLog(); 
	}

	private function importBacino1($item)
	{
		$properties = $item['properties'];

		if (!isset($properties['classid']) || !isset($properties['nomebacino'])){
			throw new Exception("Proprietà nomebacino o classid non trovata", 1);				
		}

        $map = array(
            'type' => '',
            'color' => '',
            'source' => '',
            'geo_json' => json_encode(array(
                "type" => "FeatureCollection",
                "features" => array($item)
            )),            
        );

		$this->currentRowId = (string)$properties['classid'];
        if(!eZContentObject::fetchByRemoteID($properties['classid'])){
			$contentOptions = new SQLIContentOptions(array(
	            'class_identifier' => 'bacino',
	            'remote_id' => $properties['classid'],
	        ));

	        $parentNodeID = $this->bacini1RootNode->attribute('node_id');

	        $content = SQLIContent::create($contentOptions);
	        $content->fields->name = $properties['nomebacino'];
	        $content->fields->map = json_encode($map);
	        $content->fields->level = 'PRINCIPALE';
	        $content->fields->objectid = isset($properties['objectid']) ? $properties['objectid'] : null;
	        $content->fields->classid = $properties['classid'];

	        
	        $content->addLocation(SQLILocation::fromNodeID($parentNodeID));
	        $publisher = SQLIContentPublisher::getInstance();
	        $publisher->publish($content);	        
			unset($content);
        }
        $this->currentRowId = null;
	}

	private function processBacino2File($filePath)
	{
		$this->setFileNodeStatus(self::STATUS_DOING);

		$dataSource = json_decode(file_get_contents($filePath), true);
		foreach ($dataSource['features'] as $item) {
    		try{
        		$this->importBacino2($item);	        		
        	}catch(Exception $e){
        		$this->appendFileNodeLog($e->getMessage());   
        	}
    	}

		if ($this->currentFileHasError)
        	$this->setFileNodeStatus(self::STATUS_ERROR);
        else
        	$this->setFileNodeStatus(self::STATUS_DONE);

        $this->storeFileNodeLog(); 
	}

	private function importBacino2($item)
	{
		$properties = $item['properties'];

		if (!isset($properties['classid']) || !isset($properties['nomesottobacino'])){
			throw new Exception("Proprietà nomebacino o classid non trovata", 1);				
		}

        $map = array(
            'type' => '',
            'color' => '',
            'source' => '',
            'geo_json' => json_encode(array(
                "type" => "FeatureCollection",
                "features" => array($item)
            )),            
        );

		$this->currentRowId = (string)$properties['classid'];
        if(!eZContentObject::fetchByRemoteID($properties['classid'])){
			$contentOptions = new SQLIContentOptions(array(
	            'class_identifier' => 'bacino',
	            'remote_id' => $properties['classid'],
	        ));

	        $parentNodeID = $this->bacini2RootNode->attribute('node_id');

	        $content = SQLIContent::create($contentOptions);
	        $content->fields->name = strtoupper($properties['nomesottobacino']);
	        $content->fields->map = json_encode($map);
	        $content->fields->level = 'I LIVELLO';
	        $content->fields->objectid = isset($properties['objectid']) ? $properties['objectid'] : null;
	        $content->fields->classid = $properties['classid'];
	        if (isset($properties['parent_classid'])){
	        	$bacinoSuperiore = eZContentObject::fetchByRemoteID($properties['parent_classid']);
	        	if($bacinoSuperiore instanceof eZContentObject){
	        		$content->fields->bacino_superiore = $bacinoSuperiore->attribute('id');
	        	}
	        }
	        
	        $content->addLocation(SQLILocation::fromNodeID($parentNodeID));
	        $publisher = SQLIContentPublisher::getInstance();
	        $publisher->publish($content);	        
			unset($content);
        }
        $this->currentRowId = null;
	}

	private function appendFileNodeLog($message)
	{
		if(!isset($this->currentFileLog[$this->currentFileNode->attribute('node_id')])){
			$this->currentFileLog[$this->currentFileNode->attribute('node_id')] = array();
		}
		
		$context = '';
		if ($this->currentRowId){
			$context = " (id " . $this->currentRowId . ')';
		}
		$content = "<p>{$message} " . $context . "</p>";			
		$this->currentFileLog[$this->currentFileNode->attribute('node_id')][] = $content;
	}

	private function storeFileNodeLog()
	{
		$dataMap = $this->currentFileNode->dataMap();
		if (isset($dataMap['description'])){
			$time = strftime( "%b %d %Y %H:%M:%S", strtotime( "now" ) );
			$content = "<p>Elaborato il {$time}</p>" . implode("\n", $this->currentFileLog[$this->currentFileNode->attribute('node_id')]);
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


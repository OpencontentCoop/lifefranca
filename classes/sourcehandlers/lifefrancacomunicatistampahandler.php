<?php

use Opencontent\Opendata\Rest\Client\HttpClient;

class LifeFrancaComunicatiStampaHandler extends SQLIImportAbstractHandler implements ISQLIImportHandler
{
	protected $rowIndex = 0;
	
	protected $rowCount;
	
	protected $currentGUID;

	protected $parentNodeID;

	protected $imagesParentNodeId = 51;
	
	protected $language;

	protected $remoteUrl;

	protected $dataSource = array();

	protected $debug = false;

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
		if (isset($this->options['parent'])){
			$this->parentNodeID = $this->options['parent'];
		}else{
			$this->parentNodeID = $this->handlerConfArray['DefaultParentNodeID'];
		}
		$query = $this->handlerConfArray['Query'];
		$startDate = '2018-09-01';
		$lastImportItem = eZDB::instance()->arrayQuery("SELECT * FROM sqliimport_item WHERE handler = 'lifefrancomunicatistampa' ORDER BY requested_time desc LIMIT 1");
		if(count($lastImportItem) > 0){
			$lastTimestamp = $lastImportItem[0]['requested_time'];
			$lastTimestamp = $lastTimestamp - 86400;
			$startDate = date('Y-m-d', $lastTimestamp);
		}
		$query = str_replace('%start_date%', $startDate, $query);
		$this->cli->output("Load data: $query");
		$this->loadData($this->handlerConfArray['Endpoint'] . rawurlencode($query));

		$this->remoteUrl = parse_url($this->handlerConfArray['Endpoint'], PHP_URL_SCHEME) . '://' . parse_url($this->handlerConfArray['Endpoint'], PHP_URL_HOST);

	}

	private function loadData($url)
	{		
		$url = str_replace('http:', 'https:', $url);		
		$response = json_decode(
			eZHTTPTool::getDataByURL($url),
			true
		);
		if (isset($response['searchHits'])){
			$this->dataSource = array_merge(
				$this->dataSource,
				$response['searchHits']
			);
		}

		if (isset($response['nextPageQuery']) && $response['nextPageQuery']){
			$this->loadData($response['nextPageQuery']);
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
		$metadata = $row['metadata'];
		$data = $row['data'][$this->language];

		$remoteID = $this->remoteIDPrefix . $metadata['remoteId'];
		$published = date("U", strtotime($metadata['published']));
		$lastModified = date("U", strtotime($metadata['modified']));

		$doImport = true;
		$alreadyImported = eZContentObject::fetchByRemoteID($remoteID);
		if ($alreadyImported instanceof eZContentObject){
			$doImport = false;
			if ($alreadyImported->attribute('modified') != $lastModified){
				$doImport = true;
			}
		}
		if ($doImport){
			$this->currentGUID = $metadata['name'][$this->language];
	        $contentOptions = new SQLIContentOptions( array(
	            'class_identifier' => 'comunicato',
	            'remote_id' => $remoteID
	        ));
	        
	        $content = $this->createContent($contentOptions);

        	$content->fields->occhiello = (string)$data['occhiello'];
        	$content->fields->titolo = (string)$data['titolo'];
        	$content->fields->sottotitolo = (string)$data['sottotitolo'];
        	$content->fields->abstract = (string)$data['abstract'];
        	$content->fields->testo_completo = $this->getRichContent((string)$data['abstract']);
        	$content->fields->published = $data['published'] ? date("U", strtotime($data['published'])) : null;
        	$content->fields->luogo = (string)$data['luogo'];
        	if (count($data['immagini']) > 0){
        		$content->fields->immagini = $this->importImages($data['immagini']);
        	}  
			$tags = array_merge(
				$data['tematica'],
				$data['focus'],
				$data['tags'],
				$data['argomento']
			);
        	$content->fields->tags = implode(',', array_unique($tags));

        	$links = array();
        	if (!empty($data['link'])){
        		foreach ($data['link'] as $link) {
        			if (!empty($link['url'])){
        				$links[] = $link;
        			}
        		}
        	}
        	$this->appendLinks($data['audio'], $links);
        	$this->appendLinks($data['video'], $links);
        	$this->appendLinks($data['allegati'], $links);

        	$rows = array();
	        foreach( $links as $item ){
	            $rows[] = implode( '|', array_values( $item ) );
	        }
	        $content->fields->link = implode('&', $rows);

	        $content->fields->numero = (string)$data['numero'];
			if(isset($data['geo']['latitude']) && $data['geo']['latitude'] != 0){
	        	$content->fields->geo = "1|#{$data['geo']['latitude']}|#{$data['geo']['longitude']}|#{$data['geo']['address']}";
	        }

	        $content->fields->fonte = $this->remoteUrl . '/content/view/full/' . $metadata['mainNodeId'] . '|Comunicato numero ' . $data['numero'] . ' - ' . $metadata['name'][$this->language];

        	if ($content instanceof SQLIContent){
				$content->addLocation( SQLILocation::fromNodeID( $this->parentNodeID ) );
				$publisher = SQLIContentPublisher::getInstance();
				$publisher->publish( $content );
				$data[] = $content->id;
				$object = $content->getRawContentObject();
				$object->setAttribute('published', $published);
				$object->setAttribute('modified', $lastModified);
				$object->store();
			}else{
				print_r($content);
			}

			unset($content);
		}
	}

	private function appendLinks($sourceList, &$links)
	{		
		foreach ($sourceList as $item) {
			if (isset($item['mainNodeId'])){
				$links[] = array(
					'name' => $item['name'][$this->language],
					'url' => $this->remoteUrl . '/content/view/full/' . $item['mainNodeId']
				);
			}
		}
	}

	private function importImages($sourceList)
	{
		$imagesIdList = array();
		foreach ($sourceList as $item) {
			if (isset($item['link'])){
				$source = json_decode(
					eZHTTPTool::getDataByURL($item['link']),
					true
				);
				if ($source){					
					$metadata = $source['metadata'];
					$data = $source['data'][$this->language];
					
					$remoteID = $this->remoteIDPrefix . $metadata['remoteId'];
					$alreadyImported = eZContentObject::fetchByRemoteID($remoteID);
					if ($alreadyImported instanceof eZContentObject){
						$imagesIdList[] = $alreadyImported->attribute('id');
					}else{
						$url = $this->remoteUrl . '/' . $data['image']['url'];

						$contentOptions = new SQLIContentOptions( array(
				            'class_identifier' => 'image',
				            'remote_id' => $remoteID
				        ));
						$content = $this->createContent($contentOptions);
						$content->fields->name = $metadata['name'][$this->language];						
						
						if ($content instanceof SQLIContent){
							$content->fields->image = $this->getRemoteFile($url);
							$content->addLocation( SQLILocation::fromNodeID( $this->imagesParentNodeId ) );
							$publisher = SQLIContentPublisher::getInstance();
							$publisher->publish( $content );
							$imagesIdList[] = $content->id;
							unset($content);
						}else{
							$imagesIdList[] = $url;
						}
					}					
				}
			}
		}

		return implode('-', $imagesIdList);
	}

	private function createContent($contentOptions)
	{
		if ($this->debug){
	        $content = new stdClass();
	        $content->options = $contentOptions;
	        $content->fields = new stdClass();
		}else{
			$content = SQLIContent::create( $contentOptions );
		}		

        return $content;
	}

	public function getHandlerIdentifier()
	{
		return 'lifefrancacomunicatistampa';
	}

	public function getHandlerName()
	{
		return 'Life Franca Comunicati Stampa PAT Handler';
	}

	public function getProgressionNotes()
	{
		return 'Currently importing : ' . $this->currentGUID;
	}

}


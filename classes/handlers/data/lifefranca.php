<?php

use Opencontent\Opendata\Api\ContentSearch;
use Opencontent\Opendata\Api\Values\SearchResults;

class DataHandlerLifeFranca implements OpenPADataHandlerInterface
{
	/**
     * @var ContentSearch
     */
    private $contentSearch;

	public function __construct( array $Params )
    {
        $module = isset( $Params['Module'] ) ? $Params['Module'] : false;
        if ( $module instanceof eZModule ){
            $module->setTitle( "LifeFranca" );
        }

        $this->contentSearch = new ContentSearch();
        $this->contentSearch->setEnvironment(new DefaultEnvironmentSettings());
    }

	public function getData()
	{
		$http = eZHTTPTool::instance();
		
		$field = $http->hasVariable('field') ? $http->variable('field') : false;
		$value = $http->hasVariable('value') ? $http->variable('value') : false;
		$types = $http->hasVariable('types') ? $http->variable('types') : false;

		if (!$field || !$value){
			return array();
		}

		if ($http->hasVariable('type')){			
			if ($http->variable('type') == 'opere'){				
				return $this->getOpere($field, $value, $types);
			}

			if ($http->variable('type') == 'eventi'){				
				return $this->getEventi($field, $value);
			}

			if ($http->variable('type') == 'timeline'){				
				return $this->getEventiTimeline($field, $value);
			}
		}

		return $data;
	}

	private static function isDebug()
	{
		return isset($_GET['debug']);
	}

	private static function innerGetOpereFacetsData($field, $value)
	{
		$contentSearch = new ContentSearch();
		$contentSearch->setEnvironment(new DefaultEnvironmentSettings());

    	$classAttributeId = eZContentClassAttribute::classAttributeIDByIdentifier('opera/tipoopera');
		$classAttribute = eZContentClassAttribute::fetch($classAttributeId);
		$tipoopera = array();
		if ($classAttribute instanceof eZContentClassAttribute){
			$classAttributeContent = $classAttribute->content();
			if (isset($classAttributeContent['options'])){
				$tipoopera = array_column($classAttributeContent['options'], 'name');				
			}
		}
		
		$baseQuery = 'classes [opera] facets [tipoopera|alpha|50] limit 1 stats [field=>extra_quantita_sf, facet=>[attr_tipoopera_s]] pivot [facet=>[attr_tipoopera_s,attr_unita_misura_s],mincount=>0]';
		$facets = array();
		if ($value != 'all'){
			$query = "$field = $value $baseQuery";	
		}else{
			$query = "$baseQuery";
		}
		
		$name = $value;
		if (is_numeric($value)){
			$object = eZContentObject::fetch($value);
			if ($object instanceof eZContentObject){
				$name = $object->attribute('name');
			}
		}
		try {
            $data = $contentSearch->search($query, array());
        } catch (Exception $e) {
            eZDebug::writeError($e->getMessage() . "\n" . $e->getTraceAsString(), __METHOD__);
            $data = new SearchResults();
            $data->nextPageQuery = null;
            $data->searchHits = array();
            $data->totalCount = 0;
            $data->facets = array();
            $data->query = $query; 
            $data->error = $e->getMessage();
        }

        if (isset($data->stats) && isset($data->pivot)){        	
        	$facets = array(
        		'value_name' => $name,
        		'facets' => array()
        	);
        	$stats = $data->stats['stats_fields']['extra_quantita_sf']['facets']['attr_tipoopera_s'];
        	$pivot = $data->pivot['attr_tipoopera_s,attr_unita_misura_s'];
        	foreach ($tipoopera as $tipo) {
        		$facetName = $tipo;
        		foreach ($pivot as $pivotItem) {
        			if($pivotItem['value'] == $tipo){
        				$facetName .= ' - ' . $pivotItem['pivot'][0]['value'];
        			}
        		}
        		$facetValue = isset($stats[$tipo]['sum']) ? round($stats[$tipo]['sum'], 2) : 0;
        		$facets['facets'][$facetName] = $facetValue;
        	}       
        }

        if (self::isDebug()) return array($data, $facets);

        return $facets;
	}

	private function getOpereFacetsData($field, $value)
	{
		if (isset($_GET['nocache'])){
			return self::innerGetOpereFacetsData($field, $value);
		}

		$fileName = "opere-$field-$value.cache";
		$cacheFilePath = eZDir::path( array( eZSys::cacheDirectory(), 'lifefranca', $fileName ) );

		$data = eZClusterFileHandler::instance($cacheFilePath)->processCache(
            function( $file, $mtime, $args ){
		        $result = include( $file );

		        return $result;
		    },
            function($file, $args){
		        $field = $args['field'];
		        $value = $args['value'];
		        return array( 
		        	'content' => self::innerGetOpereFacetsData($field, $value),
                  	'scope'   => 'lifefranca' 
              	);					
            },
            null,
            null,
            array('field' => $field, 'value' => $value)
        );

        return $data;
	}
	
	private function getOpere($field, $value, $types)
	{
		$series = $categories = array();
		if (!empty($types)){									
			if (!is_array($value)){
				$value = array($value);
			}
			foreach ($value as $item) {
				$facetsData = $this->getOpereFacetsData($field, intval($item));	

				if (self::isDebug()) return $facetsData;

				$facets = $facetsData['facets'];
				$filteredFacets = array();
				foreach ($facets as $key => $value) {
					$keyParts = explode(' - ', $key);
					if (in_array($keyParts[0], $types)){
						$filteredFacets[$key] = $value;
					}
				}
				$series[] = array(
					'name' => $facetsData['value_name'],
					'data' => array_values($filteredFacets)
				);
				if (empty($categories)) $categories = array_keys($filteredFacets);
			}
		}
			
		$data = array(
		    'chart' => array(
		        'type' => 'column',
		        'width' => null
		    ),
		    'title' => null,		 
		    'subtitle' => null,
		    'xAxis' => array(
		        'categories' => $categories,
		        'crosshair' => true,
		        'labels' => []		        
		    ),
		    'yAxis' => array(
		        'allowDecimals' => false,
		        'min' => 0,
		        'title' => null,			        
		    ),		    
		    'plotOptions' => array(
		        'column' => array(
		            'pointPadding' => 0.2,
		            'borderWidth' => 0
		        )
		    ),
		    'series' => $series
		);

		return $data;
	}

	private static function innerGetEventiFacetsData($field, $value)
	{
		$contentSearch = new ContentSearch();
		$contentSearch->setEnvironment(new DefaultEnvironmentSettings());

		$baseQuery = 'classes [historical_event] facets [class|alpha|50] limit 1 range [field=>extra_data_dt,start=>1000-01-01,end=>NOW,gap=>+1MONTH]';
		$facets = array();
		if ($value != 'all'){
			$query = "$field = $value $baseQuery";	
		}else{
			$query = "$baseQuery";
		}
		
		$name = $value;
		if (is_numeric($value)){
			$object = eZContentObject::fetch($value);
			if ($object instanceof eZContentObject){
				$name = $object->attribute('name');
			}
		}
		try {
            $data = $contentSearch->search($query, array());
        } catch (Exception $e) {
            eZDebug::writeError($e->getMessage() . "\n" . $e->getTraceAsString(), __METHOD__);
            $data = new SearchResults();
            $data->nextPageQuery = null;
            $data->searchHits = array();
            $data->totalCount = 0;
            $data->facets = array();
            $data->query = $query; 
            $data->error = $e->getMessage();
        }

        if (isset($data->facets)){
        	$facets = array(
        		'value_name' => $name,
        		'facets' => $data->facets[0]['data'],
        		'range' => $data->range
        	);        	 
        }

        if (self::isDebug()) return array($data, $facets);

        return $facets;
	}

	private function getEventiFacetsData($field, $value)
	{
		if (isset($_GET['nocache'])){
			return self::innerGetEventiFacetsData($field, $value);
		}

		$fileName = "eventi-$field-$value.cache";
		$cacheFilePath = eZDir::path( array( eZSys::cacheDirectory(), 'lifefranca', $fileName ) );

		$data = eZClusterFileHandler::instance($cacheFilePath)->processCache(
            function( $file, $mtime, $args ){
		        $result = include( $file );

		        return $result;
		    },
            function($file, $args){
		        $field = $args['field'];
		        $value = $args['value'];
		        return array( 
		        	'content' => self::innerGetEventiFacetsData($field, $value),
                  	'scope'   => 'lifefranca' 
              	);					
            },
            null,
            null,
            array('field' => $field, 'value' => $value)
        );

        return $data;
	}

	private function getEventi($field, $value)
	{
		$series = $categories = array();
		if (!is_array($value)){
			$value = array($value);
		}
		foreach ($value as $item) {
			$facetsData = $this->getEventiFacetsData($field, intval($item));	

			if (self::isDebug()) return $facetsData;

			$facets = $facetsData['facets'];
			if (empty($facets)){
				$facets = array('historical_event' => 0);
			}
			$series[] = array(
				'name' => $facetsData['value_name'],
				'data' => array_values($facets)
			);
			if (empty($categories)) $categories = array_keys($facets);
		}
			
		$data = array(
		    'chart' => array(
		        'type' => 'column',
		        'width' => null
		    ),
		    'title' => null,		 
		    'subtitle' => null,
		    'xAxis' => array(
		        'categories' => $categories,
		        'crosshair' => true,
		        'labels' => []		        
		    ),
		    'yAxis' => array(
		        'allowDecimals' => false,
		        'min' => 0,
		        'title' => null,			        
		    ),		    
		    'plotOptions' => array(
		        'column' => array(
		            'pointPadding' => 0.2,
		            'borderWidth' => 0
		        )
		    ),
		    'series' => $series
		);

		return $data;
	}
	
	private function getTimestampFromSolrDate($value)
	{
		$parts = explode('T', $value);		
		$parts = explode('-', $parts[0]);

		return mktime(0, 0, 0, $parts[1], $parts[2], $parts[0]) * 1000;
	}

	private function getEventiTimeline($field, $value)
	{
		$series = $categories = array();
		if (!is_array($value)){
			$value = array($value);
		}
		$series = array();
		foreach ($value as $item) {
			$facetsData = $this->getEventiFacetsData($field, intval($item));	
			$range = $facetsData['range']['extra_data_dt']['counts'];
			$rangeParsed = array();
			foreach ($range as $key => $value) {
				$rangeParsed[] = array($this->getTimestampFromSolrDate($key), $value);
			}
			$series[] = array(
		    	'name' => $facetsData['value_name'],
		    	'data' => $rangeParsed,
		    );
		}

		if (self::isDebug()) return $facetsData;

		$data = array(
		    'chart' => array(
		        'zoomType' => 'x',
		        'type' => 'spline'
		    ),
		    'title' => null,		 
		    'subtitle' => null,
		    'xAxis' => array(
		        'type' => 'datetime',
		        'dateTimeLabelFormats' => array(		        	
            		'day' => '%b %Y',
				    'week' => '%b %Y',
				    'month' => '%b %Y',
				    'year' => '%Y'
		        )
		    ),
		    'yAxis' => array(
		        'title' => array(
		        	'text' => 'Numero di eventi',
		        ),	
		        'min' => 0		        
		    ),	
		    'tooltip' => array(
		        'xDateFormat' => '%Y-%m',
		        'shared' => true
		    ),
		    'plotOptions' => array(
                'spline' => array(
                    'marker' => array(
                        'enabled' => true
                    ),                    
                )
            ),	    		    
		    'series' => $series
		);

		return $data;
	}

	public static function clearCache()
	{		
	    $fileHandler = eZClusterFileHandler::instance();
	    $commonSuffix = '';
	    $fileHandler->fileDeleteByDirList( array( 'lifefranca' ), eZSys::cacheDirectory(), $commonSuffix );
	}

}
    
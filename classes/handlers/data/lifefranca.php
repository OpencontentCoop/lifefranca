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
		if ($http->hasVariable('field')){
			$field = $http->variable('field');
		}

		if ($http->hasVariable('value')){
			$value = $http->variable('value');
		}

		if (!$field || !$value){
			return array();
		}
		if ($http->hasVariable('type')){
			if ($http->variable('type') == 'opere'){
				return $this->getOpere($field, $value);
			}
			if ($http->variable('type') == 'opere-aggr'){
				return $this->getOpereAggregate($field, $value);
			}
		}

		return $data;
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
				$tipooperaValues = array_column($classAttributeContent['options'], 'name');
				$tipoopera = array_fill_keys($tipooperaValues, 0);
			}
		}
		
		$baseQuery = 'classes [opera] facets [tipoopera|alpha|50] limit 1';
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
        }
        if (isset($data->facets[0])){
        	$facets = array(
        		'value_name' => $name,
        		'facets' => array_merge(
	        		$tipoopera,
	        		$data->facets[0]['data']
	    		)
        	);
        }

        return $facets;
	}

	private function getOpereFacetsData($field, $value)
	{
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

	private function getOpereAggregate($field, $value)
	{
		if (!is_array($value)){
			$value = array($value);
		}
		$value[] = 'all';
		$series = $categories = array();
		foreach ($value as $item) {
			$facetsData = $this->getOpereFacetsData($field, intval($item));
			$facets = $facetsData['facets'];
			$series[] = array(
				'name' => $item == 'all' ? 'Totale' : $facetsData['value_name'],
				'data' => array_values($facets),
				'visible' => $item != 'all'
			);
			if (empty($categories)) $categories = array_keys($facets);

		}
			
		$data = array(
		    'chart' => array(
		        'type' => 'bar',
		        'width' => null,
		    ),
		    'title' => null,
		 //    'title' => array(
			// 	'text' => 'Opere per tipologia'
			// ),
		    'subtitle' => null,
		    'xAxis' => array(
		        'categories' => $categories,
		        'crosshair' => true
		    ),
		    'yAxis' => array(
		        'min' => 0,
		        'title' => null
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

	private function getOpere($field, $value)
	{		
		$facetsData = $this->getOpereFacetsData($field, $value);
		$facets = $facetsData['facets'];
		$series = array();
		foreach ($facets as $key => $value) {
        	$series[] = array(
        		'name' => $key . ' (' . $value . ')',
            	'y' => (int)$value
        	);
        }
		$data = array(
			'chart' => array(
				'plotBackgroundColor' => null,
		        'plotBorderWidth' => null,
		        'plotShadow' => false,
		        'type' => 'pie'
			),
			'title' => null,
			'tooltip' => array(
		        'pointFormat' => '<b>{point.y} opere ({point.percentage:.1f}%)</b>'
		    ),
		    'plotOptions' => array(
		        'pie' => array(
		            'allowPointSelect' => true,
		            'cursor' => 'pointer',
		            'dataLabels' => array(
		                'enabled' => false
		            ),
		            'showInLegend' => true
		        )
		    ),
		    'series' => array(
		    	array(
			        'name' => 'Opere',
			        'colorByPoint' => true,
			        'data' => $series
			    )
			)
		);

		return $data;
	}
}
    
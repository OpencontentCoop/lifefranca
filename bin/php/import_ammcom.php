<?php
require 'autoload.php';

$script = eZScript::instance( array( 'description' => ( 'Import' ),
                                     'use-session' => false,
                                     'use-modules' => true,
                                     'use-extensions' => true ) );

$script->startup();

$options = $script->getOptions();
$script->initialize();
$script->setUseDebugAccumulators( true );

$cli = eZCLI::instance();

try
{
    $rawData = file_get_contents('extension/lifefranca/data/ammcom.geojson');
    $data = json_decode($rawData, true);

    $count = count($data['features']);
    $i = 0;
    foreach ($data['features'] as $item) {
        $i++;
        $properties = $item['properties'];

        $map = array(
            'type' => '',
            'color' => '',
            'source' => '',
            'geo_json' => json_encode(array(
                "type" => "FeatureCollection",
                "features" => array(
                    $item
                )
            )),            
        );

        $attributeList = array(
            'name' => $properties['DESC_'],
            'superficie' => $properties['SUPCOM'],
            'altitudine' => $properties['ALTCOM'],
            'map' => json_encode($map)
        );

        $params = array();        
        $params['class_identifier'] = 'comune';
        $params['remote_id'] = $attributeList['name'];
        $params['parent_node_id'] = 1296;
        $params['attributes'] = $attributeList; 

        $contentObject = eZContentFunctions::createAndPublishObject($params);
        eZContentObject::clearCache();

        $cli->output("$i/$count " . $attributeList['name']);
    }

    $script->shutdown();
}
catch( Exception $e )
{
    $errCode = $e->getCode();
    $errCode = $errCode != 0 ? $errCode : 1; // If an error has occured, script must terminate with a status other than 0
    $script->shutdown( $errCode, $e->getMessage() );
}

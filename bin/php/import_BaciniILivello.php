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

$user = eZUser::fetchByName( 'admin' );
eZUser::setCurrentlyLoggedInUser( $user , $user->attribute( 'contentobject_id' ) );

$parentNodeId = 1581;

try
{
    // function importFullMaps()
    // {
    //     global $parentNodeId, $cli;        
    //     //$rawData = file_get_contents('extension/lifefranca/data/BaciniILivello.geojson');
    //     $rawData = file_get_contents('extension/lifefranca/data/ambiti_territoriali/sottobacini.json');
        
    //     $data = json_decode($rawData, true);

    //     $count = count($data['features']);
    //     $i = 0;
    //     foreach ($data['features'] as $item) {
    //         $i++;
    //         $properties = $item['properties'];

    //         $map = array(
    //             'type' => '',
    //             'color' => '',
    //             'source' => '',
    //             'geo_json' => json_encode(array(
    //                 "type" => "FeatureCollection",
    //                 "features" => array(
    //                     $item
    //                 )
    //             )),            
    //         );

    //         print_r($properties);

    //         // $attributeList = array(
    //         //     'name' => $properties['nomebacino'],
    //         //     'level' => 'I LIVELLO',
    //         //     'objectid' => $properties['objectid'],
    //         //     'classid' => $properties['classid'],
    //         //     'map' => json_encode($map)
    //         // );

    //         // $params = array();        
    //         // $params['class_identifier'] = 'bacino';
    //         // $params['remote_id'] = 'bacino_' . $attributeList['objectid'] . $attributeList['classid'];
    //         // $params['parent_node_id'] = $parentNodeId;
    //         // $params['attributes'] = $attributeList; 

    //         // $contentObject = eZContentFunctions::createAndPublishObject($params);
    //         // eZContentObject::clearCache();

    //         $cli->output("$i/$count " . $attributeList['name']);
    //     }
    // }

    // function importSimpleMaps()
    // {
    //     global $parentNodeId, $cli;

    //     $parentNode = eZContentObjectTreeNode::fetch($parentNodeId);
    //     $bacini = array();
    //     foreach ($parentNode->children() as $node) {
    //         $slug = strtolower($node->attribute('name'));
    //         $bacini[$slug] = $node;
    //     }

    //     $rawData = file_get_contents('extension/lifefranca/data/BaciniILivello.solo_contorno.geojson');
    //     $data = json_decode($rawData, true);

    //     $count = count($data['features']);
    //     $i = 0;
    //     foreach ($data['features'] as $item) {
    //         $i++;
    //         $properties = $item['properties'];

    //         $map = array(
    //             'type' => '',
    //             'color' => '',
    //             'source' => '',
    //             'geo_json' => json_encode(array(
    //                 "type" => "FeatureCollection",
    //                 "features" => array(
    //                     $item
    //                 )
    //             )),            
    //         );

    //         $name = $properties['nomebacino'];
    //         $slug = strtolower($name);
    //         $cli->output($name, false);    
    //         if (isset($bacini[$slug])){
    //             $cli->output(' ' . $bacini[$slug]->attribute('contentobject_id'));
    //             $params = array();
    //             $params['attributes'] = array(
    //                 'name' => $name,                    
    //                 'map' => json_encode($map)
    //             );
    //             $result = eZContentFunctions::updateAndPublishObject( $bacini[$slug]->object(), $params );
    //             if( !$result ){
    //                 $cli->error("$i/$count " . "Il bacino $name non è stato salvato");
    //             }
    //         }else{
    //             $cli->warning("$i/$count " . "Il bacino $name non è presente nel sistema");
    //         }
    //     }
    // }

    // importSimpleMaps();

    //$rawData = file_get_contents('extension/lifefranca/data/BaciniILivello.geojson');
    $rawData = file_get_contents('extension/lifefranca/data/ambiti_territoriali/sottobacini.json');
    
    $data = json_decode($rawData, true);

    $bacini = eZContentObjectTreeNode::fetch($parentNodeId)->children();

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

        foreach ($bacini as $bacino) {
            $dataMap = $bacino->dataMap();
            if ($dataMap['classid']->toString() == $properties['classid']){
                $cli->warning($bacino->attribute('name'). ' -> ', false);                
                $bacino->setAttribute('remote_id', $properties['classid']);
                $bacino->store();
                $bacino->object()->setAttribute('remote_id', $properties['classid']);
                $bacino->object()->store();
            }
        }

        $cli->output("$i/$count " . $properties['nomesottobacino']);
    }

    $script->shutdown();
}
catch( Exception $e )
{
    $errCode = $e->getCode();
    $errCode = $errCode != 0 ? $errCode : 1; // If an error has occured, script must terminate with a status other than 0
    $script->shutdown( $errCode, $e->getMessage() );
}

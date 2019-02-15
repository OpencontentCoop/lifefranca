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

$user = eZUser::fetchByName( 'admin' );
eZUser::setCurrentlyLoggedInUser( $user , $user->attribute( 'contentobject_id' ) );

$cli = eZCLI::instance();

try
{
    //$rawData = file_get_contents('extension/lifefranca/data/ammcom.geojson');
    $rawData = file_get_contents('extension/lifefranca/data/ambiti_territoriali/comuni.json');
    $data = json_decode($rawData, true);

    $comuni = eZContentObjectTreeNode::fetch(1296)->children();
    $sortIdList = $importIdList = array();
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

        // foreach ($comuni as $comune) {
        //     //$dataMap = $comune->dataMap(); 
        //     $string = $properties['comune'];
        //     $string = str_replace("E'", 'È', $string);
        //     $string = str_replace("A'", 'À', $string);
        //     $string = str_replace("O'", 'Ò', $string);
        //     $string = str_replace("SORAGA", 'SORAGA DI FASSA', $string);       
        //     if ($comune->object()->attribute('remote_id') == $properties['classid']){
        //         $cli->warning($comune->attribute('name'). ' -> ', false);                
        //         // $comune->setAttribute('remote_id', $properties['classid']);
        //         // $comune->store();
        //         // $comune->object()->setAttribute('remote_id', $properties['classid']);
        //         // $comune->object()->store();
        //     }
        // }

        if(!eZContentObject::fetchByRemoteID($properties['classid'])){
            $attributeList = array(
                'name' => $properties['comune'],            
                'map' => json_encode($map)
            );

            $params = array();        
            $params['class_identifier'] = 'comune';
            $params['remote_id'] = $properties['classid'];
            $params['parent_node_id'] = 1296;
            $params['attributes'] = $attributeList; 

            $contentObject = eZContentFunctions::createAndPublishObject($params);
            //eZContentObject::clearCache();
            $cli->warning("$i/$count " . $properties['comune'] . ' '  . $properties['classid']);
        }else{
            $cli->output("$i/$count " . $properties['comune'] . ' '  . $properties['classid']);
        }

        $id = str_replace('AMB003_', '', $properties['classid']);
        $importIdList[$properties['classid']] = $properties['comune'];
        $sortIdList[$id] = $properties['comune'];
        
    }

    ksort($sortIdList);
    //print_r($sortIdList);

    foreach ($comuni as $comune) {
        if(!isset($importIdList[$comune->object()->attribute('remote_id')])){
            $cli->error($comune->attribute('name'));
            //eZContentObjectTreeNode::hideSubTree( $comune );
        }else{
            $cli->warning($comune->attribute('name'));
            //eZContentObjectTreeNode::unhideSubTree( $comune );
        }
        //eZSearch::addObject($comune->object(), true);
    }

    $script->shutdown();
}
catch( Exception $e )
{
    $errCode = $e->getCode();
    $errCode = $errCode != 0 ? $errCode : 1; // If an error has occured, script must terminate with a status other than 0
    $script->shutdown( $errCode, $e->getMessage() );
}

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
$trans = eZCharTransform::instance();
try
{
    //$rawData = file_get_contents('extension/lifefranca/data/comunita_di_valle.geojson');
    $rawData = file_get_contents('extension/lifefranca/data/ambiti_territoriali/comunita_di_valle.json');
    $data = json_decode($rawData, true);
    $comuni = eZContentObjectTreeNode::fetch(30698)->children();
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

        // $attributeList = array(
        //     'name' => $properties['nome'],            
        //     'map' => json_encode($map)
        // );
        
        $remoteId = $trans->transformByGroup( $properties['comunita_di_valle'], 'identifier' );

        foreach ($comuni as $comune) {                        
            if ($comune->object()->attribute('remote_id') == $remoteId){
                $cli->warning($comune->attribute('name'). ' -> ', false);                
                $comune->setAttribute('remote_id', $properties['classid']);
                $comune->store();
                $comune->object()->setAttribute('remote_id', $properties['classid']);
                $comune->object()->store();
            }
        }

        // $params = array();        
        // $params['class_identifier'] = 'comunita';
        // $params['remote_id'] = $remoteId;
        // $params['parent_node_id'] = 30698;
        // $params['attributes'] = $attributeList; 

        $cli->output("$i/$count " . $properties['comunita_di_valle'] . ' ' .  $remoteId);

        // if(!eZContentObject::fetchByRemoteID($remoteId)){
        //     $contentObject = eZContentFunctions::createAndPublishObject($params);
        //     eZContentObject::clearCache();
        // }

    }

    $script->shutdown();
}
catch( Exception $e )
{
    $errCode = $e->getCode();
    $errCode = $errCode != 0 ? $errCode : 1; // If an error has occured, script must terminate with a status other than 0
    $script->shutdown( $errCode, $e->getMessage() );
}

<?php
require 'autoload.php';

$script = eZScript::instance( array( 'description' => ( 'Relate sottobacini' ),
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


$baciniPrincipaliNodeid = 1550;
$sottobaciniNodeid = 1581;

try
{
    $geoData = array();
    $baciniNames = array();
    $baciniPrincipali = eZContentObjectTreeNode::subTreeByNodeID(array(
        'ClassFilterType' => 'include',
        'ClassFilterArray' => array('bacino')
    ), $baciniPrincipaliNodeid);

    foreach ($baciniPrincipali as $bacino) {
        $dataMap = $bacino->dataMap();
        $map = $dataMap['sub_map'];
        $data = $map->dataType()->getWKTList($map);
        $geoData[$bacino->attribute('contentobject_id')] = $data;
        $baciniNames[$bacino->attribute('contentobject_id')] = $bacino->attribute('name');
    }

    $sottobacini = eZContentObjectTreeNode::subTreeByNodeID(array(
        'ClassFilterType' => 'include',
        'ClassFilterArray' => array('bacino')
    ), $sottobaciniNodeid);

    foreach ($sottobacini as $sottobacino) {
        $dataMap = $sottobacino->dataMap();
        $bacinoSuperiore = array();
        $map = $dataMap['sub_map'];
        $data = $map->dataType()->getWKTList($map);
        foreach ($geoData as $bacinoId => $value) {
            foreach ($value as $item) {            
                foreach ($data as $sottobacinoValue) {                    
                    if ($sottobacinoValue == $item){                                                
                        $bacinoSuperiore[] = $bacinoId;
                        break;
                    }
                }
            }
        }
        if (!empty($bacinoSuperiore)){
            $bacinoSuperiore = array_unique($bacinoSuperiore);            
            // $dataMap['bacino_superiore']->fromString($bacinoSuperiore[0]);
            // $dataMap['bacino_superiore']->store();
            $cli->output($sottobacino->attribute('name') . ' -> ' . $baciniNames[$bacinoSuperiore[0]]);
            eZSearch::addObject($sottobacino, true);
        }else{
            $cli->warning($sottobacino->attribute('name') . ' -> ?');
        }
    }

    $script->shutdown();
}
catch( Exception $e )
{
    $errCode = $e->getCode();
    $errCode = $errCode != 0 ? $errCode : 1; // If an error has occured, script must terminate with a status other than 0
    $script->shutdown( $errCode, $e->getMessage() );
}

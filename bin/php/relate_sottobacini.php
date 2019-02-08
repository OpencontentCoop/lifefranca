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


function findRelation($string, $classIdentifier)
{
    $string = trim($string);

    // eccezioni e smartellamenti
    if ($classIdentifier == 'bacino'){
        $string = str_replace('-MERDAR', '- MERDAR', $string);
        $string = str_replace('STRIGNO-OSPONDAEDALETTO', 'STRIGNO-OSPEDALETTO', $string);
        $string = str_replace('RIO SPONDAOREGGIO', 'RIO SPOREGGIO', $string);
        $string = str_replace('CASTELNUOVO-GRIGNO VERSANTE DX', 'CASTELNUOVO-GRIGNO VS. DX', $string);
        $string = str_replace('TORRENTE LENO', 'TORR. LENO', $string);
    }

    if ($classIdentifier == 'comune'){
        $string = str_replace('SAN JAN DI FASSA', 'SÈN JAN DI FASSA', $string);
    }

    $searchResult = eZSearch::search(
        trim($string),
        array(
            'SearchContentClassID' => array($classIdentifier),
            'SearchLimit' => 1,
            'Filter' => array('attr_name_s:"' . $string . '"'),
            'Limitation' => array()
        )
    );
    if ( $searchResult['SearchCount'] > 0 ){
        return $string . ' = ' . $searchResult['SearchResult'][0]->attribute( 'contentobject_id' ); 
    }
    
    return $string . ' = ???';
}
var_dump( findRelation('STRIGNO-OSPONDAEDALETTO', 'bacino') );
die();

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
        /*        
        $name = $dataMap['name']->toString();
        $uppername = strtoupper($name);
        $cli->output($uppername);
        $dataMap['name']->fromString($uppername);
        $dataMap['name']->store();
        eZSearch::addObject($bacino->object(), true);
        */
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

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

try
{
    $parentNodeID = 30407;
    $jsonFiles = array(
        "lifefranca_folder.json" => "pagina_sito",
        "lifefranca_struttura_organizzativa.json" => "struttura_organizzativa",
        "lifefranca_persona_2.json" => "persona",
        "lifefranca_persona_1.json" => "persona",
    );

    foreach ($jsonFiles as $file => $classIdentifier) {
        $data = json_decode(file_get_contents('extension/lifefranca/data/' . $file), true);
        if ($data){
            if (isset($data['searchHits'])){
                foreach ($data['searchHits'] as $hit) {
                    $meta = $hit['metadata'];
                    $content = $hit['data']['ita-IT'];
                    $classIdentifier = $meta['classIdentifier'];
                    if (in_array($classIdentifier, array('image', 'folder', 'persona', 'struttura_organizzativa', 'banner'))){                                                

                        $attributeList = null;
                        if ($classIdentifier == 'folder'){
                            $classIdentifier = 'pagina_sito';
                            $attributeList = array(
                                'name' => $content['name'],
                                'short_name' => $content['short_name'],
                                'abstract' => SQLIContentUtils::getRichContent($content['short_description']),
                                'description' => SQLIContentUtils::getRichContent($content['description']),
                                'image' => (isset($content['image']['url'])) ? 'extension/lifefranca/data/' . basename($content['image']['url']) : null,
                            );
                        }

                        if ($classIdentifier == 'persona'){                            
                            $attributeList = array(
                                'email' => $content['email'],
                                'fax' => $content['fax'],
                                'matricola' => $content['matricola'],
                                'nome' => $content['nome'],
                                'cognome' => $content['cognome'],
                                'numero_breve' => $content['numero_breve'],
                                'sede_lavoro' => $content['sede_lavoro'],
                                'telefono' => $content['telefono'],
                                'telefono2' => $content['telefono2'],
                                'telefono3' => $content['telefono3'],
                                'titolo' => $content['titolo'],                                
                                
                            );
                        }

                        if ($classIdentifier == 'struttura_organizzativa'){                            
                            $attributeList = array(
                                'codice_struttura' => $content['codice_struttura'],
                                // 'codice_struttura_superiore' => $content['codice_struttura_superiore'],
                                // 'tipo_struttura_organizzativa' => $content['tipo_struttura_organizzativa'],
                                'geo' => $content['geo'],
                                'descrizione' => $content['descrizione'],
                                'descrizione_breve' => $content['descrizione_breve'],
                                'email' => $content['email'],
                                'fax' => $content['fax'],
                                'indirizzo' => $content['indirizzo'],
                                'orari' => $content['orari'],
                                'pec' => $content['pec'],
                                'responsabile' => $content['responsabile'],
                                'sito_web' => $content['sito_web'],
                                'telefono' => $content['telefono'],
                                'telefono2' => $content['telefono2'],
                                'stato_chiusura' => $content['stato_chiusura'],
                                //'persone' => $content['persone'],
                                'declaratoria' => $content['declaratoria'],
                                'cod_strutt_sup' => $content['cod_strutt_sup'],
                            );
                        }



                        if ($attributeList){
                            if (!eZContentObject::fetchByRemoteID($meta['remoteId'])){
                                
                                $cli->output($classIdentifier . ' ' . $meta['remoteId']. ' ' . $meta['name']['ita-IT']);

                                $params                     = array();
                                $params['remote_id']       = $meta['remoteId'];
                                $params['class_identifier'] = $classIdentifier;
                                $params['parent_node_id']   = $parentNodeID;
                                $params['attributes']       = $attributeList;
                                $contentObject = eZContentFunctions::createAndPublishObject( $params );
                            }
                        }

                    }
                }
            }
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

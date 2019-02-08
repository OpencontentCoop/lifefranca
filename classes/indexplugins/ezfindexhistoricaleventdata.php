<?php

class ezfIndexHistoricalEventData implements ezfIndexPlugin
{
    /**
     * @param eZContentObject $contentObect
     * @param array $docList
     */
    public function modify(eZContentObject $contentObject, &$docList)
    {
        if ($contentObject->attribute('class_identifier') == 'historical_event') {            
            $dataMap = $contentObject->dataMap();
            if (isset($dataMap['data']) && $dataMap['data']->hasContent()) {
                $dataString = $dataMap['data']->toString();
                $parts = explode('/', $dataString);
                $data = null;
                if(strlen($parts[0]) == 4){
                    $anno = $parts[0];
                    $mese = 12;
                    if (isset($parts[1])){
                        $mese = $parts[1];
                    }
                    $giorno = 1;
                    if (isset($parts[2])){
                        $giorno = $parts[2];
                    }
                    $timestamp = mktime(0, 0, 0, $mese, $giorno, $anno);
                    $data = ezfSolrDocumentFieldBase::convertTimestampToDate($timestamp);
                }                

                if ($data){
                    $version = $contentObject->currentVersion();
                    if ($version === false) {
                        return;
                    }
                    $availableLanguages = $version->translationList(false, false);
                    foreach ($availableLanguages as $languageCode) {
                        if ($docList[$languageCode] instanceof eZSolrDoc) {
                            if ($docList[$languageCode]->Doc instanceof DOMDocument) {
                                $docList[$languageCode]->addField('extra_data_dt', $data);
                            }elseif ( is_array( $docList[$languageCode]->Doc ) && !isset( $docList[$languageCode]->Doc['extra_quantita_sf'] )){                                                    
                                $docList[$languageCode]->addField('extra_data_dt', $data );                        
                            }
                        }
                    }
                }
            }
        }
    }
}

<?php

class ezfIndexOperaQuantita implements ezfIndexPlugin
{
    /**
     * @param eZContentObject $contentObect
     * @param array $docList
     */
    public function modify(eZContentObject $contentObject, &$docList)
    {
        if ($contentObject->attribute('class_identifier') == 'opera') {            
            $dataMap = $contentObject->dataMap();
            if (isset($dataMap['quantita']) && $dataMap['quantita']->hasContent()) {
                $quantitaAsFloat = (float)$dataMap['quantita']->toString();

                $version = $contentObject->currentVersion();
                if ($version === false) {
                    return;
                }
                $availableLanguages = $version->translationList(false, false);
                foreach ($availableLanguages as $languageCode) {
                    if ($docList[$languageCode] instanceof eZSolrDoc) {
                        if ($docList[$languageCode]->Doc instanceof DOMDocument) {
                            $docList[$languageCode]->addField('extra_quantita_sf', $quantitaAsFloat);
                        }elseif ( is_array( $docList[$languageCode]->Doc ) && !isset( $docList[$languageCode]->Doc['extra_quantita_sf'] )){                                                    
                            $docList[$languageCode]->addField('extra_quantita_sf', $quantitaAsFloat );                        
                        }
                    }
                }
            }
        }
    }
}

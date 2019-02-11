<?php /* #?ini charset="utf-8"?

[ImportSettings]
AvailableSourceHandlers[]
AvailableSourceHandlers[]=lifefrancacsv
AvailableSourceHandlers[]=lifefrancomunicatistampa

[lifefrancacsv-HandlerSettings]
Enabled=true
Name=LifeFranca CSV Handler
ClassName=LifeFrancaCSVHandler

[lifefrancomunicatistampa-HandlerSettings]
Enabled=true
Name=LifeFranca Comunicati Stampa Handler
ClassName=LifeFrancaComunicatiStampaHandler
DefaultParentNodeID=1181
Endpoint=https://www.ufficiostampa.provincia.tn.it/api/opendata/v2/content/search/
Query=classes [comunicato] and tematica contains ['"TERRITORIO E AMBIENTE"', '"PROTEZIONE CIVILE"'] and published range [2018-09-01,NOW] sort [published=>desc]


*/ ?>

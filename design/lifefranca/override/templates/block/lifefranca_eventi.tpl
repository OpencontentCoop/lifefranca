{run-once}
{ezscript_require( array(    
    'ezjsc::jquery', 
    'ezjsc::jqueryUI', 
    'plugins/noUiSlider/jquery.nouislider.all.js',
    'bootstrap/transition.js',
    'bootstrap/collapse.js',
    'jquery.opendataTools.js',
    'jquery.opendataSearchView.js',
    'leaflet.js',
    'ezjsc::jquery',
    'Leaflet.MakiMarkers.js',
    'jquery.ocdrawmap.js',
    'lifeFrancaSearchView.js',
    'lifefranca-eventi.js',
    'jsrender.js',
    'highcharts/highcharts.js'
))}
{ezcss_require( array('lifefranca.css', 'leaflet/leaflet.0.7.2.css', 'highcharts/highcharts.css', 'plugins/noUiSlider/jquery.nouislider.min.css') )}    
{/run-once}

{set_defaults(hash('show_title', true(), 'items_per_row', 1, language, 'ita-IT'))}

{def $bacini = fetch(content, tree, hash(
    'parent_node_id', 1, 
    'class_filter_type', 'include', 
    'class_filter_array', array('bacino'), 
    'attribute_filter', array(array('bacino/level', '=', '0')), 
    'sort_by', array('name', true()),
    'limitation', array(),
    'limit', 200)
)}

{def $sottobacini = fetch(content, tree, hash(
    'parent_node_id', 1, 
    'class_filter_type', 'include', 
    'class_filter_array', array('bacino'), 
    'attribute_filter', array(array('bacino/level', '=', '1')), 
    'sort_by', array('attribute',true(), 'bacino/bacino_superiore'),
    'limitation', array(),
    'limit', 200)
)}

{def $comuni = fetch(content, tree, hash(
    'parent_node_id', 1, 
    'class_filter_type', 'include', 
    'class_filter_array', array('comune'),  
    'sort_by', array('name', true()),
    'limitation', array(),
    'limit', 300)
)}

{def $comunita = fetch(content, tree, hash(
    'parent_node_id', 1, 
    'class_filter_type', 'include', 
    'class_filter_array', array('comunita'),  
    'sort_by', array('name', true()),
    'limitation', array(),
    'limit', 300)
)}

{def $first_event_timestamp = api_search("select-fields [extradata.timestamp] classes [historical_event] sort [raw[extra_data_dt]=>asc] limit 1")[0]}
{def $start_event_timestamp = 631152000} {*1/1/1990*}
<div class="openpa-widget {$block.view}">
    {if and( $show_title, $block.name|ne('') )}
        <h3 class="openpa-widget-title"><span>{$block.name|wash()}</span></h3>
    {/if}
    <div class="openpa-widget-content facet-search" id="lifefranca-eventi">
        
        <a href="#" class="Button Button--default btn-block open-xs-filter u-sm-hidden u-md-hidden u-lg-hidden"><i class="fa fa-filter"></i> Filtri</a>
        
        <div class="Grid Grid--withGutter filters-wrapper hidden-xs">
            
            <div class="Grid-cell u-sizeFull u-sm-size1of2 u-md-size1of2 u-lg-size1of2 filter-wrapper">                    
                <h4 class="u-text-h4 widget_title">
                    <a data-toggle="collapse" href="#contesto" aria-expanded="false" aria-controls="contesto">
                        <i class="fa fa-plus"></i>
                        <span>Contesto di ricerca</span>                          
                    </a>
                    <ul class="list-inline current-xs-filters">
                        <li>Bacini principali</li>
                    </ul>                
                </h4>
                <div class="widget collapse" id="contesto">                    
                    <ul class="Linklist Linklist--padded base-layer-buttons" data-filter="contesto">
                        <li class="active">
                            <a href="#" data-layer="bacinoprincipale" data-target="bacinoprincipale.id" data-color="#007fff" class="Linklist-link Linklist-link--lev1">Bacini principali</a>
                        </li>
                        <li>
                            <a href="#" data-layer="sottobacino" data-target="sottobacino.id" data-color="#f00" class="Linklist-link">Sottobacini</a>
                        </li>
                        <li>
                            <a href="#" data-layer="comune" data-target="comune.id"  data-color="#333" class="Linklist-link">Comuni</a>
                        </li>
                        <li>
                            <a href="#" data-layer="comunita" data-target="comunita.id"  data-color="#690" class="Linklist-link">Comunità di Valle</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="Grid-cell u-sizeFull u-sm-size1of2 u-md-size1of2 u-lg-size1of2 filter-wrapper">                    
                <h4 class="u-text-h4 widget_title">
                    <a data-toggle="collapse" href="#bacinoprincipale" aria-expanded="false" aria-controls="bacinoprincipale">
                        <i class="fa fa-plus"></i>
                        <span>Bacino principale</span>                          
                    </a>
                    <ul class="list-inline current-xs-filters"></ul>                
                </h4>
                <div class="widget collapse" id="bacinoprincipale">                    
                    <ul class="Linklist Linklist--padded" data-filter="bacinoprincipale.id">
                      <li class="remove-filter"><a href="#" data-value="all">Rimuovi filtri</a></li>                      
                      {foreach $bacini as $item}                        
                        <li>
                            <a href="#" 
                               class="Linklist-link" 
                               data-name="{$item.name|wash()}"
                               data-geojson='{$item.data_map.map.content.geo_json|explode("'")|implode('')}'                                
                               data-value="{$item.contentobject_id|wash()}">
                                {$item.name|wash()}
                            </a>
                        </li>
                        {/foreach}
                    </ul>
                </div>
            </div>

            <div class="Grid-cell u-sizeFull u-sm-size1of2 u-md-size1of2 u-lg-size1of2 filter-wrapper" style="display: none;">                    
                <h4 class="u-text-h4 widget_title">
                    <a data-toggle="collapse" href="#sottobacino" aria-expanded="false" aria-controls="sottobacino">
                        <i class="fa fa-plus"></i>
                        <span>Sottobacino</span>                          
                    </a>
                    <ul class="list-inline current-xs-filters"></ul>                
                </h4>
                <div class="widget collapse" id="sottobacino">                    
                    <ul class="Linklist Linklist--padded" data-filter="sottobacini.id">
                      <li class="remove-filter"><a href="#" data-value="all">Rimuovi filtri</a></li>
                      {def $current_bacino_name = ''}
                      {foreach $sottobacini as $item}
                        {def $bacino = $item.data_map.bacino_superiore.content}
                        {if $bacino.name|ne($current_bacino_name)}
                            <li class="u-padding-all-s u-background-grey-20"><span class="u-color-black">{$bacino.name|wash()}</span></li>
                            {set $current_bacino_name = $bacino.name}
                        {/if}
                        <li>
                            <a href="#" 
                               class="Linklist-link" 
                               data-name="{$item.name|wash()}"
                               data-bacino="{$bacino.id}"
                               data-geojson='{$item.data_map.map.content.geo_json|explode("'")|implode('')}' 
                               data-value="{$item.contentobject_id|wash()}">
                                {$item.name|wash()}
                            </a>
                        </li>
                        {undef $bacino}
                        {/foreach}
                    </ul>
                </div>
            </div>

            <div class="Grid-cell u-sizeFull u-sm-size1of2 u-md-size1of2 u-lg-size1of2 filter-wrapper" style="display: none;">                    
                <h4 class="u-text-h4 widget_title">
                    <a data-toggle="collapse" href="#comune" aria-expanded="false" aria-controls="comune">
                        <i class="fa fa-plus"></i>
                        <span>Comune</span>                          
                    </a>
                    <ul class="list-inline current-xs-filters"></ul>                
                </h4>
                <div class="widget collapse" id="comune">                    
                    <ul class="Linklist Linklist--padded" data-filter="comune.id">
                      <li class="remove-filter"><a href="#" data-value="all">Rimuovi filtri</a></li>
                      {foreach $comuni as $item}                        
                        <li>
                            <a href="#" 
                               class="Linklist-link" 
                               data-name="{$item.name|wash()}"
                               data-geojson='{$item.data_map.map.content.geo_json|explode("'")|implode('')}' 
                               data-value="{$item.contentobject_id|wash()}">
                                {$item.name|wash()}
                            </a>
                        </li>
                        {/foreach}
                    </ul>
                </div>
            </div>

            <div class="Grid-cell u-sizeFull u-sm-size1of2 u-md-size1of2 u-lg-size1of2 filter-wrapper" style="display: none;">                    
                <h4 class="u-text-h4 widget_title">
                    <a data-toggle="collapse" href="#comunita" aria-expanded="false" aria-controls="comunita">
                        <i class="fa fa-plus"></i>
                        <span>Comunità di valle</span>                          
                    </a>
                    <ul class="list-inline current-xs-filters"></ul>                
                </h4>
                <div class="widget collapse" id="comunita">                    
                    <ul class="Linklist Linklist--padded" data-filter="comunita.id">
                      <li class="remove-filter"><a href="#" data-value="all">Rimuovi filtri</a></li>
                      {foreach $comunita as $item}                        
                        <li>
                            <a href="#" 
                               class="Linklist-link" 
                               data-name="{$item.name|wash()}"
                               data-geojson='{$item.data_map.map.content.geo_json|explode("'")|implode('')}' 
                               data-value="{$item.contentobject_id|wash()}">
                                {$item.name|wash()}
                            </a>
                        </li>
                        {/foreach}
                    </ul>
                </div>
            </div>
            
        </div>

        <div class="year-selector-container">          
          <div id="year-selector">
            <p style="margin-bottom: 10px">
                <strong>Periodo:</strong> 
                <small class="event-start">da <span></span></small> 
                <small class="event-end">a <span></span></small>
            </p>
            <div id="year-selector-slider" style="padding: 0 20px" data-start="{$first_event_timestamp}" data-initial="{$start_event_timestamp}" data-end="{currentdate()}"></div>            
          </div>
          <input id="data-year-selector" type="hidden" name="year" value="" />
        </div>

                
        <div class="current-filters-wrapper">            
            <ul class="current-filter" style="margin: 20px 0"></ul>
        </div>        
        
        <div class="Grid Grid--withGutter">
            <div class="Grid-cell u-sizeFull u-sm-size2of3 u-md-size2of3 u-lg-size2of3 map-container">
                <div class="map" style="width: 100%; height: 500px"></div>
            </div>
            <div class="Grid-cell u-sizeFull u-sm-size1of3 u-md-size1of3 u-lg-size1of3" style="height: 500px;overflow-y: auto;">
                <div class="Grid Grid--withGutter current-filter-aggregate"></div>                 
            </div>
            <div class="Grid-cell u-sizeFull">                
                <div class="current-result"></div>        
            </div>
        </div>
            
    </div>
</div>

{run-once}
{literal}
<script id="tpl-filter" type="text/x-jsrender">
<div class="Grid-cell u-sizeFull u-sm-size1of2 u-md-size1of2 u-lg-size1of2 filter-wrapper">                    
    <h4 class="u-text-h4 widget_title">
        <a data-toggle="collapse" href="#{{:queryField}}" aria-expanded="false" aria-controls="{{:queryField}}">
            <i class="fa fa-plus"></i>
            <span>{{:label}}</span>                          
        </a>
        <ul class="list-inline current-xs-filters"></ul>                
    </h4>
    <div class="widget collapse" id="{{:queryField}}">                    
        <ul class="Linklist Linklist--padded" data-filter="{{:queryField}}">
          <li class="remove-filter"><a href="#" data-value="all">Rimuovi filtri</a></li>
        </ul>
    </div>
</div>
</script>

<script id="tpl-close-xs-filter" type="text/x-jsrender">
    <a href="#{{:id}}" class="Button Button--default btn-block close-xs-filter" style="display: none;"><i class="fa fa-times"></i> Chiudi</a>
</script>

<script id="tpl-spinner" type="text/x-jsrender">
<div class="Grid-cell spinner u-textCenter" style="position: absolute;z-index: 1001;padding-top: 250px; height: 500px; width: 98%;background: #333;opacity: 0.5;">
    <i class="fa fa-circle-o-notch fa-spin fa-3x fa-fw" style="color:#fff"></i>
    <span class="sr-only">Loading...</span>
</div>
</script>

<script id="tpl-empty" type="text/x-jsrender">
<div class="Grid-cell u-textCenter">
    <i class="fa fa-times"></i> Nessun risultato trovato
</div>
</script>

<script id="tpl-item" type="text/x-jsrender">
<div class="Grid-cell u-sizeFull u-sm-size1of2 u-md-size1of2 u-lg-size1of2">    
    <div class="openpa-panel">              
      <div class="openpa-panel-content">        
        <h3 class="Card-title">
          <a class="Card-titleLink" href="{{:~settings('accessPath')}}/content/view/full/{{:metadata.mainNodeId}}" title="{{:~i18n(metadata.name)}}">{{:~i18n(metadata.name)}}</a>
        </h3>
        {{if ~i18n(data,'abstract')}}
        <div class="Card-text">{{:~i18n(data,'abstract')}}</div>
        {{/if}}
      </div>
      <a class="readmore" href="{{:~settings('accessPath')}}/content/view/full/{{:metadata.mainNodeId}}" title="{{:~i18n(data,'name')}}">Dettaglio</a>        
    </div>
</div>
</script>

<script id="tpl-load-other" type="text/x-jsrender">
<div class="Grid-cell u-textCenter">
    <a href="#" class="Button Button--default u-margin-all-xxl">Carica altri risultati</a>
</div>
</script>

<script id="tpl-chart" type="text/x-jsrender">
<div class="Grid-cell u-sizeFull u-margin-top-s u-margin-bottom-s">
    <div class="u-nbfc u-border-all-xxs u-color-grey-30 u-background-white"
         style="position:relative; border-color:{{:color}} !important">    
        <div class="u-padding-all-s">
            <p class="u-margin-bottom-xxs u-color-grey-80 u-text-xs">{{:label}}</p>
            <h3 class="u-text-4 u-color-black">{{:name}}</h3> 
            <div class="chart"></div>       
        </div>
    </div>
</div>
</script>
<script id="tpl-chart-aggr" type="text/x-jsrender">
<div class="Grid-cell u-sizeFull u-margin-bottom-l">
    <div class="u-nbfc u-border-all-xxs u-color-grey-30 u-background-white"
         style="position:relative; border-color:{{:color}} !important; box-shadow: 0 1px 2px 0 rgba(50,50,50,.35) !important">            
        <div class="u-padding-all-s">            
            <h3 class="u-margin-bottom-xxs u-color-grey-80 u-text-xs">{{:label}}</h3>
            <div class="chart" style="min-height:{{:height}}px;width100%"></div>       
        </div>
    </div>
</div>
</script>

{/literal}
{/run-once}

<script>{literal}
$(document).ready(function(){
    $.opendataTools.settings('accessPath', "{/literal}{''|ezurl(no,full)}{literal}");

    $('#lifefranca-eventi').lifeFrancaBlock({        
        'cssClasses': {
            'item': 'Linklist-link',
            'itemActive': 'Linklist-link--lev1',
            'listWrapper': 'Linklist Linklist--padded',
            'itemEmpty': ''
        },
        'dataChart': 'eventi',
        'viewHelpers': $.extend({}, $.opendataTools.helpers, {
            'firstImageUrl': function (image) {                        
                if (image.length > 0 && typeof image[0].id == 'number') {
                    return $.opendataTools.settings('accessPath') + '/image/view/' + image[0].id + '/agid_panel';
                }
                return image.url;
            }
        }),
        'layers':[
            {
                'queryField': 'bacinoprincipale.id',
                'label': 'Bacino principale',
                'render': false,
                'layerOptions': {color:'#007fff',weight: 2,opacity: 0.9}
            },
            {
                'queryField': 'sottobacini.id',
                'label': 'Sottobacino',
                'render': false,
                'layerOptions': {color:'#f00',weight: 2,opacity: 0.9}
            },
            {
                'queryField': 'comune.id',
                'label': 'Comune',
                'render': false,
                'layerOptions': {color:'#333',weight: 2,opacity: 0.9}
            },
            {
                'queryField': 'comunita.id',
                'label': 'Comunità di Valle',
                'render': false,
                'layerOptions': {color:'#690',weight: 2,opacity: 0.9}
            }
        ]
    });   
});
{/literal}</script>
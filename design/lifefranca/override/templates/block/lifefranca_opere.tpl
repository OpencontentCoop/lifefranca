{run-once}
{ezscript_require( array(    
    'bootstrap/transition.js',
    'bootstrap/collapse.js',
    'jquery.opendataTools.js',
    'jquery.opendataSearchView.js',
    'leaflet.js',
    'ezjsc::jquery',
    'Leaflet.MakiMarkers.js',
    'jquery.ocdrawmap.js',
    'lifefranca.js',
    'jsrender.js',
    'highcharts/highcharts.js'
))}
{ezcss_require( array('lifefranca.css', 'leaflet/leaflet.0.7.2.css', 'highcharts/highcharts.css') )}    
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

<div class="openpa-widget {$block.view}">
    {if and( $show_title, $block.name|ne('') )}
        <h3 class="openpa-widget-title"><span>{$block.name|wash()}</span></h3>
    {/if}
    <div class="openpa-widget-content facet-search" id="lifefranca-opere">
        
        <a href="#" class="Button Button--default btn-block open-xs-filter u-sm-hidden u-md-hidden u-lg-hidden"><i class="fa fa-filter"></i> Filtri</a>
        
        <div class="Grid Grid--withGutter filters-wrapper hidden-xs">
            <div class="Grid-cell u-sizeFull u-sm-size1of3 u-md-size1of3 u-lg-size1of3 filter-wrapper">                    
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

            <div class="Grid-cell u-sizeFull u-sm-size1of3 u-md-size1of3 u-lg-size1of3 filter-wrapper">                    
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

            <div class="Grid-cell u-sizeFull u-sm-size1of3 u-md-size1of3 u-lg-size1of3 filter-wrapper">                    
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
        </div>
        
        <div class="base-layer-buttons">
            <a href="#" data-layer="bacinoprincipale" data-target="bacinoprincipale.id" data-color="#007fff" class="btn btn-default btn-xs active">Bacini principali</a>
            <a href="#" data-layer="sottobacino" data-target="sottobacino.id" data-color="#f00" class="btn btn-default btn-xs">Sottobacini</a>
            <a href="#" data-layer="comune" data-target="comune.id"  data-color="#333" class="btn btn-default btn-xs">Comuni</a>
        </div>
        <div class="map" style="width: 100%; height: 500px"></div>
        <div class="current-result"></div>
        <div class="current-filters-wrapper">            
            <div class="Grid Grid--withGutter current-filter-aggregate"></div>
            <div class="Grid Grid--withGutter current-filter"></div>
        </div>        
            
    </div>
</div>

{run-once}
{literal}
<script id="tpl-filter" type="text/x-jsrender">
<div class="Grid-cell u-sizeFull u-sm-size1of3 u-md-size1of3 u-lg-size1of3 filter-wrapper">                    
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
<div class="Grid-cell spinner u-textCenter">
    <i class="fa fa-circle-o-notch fa-spin fa-3x fa-fw"></i>
    <span class="sr-only">Loading...</span>
</div>
</script>

<script id="tpl-empty" type="text/x-jsrender">
<div class="Grid-cell u-textCenter">
    <i class="fa fa-times"></i> Nessun risultato trovato
</div>
</script>

<script id="tpl-item" type="text/x-jsrender">
<div class="Grid-cell u-sizeFull u-sm-size1of2 u-md-size1of2 u-lg-size1of3 u-margin-bottom-l">    
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
<div class="Grid-cell u-size1of2 u-sm-size1of2 u-md-size1of3 u-lg-size1of3">
    <div class="u-nbfc u-border-all-xxs u-color-grey-30 u-background-white u-margin-top-s"     
         style="position:relative; border-color:{{:color}} !important">    
        <a href="#" style="margin:10px;position:absolute;right:0;color:{{:color}}" class="close"><i class="fa fa-times"></i></a>
        <div class="u-padding-all-s u-layout-prose">
            <p class="u-margin-bottom-xxs u-color-grey-80 u-text-xs">{{:label}}</p>
            <h3 class="u-text-4 u-color-black">{{:name}}</h3> 
            <div class="chart"></div>       
        </div>
    </div>
</div>
</script>
<script id="tpl-chart-aggr" type="text/x-jsrender">
<div class="Grid-cell u-sizeFull">
    <div class="u-nbfc u-border-all-xxs u-color-grey-30 u-background-white u-margin-top-s"     
         style="position:relative; border-color:{{:color}} !important">            
        <a href="#" style="margin:10px;position:absolute;right:0;color:{{:color}}" class="close"><i class="fa fa-times"></i></a>
        <div class="u-padding-all-s">            
            <p class="u-margin-bottom-xxs u-color-grey-80 u-text-xs">Dati aggregati per {{:label}}</p>            
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

    $('#lifefranca-opere').lifeFrancaBlock({
        'query': "classes [opera] limit 1",
        'cssClasses': {
            'item': 'Linklist-link',
            'itemActive': 'Linklist-link--lev1',
            'listWrapper': 'Linklist Linklist--padded',
            'itemEmpty': ''
        },
        'dataChart': 'opere',
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
            }
        ]
    });   
});
{/literal}</script>
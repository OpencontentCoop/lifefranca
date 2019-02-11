;(function ($, window, document, undefined) {

    $.opendataTools.settings('endpoint', {search: '/openpa/data/lifefranca'});
    
    var MapLayerFactory = function(label, queryField, containerSelector, cssClasses, currentMap, currentBackground, layerOptions){
        return {

            type: 'layer',

            cssClasses: cssClasses,

            label: label,

            showSpinner: false,
            
            showCount: false,
            
            multiple: true,

            current: ['all'],

            name: queryField,

            container: containerSelector,

            layer: L.featureGroup(),

            layerOptions: layerOptions,

            buildQueryFacet: function () {
                // non mi serve filtrare la query perché i risultati vengono calcolati direttamente dai filtri attivi
                return null;

            },

            buildQuery: function () {
                
                // non mi serve filtrare la query perché i risultati vengono calcolati direttamente dai filtri attivi
                return null;
                
                var currentValues = this.getCurrent();
                if (currentValues.length && jQuery.inArray('all', currentValues) == -1) {
                    return queryField+' in [\'' + $.map(currentValues, function (item) {
                        return item.toString()
                            .replace(/"/g, '\\\"')
                            .replace(/'/g, "\\'")
                            .replace(/\(/g, "\\(")
                            .replace(/\)/g, "\\)");
                    }).join("','") + '\']';
                }

                return null;
            },

            addToLayer: function(link){
                var self = this; 
                //console.log('filter.addToLayer ', link.data('value'), self.layer);
                var id = link.data('value');
                var json = link.data('geojson');                
                // console.log(self.name, id);
                // if (self.name == 'bacinoprincipale.id'){                                        
                //     $('#sottobacino').find('[data-bacino="'+id+'"]').trigger('click');
                // }
                var added = $.addGeoJSONLayer(json, currentMap, self.layer, null, self.layerOptions, null,
                    function(feature, layer) {                  
                        feature.properties._id = link.data('value');
                    }
                );                
            },

            filterClickEvent: function (e, view) {
                var self = this;                   
                var selectedValue = [];                
                var selected = $(e.currentTarget);
                self.layer._selected = [];
                self.layer.clearLayers();
                if (selected.data('value') != 'all'){
                    var selectedWrapper = selected.parent();            
                    if (this.multiple){                                
                        if (selectedWrapper.hasClass(self.cssClasses.itemWrapperActive)){
                            selectedWrapper.removeClass(self.cssClasses.itemWrapperActive);   
                            selected.removeClass(self.cssClasses.itemActive);
                        }else{
                            selectedWrapper.addClass(self.cssClasses.itemWrapperActive);   
                            selected.addClass(self.cssClasses.itemActive);
                        }
                        $('li.'+self.cssClasses.itemWrapperActive, $(this.container)).each(function(){
                            var value = $(this).find('a').data('value');
                            if (value != 'all'){
                                selectedValue.push(value);
                                self.addToLayer($(this).find('a'));
                            }
                        });  
                    }else{
                        $('li', $(this.container)).removeClass(self.cssClasses.itemWrapperActive);
                        $('li a', $(this.container)).removeClass(self.cssClasses.itemActive);
                        selectedWrapper.addClass(self.cssClasses.itemWrapperActive);
                        selected.addClass(self.cssClasses.itemActive);
                        selectedValue = [selected.data('value')];
                        self.addToLayer(selected);
                    }                
                    if (this.showSpinner){
                        selected.parents('div.filter-wrapper').find('.widget_title a').append('<span class="loading pull-right"> <i class="fa fa-circle-notch fa-spin"></i></span>');
                    }
                }else{
                    $('li', $(this.container)).removeClass(self.cssClasses.itemWrapperActive);
                    $('li a', $(this.container)).removeClass(self.cssClasses.itemActive);
                }                
                self.layer.addTo(currentMap);
                self.layer._selected = selectedValue;
                this.setCurrent(selectedValue);
                view.doSearch();
                e.preventDefault();
            },

            init: function (view, filter) {
                var self = this;
                self.layer._selected = [];
                self.layer.on('click', function(e) {                                                                 
                    var clickedLeafletId = e.layer._leaflet_id;
                    //console.log(clickedLeafletId);
                    $.each(e.target._layers, function(){                        
                        var targetLayer = this;
                        if (targetLayer._leaflet_id == clickedLeafletId){
                            var id = targetLayer.feature.properties._id;
                            //console.log('filter.init '+id);                            
                            $('a[data-value="'+id+'"]', $(self.container)).trigger('click');
                        }else if (targetLayer._layers){
                            $.each(targetLayer._layers, function(){                            
                                if (this._leaflet_id == clickedLeafletId){
                                    var id = targetLayer.feature.properties._id;
                                    //console.log('filter.init '+id);
                                    $('a[data-value="'+id+'"]', $(self.container)).trigger('click');
                                    return;
                                }
                            });
                        }
                    });
                });
                $(self.container).find('a').on('click', function (e) {
                    self.filterClickEvent(e, view)
                });
            },

            setCurrent: function (value) {                            
                this.current = value;
            },

            getCurrent: function () {
                return this.current;
            },

            refresh: function (response, view) {
                var self = this;
                if (self.showSpinner){
                    $('span.loading').remove();
                }

                var current = self.getCurrent();
                $('li a', $(self.container)).each(function () {
                    if ($(this).data('value') !== 'all'){
                        var name = $(this).data('name');
                        $(this).html(name).data('count', 0).addClass(self.cssClasses.itemEmpty);
                    }
                });                
            },

            quoteValue: function(value){
                return value;
            },

            reset: function (view) {
                var self = this;
                $('li', $(self.container)).removeClass(self.cssClasses.itemWrapperActive);
                var currentValues = this.getCurrent();
                $.each(currentValues, function () {
                    $('li a[data-value="' + this + '"]', $(self.container)).parent().addClass(self.cssClasses.itemWrapperActive);
                });
            }
        }
    };

    $.initLifeFrancaBlockEvent = function(){
        $('.widget').on('hidden.bs.collapse', function () {
          $(this).parents('.filters-wrapper').removeClass('has-active');
          $(this).parent().removeClass('active').addClass('unactive');
          $(this).prev().find('i').removeClass('fa-times').addClass('fa-plus');       
        }).on('show.bs.collapse', function () {
          $(this).parents('.filters-wrapper').find('div.filter-wrapper').removeClass('active').addClass('unactive');
          $(this).parent().removeClass('unactive').addClass('active').show();
          $(this).parents('.filters-wrapper').addClass('has-active');
          $(this).prev().find('i').removeClass('fa-plus').addClass('fa-times');
        });

        $('body').click(function (e) {
            if($(e.target).closest('.widget').length == 0 && $(e.target).closest('.widget_title').length == 0) {
                $('.widget').each(function(){
                    if($(this).hasClass('in')){
                        $(this).removeClass('in').trigger('hidden.bs.collapse');    
                    }
                })
            }
        });

        $('.open-xs-filter').on('click', function(){
            $(this).addClass('hidden-xs');
            $('.filters-wrapper').removeClass('hidden-xs').addClass('filters-wrapper-xs');
            $('.close-xs-filter').show();
            $('body').addClass('modal-open');
        });
        $('.close-xs-filter').on('click', function(){
            $(this).hide();
            $('.open-xs-filter').removeClass('hidden-xs');
            $('.filters-wrapper').removeClass('filters-wrapper-xs').addClass('hidden-xs');
            $('body').removeClass('modal-open');
        });
    };

    $.fn.lifeFrancaBlock = function (settings) {
        
        var that = $(this);

        var options = $.extend(true, {
            'query': "",
            'filters': [],
            'filterTpl': '#tpl-filter',
            'chartTpl': '#tpl-chart',
            'chartAggrTpl': '#tpl-chart-aggr',
            'spinnerTpl': '#tpl-spinner',
            'emptyTpl': '#tpl-empty',
            'itemTpl': '#tpl-item',
            'loadOtherTpl': '#tpl-load-other',
            'closeXsFilterTpl': '#tpl-close-xs-filter',            
            'cssClasses': {
                'item': '',
                'itemActive': '',
                'itemEmpty': 'text-muted',
                'itemWrapper': '',
                'itemWrapperActive': 'active',
                'listWrapper': 'nav nav-pills nav-stacked'
            },
            'viewHelpers': $.opendataTools.helpers,
            'dataChart': ''
        }, settings)


        var filterTpl = $.templates(options.filterTpl);
        var spinner = $($.templates(options.spinnerTpl).render({}));
        var empty = $.templates(options.emptyTpl).render({});
        var cssClasses = options.cssClasses;
        var chartTpl = $.templates(options.chartTpl);
        var chartAggrTpl = $.templates(options.chartAggrTpl);

        var osmUrl = 'http://{s}.tile.osm.org/{z}/{x}/{y}.png'; //http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            osmAttrib = '&copy; <a href="http://openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            osm = L.tileLayer(osmUrl, { maxZoom: 18, attribution: osmAttrib });
                
        var currentBackground;
        var currentBackgroundFilter;
        var currentMap = new L.Map(that.find('.map')[0], { minZoom: 9, center: new L.LatLng(0, 0), zoom: 13 })
            .addLayer(osm);        
        currentMap.scrollWheelZoom.disable();
        var baseLayer = L.featureGroup();

        var selectBaseLayer = function(layerElement){
            if (layerElement.length > 0){
                var filterWrapper = layerElement.parents('.filter-wrapper');
                var layerName = layerElement.data('layer');
                currentBackgroundFilter = layerElement.data('target');;
                var layerColor = layerElement.data('color');
                
                var currentLayerName = $('.base-layer-buttons li.active a').removeClass('Linklist-link--lev1').data('layer');
                $('.base-layer-buttons li').removeClass('active');
                $('.base-layer-buttons a[data-layer="'+layerName+'"]').parent().addClass('active');
                $('.base-layer-buttons li a[data-layer="'+layerName+'"]').addClass('Linklist-link--lev1');
                $('a[data-value="all"]', $('#'+currentLayerName)).trigger('click');
                $('#'+currentLayerName).parents('.filter-wrapper').hide();
                $('#'+layerName).parents('.filter-wrapper').show();
                
                baseLayer.clearLayers();
                
                that.find('#'+layerName+' ul li a').each(function(){
                    var link = $(this);
                    var geoJSON = link.data('geojson');
                    if (geoJSON){                    
                        //geoJSON.properties.name = $(this).data('name');
                        var geoJSONLayer = $.addGeoJSONLayer(geoJSON, currentMap, baseLayer, null, {
                            color: layerColor,
                            weight: 2,
                            opacity: 0.3                        
                        },
                        null,
                        function(feature, layer) {
                            feature.properties._id = link.data('value');
                        });
                        geoJSONLayer.on('click', function(e) {                         
                            //console.log('base click.event on '+link.data('value'), e);                    
                            $('a[data-value="'+link.data('value')+'"]', $('#'+layerName)).trigger('click');
                        });
                    }
                });
                if (baseLayer.getLayers().length > 0) {
                    currentMap.fitBounds(baseLayer.getBounds());
                    currentMap.setMaxBounds(baseLayer.getBounds());
                }
                baseLayer.addTo(currentMap);
                currentBackground = baseLayer;

                filterWrapper.find('.current-xs-filters').html('<li>'+layerElement.text()+'</li>');

                // if (filterWrapper.find('.collapse').hasClass('in')){
                //     filterWrapper.find('h4.widget_title a').trigger('click');
                // }
            }
        }        
        $('.base-layer-buttons a').on('click', function(e){            
            selectBaseLayer($(this));
            e.preventDefault();
        });

        selectBaseLayer($('.base-layer-buttons li.active a'));

        var searchView = that.lifeFrancaSearchView({
            query: options.query,  
            onLoadResults: function (response, query, appendResults, view) {                

                var currentFilterContainer = view.container.find('.current-filter');
                var currentFilterAggregateContainer = view.container.find('.current-filter-aggregate');    
                var currentFilterTimeContainer = view.container.find('.current-result');                
                
                currentFilterContainer.empty(); 
                currentFilterAggregateContainer.empty();               
                currentFilterTimeContainer.empty();               

                $.each(view.filters, function(){
                    var filter = this;                                        
                    var currentValues = filter.getCurrent();
                    var filterContainer = $(filter.container);  
                    var field = filter.name;
                    var currentXsFilterContainer = filterContainer.parents('div.filter-wrapper').find('.current-xs-filters');                
                    currentXsFilterContainer.empty();
                    if (currentValues.length && jQuery.inArray('all', currentValues) == -1) {                        
                        var item = $('<li style="margin-bottom:20px"><strong>'+ filter.label +'</strong>:</li>');                    
                        $.each(currentValues, function(){                        
                            var value = this;
                            var valueElement = $('a[data-value="'+filter.quoteValue(value)+'"]', filter.container);                        
                            var name = valueElement.data('name');
                            currentXsFilterContainer.append('<li>'+name+'</li>');                        
                            $('<a href="#" style="margin:0 5px"><i class="fa fa-times"></i> '+name+'</a>')                            
                                .on('click', function(e){
                                    valueElement.trigger('click');
                                    e.preventDefault();
                                })
                                .appendTo(item);
                        });
                        item.appendTo(currentFilterContainer);
                        
                        if (filter.type == 'layer'){
                            var currentFilterAggrItem = $(chartAggrTpl.render({
                                label: 'Numero totale',
                                color: filter.layerOptions.color,
                                height: 420
                            }));
                            currentFilterAggregateContainer.html(currentFilterAggrItem);

                            var currentFilterTimeItem = $(chartTpl.render({
                                label: 'Timeline degli eventi',
                                color: filter.layerOptions.color,
                                height: 420
                            }));                                                                                 
                            currentFilterTimeContainer.html(currentFilterTimeItem);

                            $.get('/openpa/data/lifefranca', {type: options.dataChart, field: field, value: currentValues}, function(response) {
                                response.tooltip = {
                                    headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
                                    pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                                        '<td style="padding:0"><b>{point.y}</b></td></tr>',
                                    footerFormat: '</table>',
                                    shared: true,
                                    useHTML: true
                                };
                                Highcharts.chart(currentFilterAggrItem.find('.chart')[0], response);
                            });

                            $.get('/openpa/data/lifefranca', {type: 'timeline', field: field, value: currentValues}, function(response) {
                                Highcharts.chart(currentFilterTimeItem.find('.chart')[0], response);                                
                            });                            
                        }
                    }else{
                        filterContainer.find('li a[data-value="all"]').parent().addClass('active');
                    }
                });

            },
            onLoadErrors: function (errorCode, errorMessage, jqXHR, view) {
                view.container.html('<div class="alert alert-danger">' + errorMessage + '</div>')
            }
        }).data('lifeFrancaSearchView');

        var template = $.templates(options.filterTpl);
        if (options.layers.length > 0){
            $.each(options.layers, function(){
                var filter = this;
                filter = $.extend({}, {
                    render: true,
                    type: 'null',                     
                    containerSelector: '#'+that.attr('id')+' ul[data-filter="'+filter.queryField+'"]',
                    cssClasses: options.cssClasses,
                    layerOptions: {}
                }, filter);
                
                if (filter.render)
                    that.find('.filters-wrapper').append($(template.render(filter)));
                
                var mapFilter = 
                searchView.addFilter(MapLayerFactory(
                    filter.label,
                    filter.queryField, 
                    filter.containerSelector,                     
                    filter.cssClasses,
                    currentMap,
                    currentBackground,
                    filter.layerOptions
                ));                
            });            
            that.find('.filters-wrapper').append($($.templates(options.closeXsFilterTpl).render({id: that.attr('id')})));
        }

        $.initLifeFrancaBlockEvent();        

        searchView.init(); //.doSearch();        
        $.each(searchView.filters, function(){
            var filter = this;              
            if (filter.name == 'bacinoprincipale.id'){
                $(filter.container).find('a.Linklist-link').first().trigger('click');
            }            
        });

        return this;
    };
})(jQuery, window, document);
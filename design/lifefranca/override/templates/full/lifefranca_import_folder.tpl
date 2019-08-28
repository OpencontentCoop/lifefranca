{set_defaults(hash(
  'page_limit', 50,
))}
<div class="openpa-full class-{$node.class_identifier}">

    <div class="title">
        <h2>{$node.name|wash()}</h2>
    </div>

    <div class="content-container">
        <div class="content">
            <div class="text-center u-margin-bottom-xl">
                <a class="button defaultbutton" href={concat("/ezmultiupload/upload/",$node.node_id)|ezurl} title="{'Multiupload'|i18n('extension/ezmultiupload')}">
                    Carica file {if $node.name|downcase()|contains('csv')}csv{elseif $node.name|downcase()|contains('json')}json{/if}
                </a>
            </div>

            {def $children_count = fetch( content, list_count, hash( 'parent_node_id', $node.node_id ) )}
            {if $children_count}
              <table class="list">
                <tr>
                    <th>Stato</th>
                    <th>Data</th>
                    <th>File</th>                    
                    <th>Errori</th>
                    <th></th>                    
                </tr>
                {foreach fetch( content, list, hash( 'parent_node_id', $node.node_id,
                                                        'offset', $view_parameters.offset,
                                                        'sort_by', array(published, false()),
                                                        'limit', $page_limit )) as $child }
                  <tr>
                    <td>{foreach $child.object.state_identifier_array as $state}{if $state|begins_with('csv_import')}{$state|explode('csv_import/')|implode('')|explode('_')|implode(' ')}{/if}{/foreach}</td>
                    <td>{$child.object.published|l10n(shortdatetime)}</td>
                    <td>
                        <a href={concat("content/download/",$child.data_map.file.contentobject_id,"/",$child.data_map.file.id,"/file/",$child.data_map.file.content.original_filename)|ezurl}>              
                            {$child.data_map.file.content.original_filename|wash( xhtml )}
                        </a>
                    </td>
                    <td style="font-size: .75em" class="errors"><div style="max-height:200px;overflow-y: auto;padding-right: 20px;">{attribute_view_gui attribute=$child.data_map.description}</div></td>  
                    <td style="white-space: nowrap;">{include uri="design:parts/toolbar/node_edit.tpl" current_node=$child}{include uri="design:parts/toolbar/node_trash.tpl" current_node=$child}</td>                  
                  </tr>
                {/foreach}
              </table>

              {include name=navigator
                       uri='design:navigator/google.tpl'
                       page_uri=$node.url_alias
                       item_count=$children_count
                       view_parameters=$view_parameters
                       item_limit=$page_limit}

            {/if}
           
        </div>
    </div>
</div>
<style type="text/css">td.errors p{ldelim}white-space: nowrap{rdelim}</style>

{include uri='design:parts/load_website_toolbar.tpl' current_user=fetch(user, current_user)}

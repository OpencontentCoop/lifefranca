{def $is_area_tematica = is_area_tematica()}
{def $footerBlocks = 5
     $has_notes = false()
     $has_contacts = false()
     $has_links  = false()
     $has_social  = false()
     $footerBlocksClass = 'u-sizeFull'}

{if and($is_area_tematica, $is_area_tematica|has_attribute('link'))}
    {def $footer_links = array()}
    {foreach $is_area_tematica|attribute('link').content.relation_list as $item}
        {set $footer_links = $footer_links|append(fetch(content, node, hash(node_id, $item.node_id)))}
    {/foreach}
{else}
    {def $footer_links = fetch( 'openpa', 'footer_links' )}
{/if}

{if and($is_area_tematica, $is_area_tematica|has_attribute('note_footer'))}
    {def $footer_notes = $is_area_tematica|attribute('note_footer')}
{else}
    {def $footer_notes = fetch( 'openpa', 'footer_notes' )}
{/if}

{if count( $footer_notes )|gt(0)}
    {set $has_notes = true()}
{else}
    {set $footerBlocks = $footerBlocks|sub(1)}
{/if}

{if and($is_area_tematica, $is_area_tematica|has_attribute('contacts'))}
    {def $contacts = parse_contacts_matrix($is_area_tematica)}
{else}
    {def $contacts = $pagedata.contacts}
{/if}

{if or(is_set($contacts.indirizzo), is_set($contacts.telefono), is_set($contacts.fax),
       is_set($contacts.email), is_set($contacts.pec), is_set($contacts.web))}
    {set $has_contacts = true()}
{else}
    {set $footerBlocks = $footerBlocks|sub(1)}
{/if}

{if or(is_set($contacts.facebook), is_set($contacts.twitter), is_set($contacts.linkedin), is_set($contacts.instagram))}
    {set $has_social = true()}
{else}
    {set $footerBlocks = $footerBlocks|sub(1)}
{/if}

{if count( $footer_links )|gt(0)}
    {set $has_links = true()}
{else}
    {set $footerBlocks = $footerBlocks|sub(1)}
{/if}

{if $footerBlocks|gt(1)}
    {set $footerBlocksClass = concat('u-md-size1of', $footerBlocks, ' u-lg-size1of', $footerBlocks) }
{/if}

<div class="footer-container u-hiddenPrint">
  <div class="u-layout-wide u-layoutCenter u-layout-r-withGutter">
    <footer class="Footer u-hiddenPrint">

        <div class="Grid Grid--withGutter">

            <div class="Footer-block Grid-cell {$footerBlocksClass}">
                <img src="{'images/logo_pat_footer.png'|ezdesign(no)}" alt="Provincia autonoma di Trento" style="width: 120px;" /> 
            </div>

            {if $has_notes}
                <div class="Footer-block Grid-cell {$footerBlocksClass}">
                    <h2 class="Footer-blockTitle">{ezini('SiteSettings','SiteName')}</h2>
                    <div class="u-lineHeight-xl u-color-white">
                        {attribute_view_gui attribute=$footer_notes}
                    </div>
                </div>
            {/if}

            {if $has_contacts}
                <div class="Footer-block Grid-cell {$footerBlocksClass}">
                    <h2 class="Footer-blockTitle">{'Contacts'|i18n('openpa/footer')}</h2>
                    {include uri='design:footer/contacts_list.tpl' contacts=$contacts}
                </div>
            {/if}

            {if $has_links}
                <div class="Footer-block Grid-cell {$footerBlocksClass}">
                    <h2 class="Footer-blockTitle">{'Links'|i18n('openpa/footer')}</h2>
                    <ul>
                        {foreach $footer_links as $item}
                            {def $href = $item.url_alias|ezurl(no)}
                            {if eq( $ui_context, 'browse' )}
                                {set $href = concat("content/browse/", $item.node_id)|ezurl(no)}
                            {elseif $pagedata.is_edit}
                                {set $href = '#'}
                            {elseif and( is_set( $item.data_map.location ), $item.data_map.location.has_content )}
                                {set $href = $item.data_map.location.content}
                            {/if}
                            <li><a href="{$href}"
                                   title="Leggi {$item.name|wash()}">{$item.name|wash()}</a>
                            </li>
                            {undef $href}
                        {/foreach}
                    </ul>
                </div>
            {/if}

            {if $has_social}
                <div class="Footer-block Grid-cell {$footerBlocksClass}">
                    <h2 class="Footer-blockTitle">{'Follow us'|i18n('openpa/footer')}</h2>
                    {include uri='design:footer/social.tpl'}
                </div>
            {/if}

        </div>

        <div class="Footer-links u-cf"></div>

        <div class="Grid Grid--withGutter">
            <div class="Footer-block Grid-cell u-md-size3of5 u-lg-size3of5">
                {include uri='design:footer/copyright.tpl'}
            </div>
            <div class="Footer-block Grid-cell u-md-size2of5 u-lg-size2of5 text-right">
                {if $pagedata.is_login_page|not()}
                    {include uri='design:footer/user_access.tpl'}
                {/if}
            </div>
        </div>
    </footer>
  </div>
</div>

<a href="#" title="torna all'inizio del contenuto" class="ScrollTop js-scrollTop js-scrollTo">
    <i class="ScrollTop-icon Icon-collapse" aria-hidden="true"></i>
    <span class="u-hiddenVisually">{"back to top"|i18n('openpa/footer')}</span>
</a>
<style type="text/css">
    .Grid--withGutter > .Footer-block{ldelim}padding: 0 1.8rem;{rdelim}
    .Footer-block h2{ldelim}font-size: 1.8rem !important;padding-bottom: 1.5rem !important;border-bottom: 1px solid #666;margin-bottom: 1.1rem !important;{rdelim}
    .Footer-block .u-lineHeight-xl{ldelim}font-size:1.4rem !important;{rdelim}
    .Footer-block li, .Footer-links, .Footer-subBlock{ldelim}border:none !important;{rdelim}
    .Footer-block li i{ldelim}display: table-cell;vertical-align: top;padding-right: 10px;{rdelim}
    .Footer-block li a{ldelim}display: table-cell;vertical-align: top;{rdelim}
</style>
{undef}

<script>
{literal}
function bindEvent(element, eventName, eventHandler) {
    if (element.addEventListener) {
        element.addEventListener(eventName, eventHandler, false);
    } else if (element.attachEvent) {
        element.attachEvent('on' + eventName, eventHandler);
    }
}
function disableRightClick(){
    document.addEventListener("contextmenu", function (e) {
        e.preventDefault();
    }, false);
}
function disableMailto(){
    jQuery('a[href^="mailto:"]').on('click', function(e){
        e.preventDefault(); 
    });
    jQuery("a").click(function( e ) {
        e.preventDefault();
    });
    jQuery('a[href*="index.html"],[href*="lifefranca.eu"]').click(function( event ) {
        jQuery(this).unbind('click');
    });
}
function hideCookiesModal(){
    jQuery("#moove_gdpr_cookie_info_bar").css("display","none");
    jQuery("#cookieChoiceInfo").css("display","none");
}
// Listen to messages from parent window
bindEvent(window, 'message', function (e) {
    if(e.data=='iframecalling'){
        hideCookiesModal();
        disableRightClick();
        disableMailto();
    }
});
{/literal}
</script>

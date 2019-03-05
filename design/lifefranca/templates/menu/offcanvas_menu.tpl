{def $is_area_tematica = is_area_tematica()}
{if and($is_area_tematica, $is_area_tematica|has_attribute('link_al_menu_orizzontale'))}
    {def $top_menu_node_ids = array()}
    {foreach $is_area_tematica|attribute('link_al_menu_orizzontale').content.relation_list as $item}
        {set $top_menu_node_ids = $top_menu_node_ids|append($item.node_id)}
    {/foreach}
{else}
    {def $top_menu_node_ids = openpaini( 'TopMenu', 'NodiCustomMenu', array() )}
{/if}
{def $top_menu_node_ids_count = $top_menu_node_ids|count()}

{def $main_styles = openpaini( 'Stili', 'Nodo_NomeStile', array() )}
{def $item_class = "no-main-style"}

<section class="Offcanvas Offcanvas--right Offcanvas--modal js-fr-offcanvas u-jsVisibilityHidden u-nojsDisplayNone u-hiddenPrint" id="menu">
    <h2 class="u-hiddenVisually">Menu di navigazione</h2>
    <div class="Offcanvas-content u-background-white">
        <div class="Offcanvas-toggleContainer u-background-70 u-jsHidden">
            <a class="Hamburger-toggleContainer u-block u-color-white u-padding-bottom-xxl u-padding-left-s u-padding-top-xxl js-fr-offcanvas-close"
               aria-controls="menu" aria-label="esci dalla navigazione" title="esci dalla navigazione" href="#">
                <span class="Hamburger-toggle is-active" aria-hidden="true"></span>
            </a>
        </div>
        <nav>
            <ul class="Linklist Linklist--padded Treeview Treeview--default js-Treeview u-text-r-xs">

                {if $top_menu_node_ids_count}
                    {foreach $top_menu_node_ids as $id}
                        {def $tree_menu = tree_menu( hash( 'root_node_id', $id, 'scope', 'top_menu'))}

                        <li>
                            {include recursion=0
                                     name=top_menu
                                     uri='design:menu/offcanvas_menu_item.tpl'
                                     menu_item=$tree_menu}
                            {if and(is_set($tree_menu.has_children), $tree_menu.has_children)}
                                {if $tree_menu.max_recursion|eq(1)}
                                    <ul>
                                        {foreach $tree_menu.children as $child}
                                            <li>
                                                {include recursion=1
                                                         name="top_sub_menu" uri='design:menu/offcanvas_menu_item.tpl'
                                                         menu_item=$child}
                                            </li>
                                        {/foreach}
                                    </ul>
                                {else}
                                    <ul>
                                        {foreach $tree_menu.children as $child}
                                            <li>
                                                {include recursion=2
                                                         name="top_sub_menu"
                                                         uri='design:menu/offcanvas_menu_item.tpl'
                                                         menu_item=$child}
                                                {if $child.has_children}
                                                    <ul>
                                                        {foreach $child.children as $sub_child}
                                                            <li>{include recursion=3
                                                                         name="top_sub_menu"
                                                                         uri='design:menu/offcanvas_menu_item.tpl'
                                                                         menu_item=$sub_child}
                                                            </li>
                                                        {/foreach}
                                                    </ul>
                                                {/if}
                                            </li>
                                        {/foreach}
                                    </ul>
                                {/if}
                            {/if}
                        </li>
                        {undef $tree_menu}
                    {/foreach}                    
                {/if}
                
                <li>
                    <a data-megamenu-class="Button u-border-none u-color-95 u-background-compl u-text-r-xxs" href="{'/Fai-una-segnalazione'|ezurl(no)}" class="Button u-border-none u-color-95 u-background-compl u-text-r-xxs is-focus">Fai una segnalazione</a>
                </li>                

            </ul>

        </nav>
    </div>
</section>
{undef $is_area_tematica}
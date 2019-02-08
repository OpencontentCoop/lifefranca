{if and( $pagedata.is_login_page|not(), array( 'edit', 'browse' )|contains( $ui_context )|not(), openpaini( 'TopMenu', 'ShowMegaMenu', 'enabled' )|eq('enabled') )}

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


  <!-- Header-navbar -->
  <div class="Headroom-hideme u-textCenter u-hidden u-sm-hidden u-md-block u-lg-block">
    {*<nav class="Megamenu Megamenu--styled js-megamenu" data-rel=".Offcanvas .Treeview"></nav>*}

    <nav class="Megamenu Megamenu--default js-megamenu">
      <ul class="Megamenu-list">
        {if $top_menu_node_ids_count}
          {foreach $top_menu_node_ids as $id}
            {def $tree_menu = tree_menu( hash( 'root_node_id', $id, 'scope', 'top_menu'))}
            <li class="Megamenu-item">
              {include recursion=0
              name=top_menu
              uri='design:menu/header_menu_item.tpl'
              has_link=cond( $tree_menu.has_children, false(), true())
              menu_item=$tree_menu}
              {if $tree_menu.has_children}
                {if $tree_menu.max_recursion|eq(1)}
                  <div class="Megamenu-subnav u-jsDisplayNone">
                    {foreach $tree_menu.children as $child}
                      <ul class="Megamenu-subnavGroup">
                        <li>
                          {include recursion=1
                          name="top_sub_menu"
                          uri='design:menu/header_menu_item.tpl'
                          menu_item=$child}
                        </li>
                      </ul>
                    {/foreach}
                  </div>
                {else}
                  <div class="Megamenu-subnav u-jsDisplayNone">
                    {foreach $tree_menu.children as $child}
                      <ul class="Megamenu-subnavGroup">
                        <li>
                          {include
                          recursion=2
                          name="top_sub_menu"
                          uri='design:menu/header_menu_item.tpl'
                          menu_item=$child}
                          <ul>
                            {if $child.has_children}
                              {foreach $child.children as $sub_child}
                                <li>{include recursion=3
                                  name="top_sub_menu"
                                  uri='design:menu/header_menu_item.tpl'
                                  menu_item=$sub_child}
                                </li>
                              {/foreach}
                              <li>
                                {include
                                recursion=2
                                name="top_sub_menu"
                                uri='design:menu/header_leggi_tutto_item.tpl'
                                menu_item=$child}
                              </li>
                            {/if}
                          </ul>
                        </li>
                      </ul>
                    {/foreach}
                  </div>
                {/if}
              {/if}
            </li>
            {undef $tree_menu}
          {/foreach}
        {/if}
          <li data-megamenu-class="Megamenu-area" class="Megamenu-area">
            <a data-megamenu-class="Button u-border-none u-color-95 u-background-compl u-text-r-xxs" href="{'/Fai-una-segnalazione'|ezurl(no)}" class="Button u-border-none u-color-95 u-background-compl u-text-r-xxs is-focus">
              Fai una segnalazione
            </a>
          </li>
      </ul>
    </nav>
  </div>
  {undef $is_area_tematica}
{/if}


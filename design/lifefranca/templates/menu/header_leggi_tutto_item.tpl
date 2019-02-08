{set_defaults( hash(
'has_link', true()
))}

{if $has_link}
  {if $menu_item.item.internal}
    {def $href = $menu_item.item.url|ezurl(no)}
  {else}
    {def $href = $menu_item.item.url}
  {/if}
{else}
  {def $href = '#'}
{/if}

<a data-node="{$menu_item.item.node_id}" href="{$href}" {if $menu_item.item.target}target="{$menu_item.item.target}"{/if} title="Vai a {$menu_item.item.name|wash()}">
  <i class="fa fa-caret-right" aria-hidden="true"></i> Leggi tutto...
</a>

{undef $href}

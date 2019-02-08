{run-once}
{literal}
<style>
.img_tooltip {
    display: none;
    position: absolute;
    border: 1px solid #333;
    background-color: #161616;
    border-radius: 5px;
    padding: 10px;
    color: #fff;
    font-size: 12px;
}
</style>
<script>
if (typeof jQuery == 'function'){
    $(document).ready(function(){
        $('img').each(function(){
            var author = $(this).data('author');
            var license = $(this).data('license');
            if (license && license.trim() && author && author.trim()){
                $(this).hover(function () {
                    var tooltip = author + '<br /><small>' + license + '</small>';
                    $(this).data('img_tooltip', tooltip);
                    $('<p class="img_tooltip"></p>').html(tooltip).appendTo('body').fadeIn('slow');
                }, function () {                    
                    $('.img_tooltip').remove();
                }).mousemove(function (e) {
                    var mousex = e.pageX + 20;
                    var mousey = e.pageY + 10;
                    $('.img_tooltip').css({
                     top: mousey,
                     left: mousex
                    })
                });
            }
        });
    });    
}
</script>
{/literal}
{/run-once}
{default image_class=large
         css_class=false()
         image_css_class=false()
         alignment=false()
         link_to_image=false()
         href=false()
         target=false()
         hspace=false()
         border_size=0
         border_color=''
         border_style=''
         margin_size=''
         alt_text=''
         fluid=true()
         title=''
         role = 'img'}

{let image_content = $attribute.content}

{if $image_content.is_valid}

    {def $author = false()}
    {if and($attribute.object|has_attribute('author'), $attribute.object|attribute('author').data_type_string|eq('ezstring'))}
        {set $author = $attribute.object|attribute('author').content|trim()}
    {/if}

    {def $license = false()}
    {if and($attribute.object|has_attribute('license'), $attribute.object|attribute('license').data_type_string|eq('eztags'))}
        {set $license = $attribute.object|attribute('license').content.keyword_string|trim()}
    {/if}

    {let image = $image_content[$image_class]
         inline_style = ''
		 image_css_classes = array()}

	{if $fluid}
	  {set $image_css_classes = $image_css_classes|append("img-responsive")}
	{/if}
	
	{if $image_css_class}
	  {set $image_css_classes = $image_css_classes|merge($image_css_class|explode(" "))}
	{/if}
    
	{if $link_to_image}
        {set href = $image_content['original'].url|ezroot}
    {/if}
    {switch match=$alignment}
    {case match='left'}
        <div class="pull-left">
    {/case}
    {case match='right'}
        <div class="pull-right">
    {/case}
	{case match='center'}
        {set $image_css_classes = $image_css_classes|append("center-block")}
    {/case}
    {case/}
    {/switch}

    {if $css_class}
        <div class="{$css_class|wash}">
    {/if}

    {if and( is_set( $image ), $image )}
        {if $alt_text|not}
            {if $image.text}
                {set $alt_text = $image.text}
            {else}
                {*set $alt_text = $attribute.object.name*}
                {set $alt_text = ""}
                {set $role = "presentation"}
            {/if}
        {/if}
        {if $title|not}
            {set $title = $alt_text}
        {/if}
        {if $border_size|trim|ne('')}
            {set $inline_style = concat( $inline_style, 'border: ', $border_size, 'px ', $border_style, ' ', $border_color, ';' )}
        {/if}
        {if $margin_size|trim|ne('')}
            {set $inline_style = concat( $inline_style, 'margin: ', $margin_size, 'px;' )}
        {/if}
        {if $href}<a title="{$title|wash(xhtml)}" href={$href}{if and( is_set( $link_class ), $link_class )} class="{$link_class}"{/if}{if and( is_set( $link_id ), $link_id )} id="{$link_id}"{/if}{if $target} target="{$target}"{/if}>{/if}
        <img src={$image.url|ezroot} 
             {if $image_css_classes|count()|gt(0)}class="{$image_css_classes|implode(" ")}"{/if} 
             {if and(is_set($inline_style), ne($inline_style, ''))}{concat('style="', $inline_style, '"')}{/if} 
             {if $hspace}hspace="{$hspace}"{/if} 
             alt="{$alt_text|wash(xhtml)}" 
             title="{$title|wash(xhtml)}" 
             {if $author}data-author="{$author|wash()}"{/if}
             {if $license}data-license="{$license|wash()}"{/if}
             role="{$role}" />
        {if $href}</a>{/if}
    {/if}

    {if $css_class}
        </div>
    {/if}

    {switch match=$alignment}
    {case match='left'}
        </div>
    {/case}
    {case match='right'}
        </div>
    {/case}
    {case/}
    {/switch}

    {/let}

{/if}

{/let}

{/default}

<div class="Grid u-background-50 u-margin-bottom-m u-padding-all-xl">
  <div class="Grid-cell u-sizeFull">
    <div class="u-padding-all-xl  u-layoutCenter u-textCenter u-layout-prose" style="max-width: 45em!important">
      <h3 class="u-text-h2 u-color-white">{if $block.name|ne('')}{$block.name|wash()}{else}La mia casa è in pericolo?{/if}</strong></h3>
      
      <form class="Form" action="{'lifefranca/webgis'|ezurl(no)}" method="post">
        <div class="Form-field Form-field--withPlaceholder Grid u-background-white u-color-grey-30 u-borderRadius-s u-borderShadow-m">          
          <input class="Form-input Form-input--ultraLean Grid-cell u-sizeFill u-text-r-s u-color-black u-text-r-xs u-borderHideFocus" required="required" id="webgis_address" name="address" type="text">
          <label class="Form-label u-color-grey-40" for="webgis_address">
            <span class="u-hidden u-md-inline u-lg-inline">Inserisci il tuo indirizzo</span>
          </label>
          <button type="submit" name="CheckAddress" class="Grid-cell u-sizeFit u-background-teal-30 u-color-white u-textWeight-600 u-padding-r-left u-padding-r-right u-textUppercase u-borderRadius-s">
            Verifica indirizzo
          </button>
        </div>
      </form>

      <form class="Form" action="{'lifefranca/webgis'|ezurl(no)}" method="post">
        <div style="display:flex" class="u-margin-top-xl">
          <div class="Form-field Form-field--withPlaceholder Grid u-background-white u-color-grey-30 u-borderRadius-s u-borderShadow-m" style="width: 100%;">
            <input class="Form-input Form-input--ultraLean Grid-cell u-sizeFill u-text-r-s u-color-black u-text-r-xs u-borderHideFocus" required="required" id="webgis_coords_lat" name="lat" type="text">
            <label class="Form-label u-color-grey-40" for="webgis_coords_lat">
              <span class="u-hidden u-md-inline u-lg-inline">Latitudine</span>
            </label>
          </div>
          
          <div class="Form-field Form-field--withPlaceholder Grid u-background-white u-color-grey-30 u-borderRadius-s u-borderShadow-m" style="width: 100%;">
            <input class="Form-input Form-input--ultraLean Grid-cell u-sizeFill u-text-r-s u-color-black u-text-r-xs u-borderHideFocus" required="required" id="webgis_coords_lng" name="lng" type="text">          
            <label class="Form-label u-color-grey-40" for="webgis_coords_lng">
              <span class="u-hidden u-md-inline u-lg-inline">Longitudine</span>
            </label>          
          </div>
          
          <div class="Form-field Form-field--withPlaceholder Grid u-background-white u-color-grey-30 u-borderRadius-s u-borderShadow-m" style="width: 100%;">
            <button type="submit" name="CheckCoords" class="Grid-cell u-sizeFit u-background-teal-30 u-color-white u-textWeight-600 u-padding-r-left u-padding-r-right u-textUppercase u-borderRadius-s" style="width: 100%;">
                Verifica coordinate
            </button>
          </div>
        </div>
      </form>

    </div>
  </div>
</div>

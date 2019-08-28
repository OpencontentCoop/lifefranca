{ezscript_require( array( 'ezjsc::yui3', 'ezjsc::yui3io') )}
{ezcss_require( 'ezmultiupload.css' )}
<script>
    (function(config){ldelim}
        config['modules']['ezmultiupload'] = {ldelim}
            type: 'js',
            fullpath: '{"javascript/ezmultiupload.js"|ezdesign( 'no' )}',
            requires: ["uploader", "node", "event-base", "json-parse", "anim"],
            after: ["uploader"],
            skinnable: false
            {rdelim};

        YUI(config).use('ezmultiupload', function (Y) {ldelim}
            Y.ez.MultiUpload.cfg = {ldelim}
                uploadURL: "{concat( 'ezmultiupload/upload/', $parent_node.node_id )|ezurl( 'no' )}",
                uploadVars: {ldelim}
                    '{$session_name}': '{$session_id}',
                    //'XDEBUG_SESSION_START': 'XDEBUG_ECLIPSE',
                    'UploadButton': 'Upload',
                    'ezxform_token': '@$ezxFormToken@'
                    {rdelim},
                allFilesRecived:  "{'All files received.'|i18n('extension/ezmultiupload')|wash(javascript)}",
                uploadCanceled:   "{'Upload canceled.'|i18n('extension/ezmultiupload')|wash(javascript)}",
                thumbnailCreated: "{'Thumbnail created.'|i18n('extension/ezmultiupload')|wash(javascript)}",
                multipleFiles: true
                {rdelim};
            Y.ez.MultiUpload.init();
            {rdelim});
        {rdelim})(YUI3_config);
</script>

<div class="content-view-ezmultiupload Prose">
<h1>{'Multiupload'|i18n('extension/ezmultiupload')}</h1>
  <div class="attribute-description">
      <p class="Prose">{'The files are uploaded to'|i18n('extension/ezmultiupload')} <a href={$parent_node.url_alias|ezurl}>{$parent_node.name|wash}</a></p>
      <div id="uploadButtonOverlay" style="position: absolute; z-index: 2"></div>
      <button id="uploadButton" type="button" class="Button hide" style="z-index: 1">{'Select files'|i18n('extension/ezmultiupload')}</button>
      <button id="cancelUploadButton" type="button" class="Button" style="width: 100px;margin-left: 150px;margin-top: 0;">{'Cancel'|i18n('extension/ezmultiupload')}</button>
      <p class="Prose"><noscript><em style="color: red;">{'Javascript has been disabled, this is needed for multiupload!'|i18n('extension/ezmultiupload')}</em></noscript></p>
  </div>
  <div id="multiuploadProgress">
      <p><span id="multiuploadProgressFile">&nbsp;</span>&nbsp;
          <span id="multiuploadProgressFileName">&nbsp;</span></p>
      <p id="multiuploadProgressMessage">&nbsp;</p>
      <div id="multiuploadProgressBarOutline"><div id="multiuploadProgressBar"></div></div>
  </div>
  <div id="thumbnails"></div>
</div>





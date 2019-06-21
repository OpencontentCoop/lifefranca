<?php

/** @var eZModule $Module */
$module = $Params['Module'];
$http = eZHTTPTool::instance();

$url = false;

if ($http->hasPostVariable('CheckAddress') && $http->hasPostVariable('address')){

	$address = urlencode($http->postVariable('address'));
	$url = "https://wglifefranca.provincia.tn.it/lifefranca-2d/?address=$address&type=0";

}elseif ($http->hasPostVariable('CheckCoords') && $http->hasPostVariable('lat') && $http->hasPostVariable('lng')){

	$lat = $http->postVariable('lat');
	$lng = $http->postVariable('lng');
	$url = "https://wglifefranca.provincia.tn.it/lifefranca-2d/?coord=$lat;$lng&type=1";
}

if ($url){
	eZHTTPTool::headerVariable( 'Location', $url );
    /* Fix for redirecting using workflows and apache 2 */
    $escapedUrl = htmlspecialchars( $url );
    $content = <<<EOT
<HTML><HEAD>
<META HTTP-EQUIV="Refresh" Content="0;URL=$escapedUrl">
<META HTTP-EQUIV="Location" Content="$escapedUrl">
</HEAD><BODY></BODY></HTML>
EOT;
    echo $content;
    eZExecution::cleanExit();

}else{
	return $module->redirectTo('/');
}

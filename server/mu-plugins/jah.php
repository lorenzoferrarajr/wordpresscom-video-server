<?php

/** 
 * jah: Prints two simple ajax javascript functions
 * Description: 
 * This file contains functions used in video_embed()
 * 
 * Author:  Automattic Inc
 * Version: 0.9
 */
 
function jah($script_tags = false) {
	echo get_jah($script_tags);
}

function get_jah($script_tags = false) {
	$res = '';
	if ($script_tags)
		$res .= "<script type='text/javascript'>\n";
	$res .= <<<JS
if (typeof jah != 'function'){
function jah(url,target) {
	var rec = null;
    // native XMLHttpRequest object
    if (window.XMLHttpRequest) {
        req = new XMLHttpRequest();
        req.onreadystatechange = jahDoneFact(req, target);
        req.open("GET", url, true);
        req.send(null);
    // IE/Windows ActiveX version
    } else if (window.ActiveXObject) {
        req = new ActiveXObject("Microsoft.XMLHTTP");
        if (req) {
            req.onreadystatechange = function() {jahDone(req, target);};
            req.open("GET", url, true);
            req.send();
		}
	}
}
}

if (typeof jahDone != 'function') {
function jahDone(r, target) {
    // only if req is "loaded"
    if (r.readyState == 4) {
        // only if "OK"
        if (r.status == 200) {
            results = r.responseText;
            document.getElementById(target).innerHTML = results;
        }
	}
}
}

if (typeof jahDoneFact != 'function') {
function jahDoneFact(r, t) {
	return function() {jahDone(r, t);}
}
}
JS;
	if ($script_tags)
		$res .= "</script>\n";
	return $res;
}

?>

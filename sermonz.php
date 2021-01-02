<?php
/*
Plugin Name: Sermo.nz
Plugin URI: http://sermo.nz
description: Sermo.nz Library Plugin
Version: 1.1
Author: Andrew Drake
Author URI: http://andrew.drake.nz
License: GPL3
*/

//configure admin settings, config etc.
include('includes/admin.inc.php');
include('includes/scripts.inc.php');

//configure routing
include('includes/routes.inc.php');

//fetch the sermonz content 
include('includes/sermonz.controller.php');

//render the sermonz content
include('includes/shortcode.inc.php');
include('includes/content.inc.php');


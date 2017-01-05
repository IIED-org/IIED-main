<?php
/*------------------------------------------------------------------------------
This controller retrieves a search form used for setting Search Parameters 
(e.g. for the default value of a relation, image, or media field).

It expects the fieldname (without the cctm_ prefix), but it also needs to handle 
setting search parameters for a new field (which won't have a fieldname yet)
It also accepts the search_parameters (serialized data describing existing values)

@param	$_POST['fieldname']
@param	$_POST['fieldtype'] (optional)
@param	$_POST['search_parameters'] (optional)
------------------------------------------------------------------------------*/
if ( ! defined('CCTM_PATH')) exit('No direct script access allowed');
// See https://code.google.com/p/wordpress-summarize-posts/issues/detail?id=39
$post_type = CCTM::get_value($_POST, 'post_type', 'post');
$cap = 'edit_posts';
if (isset($GLOBALS['wp_post_types'][$post_type]->cap->edit_posts)) {
	$cap = $GLOBALS['wp_post_types'][$post_type]->cap->edit_posts; 
}
if (!current_user_can($cap)) die('<pre>You do not have permission to do that.</pre>');

$fieldname = CCTM::get_value($_POST, 'fieldname');
$fieldtype = CCTM::get_value($_POST, 'fieldtype');
$type = ''; // set once we know if we've got a fieldname or a fieldtype

if (empty($fieldname) && empty($fieldtype)) {
	print '<p>'.__('fieldname or fieldtype required.', CCTM_TXTDOMAIN) .'</p>';
	return;
}

$def = CCTM::get_value(CCTM::$data['custom_field_defs'], $fieldname);

if (empty($def)) {
	$type = $fieldtype;
}
else {
	$type = CCTM::get_value($def,'type');
}

$search_parameters_str = '';
if (isset($_POST['search_parameters'])) {
	$search_parameters_str = $_POST['search_parameters'];
}
//print '<pre>'.$search_parameters_str. '</pre>'; return;
$existing_values = array();
parse_str($search_parameters_str, $existing_values);

//print '<pre>'.print_r($existing_values, true) . '</pre>'; 
require_once(CCTM_PATH.'/includes/SummarizePosts.php');
require_once(CCTM_PATH.'/includes/GetPostsQuery.php');
require_once(CCTM_PATH.'/includes/GetPostsForm.php');

$Form = new GetPostsForm();

// What options should be displayed on the form that defines the search?  
// Load up the config...
$possible_configs = array();
$possible_configs[] = '/config/search_parameters/'.$fieldname.'.php'; 	// e.g. my_field.php
$possible_configs[] = '/config/search_parameters/_'.$type.'.php'; 		// e.g. _image.php
$possible_configs[] = '/config/search_parameters/_default.php';


if (!CCTM::load_file($possible_configs)) {
	print '<p>'.__('Search parameter configuration file not found.', CCTM_TXTDOMAIN) .'</p>';	
}

// TODO: put this into the tpls folder and a make a view for it.
// save
// cancel
// description
$Form->set_placeholder('description', __('This form will determine which posts will be selectable when users create or edit a post that uses this field.',  CCTM_TXTDOMAIN));
$Form->set_placeholder('save', __('Save', CCTM_TXTDOMAIN));
$Form->set_placeholder('cancel', __('Cancel', CCTM_TXTDOMAIN));
/*
$form_tpl = '
<style>
[+css+]
</style>
<p>'. __('This form will determine which posts will be selectable when users create or edit a post that uses this field.',  CCTM_TXTDOMAIN).' <a href="http://code.google.com/p/wordpress-custom-content-type-manager/wiki/SearchParameters"><img src="'.CCTM_URL .'/images/question-mark.gif" width="16" height="16" /></a></p>
<form id="search_parameters_form" class="[+form_name+]">
	[+content+]
	<span class="button" onclick="javascript:search_parameters_save(\'search_parameters_form\');">'. __('Save', CCTM_TXTDOMAIN).'</span>
	<span class="button" onclick="javascript:tb_remove();">'.__('Cancel', CCTM_TXTDOMAIN).'</span>
</form>
';
*/

$form_tpl = CCTM::load_tpl('post_selector/search_forms/_modal.tpl');
$Form->set_name_prefix('');
$Form->set_id_prefix('');

$Form->set_tpl($form_tpl);
print $Form->generate(CCTM::$search_by, $existing_values);

/*EOF*/
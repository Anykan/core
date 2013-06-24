<?php
/*
* Project:		EQdkp-Plus
* License:		Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
* Link:			http://creativecommons.org/licenses/by-nc-sa/3.0/
* -----------------------------------------------------------------------
* Began:		2009
* Date:			$Date: 2013-03-23 18:01:39 +0100 (Sa, 23 Mrz 2013) $
* -----------------------------------------------------------------------
* @author		$Author: godmod $
* @copyright	2006-2011 EQdkp-Plus Developer Team
* @link			http://eqdkp-plus.com
* @package		eqdkpplus
* @version		$Rev: 13242 $
*
* $Id: Manage_Articles.php 13242 2013-03-23 17:01:39Z godmod $
*/

define('EQDKP_INC', true);
define('IN_ADMIN', true);
$eqdkp_root_path = './../';
include_once($eqdkp_root_path . 'common.php');

class Manage_Articles extends page_generic {
	public static function __shortcuts() {
		$shortcuts = array('user', 'tpl', 'in', 'pdh', 'jquery', 'core', 'config', 'html', 'pfh');
		return array_merge(parent::$shortcuts, $shortcuts);
	}

	public function __construct(){
		$this->user->check_auth('a_articles_man');
		$handler = array(
			'save' 		=> array('process' => 'save', 'csrf' => true),
			'update'	=> array('process' => 'update', 'csrf' => true),
			'checkalias'=> array('process' => 'ajax_checkalias'),
			'del_votes' => array('process' => 'delete_votes', 'csrf' => true),
			'del_comments' => array('process' => 'delete_comments', 'csrf' => true),
			'change_category' => array('process' => 'change_category', 'csrf' => true),
			'set_published' => array('process' => 'set_published', 'csrf' => true),
			'set_unpublished' => array('process' => 'set_unpublished', 'csrf' => true),
			'delpreviewimage' => array('process' => 'delete_previewimage', 'csrf' => true),
			'a'			=> array('process' => 'edit'),
		);
		parent::__construct(false, $handler, array('articles', 'title'), null, 'selected_ids[]');
		$this->process();
	}
	
	public function delete_previewimage(){
		$id = $this->in->get('a', 0);
		if ($id) {
			$this->pdh->put('articles', 'delete_previewimage', array($id));
			$this->pdh->process_hook_queue();
		}
	}
	
	public function set_unpublished(){
		if(count($this->in->getArray('selected_ids', 'int')) > 0) {
			$this->pdh->put('articles', 'set_unpublished', array($this->in->getArray('selected_ids', 'int')));
			$this->pdh->process_hook_queue();
			$this->core->message($this->user->lang('pk_succ_saved'), $this->user->lang('success'), 'green');
		}
		
	}
	
	public function set_published(){
		if(count($this->in->getArray('selected_ids', 'int')) > 0) {
			$this->pdh->put('articles', 'set_published', array($this->in->getArray('selected_ids', 'int')));
			$this->pdh->process_hook_queue();
			$this->core->message($this->user->lang('pk_succ_saved'), $this->user->lang('success'), 'green');
		}
		
	}
	
	public function change_category(){
		if(count($this->in->getArray('selected_ids', 'int')) > 0) {
			$intCategory = $this->in->get('new_category',0);
			$this->pdh->put('articles', 'change_category', array($this->in->getArray('selected_ids', 'int'), $intCategory));
			$this->pdh->process_hook_queue();
			$this->core->message($this->user->lang('pk_succ_saved'), $this->user->lang('success'), 'green');
		}
	}
	
	public function delete_votes(){
		$id = $this->in->get('a', 0);
		if ($id) {
			$blnResult = $this->pdh->put('articles', 'reset_votes', array($id));
			if ($blnResult){
				$this->core->message(sprintf($this->user->lang('admin_reset_voting_success'), $this->pdh->get('articles', 'title', array($id))), $this->user->lang('success'), 'green');
				$this->pdh->process_hook_queue();
			}
		}
		$this->edit();
	}
	
	public function delete_comments(){
		$id = $this->in->get('a', 0);
		if ($id) {
			$this->pdh->put('comment', 'delete_attach_id', array('articles', $id));
			$this->pdh->process_hook_queue();
			$this->core->message(sprintf($this->user->lang('admin_delete_comments_success'), $this->pdh->get('articles', 'title', array($id))), $this->user->lang('success'), 'green');
		}
		$this->edit();
	}
	
	
	
	public function ajax_checkalias(){
		$strAlias = $this->in->get('alias');
		$intAID = $this->in->get('a', 0);
		
		$blnResult = $this->pdh->get('articles', 'check_alias', array($strAlias, true));
		if (!$blnResult && $this->pdh->get('articles', 'alias', array($intAID)) === $strAlias) $blnResult = true;
		if (is_numeric($strAlias)) $blnResult = false;
		
		header('content-type: text/html; charset=UTF-8');
		if ($blnResult){
			echo 'true';
		} else {
			echo 'false';
		}
		exit;
	}
		
	public function update(){
		$cid = $this->in->get('c', 0);
		$id = $this->in->get('a', 0);
		
		$strTitle = $this->in->get('title');
		$strText = $this->in->get('text', '', 'raw');
		$strTags = $this->in->get('tags');
		$strPreviewimage = $this->in->get('previewimage');
		if ($strPreviewimage != "") $strPreviewimage = str_replace(register('pfh')->FileLink('', 'files', 'absolute'), '', $strPreviewimage);
		$strAlias = $this->in->get('alias');
		$intPublished = $this->in->get('published', 0);
		$intFeatured = $this->in->get('featured', 0);
		$intCategory = $this->in->get('category', 0);
		$intUserID = $this->in->get('user_id', 0);
		$intComments = $this->in->get('comments', 0);
		$intVotes = $this->in->get('votes', 0);
		$intHideHeader = $this->in->get('hide_header', 0);
		
		$schluesselwoerter = preg_split("/[\s,]+/", $strTags);
		$arrTags = array();
		foreach($schluesselwoerter as $val){
			$arrTags[] = utf8_strtolower($val);
		}
		
		$intDate = $this->time->fromformat($this->in->get('date'), 1);
		$strShowFrom = $strShowTo = "";
		if($this->in->exists('show_from') AND strlen($this->in->get('show_from')) AND $this->in->get('show_from') != $this->user->lang('never')) $strShowFrom = $this->time->fromformat($this->in->get('show_from'), 1);
		if($this->in->exists('show_to') AND strlen($this->in->get('show_to')) AND $this->in->get('show_to') != $this->user->lang('never')) $strShowTo = $this->time->fromformat($this->in->get('show_to'), 1);
		
		
		if ($strTitle == "" ) {
			$this->core->message('', '', 'red');
			$this->edit();
			return;
		}
		
		if ($id){
			$blnResult = $this->pdh->put('articles', 'update', array($id, $strTitle, $strText, $arrTags, $strPreviewimage, $strAlias, $intPublished, $intFeatured, $intCategory, $intUserID, $intComments, $intVotes,$intDate, $strShowFrom, $strShowTo, $intHideHeader));
		} else {
			$blnResult = $this->pdh->put('articles', 'add', array($strTitle, $strText, $arrTags, $strPreviewimage, $strAlias, $intPublished, $intFeatured, $intCategory, $intUserID, $intComments, $intVotes,$intDate, $strShowFrom, $strShowTo, $intHideHeader));
		}
		
		if ($blnResult){
			$this->pdh->process_hook_queue();
			$this->core->message($this->user->lang('success_create_article'), $this->user->lang('success'), 'green');
		} else {
			$this->core->message($this->user->lang('error_create_article'), $this->user->lang('error'), 'red');
		}
		
		$this->display();
	}
	
	public function save(){		
		$arrPublished = $this->in->getArray('published', 'int');
		$arrFeatured = $this->in->getArray('featured', 'int');
		foreach($arrPublished as $key => $val){
			$this->pdh->put('articles', 'update_featuredandpublished', array($key, $arrFeatured[$key], $val));
		}
		$this->core->message($this->user->lang('pk_succ_saved'), $this->user->lang('success'), 'green');
		$this->pdh->process_hook_queue();
	}
	
	public function delete(){
		$retu = array();

		if(count($this->in->getArray('selected_ids', 'int')) > 0) {
			foreach($this->in->getArray('selected_ids','int') as $id) {
				
				$pos[] = stripslashes($this->pdh->get('articles', 'name', array($id)));
				$retu[$id] = $this->pdh->put('articles', 'delete', array($id));
			}
		}

		if(!empty($pos)) {
			$messages[] = array('title' => $this->user->lang('del_suc'), 'text' => implode(', ', $pos), 'color' => 'green');
			$this->core->messages($messages);
		}
		
		$this->pdh->process_hook_queue();
	}
	
	public function edit(){
		$id = $this->in->get('a', 0);
		$cid = $this->in->get('c', 0);
		
		$this->jquery->Tab_header('article_category-tabs');

		$editor = register('tinyMCE');
		$editor->editor_normal(array(
			'relative_urls'	=> false,
			'link_list'		=> true,
			'pageobjects'	=> true,
			'gallery'		=> true,
			'raidloot'		=> true,
		));
		
		$arrCategoryIDs = $this->pdh->sort($this->pdh->get('article_categories', 'id_list', array()), 'articles', 'sort_id', 'asc');
		$arrCategories = array();
		foreach($arrCategoryIDs as $caid){
			$arrCategories[$caid] = $this->pdh->get('article_categories', 'name_prefix', array($caid)).$this->pdh->get('article_categories', 'name', array($caid));
		}
		
		
		if ($id){
			$this->tpl->assign_vars(array(
				'TITLE'	=> $this->pdh->get('articles', 'title', array($id)),
				'TEXT'	=> $this->pdh->get('articles', 'text', array($id)),
				'ALIAS'	=> $this->pdh->get('articles', 'alias', array($id)),
				'TAGS'	=> implode(', ', $this->pdh->get('articles', 'tags', array($id))),
				'DD_CATEGORY' => $this->html->Dropdown('category', $arrCategories, $this->pdh->get('articles', 'category', array($id))),
				'PUBLISHED_CHECKED' => ($this->pdh->get('articles', 'published', array($id))) ? 'checked="checked"' : '',
				'FEATURED_CHECKED' => ($this->pdh->get('articles', 'featured', array($id))) ? 'checked="checked"' : '',
				'COMMENTS_CHECKED' => ($this->pdh->get('articles', 'comments', array($id))) ? 'checked="checked"' : '',
				'VOTES_CHECKED' 	=> ($this->pdh->get('articles', 'votes', array($id))) ? 'checked="checked"' : '',
				'HIDE_HEADER_CHECKED' => ($this->pdh->get('articles', 'hide_header', array($id))) ? 'checked="checked"' : '',
				'DD_USER' 			=> $this->html->Dropdown('user_id',  $this->pdh->aget('user', 'name', 0, array($this->pdh->get('user', 'id_list'))), $this->pdh->get('articles', 'user_id', array($id))),
				'DATE_PICKER'		=> $this->jquery->Calendar('date', $this->time->user_date($this->pdh->get('articles', 'date', array($id)), true, false, false, function_exists('date_create_from_format')), '', array('timepicker' => true)),
				'DATE_TO_PICKER'	=> $this->jquery->Calendar('show_to', $this->time->user_date(((strlen($this->pdh->get('articles', 'show_to', array($id)))) ? $this->pdh->get('articles', 'show_to', array($id)) : 0), true, false, false, function_exists('date_create_from_format')), '', array('timepicker' => true)),
				'DATE_FROM_PICKER'	=> $this->jquery->Calendar('show_from', $this->time->user_date(((strlen($this->pdh->get('articles', 'show_from', array($id)))) ? $this->pdh->get('articles', 'show_from', array($id)) : 0), true, false, false, function_exists('date_create_from_format')), '', array('timepicker' => true)),
				'PREVIEW_IMAGE'		=> $this->html->widget(array(
						'fieldtype'	=> 'imageuploader',
						'name'		=> 'previewimage',
						'imgpath'	=> $this->pfh->FolderPath('','files'),
						'value'		=> $this->pdh->get('articles', 'previewimage', array($id)),
						'options'	=> array(
							'noimgfile'	=> "images/global/brokenimg.png",
							'deletelink'=> 'manage_articles.php'.$this->SID.'&a='.$id.'&c='.$cid.'&delpreviewimage=true&link_hash='.$this->CSRFGetToken('delpreviewimage'),
						),
					)),
			));
			
		} else {
			
			$this->tpl->assign_vars(array(
				'DD_CATEGORY' => $this->html->Dropdown('category', $arrCategories, $cid),
				'PUBLISHED_CHECKED'=> 'checked="checked"',
				'COMMENTS_CHECKED' => 'checked="checked"',
				'DD_USER' 		   => $this->html->Dropdown('user_id',  $this->pdh->aget('user', 'name', 0, array($this->pdh->get('user', 'id_list'))), $this->user->id),
				'DATE_PICKER'		=> $this->jquery->Calendar('date', $this->time->user_date($this->time->time, true, false, false, function_exists('date_create_from_format')), '', array('timepicker' => true)),
				'DATE_TO_PICKER'	=> $this->jquery->Calendar('show_to', $this->time->user_date(0, true, false, false, function_exists('date_create_from_format')), '', array('timepicker' => true)),
				'DATE_FROM_PICKER'	=> $this->jquery->Calendar('show_from', $this->time->user_date(0, true, false, false, function_exists('date_create_from_format')), '', array('timepicker' => true)),
				'PREVIEW_IMAGE'		=> $this->html->widget(array(
						'fieldtype'	=> 'imageuploader',
						'name'		=> 'previewimage',
						'imgpath'	=> $this->pfh->FolderPath('logo','eqdkp'),
						'options'	=> array(
							'noimgfile'	=> "images/global/brokenimg.png"
						),
					)),
			));
		}
		
		$routing = register('routing');
		$arrPageObjects = $routing->getPageObjects();
		
		$this->tpl->add_js(
			'var pageobjects = '.json_encode($arrPageObjects).';'
		);

		$this->tpl->assign_vars(array(
			'CID' => $cid,
			'AID' => $id,
			'CATEGORY_NAME' => $this->pdh->get('article_categories', 'name', array($cid)),
			'ARTICLE_NAME' => $this->pdh->get('articles', 'title', array($id)),
		));
		$this->core->set_vars(array(
			'page_title'		=> (($id) ? $this->user->lang('manage_articles').': '.$this->pdh->get('articles', 'title', array($id)) : $this->user->lang('add_new_article')),
			'template_file'		=> 'admin/manage_articles_edit.html',
			'display'			=> true)
		);
	}

	// ---------------------------------------------------------
	// Display form
	// ---------------------------------------------------------
	public function display() {
		$cid = $this->in->get('c', 0);
		
		$view_list = $this->pdh->get('articles', 'id_list', array($cid));

		$hptt_page_settings = $this->pdh->get_page_settings('admin_manage_articles', 'hptt_admin_manage_articles_list');

		$hptt = $this->get_hptt($hptt_page_settings, $view_list, $view_list, array('%link_url%' => 'manage_raids.php', '%link_url_suffix%' => '&amp;upd=true'));
		$page_suffix = '&amp;start='.$this->in->get('start', 0);
		$sort_suffix = '?sort='.$this->in->get('sort');

		//footer
		$raid_count = count($view_list);
		$footer_text = sprintf($this->user->lang('article_footcount'), $raid_count ,$this->user->data['user_nlimit']);
		
		$arrCategoryIDs = $this->pdh->sort($this->pdh->get('article_categories', 'id_list', array()), 'article_categories', 'sort_id', 'asc');
		$arrCategories = array();
		foreach($arrCategoryIDs as $cid2){
			if ($cid == $cid2) continue;
			$arrCategories[$cid2] = $this->pdh->get('article_categories', 'name_prefix', array($cid2)).$this->pdh->get('article_categories', 'name', array($cid2));
		}
		
		$arrMenuItems = array(
			0 => array(
				'name'	=> $this->user->lang('delete'),
				'type'	=> 'button', //link, button, javascript
				'img'	=> 'images/global/delete.png',
				'perm'	=> true,
				'link'	=> '#del_articles',
			),
			
			1 => array(
				'name'	=> $this->user->lang('mass_stat_change').': '.$this->user->lang('published'),
				'type'	=> 'button', //link, button, javascript
				'img'	=> 'images/glyphs/eye.png',
				'perm'	=> true,
				'link'	=> '#set_published',
			),
			2 => array(
				'name'	=> $this->user->lang('mass_stat_change').': '.$this->user->lang('not_published'),
				'type'	=> 'button', //link, button, javascript
				'img'	=> 'images/glyphs/eye_gray.png',
				'perm'	=> true,
				'link'	=> '#set_unpublished',
			),
			3 => array(
				'name'	=> $this->user->lang('move_to_other_category').':',
				'type'	=> 'button', //link, button, javascript
				'img'	=> 'images/global/update.png',
				'perm'	=> true,
				'link'	=> '#change_category',
				'append' => $this->html->DropDown('new_category', $arrCategories, ''),
			),
		
		);
				
		$this->confirm_delete($this->user->lang('confirm_delete_articles'));

		$this->tpl->assign_vars(array(
			'ARTICLE_LIST' => $hptt->get_html_table($this->in->get('sort'), $page_suffix, $this->in->get('start', 0), $this->user->data['user_nlimit'], $footer_text),
			'PAGINATION' => generate_pagination('manage_raids.php'.$sort_suffix, $raid_count, $this->user->data['user_rlimit'], $this->in->get('start', 0)),
			'HPTT_COLUMN_COUNT'	=> $hptt->get_column_count(),
			'CATEGORY_NAME' => $this->pdh->get('article_categories', 'name', array($cid)),
			'CID'			=> $cid,
			'BUTTON_MENU'		=> $this->jquery->ButtonDropDownMenu('manage_members_menu', $arrMenuItems, array("input[name=\"selected_ids[]\"]"), '', $this->user->lang('selected_articles').'...', ''),
		
		));

		$this->core->set_vars(array(
			'page_title'		=> $this->user->lang('manage_articles'),
			'template_file'		=> 'admin/manage_articles.html',
			'display'			=> true)
		);
	}
	
}
registry::register('Manage_Articles');
?>
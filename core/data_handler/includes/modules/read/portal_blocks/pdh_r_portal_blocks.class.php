<?php
/*
* Project:		EQdkp-Plus
* License:		Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
* Link:			http://creativecommons.org/licenses/by-nc-sa/3.0/
* -----------------------------------------------------------------------
* Began:		2010
* Date:			$Date: 2013-01-29 17:35:08 +0100 (Di, 29 Jan 2013) $
* -----------------------------------------------------------------------
* @author		$Author: wallenium $
* @copyright	2006-2011 EQdkp-Plus Developer Team
* @link			http://eqdkp-plus.com
* @package		eqdkpplus
* @version		$Rev: 12937 $
*
* $Id: pdh_r_portal_blocks.class.php 12937 2013-01-29 16:35:08Z wallenium $
*/

if ( !defined('EQDKP_INC') ){
	die('Do not access this file directly.');
}

if ( !class_exists( "pdh_r_portal_blocks" ) ) {
	class pdh_r_portal_blocks extends pdh_r_generic{
		public static function __shortcuts() {
		$shortcuts = array('pdc', 'db', 'user', 'pdh');
		return array_merge(parent::$shortcuts, $shortcuts);
	}

		public $default_lang = 'english';
		public $blocks;

		public $hooks = array(
			'portal_blocks_update'
		);

		public function reset(){
			$this->pdc->del('pdh_portal_blocks_table');
			$this->blocks = NULL;
		}
		
		public $presets = array(
				'portal_block_name' 		=> array('name', array('%block_id%'), array()),
				'portal_block_wide_content'	=> array('wide_content', array('%block_id%'), array()),
				'portal_block_usedby'		=>  array('usedby', array('%block_id%'), array()),
				'portal_block_editicon' 	=> array('editicon', array('%block_id%'), array()),
		);

		public function init(){
			$this->blocks	= $this->pdc->get('pdh_portal_blocks_table');
			if($this->blocks !== NULL){
				return true;
			}

			$pff_result = $this->db->query("SELECT * FROM __portal_blocks");
			while($drow = $this->db->fetch_record($pff_result) ){
				$this->blocks[intval($drow['id'])] = array(
					'id'				=> intval($drow['id']),
					'name'				=> $drow['name'],
					'wide_content'		=> intval($drow['wide_content']),
				);
			}
			
			$this->db->free_result($pff_result);
			$this->pdc->put('pdh_portal_blocks_table', $this->blocks, null);
		}

		public function get_id_list() {
			return array_keys($this->blocks);
		}
		
		public function get_name($intBlockID){
			if (isset($this->blocks[$intBlockID])){
				return $this->blocks[$intBlockID]['name'];
			}
			return false;
		}
		
		public function get_wide_content($intBlockID){
			if (isset($this->blocks[$intBlockID])){
				return $this->blocks[$intBlockID]['wide_content'];
			}
			return false;
		}
		
		public function get_html_wide_content($intBlockID){
			if ($this->get_wide_content($intBlockID)){
				return $this->user->lang('yes');
			}
			return $this->user->lang('no');
		}
		
		public function get_usedby($intBlockID){
			$arrLayoutIDs = $this->pdh->get('portal_layouts', 'id_list');
			$intLayoutCount = 0;
			foreach($arrLayoutIDs as $intLayoutID){
				$arrBlocks = $this->pdh->get('portal_layouts', 'blocks', array($intLayoutID));
				if (in_array('block'.$intBlockID, $arrBlocks)) $intLayoutCount++;
			}
			return $intLayoutCount;
		}
		
		public function get_editicon($intBlockID){
			return '<a href="'.$this->root_path.'admin/manage_portal.php'.$this->SID.'&amp;b='.$intBlockID.'"><img src="'.$this->root_path.'images/glyphs/edit.png" alt="edit"/></a>';
		}
		
	}//end class
}//end if
?>
<?php
/*
* Project:		EQdkp-Plus
* License:		Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
* Link:			http://creativecommons.org/licenses/by-nc-sa/3.0/
* -----------------------------------------------------------------------
* Began:		2007
* Date:			$Date$
* -----------------------------------------------------------------------
* @author		$Author$
* @copyright	2006-2011 EQdkp-Plus Developer Team
* @link			http://eqdkp-plus.com
* @package		eqdkpplus
* @version		$Rev$
*
* $Id$
*/

if(!defined('EQDKP_INC')) {
	die('Do not access this file directly.');
}

if(!class_exists('pdh_w_calendars')) {
	class pdh_w_calendars extends pdh_w_generic {

		public function reset() {
			$this->db->query("TRUNCATE TABLE __calendars;");
			$this->db->query("INSERT INTO __calendars (id,name,color,private,feed,system, type, restricted) VALUES ('1','Raids','#00628c','0',NULL,'1', '1', '1');");
			$this->db->query("INSERT INTO __calendars (id,name,color,private,feed,system, type, restricted) VALUES ('2','Userraids','#0cb20f','0',NULL,'1', '1', '0');");
			$this->db->query("INSERT INTO __calendars (id,name,color,private,feed,system, type, restricted) VALUES ('3','Standard','#ba1e1e','0',NULL,'0', '2', '0');");
			$this->pdh->enqueue_hook('calendar_update');
		}

		public function update_calendar($id, $name, $color, $feed, $private, $type, $restricted){
			$old['name']		= $this->pdh->get('calendars', 'name', array($id));
			$old['feed']		= $this->pdh->get('calendars', 'feed', array($id));
			$old['private']		= $this->pdh->get('calendars', 'private', array($id));
			$old['color']		= $this->pdh->get('calendars', 'color', array($id));
			$old['type']		= $this->pdh->get('calendars', 'type', array($id));
			$old['restricted']	= $this->pdh->get('calendars', 'restricted', array($id));
			$changes		= false;
			foreach($old as $varname => $value) {
				if(${$varname} != $value) {
					$changes = true;
				}
			}
			if($changes) {
				$objQuery = $this->db->prepare("UPDATE __calendars :p WHERE id=?")->set(array(
					'name'			=> $name,
					'feed'			=> $feed,
					'private'		=> ($private) ? 1 : 0,
					'color'			=> $color,
					'type'			=> $type,
					'restricted'	=> ($restricted) ? 1 : 0,
				))->execute($id);
				
				if(!$objQuery) {
					return false;
				}
			}
			$this->pdh->enqueue_hook('calendar_update', array($id));
			return true;
		}

		public function add_calendar($id, $name, $color, $feed, $private, $type, $restricted){
			$objQuery = $this->db->prepare('INSERT INTO __calendars :p')->set(array(
				'feed'			=> ($feed) ? $feed : '',
				'name'			=> $name,
				'color'			=> $color,
				'private'		=> ($private) ? 1 : 0,
				'type'			=> $type,
				'restricted'	=> ($restricted) ? 1 : 0
			))->execute();
			
			if($objQuery){
				$id = $objQuery->insertId;
				$this->pdh->enqueue_hook('calendar_update', array($id));
				return $id;
			}
			
			return false;
		}

		public function delete_calendar($id){
			if(!$this->pdh->get('calendars', 'system', array($id))){
				$objQuery = $this->db->prepare("DELETE FROM __calendars WHERE id=?")->execute($id);
				$this->pdh->enqueue_hook('calendar_update', array($id));
				return true;
			}
		}
	}
}
?>
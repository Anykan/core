<?php
 /*
 * Project:		EQdkp-Plus
 * License:		Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:		http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:		2010
 * Date:		$Date$
 * -----------------------------------------------------------------------
 * @author		$Author$
 * @copyright	2006-2011 EQdkp-Plus Developer Team
 * @link		http://eqdkp-plus.com
 * @package		eqdkp-plus
 * @version		$Rev$
 * 
 * $Id$
 */

if(!defined('EQDKP_INC')){
	die('Do not access this file directly.');
}

if(!class_exists('pdh_w_calendar_raids_guests')){
	class pdh_w_calendar_raids_guests extends pdh_w_generic{
	
		public function reset() {
			$this->db->query("TRUNCATE TABLE __calendar_raid_guests;");
			$this->pdh->enqueue_hook('guests_update');
		}

		public function insert_guest($eventid, $name='', $classid='', $group='', $note=''){
			$objQuery = $this->db->prepare("INSERT INTO __calendar_raid_guests :p")->set(array(	
				'calendar_events_id'	=> $eventid,
				'name'					=> $name,
				'note'					=> $note,
				'timestamp_signup'		=> $this->time->time,
				'class'					=> $classid,
				'raidgroup'				=> $group
			))->execute();
			if ($objQuery) return $objQuery->insertId;
			$this->pdh->enqueue_hook('guests_update');
			
			return false;
		}

		public function update_guest($guestid, $classid='', $group='', $note=''){
			$classname	= ($classname)	? $classname: $this->pdh->get('guests', 'class', array($guestid));
			$group		= ($group)		? $group	: $this->pdh->get('guests', 'group', array($guestid));
			$note		= ($note)		? $note 	: $this->pdh->get('guests', 'note', array($guestid));
			$objQuery = $this->db->prepare("UPDATE __calendar_raid_guests :p WHERE id=?")->set(array(
				'class'			=> $classid,
				'raidgroup'		=> $group,
				'note'			=> $note,
			))->execute($guestid);
			$this->pdh->enqueue_hook('guests_update', array($guestid));
		}

		public function delete_guest($guestid){
			$objQuery = $this->db->prepare("DELETE FROM __calendar_raid_guests WHERE id=?;")->execute($guestid);
			
			if($objQuery){
				$this->pdh->enqueue_hook('guests_update', array($guestid));
				return true;
			}
			return false;
		}
	}
}
?>
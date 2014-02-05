<?php


/*
* Project:		EQdkp-Plus
* License:		Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
* Link:			http://creativecommons.org/licenses/by-nc-sa/3.0/
* -----------------------------------------------------------------------
* Began:		2010
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

if(!defined('EQDKP_INC')){
	die('Do not access this file directly.');
}

if(!class_exists('gallery')){
	class gallery extends gen_class {	
		
		private $_cache = array();
		
		public function create($strFolder, $intSortation, $strPath, $intPageNumber  = 0){		
			$strFolder = str_replace("*+*+*", "/", $strFolder);
			$strOrigFolder = $strFolder;
			//Subfolder navigation
			if ($this->in->get('gf') != "" && $this->in->get('gsf') != ""){
				if (base64_decode($this->in->get('gf')) == $strOrigFolder) $strFolder = base64_decode($this->in->get('gsf'));
			}
			
			
			$contentFolder = $this->pfh->FolderPath($strFolder, 'files');
			$contentFolderSP = $this->pfh->FolderPath($strFolder, 'files', 'serverpath');

			$dataFolder = $this->pfh->FolderPath('system', 'files', 'plain');
			$blnIsSafe = isFilelinkInFolder($contentFolder, $dataFolder);
			if (!$blnIsSafe) return "";
			
			$arrFiles = sdir($contentFolder);
			$arrDirs = $arrImages = $arrImagesDate = array();
			foreach($arrFiles as $key => $val){
				if (is_dir($contentFolder.$val)){
					$arrDirs[] = $val;
				} else {
					$extension = strtolower(pathinfo($val, PATHINFO_EXTENSION));
					if (in_array($extension, array('jpg', 'png', 'gif', 'jpeg'))){
						$arrImages[$val] = pathinfo($val, PATHINFO_FILENAME);
						$arrImageDimensions[$val] = getimagesize($contentFolder.$val);
						if ($intSortation == 2 || $intSortation == 3) $arrImagesDate[$val] = filemtime($contentFolder.$val);
					}
				}
			}

			switch($intSortation){
				case 1: natcasesort($arrImages);
						$arrImages = array_reverse($arrImages);
				
				break;
				case 2: asort($arrImagesDate); $arrImages = $arrImagesDate;
				break;
				
				case 3: arsort($arrImagesDate); $arrImages = $arrImagesDate;
				break;
				
				default: natcasesort($arrImages);
			}
			
			$strOut = '<ul class="image-gallery">';
			$strLink = $strPath.(($intPageNumber > 1) ? '&page='.$intPageNumber : '');
			
			if($this->in->exists('gsf') && $this->in->get('gsf') != ''){
				$arrPath = array_filter(explode('/', $strFolder));
				array_pop($arrPath);
				$strFolderUp = implode('/', $arrPath);
				if ($strFolderUp == $strOrigFolder) {
					$strFolderUp = '';
				} else {
					$strFolderUp = base64_encode($strFolderUp);
				}
				$strOut .='<li class="folderup"><a href="'.$strLink.'&gf='.base64_encode($strOrigFolder).'&gsf='.$strFolderUp.'"><i class="fa fa-level-up fa-flip-horizontal"></i><br>'.$this->user->lang('back').'</a></li>';
			}
			
			natcasesort($arrDirs);
			foreach($arrDirs as $foldername){				
				$strOut .= '<li class="folder"><a href="'.$strLink.'&gf='.base64_encode($strOrigFolder).'&gsf='.base64_encode($strFolder.'/'.$foldername).'">'.sanitize($foldername).'</a></li>';
			}
			
			$strThumbFolder = $this->pfh->FolderPath('system/thumbs', 'files');
			$strThumbFolderSP = $this->pfh->FolderPath('system/thumbs', 'files', 'serverpath');
			
			foreach($arrImages as $key => $val){
				//Check for thumbnail
				$strThumbname = "thumb_".pathinfo($key, PATHINFO_FILENAME)."-150x150.".pathinfo($key, PATHINFO_EXTENSION);
				$strThumbnail = "";
				if (is_file($strThumbFolder.$strThumbname)){
					$strThumbnail = $strThumbFolderSP.$strThumbname;
				} else {
					//Create thumbnail
					$this->pfh->thumbnail($contentFolder.$key, $strThumbFolder, $strThumbname, 150);
					if (is_file($strThumbFolder.$strThumbname)){
						$strThumbnail = $strThumbFolderSP.$strThumbname;
					}
				}
								
				if($strThumbnail != ""){
					$strOut .= '<li class="image"><a href="'.$contentFolderSP.$key.'" class="lightbox_'.md5($strFolder).'" rel="'.md5($strFolder).'" title="'.sanitize($key).'; '.$arrImageDimensions[$key][0].'x'.$arrImageDimensions[$key][1].' px"><img src="'.$strThumbnail.'" alt="Image" /></a></li>';
				}
				
			}

			$strOut .= "</ul><div class=\"clear\"></div>";
			
			$this->jquery->lightbox(md5($strFolder), array('slideshow' => true, 'transition' => "elastic", 'slideshowSpeed' => 4500, 'slideshowAuto' => false));
						
			return $strOut;
		}
		
		public function raidloot($intRaidID){
			//Get Raid-Infos:
			$intEventID = $this->pdh->get('raid', 'event', array($intRaidID));
			if ($intEventID){
				$strOut = '<div class="raidloot"><h3>'.$this->user->lang('loot').' '.$this->pdh->get('event', 'html_icon', array($intEventID)).$this->pdh->get('raid', 'html_raidlink', array($intRaidID, register('routing')->simpleBuild('raid'), '', true));
				$strRaidNote = $this->pdh->get('raid', 'html_note', array($intRaidID));
				if ($strRaidNote != "") $strOut .= ' ('.$strRaidNote.')';
				$strOut .= ', '.$this->pdh->get('raid', 'html_date', array($intRaidID)).'</h3>';
				
				//Get Items from the Raid
				$arrItemlist = $this->pdh->get('item', 'itemsofraid', array($intRaidID));
				infotooltip_js();
				
				if (count($arrItemlist)){
					foreach($arrItemlist as $item){
						$buyer = $this->pdh->get('item', 'buyer', array($item));
						$strOut .=  $this->pdh->get('item', 'link_itt', array($item, register('routing')->simpleBuild('item'), '', false, false, false, false, false, true)). ' - '.$this->pdh->geth('member', 'memberlink', array($buyer, register('routing')->simpleBuild('character'), '', false,false,true,true)).
						', '.round($this->pdh->get('item', 'value', array($item))).' '.$this->config->get('dkp_name').'<br />';
					}
					
				} else {
					return '';
				}
				
				
				return $strOut.'</div>';
			}
			return '';
		}

	}
}
?>
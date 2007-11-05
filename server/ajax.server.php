<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

/* Because this is accessed via Ajax we are going to allow the session_id 
 * as part of the get request
 */

require_once '../lib/init.php';

/* Set the correct headers */
header("Content-type: text/xml; charset=" . Config::get('site_charset'));
header("Content-Disposition: attachment; filename=ajax.xml");
header("Expires: Sun, 19 Nov 1978 05:00:00 GMT"); 
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache"); 

// Set that this is an ajax include
define('AJAX_INCLUDE','1'); 

switch ($_REQUEST['page']) { 
	case 'stats': 
		require_once Config::get('prefix') . '/server/stats.ajax.php'; 
		exit; 
	break;
	case 'browse': 
		require_once Config::get('prefix') . '/server/browse.ajax.php'; 
		exit; 
	break;
	case 'random': 
		require_once Config::get('prefix') . '/server/random.ajax.php'; 
		exit; 
	break;
	case 'playlist': 
		require_once Config::get('prefix') . '/server/playlist.ajax.php'; 
		exit; 
	break;
	case 'localplay': 
		require_once Config::get('prefix') . '/server/localplay.ajax.php'; 
		exit; 
	break;
	case 'stream': 
		require_once Config::get('prefix') . '/server/stream.ajax.php';
		exit; 
	break;
	default: 
		// A taste of compatibility
	break;
} // end switch on page 

switch ($_REQUEST['action']) { 
	case 'refresh_rightbar': 
		$results['rightbar'] = ajax_include('rightbar.inc.php'); 
	break;
	/* Controls the editing of objects */
	case 'show_edit_object': 
		
		// Set the default required level
		$level = '50'; 

		switch ($_GET['type']) { 
			case 'album': 
				$key = 'album_' . $_GET['id']; 
				$album = new Album($_GET['id']); 
				$album->format(); 
			break;
			case 'artist': 
				$key = 'artist_' . $_GET['id']; 
				$artist = new Artist($_GET['id']); 
				$artist->format(); 
			break;
			case 'song': 
				$key = 'song_' . $_GET['id']; 
				$song = new Song($_GET['id']); 
				$song->format(); 
			break;
			case 'live_stream': 
				$key = 'live_stream_' . $_GET['id']; 
				$radio = new Radio($_GET['id']); 
				$radio->format(); 
			break;
			case 'playlist': 
				$key = 'playlist_row_' . $_GET['id']; 
				$playlist = new Playlist($_GET['id']); 
				$playlist->format(); 
				// If the current user is the owner, only user is required
				if ($playlist->user == $GLOBALS['user']->id) { 
					$level = '25'; 
				} 
			break;
			default: 
				$key = 'rfc3514'; 
				echo xml_from_array(array($key=>'0x1')); 
				exit; 
			break;
		} // end switch on type 

		// Make sure they got them rights
		if (!$GLOBALS['user']->has_access($level)) { 
			exit; 
		} 

		ob_start(); 
		require Config::get('prefix') . '/templates/show_edit_' . $_GET['type'] . '_row.inc.php'; 
		$results[$key] = ob_get_contents(); 
		ob_end_clean(); 
	break; 
	case 'edit_object': 

		$level = '50'; 
		
		if ($_POST['type'] == 'playlist') { 
			$playlist = new Playlist($_POST['id']); 
			if ($GLOBALS['user']->id == $playlist->user) { 
				$level = '25'; 
			} 
		} 

		// Make sure we've got them rights
		if (!$GLOBALS['user']->has_access($level) || Config::get('demo_mode')) { 
			exit; 
		} 

		switch ($_POST['type']) { 
			case 'album': 
				$key = 'album_' . $_POST['id']; 
				$album = new Album($_POST['id']); 
				$new_id = $album->update($_POST); 
				if ($new_id != $_POST['id']) { 
					$album = new Album($new_id); 
				} 
				$album->format(); 
			break;
			case 'artist': 
				$key = 'artist_' . $_POST['id']; 
				$artist = new Artist($_POST['id']); 
				$new_id = $artist->update($_POST); 
				if ($new_id != $_POST['id']) { 
					$artist = new Artist($new_id); 
				} 
				$artist->format(); 
			break;
			case 'song': 
				$key = 'song_' . $_POST['id']; 
				$song = new Song($_POST['id']);
				$song->update($_POST); 
				$song->format(); 
			break;
			case 'playlist': 
				$key = 'playlist_row_' . $_POST['id']; 
				$playlist->update($_POST); 
				$playlist->format(); 
			break;
			case 'live_stream': 
				$key = 'live_stream_' . $_POST['id']; 
				Radio::update($_POST); 
				$radio = new Radio($_POST['id']); 
				$radio->format(); 
			break;
			default: 
				$key = 'rfc3514';
				echo xml_from_array(array($key=>'0x1')); 
				exit; 
			break;
		} // end switch on type

		ob_start(); 
		require Config::get('prefix') . '/templates/show_' . $_POST['type'] . '_row.inc.php'; 
		$results[$key] = ob_get_contents(); 
		ob_end_clean(); 
	break;
	/* Controls Localplay */
	case 'localplay':
		$localplay = init_localplay();
		$localplay->connect();
		$function 	= scrub_in($_GET['cmd']);
		$value		= scrub_in($_GET['value']);
		$return		= scrub_in($_GET['return']);
		$localplay->$function($value); 
		/* Return information based on function */
		switch($function) { 
			case 'skip':
				ob_start();
				require_once(conf('prefix') . '/templates/show_localplay_playlist.inc.php');
				$results['lp_playlist'] = ob_get_contents();
				ob_end_clean();
			case 'volume_up':
			case 'volume_down':
			case 'volume_mute':
				$status = $localplay->status();
				$results['lp_volume']	= $status['volume'];
			break;
			case 'next':
			case 'stop':
			case 'prev':
			case 'pause':
			case 'play':
				if ($return) { 
					$results['lp_playing'] = $localplay->get_user_playing(); 
				} 	
			default:
				$results['3514'] = '0x1';	
			break;
		} // end switch on cmd
	break;
	case 'current_playlist':
		switch ($_REQUEST['type']) { 
			case 'delete':
				$GLOBALS['user']->playlist->delete_track($_REQUEST['id']);
			break;
		} // end switch

		$results['rightbar'] = ajax_include('rightbar.inc.php');
	break;
	// Handle the users basketcases... 
	case 'basket': 
		switch ($_REQUEST['type']) { 
			case 'album': 
			case 'artist': 
			case 'genre': 
				$object = new $_REQUEST['type']($_REQUEST['id']); 
				$songs = $object->get_songs(); 
				foreach ($songs as $song_id) { 
					$GLOBALS['user']->playlist->add_object($song_id,'song'); 
				} // end foreach
			break;
			case 'album_random': 
			case 'artist_random': 
			case 'genre_random':
				$data = explode('_',$_REQUEST['type']); 
				$type = $data['0'];
				$object = new $type($_REQUEST['id']); 
				$songs = $object->get_random_songs(); 
				foreach ($songs as $song_id) { 
					$GLOBALS['user']->playlist->add_object($song_id,'song'); 
				} 
			break; 
			case 'playlist': 
				$playlist = new Playlist($_REQUEST['id']); 
				$items = $playlist->get_items(); 
				foreach ($items as $item) { 
					$GLOBALS['user']->playlist->add_object($item['object_id'],$item['type']); 
				} 
			break;
			case 'playlist_random': 
				$playlist = new Playlist($_REQUEST['id']); 
				$items = $playlist->get_random_items(); 
				foreach ($items as $item) { 
					$GLOBALS['user']->playlist->add_object($item['object_id'],$item['type']); 
				} 
			break;
			case 'clear_all': 
				$GLOBALS['user']->playlist->clear(); 
			break;
			case 'live_stream': 
				$object = new Radio($_REQUEST['id']); 
				// Confirm its a valid ID
				if ($object->name) { 
					$GLOBALS['user']->playlist->add_object($object->id,'radio'); 
				} 
			break;
			case 'dynamic': 
				$random_type = Random::validate_type($_REQUEST['random_type']); 
				$GLOBALS['user']->playlist->add_object('0',$random_type); 
			break;
			default: 
			case 'song': 
				$GLOBALS['user']->playlist->add_object($_REQUEST['id'],'song'); 
			break;
		} // end switch
		
		$results['rightbar'] = ajax_include('rightbar.inc.php'); 
	break;
	/* For changing the current play type FIXME:: need to allow select of any type  */
	case 'change_play_type':
		$pref_id = get_preference_id('play_type');
		$GLOBALS['user']->update_preference($pref_id,$_GET['type']);

		/* Uses a drop down, no need to replace text */
		$results['play_type'] = '';
	break;
	/* reloading the now playing information */
	case 'reloadnp':
		ob_start();
		show_now_playing();	
		$results['np_data'] = ob_get_contents();
		ob_clean();
		$data = get_recently_played(); 
		if (count($data)) { 
			show_box_top(_('Recently Played')); 
			require_once Config::get('prefix') . '/templates/show_recently_played.inc.php'; 
			show_box_bottom(); 
		}
		$results['recently_played'] = ob_get_contents(); 
		ob_end_clean();
	break;
	/* Setting ratings */
	case 'set_rating':
		ob_start(); 
		$rating = new Rating($_GET['object_id'],$_GET['rating_type']);
		$rating->set_rating($_GET['rating']);
		Rating::show($_GET['object_id'],$_GET['rating_type']); 
		$key = "rating_" . $_GET['object_id'] . "_" . $_GET['rating_type'];
		$results[$key] = ob_get_contents();
		ob_end_clean();
	break;
	/* This can be a positve (1) or negative (-1) vote */
	case 'vote':
		if (!$GLOBALS['user']->has_access(25) || !conf('allow_democratic_playback')) { break; }
		/* Get the playlist */
		$tmp_playlist = get_democratic_playlist(-1);
		
		if ($_REQUEST['vote'] == '1') { 
			$tmp_playlist->vote(array($_REQUEST['object_id']));
		}
		else { 
			$tmp_playlist->remove_vote($_REQUEST['object_id']);
		}

		ob_start();
		$songs = $tmp_playlist->get_items();
		require_once(conf('prefix') . '/templates/show_tv_playlist.inc.php');
		$results['tv_playlist'] = ob_get_contents(); 
		ob_end_clean();
	break;
	// Used to change filter/settings on browse
	case 'browse':
		if ($_REQUEST['key'] && $_REQUEST['value']) { 
			// Set any new filters we've just added
			Browse::set_filter($_REQUEST['key'],$_REQUEST['value']); 
		} 
		if ($_REQUEST['sort']) { 
			// Set the new sort value
			Browse::set_sort($_REQUEST['sort']); 
		} 

		// Refresh the browse div with our new filter options
		$object_ids = Browse::get_objects(); 

		ob_start(); 
		Browse::show_objects($object_ids); 
		$results['browse_content'] = ob_get_contents(); 
		ob_end_clean(); 
	break;
	case 'page': 
		Browse::set_start($_REQUEST['start']); 

		ob_start(); 
		Browse::show_objects(); 
		$results['browse_content'] = ob_get_contents(); 
		ob_end_clean(); 
	break;
	case 'sidebar': 
		switch ($_REQUEST['button']) {
			case 'home':
			case 'browse':
			case 'localplay':
			case 'player':
			case 'preferences':
				$button = $_REQUEST['button']; 
			break;
			case 'admin':
				if ($GLOBALS['user']->has_access(100)) { $button = $_REQUEST['button']; } 
				else { exit; } 
			break;
			default: 
				exit; 
			break;
		} // end switch on button  

		ob_start(); 
		$_SESSION['state']['sidebar_tab'] = $button; 
		require_once Config::get('prefix') . '/templates/sidebar.inc.php';
		$results['sidebar'] = ob_get_contents(); 
		ob_end_clean(); 
	break;
	default:
		$results['rfc3514'] = '0x1';
	break;
} // end switch action

// Go ahead and do the echo
echo xml_from_array($results); 

?>

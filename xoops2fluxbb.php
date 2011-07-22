<?php
/**
 *---------------------------------------------------------------------------
 * Migration from Xoops (CBB) to fluxBB.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *---------------------------------------------------------------------------
 *
 * This script is base on Xoops2punBB:
 * @author Guillaume Kulakowski <guillaume@llaumgui.com>
 * @copyright Guillaume Kulakowski
 *
 */

/**
 * Xoops2punBB 
 * 
 * @package 
 * @author Mario Santagiuliana <mario@marionline.it> 
 * @license GPL Version 3.0 {@link http://www.gnu.org/licenses/gpl-3.0.txt}
 */
class Xoops2punBB {

	/**
	 * _config 
	 * 
	 * @var array
	 * @access protected
	 */
	var $_config = array(	
		// MySQL :
		'db_host'      => 'localhost', // MySQL hostname or IP, generally localhost is ok
		'db_login'     => 'conv',      // MySQL Username
		'db_pass'      => 'convpass',  // MySQL Password
		'db_name'      => 'FOL2',      // MySQL Database Name

		'punbb_prefix' => 'fluxbb_',   // Table prefix of fluxbb forum
		'xoops_prefix' => 'xoops_',    // Table prefix of Xoops CMS

		// Debug :
		'debug_mod'    => false,       // Activation(true) / dactivation (false)

		// Groups conversion :
		'groupid'      => array ( 2 => 4, 4 => 2 ), // Xoops register user id is 2, fluxbb legister user is 4

		// Options :
		'language'    => 'French',     // Default members language
		'style'       => 'Oxygen',     // Default style for members (Oxygen)
	);

	/**
	 * _DB 
	 * 
	 * @var mixed
	 * @access private
	 */
	private $_DB;

	/**
	 * _query 
	 * Last query result
	 * 
	 * @var mixed
	 * @access protected
	 */
	var $_query;

	/**
	 * __construct 
	 * 
	 * @author Guillaume Kulakowski <guillaume AT llaumgui DOT com>
	 * @since 0.1
	 * @access protected
	 * @return void
	 */
	function __construct() {
	
		// Open DB connection.
		$this->connect();

		// Start conversion
		$this->convGroups();
		$this->convMember();
		$this->convCategory();
		$this->convForum();
		$this->convTopic();
		$this->convPost();

		// Close DB connection
		$this->close();

		echo "Migration DONE!" . PHP_EOL;
	}

	/**
	 * convGroups 
	 * Groups conversion, add groups from xoops groups table that are not
	 * the default groups (Administrators, moderators, guests and members).
	 * 
	 * @author Guillaume Kulakowski <guillaume AT llaumgui DOT com>
	 * @since 0.1
	 * @access public
	 * @return void
	 */
	public function convGroups () {

		$this->emptyTable( "groups", "WHERE g_id > 4" );
		$query_result = $this->query( "SELECT * FROM " . $this->_config['xoops_prefix'] . "groups ORDER BY groupid" );

		while ( $groups = $this->fetch_array( $query_result ) ) {
			/*
			 * Group id conversion
			 * Change the script like yuo needs.
			 */
			$groups['groupid'] = $this->convertGroupId($groups['groupid']);
			if ( $groups['groupid'] > 4 ) {
				$tab =	array(
					'g_id'                     => $groups['groupid'],
					'g_title'                  => $this->parseString( $groups['name'] ),
					'g_user_title'             => $this->parseString( $groups['name'] ),
					'g_read_board'             => 1,
					'g_post_replies'           => 1,
					'g_post_topics'            => 1,
					'g_edit_posts'             => 1,
					'g_delete_posts'           => 1,
					'g_delete_topics'          => 1,
					'g_set_title'              => 0,
					'g_search_users'           => 1,
					'g_post_flood'             => 30,
					'g_search_flood'           => 30,
					);
				$this->query( $this->buidInsert( 'groups', $tab ) );
			}
		}

		echo "Groups migration done. You should check the permission of the groups from Fluxbb control panel." . PHP_EOL . PHP_EOL;
	}

	/**
	 * Convertion des membres.
	 * Penser à mettre les avatars dans le dossier img/avatars/ et y donnner les bons droits.
	 * @author Guillaume Kulakowski <guillaume AT llaumgui DOT com>
	 * @since 0.1
	 */
	function convMember () {

		$this->emptyTable( "users", "WHERE id >= 1" );

		$query_result = $this->query( "SELECT * FROM " . $this->_config['xoops_prefix'] . "users ORDER BY uid" );

		while ( $member = $this->fetch_array( $query_result ) ) {
			/*
			 * Avatars :
			 */
			if ( $member['user_avatar'] == '' || $member['user_avatar'] == 'blank.gif' ) {
				$use_avatar = 0;
			} else {
				$use_avatar = 1;
				$avatar		= explode('.', $member['user_avatar']);
				/* rename( 	'../img/avatars/'.$member['user_avatar'], 
							'../img/avatars/'.$member['uid'].'.'.$avatar[1] ); */
			}

			/*
			 * Dernier post :
			 */
			$lastPost = $this->getLastPostMember( $member['uid']);

			$tab = array(
				'id'               => $member['uid'],
				'group_id'         => 4,
				'username'         => $this->parseString( $member['uname'] ),
				'password'         => $member['pass'],
				'email'            => $this->parseString( $member['email'] ),
				'title'            => 'NULL',
				'realname'         => $this->parseString( $member['name'] ),
				'url'              => $this->parseString( $member['url'] ),
				'jabber'           => 'NULL',
				'icq'              => $this->parseString( $member['user_icq'] ),
				'msn'              => $this->parseString( $member['user_msnm'] ),
				'aim'              => $this->parseString( $member['user_aim'] ),
				'yahoo'            => $this->parseString( $member['user_yim'] ),
				'location'         => $this->parseString( $member['user_from'] ),
				//'use_avatar'     => $use_avatar,
				'signature'        => $this->parseString( $member['user_sig'] ),
				'disp_topics'      => 'NULL',
				'disp_posts'       => 'NULL',
				'email_setting'    => 1,
				//'save_pass'      => 1,
				'notify_with_post' => 0,
				'show_smilies'     => 1,
				'show_img'         => 1,
				'show_img_sig'     => 1,
				'show_avatars'     => 1,
				'show_sig'         => 1,
				'timezone'         => 0,
				'language'         => $this->_config['language'],
				'style'            => $this->_config['style'],
				'num_posts'        => $this->countPostMember( $member['uid']),
				'last_post'        => $lastPost['post_time'],
				'registered'       => $member['user_regdate'],
				'registration_ip'  => '0.0.0.0',
				'last_visit'       => $member['last_login'],
				'admin_note'       => 'NULL',
				'activate_string'  => 'NULL',
				'activate_key'     => 'NULL',
			);

			$this->query( $this->buidInsert( 'users', $tab ) );
		}

		echo "Migration members DONE." . PHP_EOL . PHP_EOL;
	}



	/**
 	 * Convertion des catégories.
 	 * @author Guillaume Kulakowski <guillaume AT llaumgui DOT com>
	 * @since 0.1
 	 */
 	function convCategory () {
 	
 		$this->emptyTable( "categories" );
 		
 		$query = $this->query( "SELECT * FROM ".$this->_config['xoops_prefix']."bb_categories ORDER BY cat_id" );
 			
 		while ( $cat = $this->fetch_array($query) ) {
			
			$tab =	array(	'id' 								=> $cat['cat_id'],
 									'cat_name'						=> $this->parseString( $cat['cat_title'] ),
				 					'disp_position'				=> $cat['cat_order'],

 								);
 			$this->query( $this->buidInsert( 'categories', $tab) );
 		}
 		echo "Catégories migrés.\n\n";					
	}



	/**
 	 * Convertion forums.
 	 * @author Guillaume Kulakowski <guillaume AT llaumgui DOT com>
	 * @since 0.1
 	 */
 	function convForum () {
 	
 		$this->emptyTable( "forums" );
 		
 		$query = $this->query( "SELECT * FROM ".$this->_config['xoops_prefix']."bb_forums ORDER BY forum_id" );
 			
 		while ( $forum = $this->fetch_array($query) ) {
 		
			$lastPost	 = $this->getLastPostForum( $forum['forum_id'] );
			
			$tab =	array(	'Id' 								=> $forum['forum_id'],
									'forum_name'  					=> $this->parseString( $forum['forum_name'] ),
									'forum_desc' 					=> $this->parseString( $forum['forum_desc'] ),
									'redirect_url'					=> 'NULL',
									'moderators' 					=> 'NULL',
									'num_topics' 					=> $this->countForumTopic( $forum['forum_id'] ),
									'num_posts' 					=> $this->countForumPost( $forum['forum_id'] ),
									'last_post' 					=> $lastPost['post_time'],
									'last_post_id' 				=> $lastPost['post_id'],
									'last_poster' 					=> $this->parseString( $lastPost['uname'] ),
									'sort_by' 						=> 0,
									'disp_position' 				=> $forum['forum_order'],
									'cat_id'							=> $forum['cat_id'],
 								);
 			$this->query( $this->buidInsert( 'forums', $tab) );
 		}
 		echo "Forums migrés.\n\n";		
	}



	/**
 	 * Convertion des topics.
 	 * @author Guillaume Kulakowski <guillaume AT llaumgui DOT com>
	 * @since 0.1
 	 */
 	function convTopic () {
 	
 		$this->emptyTable( "topics" );
 		
 		$query = $this->query( "SELECT * FROM ".$this->_config['xoops_prefix']."bb_topics ORDER BY topic_id " );
 			
 		while ( $topic = $this->fetch_array($query) ) {
 			$firstPost	 = $this->getFirstPostTopic( $topic['topic_id'] );
 			$lastPost	 = $this->getLastPostTopic( $topic['topic_id'] );
			
			$tab =	array(	'id' 								=> $topic['topic_id'],
 									'poster'							=> $this->parseString( $firstPost['uname'] ),
				 					'subject'						=> $this->parseString( $topic['topic_title'] ),
									'posted'							=> $topic['topic_time'],
									'last_post'						=> $lastPost['post_time'],
									'last_post_id'					=> $lastPost['post_id'],
									'last_poster'					=> $this->parseString( $lastPost['uname'] ),
									'num_views'						=> $topic['topic_views'],
									'num_replies'					=> $topic['topic_replies'],
				 					'closed'							=> $topic['topic_status'],
				 					'sticky'							=> $topic['topic_sticky'],
				 					'moved_to'						=> 'NULL',
				 					'forum_id'						=> $topic['forum_id'],
 								);
 			$this->query( $this->buidInsert( 'topics', $tab) );
 		}
  		echo "Topics migrés.\n\n";		
	}



	/**
 	 * Convertion des topics.
 	 * La le SELECT * est un peu trop massif ! ! ! Donc je boucle plusieurs fois...
 	 * @author Guillaume Kulakowski <guillaume AT llaumgui DOT com>
	 * @since 0.1
 	 */
 	function convPost () {
 	
 		$this->emptyTable( "posts" );
 		$nbPost	= '1000';		// traiter les postes par 1000.
 		$i=0;
 		 		 		
 		$lQuery = $this->query( "SELECT MAX(post_id) as post_id FROM ".$this->_config['xoops_prefix']."bb_posts p ORDER BY p.post_id" );
 		$maxId = $this->fetch_array($lQuery);
		$maxId =	$maxId['post_id'];
		echo "\tMaxId = $maxId.\n";	
 		
 		while ( $i < $maxId ) {
 			$query = $this->query( "SELECT * FROM ".$this->_config['xoops_prefix']."bb_posts p
 												LEFT JOIN ".$this->_config['xoops_prefix']."bb_posts_text pt 	ON p.post_id=pt.post_id
 												LEFT JOIN ".$this->_config['xoops_prefix']."users u					ON p.uid=u.uid
 											WHERE p.post_id >= ".$i." AND p.post_id < ".($i+$nbPost)."
 											ORDER BY p.post_id" );
 			
	 		while ( $post = $this->fetch_array($query) ) {		
				$tab =	array(	'id' 								=> $post['post_id'],
										'poster' 						=> $this->parseString( $post['uname'] ),
										'poster_id'						=> $post['uid'],
										'poster_ip'						=> long2ip($post['poster_ip']),
										'poster_email'					=> 'NULL',
										'message'						=> $this->parseString( $post['post_text'] ),
										'hide_smilies'					=> $this->convertSmiles( $post['dosmiley'] ),
										'posted'							=> $post['post_time'],
										'edited'							=> 'NULL',
										'edited_by'						=> 'NULL',
										'topic_id'						=> $post['topic_id'],
	 								);
	 			$this->query( $this->buidInsert( 'posts', $tab) );
	 		}
	 		echo "\tPost $i à ".($i+$nbPost)." traités\n";
	 		$i	= $i + $nbPost;
	 	}
	 	echo "Postes migrés.\n\n";						
	}





/* ---------------------------------------------------------------------------
 *
 * Sous fonction utile pour la migration :
 *
 * -------------------------------------------------------------------------*/

	/**
	 * convertGroupId 
	 * Convert group id to match the new fluxbb table id. Example: the
	 * old xoops members have group id 2 but in fluxbb the members group id
	 * is 4. @see class::$_config['groupid'].
	 * 
	 * @author Guillaume Kulakowski <guillaume AT llaumgui DOT com>
	 * @since 0.1
	 * @param int $groupid 
	 * @access private
	 * @return int Group id modify
	 */
	private function convertGroupId( $groupid ) {

		if ( isset( $this->_config['groupid'][$groupid] ) ) {
			return $this->_config['groupid'][$groupid];
		} else {
			return $groupid;
		}
	}



	/**
 	 * Convertion de hide et show smilies.
 	 * @author Guillaume Kulakowski <guillaume AT llaumgui DOT com>
	 * @since 0.1
	 * @param boolean		$show 	Smilies affichés ?
	 * @return boolean Smilies cachés ?
	 */
 	function convertSmiles ($show) {
 		
 		if ($show == 0) return 1;
 		if ($show == 1) return 0;
 	}



	/**
 	 * Nombre de postes d'un membres.
 	 * @author Guillaume Kulakowski <guillaume AT llaumgui DOT com>
	 * @since 0.1
	 * @param int	$uid 	Id du membre.
	 * @return int Nombre de postes.
	 */
 	function countPostMember ($uid) {
 		
 		$count 	= $this->query("	SELECT count(*) as count
 											FROM ".$this->_config['xoops_prefix']."bb_posts
 											WHERE uid = $uid");
 		$count	= $this->fetch_array($count);
 		
 		return $count['count'];
 	}	 



	/**
 	 * Nombre de topic d'un forum.
 	 * @author Guillaume Kulakowski <guillaume AT llaumgui DOT com>
	 * @since 0.1
	 * @param int	$forum_id 	id du forum.
	 * @return int Nombre de topics.
	 */
 	function countForumTopic ($forum_id) {
 		
 		$count 	= $this->query("	SELECT count(*) as count
 											FROM ".$this->_config['xoops_prefix']."bb_topics
 											WHERE forum_id  = $forum_id ");
 		$count	= $this->fetch_array($count);
 		
 		return $count['count'];
 	}	 
 	
 	
 	
	/**
 	 * Nombre de post d'un forum.
 	 * @author Guillaume Kulakowski <guillaume AT llaumgui DOT com>
	 * @since 0.1
	 * @param int	$forum_id 	id du forum.
	 * @return int Nombre de postes.
	 */
 	function countForumPost ($forum_id) {
 		
 		$count 	= $this->query("	SELECT count(*) as count
 											FROM ".$this->_config['xoops_prefix']."bb_posts
 											WHERE forum_id  = $forum_id ");
 		$count	= $this->fetch_array($count);
 		return $count['count'];
 	}

 
 
  	/**
 	 * Récupération des infos sur le dernier post du membre.
 	 * @author Guillaume Kulakowski <guillaume AT llaumgui DOT com>
	 * @since 0.1
	 * @param int	$uid 	id du membre.
	 * @return int Informations sur le dernier poste d'un membre.
	 */
 	function getLastPostMember ($uid) {
 		
 		$get 	= $this->query("	SELECT *
 										FROM ".$this->_config['xoops_prefix']."bb_posts
 										WHERE uid = $uid
 											AND post_id=(	SELECT MAX(post_id)
 																	FROM ".$this->_config['xoops_prefix']."bb_posts
 																	WHERE uid = $uid
 																	GROUP BY uid)	");
 		$get	= $this->fetch_array($get);

		return $get;
 	}
 	 
 
  
  	/**
 	 * Récupération des infos sur le dernier post d'un forum.
 	 * @author Guillaume Kulakowski <guillaume AT llaumgui DOT com>
	 * @since 0.1
	 * @param int	$forum_id 	forum_id du forum.
	 * @return int Information sur le dernier postes.
	 */
 	function getLastPostForum ($forum_id) {
 		
 		$qGet = $this->query("	SELECT post_id, p.post_time, u.uname, p.uid
 										FROM ".$this->_config['xoops_prefix']."bb_posts p
 											LEFT JOIN ".$this->_config['xoops_prefix']."users u ON p.uid = u.uid
 										WHERE forum_id = $forum_id
 										 	AND p.post_id=(	SELECT MAX(post_id)
 																	FROM ".$this->_config['xoops_prefix']."bb_posts
 																	WHERE forum_id = $forum_id
 																	GROUP BY forum_id)	");
 		$get	= $this->fetch_array($qGet);

		// Cas de l'invité : 		
 		if ( $get['uid'] == 0 ) {
 			$get['uid'] 	= 1;
 			$get['uname'] 	= utf8_decode('Invité');
 		}
 		
		// Cas du forum sans topic : 		
 		if ( $this->num_rows($qGet) == 0 ) {
 			$get['uid'] 	= 'NULL';
 			$get['uname'] 	= 'NULL';
 			$get['post_id'] 	= 'NULL';
 			$get['post_time'] 	= 'NULL';
 		} 

 		return $get;
 	}



  	/**
 	 * Récupération des infos sur le dernier post.
 	 * @author Guillaume Kulakowski <guillaume AT llaumgui DOT com>
	 * @since 0.1
	 * @param int	$forum_id 	forum_id du forum.
	 * @return int Nombre
	 */
 	function getLastPostTopic ($topic_id) {
 		
 		$get 	= $this->query("	SELECT post_id, p.post_time, u.uname, p.uid
 										FROM ".$this->_config['xoops_prefix']."bb_posts p
 											LEFT JOIN ".$this->_config['xoops_prefix']."users u ON p.uid = u.uid
 										WHERE topic_id = $topic_id
 										 	AND p.post_id=(	SELECT MAX(post_id)
 																	FROM ".$this->_config['xoops_prefix']."bb_posts
 																	WHERE topic_id = $topic_id
 																	GROUP BY topic_id)	");

 		$get	= $this->fetch_array($get);
 		
 		// Cas de l'invité : 		
 		if ( $get['uid'] == 0 ) {
 			$get['uid'] 	= 1;
 			$get['uname'] 	= utf8_decode('Invité');
 		}

 		return $get;
 	}



  	/**
 	 * Récupération des infos sur le dernier post.
 	 * @author Guillaume Kulakowski <guillaume AT llaumgui DOT com>
	 * @since 0.1
	 * @param int	$forum_id 	forum_id du forum.
	 * @return int Nombre
	 */
 	function getFirstPostTopic ($topic_id) {
 		
 		$get 	= $this->query("	SELECT post_id, p.post_time, u.uname, p.uid
 										FROM ".$this->_config['xoops_prefix']."bb_posts p
 										LEFT JOIN ".$this->_config['xoops_prefix']."users u ON p.uid = u.uid
 										WHERE topic_id = $topic_id
 										 	AND p.post_id=(	SELECT MIN(post_id)
 																	FROM ".$this->_config['xoops_prefix']."bb_posts
 																	WHERE topic_id = $topic_id
 																	GROUP BY topic_id)	");
 		$get	= $this->fetch_array($get);
 		
 		// Cas de l'invité : 		
 		if ( $get['uid'] == 0 ) {
 			$get['uid'] 	= 1;
 			$get['uname'] 	= utf8_decode('Invité');
 		}

 		return $get;
 	}

	/**
	 * parseString 
	 * 
	 * @param string $string 
	 * @access private
	 * @return string
	 */
	private function parseString( $string ) {
		return mysql_escape_string( $string );
	}

	/**
	 * connect 
	 *
	 * Connection to the database.
	 * @author Guillaume Kulakowski <guillaume AT llaumgui DOT com>
	 * @since 0.1
	 * @access public
	 * @return void
	 */
	public function connect() {
		$this->_DB = mysql_connect(
			$this->_config['db_host'],
			$this->_config['db_login'],
			$this->_config['db_pass']
		) or die ( "Error in connection with server database..." );

		$this->_DB = mysql_select_db(
			$this->_config['db_name'],
			$this->_DB
		) or die ( "Error connection to database " . $this->_config['db_name'] );
	}

	/**
	 * close 
	 * 
	 * @access public
	 * @return void
	 */
	public function close() {
		mysql_close( $this->_DB );
	}

	/**
	 * emptyTable 
	 * 
	 * @author Guillaume Kulakowski <guillaume AT llaumgui DOT com>
	 * @since 0.1
	 * @param mixed $table  Table or tables to be served
	 * @param string $where 
	 * @access protected
	 * @return void
	 */
	protected function emptyTable ( $table, $where= "" ) {
		$this->query( "DELETE FROM " . $this->_config['punbb_prefix'] . $table . " " . $where );
		echo "Table $table purged" . PHP_EOL;
	}

	/**
	 * query 
	 * 
	 * @author Guillaume Kulakowski <guillaume AT llaumgui DOT com>
	 * @since 0.1
	 * @param string $query
	 * @access protected
	 * @return query result
	 */
	protected function query( $query ) {
		if ( $this->_config['debug_mod'] ) {
			echo "$query" . PHP_EOL;
		}

		$this->_query = mysql_query( $query ) or die( "MySQL error :\n\t" . mysql_error() . "\nMySQL error code : " . mysql_errno() . "\n\t" . $query. "\n\n");
		return $this->_query; 
	}

	/**
	 * fetch_array 
	 * mysql_fetch_assoc wrapper
	 * 
	 * @author Guillaume Kulakowski <guillaume AT llaumgui DOT com>
	 * @since 0.1
	 * @param array $query 
	 * @access protected
	 * @return void
	 */
	protected function fetch_array( $query = null ) {
		if ( $query === null ) {
			return mysql_fetch_assoc( $this->_query );
		} else {
			return mysql_fetch_assoc( $query );
		}
	}


	/**
 	 * Fonction mysql_num_rows.
 	 * @author Guillaume Kulakowski <guillaume AT llaumgui DOT com>
	 * @since 0.1
	 * @param id$ query 	Requête.
 	 */
	function num_rows($query = "") {
	
		if ( empty($query) ) {
			return mysql_num_rows ($this->_query);
		}
		else {
			return mysql_num_rows ($query);
		}
	}

	/**
	 * buidInsert 
	 * Construction of insertion requests
	 * 
	 * @author Guillaume Kulakowski <guillaume AT llaumgui DOT com>
	 * @since 0.1
	 * @param string $table The table
	 * @param array $tab Parameters
	 * @access protected
	 * @return void
	 */
	protected function buidInsert( $table, $tab ) {

		$_key;
		$_value;

		foreach ( $tab as $key => $value ) {
			$_key[]   = $key;
			$_value[] = $value;
		}

		$key   = "`" . implode( "`, `", $_key )."`";
		$value = "'" . implode( "', '", $_value ) . "'";
		$value = str_replace( "'NULL',", "NULL,", $value );

		return "INSERT INTO ".$this->_config['punbb_prefix'].$table." (" . $key . ") VALUES (" . $value . ")";
	}

} //EOC



/*
 * Appel :
 */
$convertion = new Xoops2punBB();

?>

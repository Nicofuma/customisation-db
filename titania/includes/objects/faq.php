<?php
/**
*
* @package Titania
* @version $Id$
* @copyright (c) 2008 phpBB Customisation Database Team
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
if (!defined('IN_TITANIA'))
{
	exit;
}

if (!class_exists('titania_message_object'))
{
	require TITANIA_ROOT . 'includes/core/object_message.' . PHP_EXT;
}

/**
* Class to titania faq.
* @package Titania
*/
class titania_faq extends titania_message_object
{
	/**
	 * SQL Table
	 *
	 * @var string
	 */
	protected $sql_table = TITANIA_CONTRIB_FAQ_TABLE;

	/**
	 * SQL identifier field
	 *
	 * @var string
	 */
	protected $sql_id_field = 'faq_id';

	/**
	 * Object type (for message tool)
	 *
	 * @var string
	 */
	protected $object_type = TITANIA_FAQ;

	/**
	 * Constructor class for titania faq
	 *
	 * @param int $faq_id
	 */
	public function __construct($faq_id = false)
	{
		// Configure object properties
		$this->object_config = array_merge($this->object_config, array(
			'faq_id'			=> array('default' => 0),
			'contrib_id' 		=> array('default' => 0),
			'faq_subject' 		=> array('default' => '',	'message_field' => 'subject', 'max' => 255),
			'faq_text' 			=> array('default' => '',	'message_field' => 'message'),
			'faq_text_bitfield'	=> array('default' => '',	'message_field' => 'message_bitfield'),
			'faq_text_uid'		=> array('default' => '',	'message_field' => 'message_uid'),
			'faq_text_options'	=> array('default' => 7,	'message_field' => 'message_options'),
			'faq_views'			=> array('default' => 0),
			'faq_access'		=> array('default' => 2,	'message_field'	=> 'access'),
			'left_id'			=> array('default' => 0),
			'right_id'			=> array('default' => 0),
		));

		if ($faq_id !== false)
		{
			$this->faq_id = $faq_id;
			$this->load();
		}
	}

	/**
	* Validate that all the data is correct
	*
	* @return array empty array on success, array with (string) errors ready for output on failure
	*/
	public function validate()
	{
		$error = array();

		if (utf8_clean_string($this->faq_subject) === '')
		{
			$error[] = phpbb::$user->lang['EMPTY_SUBJECT'];
		}

		$message_length = utf8_strlen($this->faq_text);
		if ($message_length < (int) phpbb::$config['min_post_chars'])
		{
			$error[] = sprintf(phpbb::$user->lang['TOO_FEW_CHARS_LIMIT'], $message_length, (int) phpbb::$config['min_post_chars']);
		}
		else if ($message_length > (int) phpbb::$config['max_post_chars'])
		{
			$error[] = sprintf($user->lang['TOO_MANY_CHARS_POST'], $message_length, (int) phpbb::$config['max_post_chars']);
		}

		return $error;
	}

	/**
	* Submit data in the post_data format (from includes/tools/message.php)
	*
	* @param object $message The message object
	*/
	public function post_data($message)
	{
		$this->__set_array(array(
			'contrib_id'		=> titania::$contrib->contrib_id,
		));

		parent::post_data($message);
	}

	/**
	* Build view URL for a faq
	*
	* @param string $action
	* @param int $faq_id
	*
	* @return string
	*/
	public function get_url($action = '', $faq_id = false)
	{
		$url = titania::$contrib->get_url('faq');
		$faq_id = (($faq_id) ? $faq_id : $this->faq_id);

		if ($action == 'create')
		{
			return titania_url::append_url($url, array('action' => $action));
		}
		else if (!$action)
		{
			return titania_url::append_url($url, array('f' => $faq_id, '#' => 'details'));
		}

		return titania_url::append_url($url, array('action' => $action, 'f' => $faq_id));
	}

	/**
	 * Update data or submit new faq
	 *
	 * @return void
	 */
	public function submit()
	{
		titania_search::index(TITANIA_FAQ, $this->faq_id, array(
			'title'			=> $this->faq_subject,
			'text'			=> $this->faq_text,
			'text_uid'		=> $this->faq_text_uid,
			'text_bitfield'	=> $this->faq_text_bitfield,
			'text_options'	=> $this->faq_text_options,
			'author'		=> 0,
			'date'			=> 0,
			'url'			=> titania_url::unbuild_url($this->get_url()),
			'access_level'	=> $this->faq_access,
		));

		parent::submit();
	}

	public function delete()
	{
		titania_search::delete(TITANIA_FAQ, $this->faq_id);

		parent::delete();
	}

	/**
	* Move a FAQ item
	*
	* @param string $direction (up|down)
	*/
	public function move($faq_row, $action = 'move_up', $steps = 1)
	{
		/**
		* Fetch all the siblings between the faq's current spot
		* and where we want to move it to. If there are less than $steps
		* siblings between the current spot and the target then the
		* faq will move as far as possible
		*/
		$sql = 'SELECT faq_id, left_id, right_id
			FROM ' . $this->sql_table . '
			WHERE contrib_id = ' . $this->contrib_id . '
				AND ' . (($action == 'move_up') ? "right_id < {$faq_row['right_id']} ORDER BY right_id DESC" : "left_id > {$faq_row['left_id']} ORDER BY left_id ASC");

		$result = phpbb::$db->sql_query_limit($sql, $steps);

		$target = array();
		while ($row = phpbb::$db->sql_fetchrow($result))
		{
			$target = $row;
		}
		phpbb::$db->sql_freeresult($result);

		if (!sizeof($target))
		{
			// The faq is already on top or bottom
			return false;
		}

		/**
		* $left_id and $right_id define the scope of the nodes that are affected by the move.
		* $diff_up and $diff_down are the values to substract or add to each node's left_id
		* and right_id in order to move them up or down.
		* $move_up_left and $move_up_right define the scope of the nodes that are moving
		* up. Other nodes in the scope of ($left_id, $right_id) are considered to move down.
		*/
		if ($action == 'move_up')
		{
			$left_id = $target['left_id'];
			$right_id = $faq_row['right_id'];

			$diff_up = $faq_row['left_id'] - $target['left_id'];
			$diff_down = $faq_row['right_id'] + 1 - $faq_row['left_id'];

			$move_up_left = $faq_row['left_id'];
			$move_up_right = $faq_row['right_id'];
		}
		else
		{
			$left_id = $faq_row['left_id'];
			$right_id = $target['right_id'];

			$diff_up = $faq_row['right_id'] + 1 - $faq_row['left_id'];
			$diff_down = $target['right_id'] - $faq_row['right_id'];

			$move_up_left = $faq_row['right_id'] + 1;
			$move_up_right = $target['right_id'];
		}

		// Now do the dirty job
		$sql = 'UPDATE ' . $this->sql_table . "
			SET left_id = left_id + CASE
				WHEN left_id BETWEEN {$move_up_left} AND {$move_up_right} THEN -{$diff_up}
				ELSE {$diff_down}
			END,
			right_id = right_id + CASE
				WHEN right_id BETWEEN {$move_up_left} AND {$move_up_right} THEN -{$diff_up}
				ELSE {$diff_down}
			END
			WHERE contrib_id = " . $this->contrib_id . "
				AND left_id BETWEEN {$left_id} AND {$right_id}
				AND right_id BETWEEN {$left_id} AND {$right_id}";
		phpbb::$db->sql_query($sql);

		return true;
	}

	/*
	 * Increase a FAQ views counter
	 *
	 * @return void
	 */
	public function increase_views_counter()
	{
		if (phpbb::$user->data['is_bot'])
		{
			return;
		}

		$sql = 'UPDATE ' . $this->sql_table . '
			SET faq_views = faq_views + 1
			WHERE faq_id = ' . (int) $this->faq_id;
		phpbb::$db->sql_query($sql);
	}

}

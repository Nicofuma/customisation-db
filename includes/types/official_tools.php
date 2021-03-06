<?php
/**
*
* This file is part of the phpBB Customisation Database package.
*
* @copyright (c) phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
* For full copyright and license information, please see
* the docs/CREDITS.txt file.
*
*/

define('TITANIA_TYPE_OFFICIAL_TOOL', 4);

class titania_type_official_tools extends titania_type_base
{
	/**
	 * The type id
	 *
	 * @var int type id (for custom types not specified in titania to start, please start with 10 in case we add any extra later)
	 */
	public $id = 4;
	
	/**
	 * The type name
	 *
	 * @var string (any lang key that includes the type should match this value)
	 */
	public $name = 'official_tool';

	/**
	 * For the url slug
	 *
	 * @var string portion to be used in the URL slug
	 */
	public $url = 'official_tool';

	// Official tools do not require validation (only team members can submit them)
	public $require_validation = false;
	public $use_queue = false;

	public function __construct()
	{
		$this->lang = phpbb::$user->lang['OFFICIAL_TOOL'];
		$this->langs = phpbb::$user->lang['OFFICIAL_TOOLS'];
	}

	/**
	* Check auth level
	*
	* @param string $auth ('view', 'test', 'validate')
	* @return bool
	*/
	public function acl_get($auth)
	{
		switch ($auth)
		{
			// No queue for the official tools
			case 'queue_discussion' :
			case 'view' :
			case 'validate' :
				return false;
			break;

			case 'submit' :
			case 'moderate' :
				return phpbb::$auth->acl_gets(array('u_titania_mod_official_tool_moderate', 'u_titania_mod_contrib_mod', 'u_titania_admin'));
			break;
		}

		return false;
	}
}

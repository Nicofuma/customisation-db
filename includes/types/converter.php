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

define('TITANIA_TYPE_CONVERTER', 3);

class titania_type_converter extends titania_type_base
{
	/**
	 * The type id
	 *
	 * @var int type id (for custom types not specified in titania to start, please start with 10 in case we add any extra later)
	 */
	public $id = 3;

	/**
	 * The type name
	 *
	 * @var string (any lang key that includes the type should match this value)
	 */
	public $name = 'converter';

	/**
	 * For the url slug
	 *
	 * @var string portion to be used in the URL slug
	 */
	public $url = 'converter';

	// Validation messages (for the PM)
	public $validation_subject = 'CONVERTER_VALIDATION';
	public $validation_message_approve = 'CONVERTER_VALIDATION_MESSAGE_APPROVE';
	public $validation_message_deny = 'CONVERTER_VALIDATION_MESSAGE_DENY';

	public function __construct()
	{
		$this->lang = phpbb::$user->lang['CONVERTER'];
		$this->langs = phpbb::$user->lang['CONVERTERS'];
		$this->forum_database = titania::$config->forum_converter_database;
		$this->forum_robot = titania::$config->forum_converter_robot;
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
			// Can submit a converter
			case 'submit' :
				return true;
			break;

			// Can view the convertor queue discussion
			case 'queue_discussion' :
				return phpbb::$auth->acl_get('u_titania_mod_converter_queue_discussion');
			break;

			// Can view the convertor queue
			case 'view' :
				return phpbb::$auth->acl_get('u_titania_mod_converter_queue');
			break;

			// Can validate convertors in the queue
			case 'validate' :
				return phpbb::$auth->acl_get('u_titania_mod_converter_validate');
			break;

			// Can moderate convertors
			case 'moderate' :
				return phpbb::$auth->acl_gets(array('u_titania_mod_converter_moderate', 'u_titania_mod_contrib_mod'));
			break;
		}

		return false;
	}
}

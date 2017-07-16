<?php
/**
*
* National Flags extension for the phpBB Forum Software package.
*
* @copyright (c) 2015 Rich McGirr (RMcGirr83)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace rmcgirr83\nationalflags\event;

/**
* @ignore
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{
	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\db\driver\driver */
	protected $db;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\extension\manager "Extension Manager" */
	protected $ext_manager;

	/** @var string phpBB root path */
	protected $phpbb_root_path;

	/** @var string phpEx */
	protected $php_ext;

	/* @var \rmcgirr83\nationalflags\core\nationalflags */
	protected $nationalflags;

	/**
	* Constructor
	*
	* @param \phpbb\auth\auth					$auth			Auth object
	* @param \phpbb\config\config               $config         Config object
	* @param \phpbb\controller\helper           $helper         Controller helper object
	* @param \phpbb\db\driver\driver			$db				Database object
	* @param \phpbb\request\request				$request		Request object
	* @param \phpbb\template\template           $template       Template object
	* @param \phpbb\user                        $user           User object
	* @param \phpbb\extension\manager			$ext_manager		Extension manager object
	* @param string                             $phpbb_root_path	phpBB root path
	* @param string                             $php_ext			phpEx
	* @param \rmcgirr83\nationalflags\core\nationalflags	$nationalflags	methods to be used by class
	* @access public
	*/
	public function __construct(
			\phpbb\auth\auth $auth,
			\phpbb\config\config $config,
			\phpbb\controller\helper $helper,
			\phpbb\db\driver\driver_interface $db,
			\phpbb\request\request $request,
			\phpbb\template\template $template,
			\phpbb\user $user,
			\phpbb\extension\manager $ext_manager,
			$phpbb_root_path,
			$php_ext,
			\rmcgirr83\nationalflags\core\nationalflags $nationalflags)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->helper = $helper;
		$this->db = $db;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->ext_manager	 = $ext_manager;
		$this->root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
		$this->nationalflags = $nationalflags;

		$this->ext_path = $this->ext_manager->get_extension_path('rmcgirr83/nationalflags', true);
	}

	/**
	 * Assign functions defined in this class to event listeners in the core
	 *
	 * @return array
	 * @static
	 * @access public
	 */
	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup'							=> 'user_setup',
			'core.index_modify_page_title'				=> 'index_modify_page_title',
			'core.page_header_after'					=> 'page_header_after',
			'core.ucp_profile_modify_profile_info'		=> 'user_flag_profile',
			'core.ucp_profile_validate_profile_info'	=> 'user_flag_profile_validate',
			'core.ucp_profile_info_modify_sql_ary'		=> 'user_flag_profile_sql',
			'core.ucp_register_data_before'				=> 'user_flag_profile',
			'core.ucp_register_data_after'				=> 'user_flag_profile_validate',
			'core.ucp_register_user_row_after'			=> 'user_flag_registration_sql',
			'core.acp_users_modify_profile'				=> 'user_flag_profile',
			'core.acp_users_profile_modify_sql_ary'		=> 'user_flag_profile_sql',
			'core.viewonline_overwrite_location'		=> 'viewonline_page',
			'core.viewtopic_assign_template_vars_before'	=> 'viewtopic_template_vars_before',
			'core.viewtopic_cache_user_data'			=> 'viewtopic_cache_user_data',
			'core.viewtopic_cache_guest_data'			=> 'viewtopic_cache_guest_data',
			'core.viewtopic_modify_post_row'			=> 'viewtopic_modify_post_row',
			'core.memberlist_view_profile'				=> 'memberlist_view_profile',
			'core.search_get_posts_data'				=> 'search_get_posts_data',
			'core.search_modify_tpl_ary'				=> 'search_modify_tpl_ary',
			'core.search_results_modify_search_title'	=> 'search_modify_search_title',
			'core.ucp_pm_view_messsage'					=> 'ucp_pm_view_messsage',
		);
	}

	/**
	 * Set up the flags and add the lang vars
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function user_setup($event)
	{
			// Need to ensure the flags are cached on page load
			$this->nationalflags->cache_flags();
			$lang_set_ext = $event['lang_set_ext'];
			$lang_set_ext[] = array(
				'ext_name' => 'rmcgirr83/nationalflags',
				'lang_set' => 'common',
			);
			$event['lang_set_ext'] = $lang_set_ext;
	}

	/**
	 * Set up the flags on the index page
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function index_modify_page_title($event)
	{
		if (!$this->config['flags_display_index'] || !$this->nationalflags->display_flags_on_forum())
		{
			return false;
		}
		//display flags on the index page
		$this->nationalflags->top_flags();
	}

	/**
	 * Create URL and message to users if wanted.
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function page_header_after($event)
	{
		if (!$this->auth->acl_get('u_chgprofileinfo'))
		{
			return;
		}

		$page_name = substr($this->user->page['page_name'], 0, strpos($this->user->page['page_name'], '.'));

		if ($page_name == 'ucp')
		{
			return;
		}
		if ($this->config['flags_display_msg'])
		{
			$this->template->assign_vars(array(
				'S_FLAG_MESSAGE'	=> (empty($this->user->data['user_flag'])) ? true : false,
				'L_FLAG_PROFILE'	=> $this->user->lang('USER_NEEDS_FLAG', '<a href="' . append_sid("{$this->root_path}ucp.$this->php_ext", 'i=profile') . '">', '</a>'),
			));
		}
	}

	/**
	 * Allow users to change their flag
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function user_flag_profile($event)
	{
		if (DEFINED('IN_ADMIN'))
		{
			$user_flag = $event['user_row']['user_flag'];
		}
		else
		{
			$user_flag = $this->user->data['user_flag'];
		}

		// Request the user option vars and add them to the data array
		$event['data'] = array_merge($event['data'], array(
			'user_flag'	=> $this->request->variable('user_flag', (int) $user_flag),
		));

		$flags = $this->nationalflags->get_flag_cache();
		$has_default = false;
		foreach ($flags as $flag => $settings)
		{
			if (!empty($settings['flag_default']))
			{
				$has_default = true;
			}
		}

		$this->template->assign_vars(array(
			'FLAG_DEFAULT' => (empty($event['data']['user_flag']) && $has_default) ? true : false,
		));
		$this->display_flag_options($event['data']['user_flag']);
	}

	/**
	 * Validate users changes to their flag
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function user_flag_profile_validate($event)
	{

		if ($event['submit'] && empty($event['data']['user_flag']) && $this->config['flags_required'])
		{
			$array = $event['error'];
			$array[] = $this->user->lang['MUST_CHOOSE_FLAG'];
			$event['error'] = $array;
		}
	}

	/**
	 * User changed their flag so update the database
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function user_flag_profile_sql($event)
	{
		$event['sql_ary'] = array_merge($event['sql_ary'], array(
				'user_flag' => $event['data']['user_flag'],
		));
	}

	/**
	 * Update registration data
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function user_flag_registration_sql($event)
	{
		$event['user_row'] = array_merge($event['user_row'], array(
				'user_flag' => $this->request->variable('user_flag', 0),
		));
	}

	/**
	 * Show users as viewing the flags on Who Is Online page
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function viewonline_page($event)
	{
		if ($event['on_page'][1] == 'app')
		{
			if (strrpos($event['row']['session_page'], 'app.' . $this->php_ext . '/flags') === 0)
			{
				$event['location'] = $this->user->lang('FLAGS_VIEWONLINE');
				$event['location_url'] = $this->helper->route('rmcgirr83_nationalflags_display');
			}
		}
	}

	/**
	 * Load the css file
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function viewtopic_template_vars_before($event)
	{
		$flag_display_position = $this->nationalflags->flag_display_position();

		$this->template->assign_vars(array(
			'S_FLAGS'		=> $this->nationalflags->display_flags_on_forum(),
			$flag_display_position => true,
		));
	}
	/**
	 * Update viewtopic user data
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function viewtopic_cache_user_data($event)
	{
		$array = $event['user_cache_data'];
		$array['user_flag'] = $event['row']['user_flag'];
		$event['user_cache_data'] = $array;
	}

	/**
	 * Update viewtopic guest data
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function viewtopic_cache_guest_data($event)
	{
		$array = $event['user_cache_data'];
		$array['user_flag'] = 0;
		$event['user_cache_data'] = $array;
	}

	/**
	 * Modify the viewtopic post row
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function viewtopic_modify_post_row($event)
	{
		// If setting in ACP is set to not allow guests and bots to view the flags
		if (!$this->nationalflags->display_flags_on_forum())
		{
			return false;
		}

		$flag = $this->nationalflags->get_user_flag($event['user_poster_data']['user_flag']);
		$flags = $this->nationalflags->get_flag_cache();

		$event['post_row'] = array_merge($event['post_row'], array(
			'USER_FLAG' => $flag,
			'FLAG_POSITION'	=> $this->config['flag_position'],
			'U_FLAG'	=> ($flag) ? $this->helper->route('rmcgirr83_nationalflags_getflags', array('flag_id' => $flags[$event['user_poster_data']['user_flag']]['flag_id'])) : '',
		));
	}

	/**
	 * Display flag on viewing user profile
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function memberlist_view_profile($event)
	{
		if (!empty($event['member']['user_flag']) && $this->nationalflags->display_flags_on_forum())
		{
			$flag = $this->nationalflags->get_user_flag($event['member']['user_flag']);
			$flags = $this->nationalflags->get_flag_cache();
			$flag_display_position = $this->nationalflags->flag_display_position();

			$this->template->assign_vars(array(
				'USER_FLAG'		=> $flag,
				'S_FLAGS'		=> true,
				'U_FLAG'		=> ($flag) ? $this->helper->route('rmcgirr83_nationalflags_getflags', array('flag_id' => $flags[$event['member']['user_flag']]['flag_id'])) : '',
				$flag_display_position => true,
			));
		}
	}

	/**
	 * Display flag on search
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function search_get_posts_data($event)
	{
		$array = $event['sql_array'];
		$array['SELECT'] .= ', u.user_flag';
		$event['sql_array'] = $array;
	}

	/**
	 * Display flag on search
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function search_modify_tpl_ary($event)
	{
		if ($event['show_results'] == 'topics' || !$this->nationalflags->display_flags_on_forum())
		{
			return false;
		}

		$array = $event['tpl_ary'];

		$flag = $this->nationalflags->get_user_flag($event['row']['user_flag']);
		$flags = $this->nationalflags->get_flag_cache();

		$array = array_merge($array, array(
			'USER_FLAG'		=> $flag,
			'U_FLAG'		=> ($flag) ? $this->helper->route('rmcgirr83_nationalflags_getflags', array('flag_id' => $flags[$event['row']['user_flag']]['flag_id'])) : '',
		));

		$event['tpl_ary'] = $array;
	}

	/**
	 * Search modify search title
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function search_modify_search_title($event)
	{
		if ($event['show_results'] == 'topics' || !$this->nationalflags->display_flags_on_forum())
		{
			return false;
		}

		$this->template->assign_vars(array(
			'S_FLAGS'		=> true,
		));
	}

	/**
	 * Display flag on viewing PM's
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function ucp_pm_view_messsage($event)
	{
		if (!empty($event['user_info']['user_flag']) && $this->nationalflags->display_flags_on_forum())
		{
			$flag = $this->nationalflags->get_user_flag($event['user_info']['user_flag']);

			$array = $event['msg_data'];
			$array['USER_FLAG'] = $flag;
			$array['U_FLAG'] = ($flag) ? $this->helper->route('rmcgirr83_nationalflags_getflags', array('flag_id' => $event['user_info']['user_flag'])) : '';
			$event['msg_data'] = $array;

			$flag_display_position = $this->nationalflags->flag_display_position();
			$this->template->assign_vars(array(
				'S_FLAGS'		=> true,
				$flag_display_position => true,
			));
		}
	}

	/**
	 * Display flag for user selection
	 *
	 * @param object $user_flag The event object
	 * @return null
	 * @access private
	 */
	private function display_flag_options($user_flag)
	{
		$flags = $this->nationalflags->get_flag_cache();
		$flag_name = $flag_image = '';

		foreach ($flags as $key => $value)
		{
			if ($value['flag_default'])
			{
				$flag_name = $value['flag_name'];
				$flag_image = $value['flag_image'];
			}
		}

		if ($user_flag)
		{
			$flag_name = $flags[$user_flag]['flag_name'];
			$flag_image = $flags[$user_flag]['flag_image'];
		}

		$s_flag_options = $this->nationalflags->list_flags($user_flag);

		$this->template->assign_vars(array(
			'USER_FLAG'		=> $user_flag,
			'FLAG_IMAGE'	=> ($flag_image) ? $this->ext_path . 'flags/' . $flag_image : '',
			'FLAG_NAME'		=> $flag_name,
			'S_FLAG_OPTIONS'	=> $s_flag_options,
			'S_FLAGS'			=> true,
			'S_FLAG_REQUIRED'	=> ($this->config['flags_required']) ? true : false,
			'AJAX_FLAG_INFO' 	=> $this->helper->route('rmcgirr83_nationalflags_getflag', array('flag_id' => 'FLAG_ID')),
		));
	}
}

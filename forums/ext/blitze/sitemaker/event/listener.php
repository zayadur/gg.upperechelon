<?php
/**
 *
 * @package sitemaker
 * @copyright (c) 2013 Daniel A. (blitze)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace blitze\sitemaker\event;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	/** @var \phpbb\cache\driver\driver_interface */
	protected $cache;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var ContainerInterface */
	protected $phpbb_container;

	/** @var \phpbb\request\request_interface */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\language\language */
	protected $translator;

	/** @var \phpbb\user */
	protected $user;

	/* @var \blitze\sitemaker\services\util */
	protected $sitemaker;

	/* @var \blitze\sitemaker\services\blocks\display */
	protected $blocks;

	/** @var string phpBB root path */
	protected $phpbb_root_path;

	/** @var string */
	protected $php_ext;

	/* @var bool */
	protected $is_startpage = false;

	/**
	 * Constructor
	 *
	 * @param \phpbb\cache\driver\driver_interface		$cache					Cache driver interface
	 * @param \phpbb\config\config						$config					Config object
	 * @param \phpbb\request\request_interface			$request				Request object
	 * @param ContainerInterface						$phpbb_container		Service container
	 * @param \phpbb\template\template					$template				Template object
	 * @param \phpbb\language\language					$translator				Language object
	 * @param \phpbb\user								$user					User object
	 * @param \blitze\sitemaker\services\util			$sitemaker				Sitemaker object
	 * @param \blitze\sitemaker\services\blocks\display	$blocks					Blocks display object
	 * @param string									$root_path				phpBB root path
	 * @param string									$php_ext				php file extension
	 */
	public function __construct(\phpbb\cache\driver\driver_interface $cache, \phpbb\config\config $config, \phpbb\request\request_interface $request, ContainerInterface $phpbb_container, \phpbb\template\template $template, \phpbb\language\language $translator, \phpbb\user $user, \blitze\sitemaker\services\util $sitemaker, \blitze\sitemaker\services\blocks\display $blocks, $root_path, $php_ext)
	{
		$this->cache = $cache;
		$this->config = $config;
		$this->request = $request;
		$this->phpbb_container = $phpbb_container;
		$this->template = $template;
		$this->translator = $translator;
		$this->user = $user;
		$this->sitemaker = $sitemaker;
		$this->blocks = $blocks;
		$this->phpbb_root_path = $root_path;
		$this->php_ext = $php_ext;
	}

	/**
	 * @return array
	 */
	public static function getSubscribedEvents()
	{
		return array(
			'core.user_setup'			=> 'init_sitemaker',
			'core.permissions'			=> 'load_permission_language',
			'core.page_header'			=> 'prepend_breadcrump',
			'core.page_footer'			=> 'show_sitemaker',
			'core.adm_page_footer'		=> 'set_assets',
			'core.submit_post_end'		=> 'clear_cached_queries',
			'core.delete_posts_after'	=> 'clear_cached_queries',
			'core.display_forums_modify_sql'		=> 'set_startpage',
			'core.viewonline_overwrite_location'	=> 'add_viewonline_location',
		);
	}

	/**
	 * @param \phpbb\event\data $event
	 */
	public function init_sitemaker(\phpbb\event\data $event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'blitze/sitemaker',
			'lang_set' => 'common',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}

	/**
	 * @param \phpbb\event\data $event
	 */
	public function load_permission_language(\phpbb\event\data $event)
	{
		$permissions = $event['permissions'];
		$permissions['a_sm_settings']	= array('lang' => 'ACL_A_SM_SETTINGS', 'cat' => 'misc');
		$permissions['a_sm_manage_blocks']	= array('lang' => 'ACL_A_SM_MANAGE_BLOCKS', 'cat' => 'misc');
		$permissions['a_sm_manage_menus']	= array('lang' => 'ACL_A_SM_MANAGE_MENUS', 'cat' => 'misc');
		$event['permissions'] = $permissions;
	}

	/**
	 * If start page is set,
	 * - Add "Forum" to navbar
	 * - Add "Forum" to the breadcrump when viewing forum page (viewforum/viewtopic/posting)
	 */
	public function prepend_breadcrump()
	{
		if ($this->config['sitemaker_startpage_controller'])
		{
			$u_viewforum = $this->phpbb_container->get('controller.helper')->route('blitze_sitemaker_forum');

			// show 'Forum' menu item in navbar
			$this->template->assign_vars(array(
				'SM_FORUM_ICON'		=> $this->config['sm_forum_icon'],
				'SM_SHOW_FORUM_NAV'	=> $this->config['sm_show_forum_nav'],
				'U_SM_VIEWFORUM'	=> $u_viewforum,
			));

			// Add "Forum" to breadcrump menu when viewing forum pages (viewforum/viewtopic/posting)
			if ($this->request->is_set('f'))
			{
				$this->template->alter_block_array('navlinks', array(
					'FORUM_NAME'	=> $this->user->lang('FORUM'),
					'U_VIEW_FORUM'	=> $u_viewforum,
				));
			}
		}
	}

	/**
	 * Queries for forum data are cached unless a post is created/edited
	 * The defined constant is used as an indicator of this change so a new request is made instead
	 * @see \blitze\sitemaker\services\forum\data
	 */
	public function clear_cached_queries()
	{
		define('SITEMAKER_FORUM_CHANGED', true);
		$this->cache->destroy('sql', array(FORUMS_TABLE, TOPICS_TABLE, POSTS_TABLE, USERS_TABLE));
	}

	/**
	 * Show sitemaker blocks on front page
	 */
	public function show_sitemaker()
	{
		$this->blocks->show();
		$this->set_assets();

		if ($this->is_startpage)
		{
			$this->template->destroy_block_vars('navlinks');
			$this->template->assign_var('SM_SHOW_FORUM_NAV', $this->config['sm_show_forum_nav']);
		}

		$this->show_hide_index_blocks();
	}

	/**
	 * Send assets to template
	 */
	public function set_assets()
	{
		$this->sitemaker->set_assets();
	}

	/**
	 * @param \phpbb\event\data $event
	 */
	public function set_startpage(\phpbb\event\data $event)
	{
		if ($this->user->page['page_name'] == 'index.' . $this->php_ext && !$this->is_startpage && ($controller_object = $this->get_startpage_controller()) !== false)
		{
			$method = $this->config['sitemaker_startpage_method'];
			$this->is_startpage = true;

			$controller_dir = explode('\\', get_class($controller_object));
			$controller_style_dir = 'ext/' . $controller_dir[0] . '/' . $controller_dir[1] . '/styles';
			$this->template->set_style(array($controller_style_dir, 'styles'));

			$arguments = explode('/', $this->config['sitemaker_startpage_params']);

			/** @type \Symfony\Component\HttpFoundation\Response $response */
			$response = call_user_func_array(array($controller_object, $method), $arguments);
			$response->send();

			$this->exit_handler();
		}

		// Do not show forums marked as hidden
		$event['sql_ary'] = $this->_hide_hidden_forums($event['sql_ary']);
	}

	/**
	 * @param \phpbb\event\data $event
	 */
	public function add_viewonline_location(\phpbb\event\data $event)
	{
		if ($event['on_page'][1] == 'app' && strrpos($event['row']['session_page'], 'app.' . $this->php_ext . '/forum') === 0)
		{
			$event['location'] = $this->translator->lang('FORUM_INDEX');
			$event['location_url'] = $this->phpbb_container->get('controller.helper')->route('blitze_sitemaker_forum');
		}
	}

	/**
	 * @codeCoverageIgnore
	 */
	protected function exit_handler()
	{
		exit_handler();
	}

	/**
	 * @return object|false
	 */
	protected function get_startpage_controller()
	{
		$controller_service_name = $this->config['sitemaker_startpage_controller'];
		if ($this->phpbb_container->has($controller_service_name))
		{
			$controller_object = $this->phpbb_container->get($controller_service_name);
			$method = $this->config['sitemaker_startpage_method'];

			if (is_callable(array($controller_object, $method)))
			{
				return $controller_object;
			}
		}

		// we have a startpage controller but it does not exist or it is not callable so remove it
		if ($controller_service_name)
		{
			$this->config->set('sitemaker_startpage_controller', '');
			$this->config->set('sitemaker_startpage_method', '');
			$this->config->set('sitemaker_startpage_params', '');
		}

		return false;
	}

	/**
	 * Show or hide birthday_list, online users list, and login box on forum index
	 */
	protected function show_hide_index_blocks()
	{
		$hide_login = (bool) $this->config['sm_hide_login'];
		$hide_online = (bool) $this->config['sm_hide_online'];
		$hide_birthday = (bool) $this->config['sm_hide_birthday'];

		if ($this->config['sitemaker_startpage_controller'])
		{
			$hide_online = $hide_birthday = true;
		}

		$this->template->assign_vars(array(
			'S_USER_LOGGED_IN'			=> ($hide_login || $this->user->data['is_registered']),
			'S_DISPLAY_ONLINE_LIST'		=> !$hide_online,
			'S_DISPLAY_BIRTHDAY_LIST'	=> !$hide_birthday,
		));
	}

	/**
	 * @param array $sql_ary
	 * @return array
	 */
	protected function _hide_hidden_forums(array $sql_ary)
	{
		$sql_ary['WHERE'] .= ($sql_ary['WHERE']) ? ' AND ' : '';
		$sql_ary['WHERE'] .= 'f.hidden_forum <> 1';

		return $sql_ary;
	}
}

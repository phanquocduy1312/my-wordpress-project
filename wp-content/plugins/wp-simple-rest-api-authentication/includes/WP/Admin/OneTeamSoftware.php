<?php

declare(strict_types=1);

namespace OneTeamSoftware\WP\Admin;

class OneTeamSoftware
{
	/**
	 * @var OneTeamSoftware
	 */
	private static $instance = null;

	/**
	 * @var string
	 */
	private $mainMenuId;

	/**
	 * @var string
	 */
	private $author;

	/**
	 * @var string
	 */
	private $freePluginsApiUrl;

	/**
	 * @var string
	 */
	private $paidPluginsApiUrl;

	/**
	 * @var bool
	 */
	private $isRegistered;

	/**
	 * @var string
	 */
	private $pluginPath;

	/**
	 * returns instance of the menu
	 *
	 * @return OneTeamSoftware
	 */
	public static function instance(): OneTeamSoftware
	{
		if (!isset(self::$instance)) {
			self::$instance = new OneTeamSoftware();
		}

		return self::$instance;
	}

	/**
	 * constructor
	 */
	public function __construct()
	{
		$this->mainMenuId = 'oneteamsoftware';
		$this->author = 'oneteamsoftware';
		$this->freePluginsApiUrl = 'http://api.wordpress.org/plugins/info/1.0/';
		// NOT IN USE, LEFT FOR THE REFERENCE ONLY
		$this->paidPluginsApiUrl = 'https://1teamsoftware.com/wp-json/wp-plugin-life-server/plugins';
		$this->isRegistered = false;
		$this->pluginPath = dirname(dirname(dirname(str_replace('phar://', '', __FILE__))));
	}

	/**
	 * registers menu in WordPress
	 *
	 * @return void
	 */
	public function register(): void
	{
		if ($this->isRegistered) {
			return;
		}

		$this->isRegistered = true;

		add_action('admin_menu', [$this, 'onAdminMenu'], 2);
	}

	/**
	 * adds menu to WordPress menu
	 *
	 * @return void
	 */
	public function onAdminMenu(): void
	{
		// backward compatibility with older plugins
		global $admin_page_hooks;
		if (isset($admin_page_hooks[$this->mainMenuId])) {
			return;
		}

		add_menu_page(
			'1TeamSoftware',
			'1TeamSoftware',
			'manage_options',
			$this->mainMenuId,
			[$this, 'display'],
			plugins_url('assets/images/1TeamSoftware-icon.png', $this->pluginPath),
			26
		);

		add_submenu_page($this->mainMenuId, 'About', 'About', 'manage_options', $this->mainMenuId);
	}

	/**
	 * adds scripts
	 *
	 * @return void
	 */
	public function enqueueScripts(): void
	{
		$cssExt = defined('WP_DEBUG') && WP_DEBUG ? 'css' : 'min.css' ;

		wp_register_style(
			$this->mainMenuId . '-About',
			plugins_url('assets/css/OneTeamSoftware.' . $cssExt, str_replace('phar://', '', $this->pluginPath)),
		);
		wp_enqueue_style($this->mainMenuId . '-About');
	}

	/**
	 * displays OneTeamSoftware About page
	 *
	 * @return void
	 */
	public function display(): void
	{
		$this->enqueueScripts();

		// @codingStandardsIgnoreStart
		?>
		<div class="wrap <?php echo $this->mainMenuId; ?>">
			<img width="190" height="80" src="<?php echo plugins_url('assets/images/1TeamSoftware-logo.png', $this->pluginPath); ?>" class="header_logo header-logo" alt="1 Team Software">
			<div class="card">
				<h2 class="title">About Us</h2>
				<p><strong>1TeamSoftware</strong> specializes in providing elegant solutions for complex e-commerce challenges. We have over 15 years of software development experience as well as many years of hands on e-commerce experience, which makes us an ideal solutions provider for your e-commerce needs.</p>
				<p>We develop and publish unique and helpful free and premium <a href="https://1teamsoftware.com/woocommerce-extensions/" target="_blank">WooCommerce plugins</a> that will help to boost your sales while reducing your operation expenses.</p>
				<p>Customer satisfaction is our top priority, so we are always working hard to address any customers’ requirements and provide <a href="https://1teamsoftware.com/contact-us" target="_blank">rapid support</a> at every step.</p>
				<p>We are continually listening to the needs of our customers, so our <a href="https://1teamsoftware.com/woocommerce-extensions/" target="_blank">WooCommerce plugins</a> are constantly evolving into more feature-rich and solid products.</p>
				<p><strong>1TeamSoftware</strong> strives to provide best quality useful products and support, so we guarantee your satisfaction by offering 30-day money back guarantee and hassle-free updates.</p>
			</div>
			<div class="card">
				<h2 class="title">Contact Us</h2>
				<p>We are always here to help you with any of our great WooCommerce extensions, as well as with any customization or project you have in mind!</p>
				<span>Contact us using any of the following ways:</span>
				<ul>
					<li><a href="https://1teamsoftware.com/contact-us" target="_blank">Website Contact Form</a></li>
					<li><a href="https://www.facebook.com/1teamsoftware/" target="_blank">Facebook Page</a></li>
				</ul>
			</div>
			<?php $this->displayPlugins(); ?>
		</div>
		<?php
		// @codingStandardsIgnoreEnd
	}

	/**
	 * returns all the plugins
	 *
	 * @return array
	 */
	private function getPlugins(): array
	{
		$cacheKey = ($this->mainMenuId ?? 'oneteamsoftware') . '_plugins';
		$plugins = get_transient($cacheKey);
		if (false === empty($plugins)) {
			return $plugins;
		}

		$plugins = [];
		$plugins = $this->getFreePlugins();
		//$plugins += $this->getPaidPlugins();

		set_transient($cacheKey, $plugins, 24 * HOUR_IN_SECONDS);

		return $plugins;
	}

	/**
	 * returns a list of free plugins
	 *
	 * @return array
	 */
	private function getFreePlugins(): array
	{
		$args = (object)[
			'author' => $this->author,
			'per_page' => '120',
			'page' => '1',
			'fields' => ['slug', 'name', 'version', 'downloaded', 'active_installs', 'homepage'],
		];

		$request = [
			'action' => 'query_plugins',
			'timeout' => 15,
			'request' => serialize($args),
		];

		$response = wp_remote_post($this->freePluginsApiUrl, ['body' => $request]);
		if (is_wp_error($response)) {
			return [];
		}

		$plugins = [];

		$data = unserialize($response['body']);
		if (isset($data->plugins) && (count($data->plugins) > 0)) {
			foreach ($data->plugins as $pluginData) {
				$pluginData = json_decode(json_encode($pluginData), true);
				$plugins[$pluginData['slug']] = $pluginData;
			}
		}

		return $plugins;
	}

	/**
	 * NOT IN USE, LEFT FOR THE REFERENCE ONLY
	 *
	 * returns a list of paid plugins
	 *
	 * @return array
	 */
	private function getPaidPlugins(): array
	{
		$request = [
			'timeout' => 15,
		];

		$response = wp_remote_get($this->paidPluginsApiUrl, ['body' => $request]);
		if (is_wp_error($response)) {
			return [];
		}

		$response = json_decode($response['body'], true);
		if (false === empty($response['code'])) {
			return [];
		}

		$plugins = $response;

		return $plugins;
	}

	/**
	 * displays plugins
	 *
	 * @return void
	 */
	private function displayPlugins(): void
	{
		$plugins = $this->getPlugins();

		if (empty($plugins)) {
			return;
		}
		?>
			<div class="card">
				<h2 class="title">Our Plugins</h2>
				<?php
				$idx = 1;
				foreach ($plugins as $plugin) {
					if (
						false === empty($plugin['homepage']) &&
						false === empty($plugin['name']) &&
						false === empty($plugin['short_description']) &&
						false === empty($plugin['version'])
					) {
						echo sprintf(
							'<div class="item"><a href="%s" target="_blank"><span class="num">%d</span><span class="title">%s</span><p>%s</p><span class="extra">Version %s</span></a></div>', // phpcs:ignore
							esc_url($plugin['homepage']),
							esc_html($idx),
							esc_html($plugin['name']),
							esc_html($plugin['short_description']),
							esc_html($plugin['version'])
						);
						$idx++;
					}
				}
				?>
			</div>
		<?php
	}
}

<?php

declare(strict_types=1);

namespace OneTeamSoftware\WC\Admin\PageForm;

use OneTeamSoftware\WC\Admin\Form\Form;
use OneTeamSoftware\WP\Admin\Notices\Notices;
use OneTeamSoftware\WP\Admin\Page\PageInterface;

abstract class AbstractPageForm implements PageInterface
{
	/**
	 * @var string
	 */
	protected $formId;

	/**
	 * @var string
	 */
	protected $capability;

	/**
	 * @var boolean
	 */
	protected $displaySectionsMenu;

	/**
	 * @var Form
	 */
	protected $form;

	/**
	 * @var Notices
	 */
	protected $notices;

	/**
	 * Constructor
	 *
	 * @param string $formId
	 * @param string $capability
	 * @param string $textDomain
	 */
	public function __construct(string $formId, string $capability = '', string $textDomain = 'woocommerce')
	{
		$this->formId = $formId;
		$this->capability = $capability;
		$this->displaySectionsMenu = false;
		$this->form = new Form([], $textDomain);
		$this->notices = new Notices($formId);

		add_action('init', [$this, 'onInit']);
		add_action('admin_post_' . $formId, [$this, 'onAdminPost']);
	}

	/**
	 * We have to initialize form fields on init event in order for taxonomies to work
	 *
	 * @return void
	 */
	public function onInit(): void
	{
		$this->form->setFields($this->getFormFields());
	}

	/**
	 * Saves current form settings
	 *
	 * @return boolean
	 */
	public function onAdminPost(): bool
	{
		if (!empty($this->capability) && !current_user_can($this->capability)) {
			wp_die('You do not have sufficient permissions to access this page.');
		}

		$inputData = $this->clean(wp_unslash($_POST));

		// make sure that we can verify nonce field
		if (isset($inputData['_wpnonce']) && wp_verify_nonce($inputData['_wpnonce'], $this->formId)) {
			$data = $this->form->filter($inputData);
			$errors = $this->form->getErrors();

			if (!empty($data) && is_array($data)) {
				$result = $this->saveFormData($data);
				if ($result === true) {
					do_action('woocommerce_settings_saved');

					// overwrite/extend original data with new data
					$inputData = array_merge($inputData, $data);

					$fields = $this->getFormFields();

					// remove field's data after we've saved data
					foreach ($fields as $field) {
						if (isset($field['id'])) {
							$key = $field['id'];
							$keyLower = strtolower($key);

							if (isset($inputData[$key])) {
								unset($inputData[$key]);
							}
							if (isset($inputData[$keyLower])) {
								unset($inputData[$keyLower]);
							}
						}
					}
				} elseif (is_array($result)) {
					$errors = $result;
				}
			}

			$notices = $this->notices->notices;
			if (empty($errors) && empty($notices)) {
				$this->notices->type = 'updated';
				$this->notices->displayWithoutNotices = true;
				$this->notices->title = $this->getSuccessMessageText();
			} else {
				$this->notices->type = 'error';
				$this->notices->title = $this->getErrorMessageText();

				foreach ($errors as $error) {
					$this->notices->add($error);
				}
			}
		}

		// build GET query arguments from input data what were not used by the form
		$queryArgs = [];
		foreach ($inputData as $key => $val) {
			if (!empty($val) && !in_array($key, ['action', 'action2', '_wpnonce', '_wp_http_referer'], true)) {
				$queryArgs[$key] = stripslashes_deep($val);
			}
		}

		// use php function to build URL because wordpress one messes up tags
		$redirectUrl = add_query_arg([], 'admin.php');
		if (!empty($queryArgs)) {
			$redirectUrl .= '?' . http_build_query($queryArgs);
		}

		//return wp_redirect(add_query_arg($queryArgs, 'admin.php'));
		return wp_redirect($redirectUrl);
	}

	/**
	 * Displays this form
	 *
	 * @return void
	 */
	public function display(): void
	{
		if (!empty($this->capability) && !current_user_can($this->capability)) {
			wp_die('You do not have sufficient permissions to access this page.');
		}

		$data = $this->getFormData();

		if ($this->displaySectionsMenu) {
			$this->form->displaySectionsMenu();
		}
		?>
		<form method="post" action="admin-post.php" enctype="multipart/form-data">
			<input type="hidden" name="action" value="<?php echo esc_attr($this->formId); ?>" />
			<input type="hidden" name="page" value="<?php echo esc_attr(sanitize_text_field($_GET['page'] ?? '')); ?>" />
			<input type="hidden" name="tab" value="<?php echo esc_attr(sanitize_text_field($_GET['tab'] ?? '')); ?>" />
			<?php
			wp_nonce_field($this->formId);

			foreach ($_REQUEST as $key => $value) {
				if (
					!empty($value) &&
					strpos($key, 'action_') === false &&
					!in_array($key, ['action', 'action2', '_wpnonce', '_wp_http_referer'], true)
				) {
					echo '<input type="hidden" name="' . esc_attr(sanitize_key($key)) . '" value="' . esc_attr(sanitize_text_field($value)) . '" />';
				}
			}

			$this->form->display($data);
			?>
		</form>
		<?php
	}

	/**
	 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
	 * Non-scalar values are ignored.
	 *
	 * @param string|array $var Data to sanitize.
	 * @return string|array
	 */
	protected function clean($var)
	{
		if (is_array($var)) {
			return array_map([$this, 'clean'], $var);
		}

		return is_scalar($var) ? sanitize_text_field($var) : $var;
	}

	/**
	 * Saves data and returns true or false and it can also modify input data
	 *
	 * @param array $data
	 * @return bool
	 */
	abstract protected function saveFormData(array &$data): bool;

	/**
	 * Returns fields for the plugin settings form
	 *
	 * Example:
	 * return array(
	 *		$this->id . '_supplier_start' => array(
	 *			'title' => __('Inventory Supplier Settings', $this->id),
	 *			'type' => 'title',
	 *			'id' => $this->id . '_supplier_start'
	 *		),
	 *       'enabled' => array(
	 *			'id' => 'enabled',
	 *           'title' => __('Enable', $this->id),
	 *           'type' => 'checkbox',
	 *           'desc_tip' => __('Enable this inventory supplier.', $this->id),
	 *			'default' => 'yes',
	 *		),
	 * 		'image_rule' => array(
	 *			'id' => 'image_rule',
	 *           'title' => __('Images', $this->id),
	 *           'placeholder' => __('Rule for parsing product images?', $this->id),
	 *			'type' => 'text',
	 *			'desc_tip' => __('Rule that is used for parsing images of the product.', $this->id),
	 *			'filter' => FILTER_VALIDATE_REGEXP,
	 *			'filter_options' => array('options' => array('regexp' => '/^.{0,255}$/')),
	 *			'optional' => true,
	 *			'sanitize_function' => 'sanitize_text_field',
	 *		),
	 *      $this->id . '_supplier_end' => array(
	 *			'type' => 'sectionend',
	 *			'id' => $this->id . '_supplier_end'
	 *		),
	 * );
	 *
	 * @return array
	 */
	abstract protected function getFormFields(): array;

	/**
	 * Returns data that will be displayed in the form
	 *
	 * @return array
	 */
	protected function getFormData(): array
	{
		return [];
	}

	/**
	 * Return success message
	 *
	 * @return string
	 */
	protected function getSuccessMessageText(): string
	{
		return __('Form data have been successfully saved', 'woocommerce');
	}

	/**
	 * Return error message
	 *
	 * @return string
	 */
	protected function getErrorMessageText(): string
	{
		return __('Unable to save form data', 'woocommerce');
	}
}

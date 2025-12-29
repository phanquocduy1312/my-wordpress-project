<?php

declare(strict_types=1);

namespace OneTeamSoftware\WC\Admin\Form;

class FormFilter
{
	/**
	 * @var array
	 */
	protected $fields;

	/**
	 * @var array
	 */
	protected $errors;

	/**
	 * @var string
	 */
	protected $prefix;

	/**
	 * Constructor
	 *
	 * Example:
	 * array(
	 *		'supplier_start' => array(
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
	 *      'supplier_end' => array(
	 *			'type' => 'sectionend',
	 *			'id' => $this->id . '_supplier_end'
	 *		),
	 *);
	 *
	 * @param array $fields
	 */
	public function __construct(array $fields = [])
	{
		$this->fields = $fields;
		$this->errors = [];
		$this->prefix = '';
	}

	/**
	 * sets prefix
	 *
	 * @param string $prefix
	 * @return void
	 */
	public function setPrefix(string $prefix): void
	{
		$this->prefix = $prefix;
	}

	/**
	 * set fields used for filtering
	 *
	 * @param array $fields
	 * @return void
	 */
	public function setFields(array $fields): void
	{
		$this->fields = $fields;
	}

	/**
	 * returns fields filled with data
	 *
	 * @param array $data
	 * @return array
	 */
	public function getFields(array $data = []): array
	{
		return $this->fillFields($data);
	}

	/**
	 * returns array of errors
	 *
	 * @return array
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

	/**
	 * filters given data and return filtered result
	 *
	 * @param array $data
	 * @param bool $allowErrors
	 * @return array
	 */
	public function filter(array $data, bool $allowErrors = false): array
	{
		$this->errors = [];

		$values = [];

		foreach ($this->fields as $key => $field) {
			$dataKey = $this->getFieldDataKey($field);
			if (empty($dataKey)) {
				continue;
			}

			$value = $this->getFieldValue($data, $dataKey, $field);

			if ($value === false) {
				$label = $key;
				if (false === empty($field['title'])) {
					$label = $field['title'];
				} elseif (false === empty($field['label'])) {
					$label = $field['label'];
				}

				$this->errors[$key] = $label . ' ' . __(' is invalid', 'woocommerce');
			} else {
				$values = $this->setValueTo($values, $dataKey, $value);
			}
		}

		if (false === empty($this->errors) && !$allowErrors) {
			$values = [];
		}

		return $values;
	}

	/**
	 * helper that fills fields with data
	 *
	 * @param array $data
	 * @return array
	 */
	protected function fillFields(array $data): array
	{
		$data = $this->filter($data, true);
		if (empty($data) || !is_array($data)) {
			return $this->fields;
		}

		$fields = $this->fields;

		foreach ($fields as $key => $field) {
			$dataKey = $this->getFieldDataKey($field);
			if (empty($dataKey)) {
				continue;
			}

			$value = $this->getFieldValue($data, $dataKey, $field);

			if (isset($value)) {
				if ($field['type'] === 'checkbox') {
					if (empty($value)) {
						$field['value'] = $field['default'] = 'no';
					} else {
						$field['value'] = $field['default'] = 'yes';
					}
				} else {
					if (empty($value) && !is_numeric($value)) {
						$field['value'] = $field['default'] = '';
					} else {
						$field['value'] = $field['default'] = $value;
					}
				}
			}

			$fields[$key] = $field;
		}

		return $fields;
	}

	/**
	 * returns data key from a given field
	 *
	 * @param array $field
	 * @return string
	 */
	protected function getFieldDataKey(array $field): string
	{
		if (empty($field['type']) || in_array($field['type'], ['title', 'sectionend', 'submit'], true)) {
			return '';
		}

		$key = '';
		if (isset($field['id'])) {
			$key = $field['id'];
		} elseif (isset($field['name'])) {
			$key = $field['name'];
		}

		return $key;
	}

	/**
	 * returns a value from a given field
	 *
	 * @param array $value
	 * @param string $key
	 * @param array $field
	 * @return mixed
	 */
	protected function getFieldValue(array $value, string $key, array $field)
	{
		$value = $this->getValueFrom($value, $key);

		if ($field['type'] === 'checkbox') {
			if (isset($value)) {
				$value = filter_var($value, FILTER_VALIDATE_BOOLEAN) === true ? 1 : 0;
			} else {
				$value = 0;
			}
		}

		if (false === empty($field['filter'])) {
			$filter = $field['filter'];
			$filterOptions = isset($field['filter_options']) ? $field['filter_options'] : [];

			if (empty($value)) {
				if (empty($field['optional']) && $field['type'] !== 'checkbox') {
					$value = false;
				}
			} else {
				$value = filter_var($value, $filter, $filterOptions);
			}
		}

		if ($value !== false && isset($field['options'])) {
			foreach ((array)$value as $optionToCheck) {
				if (!isset($field['options'][$optionToCheck])) {
					$value = false;

					break;
				}
			}
		}

		if ($value !== false && isset($field['sanitize_function']) && function_exists($field['sanitize_function'])) {
			if (is_array($value)) {
				$value = array_map($field['sanitize_function'], $value);
			} else {
				$value = call_user_func($field['sanitize_function'], $value);
			}
		}

		// stripping slashes breaks \n and \r that might be the part of JSON contents of the the fields
		//if (is_string($value) && !is_numeric($value)) {
		//	$value = stripslashes_deep($value);
		//}

		return $value;
	}

	/**
	 * returns a value from a given array for a requested key
	 *
	 * @param array $value
	 * @param string $key
	 * @return mixed
	 */
	protected function getValueFrom(array $value, string $key)
	{
		$keyParts = explode('[', $this->prefix . $key);

		$currentValue = $value;
		foreach ($keyParts as $keyPart) {
			$keyPart = trim($keyPart, ']');
			if (!isset($currentValue[$keyPart])) {
				$currentValue = null;
				break;
			}

			$currentValue = &$currentValue[$keyPart];
		}

		return $currentValue;
	}

	/**
	 * sets value inside of a given array and returns modified array
	 *
	 * @param array $data
	 * @param string $key
	 * @param mixed $value
	 * @return array
	 */
	protected function setValueTo(array $data, string $key, $value): array
	{
		if (empty($key)) {
			return $data;
		}

		$keyParts = explode('[', $key);

		$valueRef = &$data; // phpcs:ignore
		foreach ($keyParts as $keyPart) {
			$keyPart = trim($keyPart, ']');
			$valueRef = &$valueRef[$keyPart];
		}

		$valueRef = $value;

		return $data;
	}
}

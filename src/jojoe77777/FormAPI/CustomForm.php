<?php

declare(strict_types=1);

namespace jojoe77777\FormAPI;

use pocketmine\form\FormValidationException;

class CustomForm extends Form {

	private array $labelMap = [];
	private array $validationMethods = [];
	/** @var bool[] */
	private array $readonlyMap = [];

	/**
	 * @param callable|null $callable
	 */
	public function __construct(?callable $callable) {
		parent::__construct($callable);
		$this->data["type"] = "custom_form";
		$this->data["title"] = "";
		$this->data["content"] = [];
	}

	/**
	 * Processes the data received from the client.
	 * This method now takes into account readonly elements (which are not part of the response)
	 *
	 * @param mixed $data
	 * @throws FormValidationException
	 */
	public function processData(&$data): void {
		if($data !== null && !is_array($data)) {
			throw new FormValidationException("Expected an array response, got " . gettype($data));
		}
		if(is_array($data)) {
			// Count expected responses only for non-readonly elements
			$expectedCount = 0;
			foreach($this->readonlyMap as $readonly) {
				if(!$readonly) {
					$expectedCount++;
				}
			}
			if(count($data) !== $expectedCount) {
				throw new FormValidationException("Expected an array response with the size " . $expectedCount . ", got " . count($data));
			}
			$new = [];
			$dataIndex = 0;
			foreach($this->validationMethods as $i => $validationMethod) {
				if($this->readonlyMap[$i] === true) {
					// Readonly element: always assign null
					$new[$this->labelMap[$i]] = null;
				} else {
					$value = $data[$dataIndex];
					if(!$validationMethod($value)) {
						throw new FormValidationException("Invalid type given for element " . $this->labelMap[$i]);
					}
					$new[$this->labelMap[$i]] = $value;
					$dataIndex++;
				}
			}
			$data = $new;
		}
	}

	/**
	 * Sets the title of the form.
	 *
	 * @param string $title
	 */
	public function setTitle(string $title): void {
		$this->data["title"] = $title;
	}

	/**
	 * @return string
	 */
	public function getTitle(): string {
		return $this->data["title"];
	}

	/**
	 * Adds a read-only label.
	 *
	 * @param string      $text
	 * @param string|null $label
	 */
	public function addLabel(string $text, ?string $label = null): void {
		$this->addContent(["type" => "label", "text" => $text]);
		$this->labelMap[] = $label ?? (string)count($this->labelMap);
		$this->validationMethods[] = static fn($v) => $v === null;
		$this->readonlyMap[] = true;
	}

	/**
	 * Adds a toggle control.
	 *
	 * @param string      $text
	 * @param bool|null   $default
	 * @param string|null $label
	 */
	public function addToggle(string $text, bool $default = null, ?string $label = null): void {
		$content = ["type" => "toggle", "text" => $text];
		if($default !== null) {
			$content["default"] = $default;
		}
		$this->addContent($content);
		$this->labelMap[] = $label ?? (string)count($this->labelMap);
		$this->validationMethods[] = static fn($v) => is_bool($v);
		$this->readonlyMap[] = false;
	}

	/**
	 * Adds a slider control.
	 *
	 * @param string      $text
	 * @param int         $min
	 * @param int         $max
	 * @param int         $step
	 * @param int         $default
	 * @param string|null $label
	 */
	public function addSlider(string $text, int $min, int $max, int $step = -1, int $default = -1, ?string $label = null): void {
		$content = ["type" => "slider", "text" => $text, "min" => $min, "max" => $max];
		if($step !== -1) {
			$content["step"] = $step;
		}
		if($default !== -1) {
			$content["default"] = $default;
		}
		$this->addContent($content);
		$this->labelMap[] = $label ?? (string)count($this->labelMap);
		$this->validationMethods[] = static fn($v) => (is_float($v) || is_int($v)) && $v >= $min && $v <= $max;
		$this->readonlyMap[] = false;
	}

	/**
	 * Adds a step slider control.
	 *
	 * @param string      $text
	 * @param array       $steps
	 * @param int         $defaultIndex
	 * @param string|null $label
	 */
	public function addStepSlider(string $text, array $steps, int $defaultIndex = -1, ?string $label = null): void {
		$content = ["type" => "step_slider", "text" => $text, "steps" => $steps];
		if($defaultIndex !== -1) {
			$content["default"] = $defaultIndex;
		}
		$this->addContent($content);
		$this->labelMap[] = $label ?? (string)count($this->labelMap);
		$this->validationMethods[] = static fn($v) => is_int($v) && isset($steps[$v]);
		$this->readonlyMap[] = false;
	}

	/**
	 * Adds a dropdown control.
	 *
	 * @param string      $text
	 * @param array       $options
	 * @param int|null    $default
	 * @param string|null $label
	 */
	public function addDropdown(string $text, array $options, int $default = null, ?string $label = null): void {
		$content = ["type" => "dropdown", "text" => $text, "options" => $options];
		if($default !== null) {
			$content["default"] = $default;
		}
		$this->addContent($content);
		$this->labelMap[] = $label ?? (string)count($this->labelMap);
		$this->validationMethods[] = static fn($v) => is_int($v) && isset($options[$v]);
		$this->readonlyMap[] = false;
	}

	/**
	 * Adds an input field.
	 *
	 * @param string      $text
	 * @param string      $placeholder
	 * @param string|null $default
	 * @param string|null $label
	 */
	public function addInput(string $text, string $placeholder = "", string $default = null, ?string $label = null): void {
		$this->addContent(["type" => "input", "text" => $text, "placeholder" => $placeholder, "default" => $default]);
		$this->labelMap[] = $label ?? (string)count($this->labelMap);
		$this->validationMethods[] = static fn($v) => is_string($v);
		$this->readonlyMap[] = false;
	}

	/**
	 * Adds a divider control (read-only).
	 */
	public function addDivider(): void {
		$this->addContent(["type" => "divider", "text" => ""]);
		$this->labelMap[] = (string)count($this->labelMap);
		$this->validationMethods[] = static fn($v) => $v === null;
		$this->readonlyMap[] = true;
	}

	/**
	 * Adds a header control (read-only) with larger font.
	 *
	 * @param string      $text
	 * @param string|null $label
	 */
	public function addHeader(string $text, ?string $label = null): void {
		$this->addContent(["type" => "header", "text" => $text]);
		$this->labelMap[] = $label ?? (string)count($this->labelMap);
		$this->validationMethods[] = static fn($v) => $v === null;
		$this->readonlyMap[] = true;
	}

	/**
	 * Adds a content element to the form.
	 *
	 * @param array $content
	 */
	private function addContent(array $content): void {
		$this->data["content"][] = $content;
	}
}

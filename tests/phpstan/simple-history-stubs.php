<?php
/**
 * Signatures of the Simple History classes our logger uses, for PHPStan only
 * (phpstan.neon.dist scanFiles). Simple History is optional at runtime. Never
 * loaded, not shipped. Same approach as CMS Tree Page View.
 *
 * @package SimpleSEO
 */

namespace Simple_History\Loggers;

abstract class Logger {
	/** @var string */
	public $slug = '';

	/** @return array<string, mixed> */
	abstract public function get_info();

	/** @return void */
	public function loaded() {}

	/**
	 * @param string               $message Message key.
	 * @param array<string, mixed> $context Context.
	 * @return mixed
	 */
	public function info_message( $message, array $context = array() ) {}
}

namespace Simple_History\Event_Details;

class Event_Details_Group {
	/** @param Event_Details_Item[] $items Items. @return static */
	public function add_items( $items ) {}

	/** @param object $formatter Formatter. @return static */
	public function set_formatter( $formatter ) {}
}

class Event_Details_Group_Diff_Table_Formatter {}

class Event_Details_Item {
	/**
	 * @param string|string[]|null $slug_or_slugs Context key(s).
	 * @param string|null          $name          Label.
	 */
	public function __construct( $slug_or_slugs = null, $name = null ) {}
}

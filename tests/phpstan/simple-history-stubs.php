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

class Event_Details_Container {
	/**
	 * @param Event_Details_Group|Event_Details_Group[] $group_or_groups Group(s).
	 * @param array<string, mixed>                      $context         Event context.
	 */
	public function __construct( $group_or_groups = [], $context = [] ) {}

	/** @return string */
	public function to_html() {}
}

class Event_Details_Item_Image_Diff_Table_Row_Formatter {
	/** @param string $src URL. @param string $caption Caption. @return static */
	public function set_new_image( $src, $caption = '' ) {}

	/** @param string $src URL. @param string $caption Caption. @return static */
	public function set_prev_image( $src, $caption = '' ) {}

	/** @param string $size 'default' or 'small'. @return static */
	public function set_size( $size ) {}
}

class Event_Details_Item {
	/** @param object $formatter Formatter. @return static */
	public function set_formatter( $formatter ) {}

	/**
	 * @param string|string[]|null $slug_or_slugs Context key(s).
	 * @param string|null          $name          Label.
	 */
	public function __construct( $slug_or_slugs = null, $name = null ) {}
}

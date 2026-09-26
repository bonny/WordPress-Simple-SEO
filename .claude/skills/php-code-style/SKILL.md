---
name: php-code-style
description: Use when writing or reviewing PHP in Simple SEO, especially code that outputs HTML. House style: namespaced functions, braces not `if (): endif;`, PHP on its own lines, printf for small markup, early returns, YAGNI.
---

# PHP code style (Simple SEO)

The house style, taken from Simple History's `code-quality` skill (`php-standards.md`) and CMS Tree Page View, plus what was decided for Simple SEO 1.0 (2026-09-25). PHPCS enforces the parts marked (lint).

## Structure

- `simple-seo.php` is a bootstrap that must parse on PHP 5.6: no types, no `??`, no `fn`, no short arrays there. Everything else lives in `src/`.
- `src/` is `namespace SimpleSEO;`, PHP 7.4 syntax, one file per concern. Plain functions; no classes, containers or abstractions until something actually needs them.
- Hooks at the top of the file, with `__NAMESPACE__ . '\\function_name'`.
- Type declarations on parameters and returns where WordPress always passes that type. Leave a hook callback's parameter untyped when core may pass something else (for example `get_pages` can pass `false`), and say so in the docblock.
- Short arrays `[]` (lint allows both, use `[]` in `src/`).
- Yoda conditions are optional (lint off). Put the constant first when comparing strings, as the existing code does: `'' !== $title`.
- YAGNI: no options, filters or "extension points" for features nobody asked for.

## Control flow

- Braces, never the alternative syntax (`if (): ... endif;`, `foreach: ... endforeach;`). (lint: `Universal.ControlStructures.DisallowAlternativeSyntax`)
- Early returns, happy path last, no `else` after a `return`.

## Outputting HTML

Small markup (a line or two): build it in PHP with `printf()` and escaping, as CMS Tree Page View does.

```php
if ( $other_plugin ) {
	printf(
		'<p class="description">%s</p>',
		esc_html( sprintf( __( '%s is active…', 'simple-seo' ), $other_plugin ) )
	);
}
```

Bigger markup: close PHP and write HTML, but every PHP statement or control structure goes in its own `<?php … ?>` block on separate lines. Only a single `echo`/escaping call may sit inline, inside HTML attributes or text.

```php
<?php
foreach ( $items as $item ) {
	?>
	<label>
		<input type="checkbox" name="<?php echo esc_attr( $item['name'] ); ?>" <?php checked( $item['on'] ); ?> />
		<?php echo esc_html( $item['label'] ); ?>
	</label>
	<?php
}
?>
```

Not this:

```php
<?php if ( $other_plugin ) : ?>
	<p>…</p>
<?php endif; ?>

<?php foreach ( $items as $item ) { ?>
	…
<?php } ?>

<?php $count = count( $items ); ?>
```

- Use `checked()`, `selected()`, `disabled()` instead of hand-built `checked='checked'`.
- Escape late, at output: `esc_html()`, `esc_attr()`, `esc_url()`. Translate and escape together: `esc_html__()`, `esc_html_e()`.

## Checks

`composer check` (PHPCS + PHPStan level 5, no baseline) must be clean. After touching `simple-seo.php`, also lint it on PHP 5.6: `docker run --rm -v "$PWD":/app -w /app php:5.6-cli php -l simple-seo.php`.

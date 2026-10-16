=== LibreBlocks Core ===
Contributors: libreblocks
Tags: custom fields, dynamic content, blocks, elementor
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 0.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Shared foundation for the LibreBlocks plugin family.

== Description ==

LibreBlocks Core provides the services used by LibreForge, LibreSift, LibreForms and the other LibreBlocks plugins:

* Field framework (24 field types with sanitization, validation and conditional logic)
* Dynamic tokens such as {{object.id}}, {{user.meta|city}} and {{date.str|+7 days}}
* Loop context that tracks the current object in nested listings
* Versioned custom tables
* REST API under libreblocks/v1
* The shared LibreBlocks admin menu and dashboard

On its own it adds only the dashboard. Install a LibreBlocks product to get features.

== Changelog ==

= 0.1.1 =
* Brazilian Portuguese (pt_BR) translation.

= 0.1.0 =
* First development release.

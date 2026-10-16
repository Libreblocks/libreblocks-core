=== LibreBlocks Core ===
Contributors: libreblocks
Tags: custom fields, dynamic content, blocks, elementor
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 0.3.1
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

= 0.3.1 =
* UI kit: collapsible sections, setting rows and an actions sidebar for editors.

= 0.3.0 =
* New admin look: top bar with tabs, stat cards, card lists, side menu editors and a sticky save bar.
* Each LibreBlocks plugin gets its own menu with its own icon.
* Shared UI kit for all LibreBlocks plugins.

= 0.2.0 =
* Shared React field renderer for admin screens (posts, terms, users, options pages).
* REST endpoint to search posts, terms and users for picker fields.

= 0.1.1 =
* Brazilian Portuguese (pt_BR) translation.

= 0.1.0 =
* First development release.

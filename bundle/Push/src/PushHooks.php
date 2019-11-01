<?php

/**
 * Static class for hooks handled by the Push extension.
 *
 * @since 0.1
 *
 * @file Push.hooks.php
 * @ingroup Push
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Karima Rafes < karima.rafes@gmail.com >
 */
final class PushHooks {

	/**
	 * Adds a link to Admin Links page.
	 *
	 * @since 0.1
	 *
	 * @param Object &$admin_links_tree
	 * @return true
	 */
	public static function addToAdminLinks( &$admin_links_tree ) {
		$ioSection = $admin_links_tree->getSection( wfMessage( 'adminlinks_importexport' )->text() );
		$mainRow = $ioSection->getRow( 'main' );
		$mainRow->addItem( ALItem::newFromSpecialPage( 'Push' ) );

		return true;
	}
}

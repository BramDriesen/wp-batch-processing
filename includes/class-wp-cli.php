<?php

if (!defined('ABSPATH')) {
	die('Direct access is not allowed.');
}

/**
 * Class Batch_Processor_CLI_Command().
 */
class Batch_Processor_CLI_Command extends WP_CLI_Command {

	/**
	 * Process a registered batch by ID.
	 *
	 * ## OPTIONS
	 *
	 * <batch-id>
	 * : The unique ID of the batch to run.
	 *
	 * ## EXAMPLES
	 *
	 * wp batch_process email_post_authors
	 */
	public function __invoke( $args, $assoc_args ) {
		list( $batch_id ) = $args;
		do_action( 'wp_batch_processing_init' );

		$batch = WP_Batch_Processor::get_instance()->get_batch($batch_id);
		if ( !$batch ) {
			WP_CLI::error( "Batch with ID '{$batch_id}' not found." );
			return;
		}

		WP_CLI::log( "Starting processing batch: {$batch_id} with {$batch->get_items_count()} items" );

		$batch_items = $batch->get_all_items();
		$progress = WP_CLI\Utils\make_progress_bar( "Processing batch items", $batch->get_items_count() );

		/** @var WP_Batch_Item $item */
		foreach ( $batch_items as $item ) {
			// Process each item in the batch.
			try {
				if ( !$batch->is_processed( $item ) ) {
					$batch->process( $item );
					$batch->mark_as_processed( $item->id );
				}
			} catch ( Exception $e ) {
				WP_CLI::warning( "Error processing item ID {$item->id}: " . $e->getMessage() );
			}
			$progress->tick();
		}

		WP_CLI::success( "Batch '{$batch_id}' processing complete." );
	}
}

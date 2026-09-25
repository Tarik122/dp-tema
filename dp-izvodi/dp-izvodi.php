<?php
/**
 * Plugin Name: DP izvodi
 * Description: Jednim klikom upisuje gotove izvode (kratke opise) u članke Druge perspektive. Alati → DP izvodi. Poslije upisa dodatak se može deaktivirati i obrisati; izvodi ostaju u člancima.
 * Version: 1.0.0
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * Author: Druga perspektiva
 * Text Domain: dp-izvodi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Izvodi iz izvodi.json: id, slug i naslov članka sa starog sajta, plus tekst izvoda. */
function dp_izvodi_data() {
	$json = file_get_contents( __DIR__ . '/izvodi.json' );
	$data = json_decode( $json, true );
	return is_array( $data ) ? $data : array();
}

/**
 * Pronalazi članak za jedan izvod: prvo po ID-u (ako se i slug ili naslov
 * slažu), zatim po slugu, zatim po tačnom naslovu. Vraća WP_Post ili null.
 */
function dp_izvodi_find_post( $item ) {
	$post = get_post( (int) $item['id'] );
	if ( $post && 'post' === $post->post_type && ( $post->post_name === $item['slug'] || $post->post_title === $item['title'] ) ) {
		return $post;
	}

	$found = get_posts(
		array(
			'name'           => $item['slug'],
			'post_type'      => 'post',
			'post_status'    => 'any',
			'posts_per_page' => 1,
		)
	);
	if ( $found ) {
		return $found[0];
	}

	$found = get_posts(
		array(
			'title'          => $item['title'],
			'post_type'      => 'post',
			'post_status'    => 'any',
			'posts_per_page' => 1,
		)
	);
	return $found ? $found[0] : null;
}

function dp_izvodi_menu() {
	add_management_page( 'DP izvodi', 'DP izvodi', 'edit_others_posts', 'dp-izvodi', 'dp_izvodi_page' );
}
add_action( 'admin_menu', 'dp_izvodi_menu' );

function dp_izvodi_page() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		return;
	}

	$items   = dp_izvodi_data();
	$message = '';

	if ( isset( $_POST['dp_izvodi_run'] ) && check_admin_referer( 'dp_izvodi_run' ) ) {
		$overwrite = ! empty( $_POST['dp_izvodi_overwrite'] );
		$written   = 0;
		foreach ( $items as $item ) {
			$post = dp_izvodi_find_post( $item );
			if ( ! $post || ! current_user_can( 'edit_post', $post->ID ) ) {
				continue;
			}
			if ( '' !== trim( $post->post_excerpt ) && ! $overwrite ) {
				continue;
			}
			if ( trim( $post->post_excerpt ) === $item['excerpt'] ) {
				continue;
			}
			$result = wp_update_post(
				wp_slash(
					array(
						'ID'           => $post->ID,
						'post_excerpt' => $item['excerpt'],
					)
				),
				true
			);
			if ( ! is_wp_error( $result ) ) {
				$written++;
			}
		}
		$message = sprintf( 'Upisano izvoda: %d.', $written );
	}

	$rows  = array();
	$count = array( 'upis' => 0, 'ima' => 0, 'nema' => 0 );
	foreach ( $items as $item ) {
		$post = dp_izvodi_find_post( $item );
		if ( ! $post ) {
			$status = 'nema';
		} elseif ( trim( $post->post_excerpt ) === $item['excerpt'] ) {
			$status = 'gotovo';
		} elseif ( '' !== trim( $post->post_excerpt ) ) {
			$status = 'ima';
		} else {
			$status = 'upis';
		}
		if ( isset( $count[ $status ] ) ) {
			$count[ $status ]++;
		}
		$rows[] = array( $item, $post, $status );
	}

	$labels = array(
		'upis'   => 'Biće upisan',
		'gotovo' => 'Upisan',
		'ima'    => 'Već ima drugi izvod (ne dira se)',
		'nema'   => 'Članak nije pronađen',
	);
	?>
	<div class="wrap">
		<h1>DP izvodi</h1>
		<?php if ( $message ) : ?>
			<div class="notice notice-success"><p><?php echo esc_html( $message ); ?></p></div>
		<?php endif; ?>

		<p>Ovaj dodatak upisuje gotove izvode (kratke opise) u članke. Tema ih prikazuje kao podnaslov ispod naslova članka i kao opis na naslovnici, u rubrikama i u pretrazi.</p>
		<p>Članci koji već imaju izvod se ne diraju, osim ako uključite opciju ispod. Poslije upisa dodatak možete deaktivirati i obrisati; izvodi ostaju u člancima i možete ih mijenjati kao i do sada (članak → desna kolona → <strong>Izvod</strong>).</p>

		<form method="post">
			<?php wp_nonce_field( 'dp_izvodi_run' ); ?>
			<p><label><input type="checkbox" name="dp_izvodi_overwrite" value="1"> Zamijeni i izvode koje su članci već imali (<?php echo (int) $count['ima']; ?>)</label></p>
			<p><button type="submit" name="dp_izvodi_run" value="1" class="button button-primary">Upiši izvode (<?php echo (int) $count['upis']; ?>)</button></p>
		</form>

		<table class="widefat striped" style="max-width:1100px">
			<thead><tr><th>Članak</th><th>Izvod</th><th>Stanje</th></tr></thead>
			<tbody>
			<?php foreach ( $rows as $row ) : list( $item, $post, $status ) = $row; ?>
				<tr>
					<td>
						<?php if ( $post ) : ?>
							<a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $item['title'] ); ?>
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( $item['excerpt'] ); ?></td>
					<td><?php echo esc_html( $labels[ $status ] ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

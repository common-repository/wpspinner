<?php
isset( $_GET['debug'] ) or header( 'Content-Type:text/css' );

if ( ! isset( $_GET['post_id'] ) && ! isset( $_GET['spin_id'] ) ) {//admin side
	$colors = isset( $_GET['colors'] ) ? explode( ';', ' ;' . $_GET['colors'] ) : array( 'red' );
	unset( $colors[0] );
	$size             = isset( $_GET['size'] ) ? (int) $_GET['size'] : 500;
	$img_size         = isset( $_GET['img_size'] ) ? (int) $_GET['img_size'] : 30;
	$img_y            = isset( $_GET['img_y'] ) ? (int) $_GET['img_y'] : 3;
	$spin_items_count = count( $colors );
	$text_position    = isset( $_GET['text_position'] ) ? (int) $_GET['text_position'] : 20;
	$spin_duration    = isset( $_GET['duration'] ) ? (int) $_GET['duration'] . 'ms' : '2500ms';
	$template         = isset( $_GET['template'] ) ? (int) $_GET['template'] : 1;
	$border           = isset( $_GET['border'] ) ? explode( ';', $_GET['border'] ) : array( 0, '' );
	$rotate           = isset( $_GET['rotate'] ) ? (int) $_GET['rotate'] : - 90;
	$img_x            = isset( $_GET['img_x'] ) ? (int) $_GET['img_x'] : 0;
	$font_size        = isset( $_GET['font_size'] ) ? (int) $_GET['font_size'] : 0;
	$border_size      = $border[0];
	$border_color     = $border[1];

	$no_image         = '../assets/images/no-image.png';
	$no_image_cropped = '../assets/images/no-image-cropped.png';

	$ID       = '[id^="gh_roulette_"]';
	$is_admin = true;
} else {// front side
	defined( 'ABSPATH' ) or exit;
	global $sp_gh;

	if ( isset( $_GET['post_id'] ) ) {
		preg_match( '/spin[-_]gh\s+id=[\'"]*(\d+)/s', get_post_field( 'post_content', (int) $_GET['post_id'] ), $matches );
		if ( empty( $matches ) ) {
			exit;
		}
		$spin_id = (int) $matches[1];

	} else {
		$spin_id = (int) $_GET['spin_id'];
	}

	$ID = '[id^="gh_roulette_' . $spin_id . '_"]';

	$opt              = $sp_gh->getSpinOptions( $spin_id );
	$colors           = array_map( function ( $item ) {
		return $item['color'];
	}, $opt['items'] );
	$size             = $opt['size'];
	$img_size         = $opt['img_size'];
	$img_y            = $opt['img_y'];
	$img_x            = $opt['img_x'];
	$spin_items_count = $opt['spin_items_count'];
	$text_position    = $opt['text_position'];
	$spin_duration    = $opt['duration'] . 'ms';
	$template         = $opt['template'];
	$border_size      = $opt['border_size'];
	$border_color     = $opt['border_color'];
	$rotate           = $opt['rotate'];
	$font_size        = $opt['font_size'];

	$no_image         = SPIN_URL . 'assets/images/no-image.png';
	$no_image_cropped = SPIN_URL . 'assets/images/no-image-cropped.png';
	$is_admin         = false;
}

if ( empty( $colors ) ) {
	return;
}


$template == 4 ? $text_position += 260 : $text_position += 80;

$unit  = 'px';
$D     = $size;
$COUNT = count( $colors );
$R     = $size * 0.5;
$DELTA = 360 / $COUNT;


ob_start();
?>
    @import url('https://fonts.googleapis.com/css?family=Roboto');


<?php echo $ID ?>.roulette {
    font-family: 'Roboto', sans-serif;
    position: relative;
    z-index: 1;
    font-size: 1em;
    width: <?php echo $D . $unit ?>;
    height: <?php echo $D . $unit ?>;
    margin: <?php echo $border_size . $unit ?> auto;
    }

<?php echo $ID ?>.roulette .gh_spinner {
    width: <?php echo $D . $unit ?>;
    height: <?php echo $D . $unit ?>;
    position: relative;
    transition: transform <?php echo $spin_duration ?> cubic-bezier(0, 1, 0.21, 1.005);
    transform: rotate(0deg);
    transform-origin: <?php echo $R . $unit . ' ' . $R . $unit ?>;
    overflow: <?php echo $template == 2 ? 'visible' : 'hidden' ?>;
    box-shadow:  0 0 0 <?php echo $border_size ?>px  <?php echo $template != 3 ? $border_color : $border_color . 'ba' ?>;
    border-radius: 50%;
    }

<?php for ( $t = 1; $t <= $COUNT; $t ++ ) : ?>
	<?php echo $ID ?>.roulette .gh_spinner.index-<?php echo $t ?> {
    transform: rotate(<?php echo 5760 + ( $t + 0.525 ) * $DELTA * - 1 ?>deg) !important;
    }

	<?php echo $ID ?>.roulette .gh_spinner.spin.index-<?php echo $t ?> {
    transform: rotate( <?php echo 2880 + ( $t + 0.525 ) * $DELTA * - 1 ?>deg) !important;
    }

	<?php echo $ID ?>.roulette .gh_spinner.index-<?php echo $t ?> .triangle:nth-of-type(<?php echo $t ?>) .content {
    opacity: 1;
    transition: all .6s <?php echo $spin_duration ?>;
    }
<?php endfor; ?>


<?php echo $ID ?>.roulette .pres_spin_anim.anim{
    animation-name: gh_spinner_amim;
    animation-duration: 0.19s;
    animation-timing-function: linear;
    animation-iteration-count: infinite;
    }

    @keyframes gh_spinner_amim {
    from{
    transform: rotate(0)
    }

    to{
    transform: rotate(360deg)
    }
    }


<?php echo $ID ?>.roulette .triangle {
    position: absolute;
    width: 0;
    height: 0;
    top: <?php echo - $R . $unit ?>;
    left: <?php echo $R . $unit ?>;
    transform-origin: 0% <?php echo $D . $unit ?>;

    border: 0 solid transparent;

    border-top-width: <?php echo ( $D + $D * 0.0025 ) . $unit //0.0025 extra :D?> ;
    border-right-width:<?php echo ( $D / ( 1 / tan( $DELTA * deg2rad( 1 ) ) ) ) . $unit ?>;

    }

<?php for ( $t = 1; $t <= $COUNT; $t ++ ) : ?>
	<?php echo $ID ?>.roulette .triangle:nth-of-type(<?php echo $t ?>) {
    color: <?php echo $colors[ $t ] ?>;
    border-top-color: <?php echo $colors[ $t ] ?>;
	<?php if ( $template == 4 ) {
		if ( ! $is_admin && ( 'image_text' == $opt['items'][ $t ]['show'] || 'image' == $opt['items'][ $t ]['show'] ) ) {
			$image = wp_get_attachment_image_url( $opt['items'][ $t ]['image'], 'spin_gh_cropped' );
			if ( ! $image ) {
				$image = $no_image_cropped;
			}
			echo 'border-image-source: url(' . $image . ');';
		}
		?>

        border-image-outset: 2;
        border-image-width: 33.4% 33.4%;
        transform: rotate(<?php echo $DELTA * $t ?>deg) scale(0.5);
        z-index: <?php echo $t ?>;
		<?php
	} else {
		?>
        z-index: <?php echo $COUNT - $t ?>;
        transform: rotate(<?php echo $DELTA * $t ?>deg) scale(2);
		<?php
	} ?>

    }
<?php endfor; ?>

<?php
$rz        = $DELTA * 0.5 . 'deg';
$ty        = - 0.8 * $text_position . $unit;
$tx        = '-13.5' * ( 1 + 1 / $COUNT ) . $unit;
$textScale = $template == 4 ? 'scale(2)' : '';

?>

<?php echo $ID ?>.roulette .triangle .content {
    color: inherit;
    position: absolute;
    top: 0;
    left: 0;
    width: 2em;
    height: 1.5em;
    line-height: 2em;
    border-radius: 100%;
    color: #FFF;
    transform: rotate(<?php echo $rz ?>) translateY(<?php echo $ty ?>) translateX(<?php echo $tx ?>);
    transform-origin: 0 100%;
    text-align: center;
    font-weight: 800;
    font-size: 10px;

    opacity: 1;
    transition: all 0 0;
    }

<?php echo $ID ?>.roulette .triangle .content > span{
<?php if ( $template == 4 ): ?>
    transform: rotate(<?php echo $rotate ?>deg) translate(-50%, 0) scale(2);
    text-shadow: 0 0 1px #263238;
<?php else: ?>
    transform: rotate(<?php echo $rotate ?>deg) translate(-50%, 0);
<?php endif; ?>
    transform-origin: bottom;
    display: block;
    white-space: nowrap;
    }

<?php echo $ID ?>.roulette .triangle .content > span .text{
    transform: translate(0, 50%) <?php echo $textScale ?>;
    display: block;
    font-weight: normal;
    font-size: <?php echo $font_size ?>px
    }

<?php echo $ID ?>.roulette .triangle .content > span img{
    width: <?php echo $img_size . $unit ?>;
    height:  <?php echo $img_size . $unit ?>;
    min-width:  <?php echo $img_size . $unit ?>;
    min-height:  <?php echo $img_size . $unit ?>;
    top: <?php echo $img_y . $unit ?> ;
    left: <?php echo $img_x ?>px;
    border: 1px solid #fff;
    object-fit: cover;
    border-radius: 50%;
    display: block;
    position: absolute;
    background-color: #fff;
<?php if ( $rotate == - 90 ): ?>
    margin-left: -<?php echo ( $text_position - ( $text_position / 5 ) ) . $unit ?>;
<?php elseif ( $rotate == 90 ): ?>
    margin-left: <?php echo ( $text_position - ( $text_position / 5 ) ) . $unit ?>;
<?php endif; ?>

    }

<?php echo $ID ?>.roulette .triangle .content i {
    font-size: 2em;
    line-height: .7;
    vertical-align: middle;
    display: inline-block;
    }

<?php echo $ID ?> .spin-start {
    display: block;
    text-align: center;
    font-weight: 800;
    background: white;
    position: absolute;
    z-index: 999;
    border-radius: 100%;
    border: none;
    box-shadow: 0 1.5px 4px rgba(0, 0, 0, 0.24), 0 1.5px 6px rgba(0, 0, 0, 0.12);
    color: #999;
    cursor: pointer;
    letter-spacing: -0.05em;
    width: 100px;
    height: 100px;
    line-height: 100px;
    top: 50%;
    left: 50%;
    margin-left: -50px;
    margin-top: -50px;
    font-weight: 800;
    z-index: 998;
    cursor: pointer;
    }

<?php echo $ID ?> .spin-start:hover,
<?php echo $ID ?> .spin-start:active {
    color: #333;
    }
<?php if ( $template == 3 ): ?>
	<?php echo $ID ?>:after{
    content: '';
    position: absolute;
    top: -<?php echo $border_size + 11 ?>px;
    left: 50%;
    transform: translate(-50%, 0);
    border-left: 35px solid transparent;
    border-right: 35px solid transparent;
    border-top: 35px solid <?php echo $border_color ?>;
    display:inline-block;
    box-sizing:content-box;
    }
	<?php echo $ID ?>:before {
    content: '';
    position: absolute;
    top: -<?php echo $border_size + 11 ?>px;
    left: 50%;
    transform: translate(-50%, 0);
    border-bottom: 100px solid #9E9E9E;
    border-left: 50px solid transparent;
    border-right: 50px solid transparent;
    height: 0;
    width: 73px;
    display:inline-block;
    box-sizing:content-box;
    }
<?php else: ?>
	<?php echo $ID ?> .spin-start:after{
    content: '';
    position: absolute;
    top: -20px;
    right: 15px;
    border-left: 35px solid transparent;
    border-right: 35px solid transparent;
    border-bottom: 35px solid #fff;
    }
<?php endif; ?>

<?php echo $ID ?> .spin-start:active {
    transform: scale(0.9);
    }

<?php echo $ID ?> .spin-start span {
    font-size: 1.6em;
    position: absolute;
    width: 100%;
    height: 100%;
    left: 0;
    top: 0;
    transition: transform 0.8s;

    }


<?php
$css = preg_replace( '/[\n\r\t\s]+/is', ' ', ob_get_clean() );
$css = str_replace( array( ' { ', '{ ', '; } ', ' } ', '; ', ': ' ), array( '{', '{', '}', '}', ';', ':' ), $css );
exit( $css );



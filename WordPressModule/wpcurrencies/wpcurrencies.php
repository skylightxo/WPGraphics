<?php
/**
 * Plugin Name: WPCurrencies
 * Description: Добавляет виджет графика для некоторых валют
 * Author:      Илья Черноморец
 * Version:     1.0

 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

require_once(dirname(__FILE__)."/chart.php");
require_once(dirname(__FILE__)."/data.php");

class trueTopPostsWidget extends WP_Widget {
 
	/*
	 * создание виджета
	 */
	function __construct() {
		parent::__construct(
			'true_top_widget', 
			'Курс валют', // заголовок виджета
			array( 'description' => 'Позволяет вывести график с курсом валют.' ) // описание
		);
	}
 
	/*
	 * фронтэнд виджета
	 */
	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] ); // к заголовку применяем фильтр (необязательно)
 
        echo $args['before_widget'];
        
		if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];
        
        // Импорт chart.php для wp_currencies_draw_plot
        wp_currencies_draw_plot();
        
		wp_reset_postdata();
 
		echo $args['after_widget'];
	}
 
	/*
	 * бэкэнд виджета
	 */
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">Заголовок</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php 
	}
 
	/*
	 * сохранение настроек виджета
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;
	}
}
 
/*
 * регистрация виджета
 */
function true_top_posts_widget_load() 
{
	register_widget( 'trueTopPostsWidget' );
}
 
function echo_currencies_data_ajax()
{
	try
    {
        $data_extractor = new DataExtractor();
    }
    catch (CurrencyExtractorError $error)
    {
        echo "<h2>WPCurrencies error:</h2>".$error->getMessage()."</h2>";
        return;
    }

    $data_extractor->echo_js_pair_data();
    die(0);
}

add_action( 'widgets_init', 'true_top_posts_widget_load' );
add_action( 'wp_ajax_currencies', 'echo_currencies_data_ajax' ); // wp_ajax_{ЗНАЧЕНИЕ ПАРАМЕТРА ACTION!!}
add_action( 'wp_ajax_nopriv_currencies', 'echo_currencies_data_ajax' );  // wp_ajax_nopriv_{ЗНАЧЕНИЕ ACTION!!}
// первый хук для авторизованных, второй для не авторизованных пользователей
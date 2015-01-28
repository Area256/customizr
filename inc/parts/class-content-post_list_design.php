<?php
/**
* Post lists design content actions
*
* 
* @package      Customizr
* @subpackage   classes
* @since        3.2.11
* @author       Rocco Aliberti <rocco@themesandco.com>, Nicolas GUILLAUME <nicolas@themesandco.com>
* @copyright    Copyright (c) 2015, Rocco Aliberti, Nicolas GUILLAUME
* @link         http://themesandco.com/customizr
* @license      http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/
if ( ! class_exists( 'TC_post_list_design' ) ) :
    class TC_post_list_design {
        static $instance;

        private $has_expanded_featured;

        function __construct () {
            self::$instance =& $this;

            $this -> has_expanded_featured = false;
            
            add_action ( 'pre_get_posts', array( $this, 'tc_post_list_design_sticky_post') );      
            add_action ( 'wp', array( $this , 'tc_post_list_design_hooks') );

        }

        function tc_post_list_design_hooks(){
            
            if ( ! $this -> tc_post_list_is_design() )
                return;
            
            add_filter( 'tc_user_options_style',
                    array( $this , 'tc_post_list_design_write_inline_css') );

            // pre loop hooks
            add_action( '__before_article_container',
                            array( $this, 'tc_post_list_design_before_loop_hooks') , 5);

            // loop hooks
            add_action( '__before_article', 
                            array( $this, 'tc_post_list_design_loop_hooks'), 0 );
 
        }

        function tc_post_list_design_before_loop_hooks(){
            //layout
            add_filter( 'tc_post_list_layout',
                            array( $this, 'tc_set_post_list_layout') );

            add_filter( 'tc_post_list_selectors',
                            array( $this, 'tc_post_list_design_article_selectors') );

            add_action( '__before_article_container',
                            array( $this, 'tc_post_list_prepare_expand_featured' ) );
            // thumbnails
            remove_filter( 'post_class',
                            array( TC_post_list::$instance , 'tc_add_thumb_shape_name'));

            remove_filter( 'tc_thumb_size_name',
                            array( TC_post_thumbnails::$instance, 'tc_set_thumb_size') );
            add_filter( 'tc_thumb_size_name',
                            array( $this, 'tc_set_thumb_size_name') );
            add_filter( 'tc_thumb_size',
                            array( $this, 'tc_set_thumb_size') );
            
            add_filter( 'tc_post_thumb_inline_style',
                            array( $this, 'tc_change_tumbnail_inline_css_width'), 20, 3 );
            
            add_filter( 'tc_post_list_design_thumb_data',
                            array( $this, 'tc_post_list_design_thumb_data') );

            // specific post list design hooks
            add_filter( 'tc_post_list_design_container',
                           array( $this, 'tc_post_list_design_post_container'), 20, 2 );

            add_action( '__before_post_list_design_figcaption_content',
                            array( $this, 'tc_post_list_design_expanded_post_title') );
            add_action( 'tc_post_list_design_figcaption_content',
                            array( $this, 'tc_post_list_design_display_figcaption_content') );
            add_action( '__after_post_list_design_figcaption_content',
                            array( $this, 'tc_post_list_design_post_link') );
        }

        function tc_post_list_design_loop_hooks(){
  
            add_action( '__before_article',
                            array( $this, 'tc_print_row_fluid_section_wrapper' ), 1 );
            add_action( '__after_article',
                            array( $this, 'tc_print_row_fluid_section_wrapper' ), 0 );

            remove_action( '__loop',
                            array( TC_post_list::$instance, 'tc_post_list_display') );
            add_action( '__loop',
                            array( $this, 'tc_post_list_display') );
        }
        
        /* Layout */
  
        /* force content + thumb layout */
        function tc_set_post_list_layout( $layout ){
            $_position                  = in_array( esc_attr( tc__f( '__get_option' ,
                                                    'tc_post_list_thumb_position' ) ),
                                            array('top', 'left') ) ? 'top' : 'bottom';

            $layout['show_thumb_first'] = ( 'top' == $_position ) ? true : false;
            $layout['content']          = 'tc-design-excerpt';
            $layout['thumb']            = 'span12 tc-design-post-container';
            
            return $layout;
        }

        /* Apply proper class to articles selectors to control articles width*/
        function tc_post_list_design_article_selectors($selectors){

            $class = $this -> is_expanded_featured() ? 'expanded ' : '';

            $class .= 'tc-design span'. ( 12 / $this -> tc_post_list_design_section_cols() );

            return str_replace( 'row-fluid', $class, $selectors );
        }

        function tc_post_list_prepare_expand_featured(){
            global $wp_query;
            
            if ( ! ( $this -> tc_consider_sticky_post() &&
                   $wp_query -> query_vars[ 'paged' ] == 0 ) )
                return;
            // prepend the first sticky
            $first_sticky = get_post( get_option( 'sticky_posts' )[0] );
            array_unshift( $wp_query -> posts, $first_sticky );
            $wp_query -> post_count = $wp_query -> post_count + 1;
            $this -> has_expanded_featured = true;
        }
 
        /* Wrap articles in a grid section*/
        function tc_print_row_fluid_section_wrapper(){
            global $wp_query;
            $current_post = $wp_query -> current_post;
            $start_post = $this -> has_expanded_featured ? 1 : 0;
            $cols = $this -> tc_post_list_design_section_cols();
            $current_filter = current_filter();
            
            if ( '__before_article' == $current_filter &&
                ( $start_post == $current_post ||
                    0 == ( $current_post - $start_post ) % $cols ) )
                    echo apply_filters( 'tc_post_list_design_grid_section',
                        '<section class="tc-post-list-design row-fluid cols-'.$cols.'">' );
            elseif ( '__after_article' == $current_filter &&
                      ( $wp_query->post_count == ( $current_post + 1 ) ||
                      0 == ( ( $current_post - $start_post + 1 ) % $cols ) ) ) {
                            
                echo '</section><!--end section.tc-post-list-design.row-fluid-->';
                echo apply_filters( 'tc_post_list_design_separator',
                    '<hr class="featurette-divider post-list-design">' );
            }
        }

        /* Wrap articles in a grid section*/
        function tc_print_row_fluid_section_wrapper_exp(){
            global $wp_query;
            $current_post = $wp_query -> current_post;
            $start_post = $this -> has_expanded_featured ? 1 : 0;
            $cols = $this -> tc_post_list_design_section_cols();
            $current_filter = current_filter();
            
            if ( '__before_article' == $current_filter && 
                ( $start_post == $current_post || $this -> is_expanded_featured() ) )
                    echo apply_filters( 'tc_post_list_design_grid_section',
                        '<section class="tc-post-list-design row-fluid cols-'.$cols.'">' );
            elseif ( '__after_article' == $current_filter &&
                ( $wp_query->post_count == ( $current_post + 1 )  || $this -> is_expanded_featured() ) ){
                echo '</section><!--end section.tc-post-list-design.row-fluid-->';
                echo apply_filters( 'tc_post_list_design_separator',
                    '<hr class="featurette-divider post-list-design">' );
            }
        }
        /* Thumbnails */
        function tc_set_thumb_size_name(){
            return  ( $this-> tc_post_list_design_section_cols() == '1' ) ?
                            'tc-design-full' : 'tc-design';
        }

        function tc_set_thumb_size(){
            $thumb = ( $this -> tc_post_list_design_section_cols() == '1' ) ? 
                            'tc_design_full_size' : 'tc_design_size';
            return TC_init::$instance -> $thumb;
        }

        function tc_post_list_design_post_container($figure_class, $content){
            return sprintf('<section class="tc-design-post"><figure class="%1$s">%2$s</figure></section>',
                $figure_class,
                $content
            );

        }

        function tc_change_tumbnail_inline_css_width($_style, $width, $height){
            return sprintf('width:100%%;height:auto;');
        }
 
        function tc_post_list_design_thumb_data( $thumb_data ){
            if ( ! empty($thumb_data[0]) )
                return $thumb_data;

            $default_thumb_id = apply_filters('tc_post_list_design_default_thumb',
                esc_attr( tc__f( '__get_option', 'tc_post_list_design_default_thumb' ) ) );
            
            if ( ! $default_thumb_id )
                return $thumb_data;

            $tc_thumb_size = apply_filters( 'tc_thumb_size_name' , 'tc-thumb' );
            $image = wp_get_attachment_image_src( $default_thumb_id, $tc_thumb_size);
    
            if ( empty( $image[0] ) )
                return $thumb_data;
            
            $_class_attr = array( 'class' => "attachment-{$tc_thumb_size} tc-design-default-thumb");
            
            $tc_thumb               = wp_get_attachment_image( $default_thumb_id, $tc_thumb_size, false, $_class_attr );
            $tc_thumb_height        = '';
            $tc_thumb_width         = '';
            
            //get height and width if not empty
            if ( ! empty($image[1]) && ! empty($image[2]) ) {
                $tc_thumb_height        = $image[2];
                $tc_thumb_width         = $image[1];
            }

            return array($tc_thumb, $tc_thumb_height, $tc_thumb_width);
        }

 
        /* Post list display */
        function tc_post_list_display(){
            global $post;
            if ( ! isset($post) || empty($post) || ! apply_filters( 'tc_show_post_in_post_list', $this -> tc_post_list_design_match_type()  , $post ) )
              return;

            //get the thumbnail data (src, width, height) if any
            $thumb_data                     = apply_filters( 'tc_post_list_design_thumb_data', TC_post_thumbnails::$instance -> tc_get_thumbnail_data() );
            
            //get the filtered post list layout
            $layout                         = apply_filters( 'tc_post_list_layout', TC_init::$instance -> post_list_layout );
            
            $tc_show_post_list_thumb        = empty($thumb_data[0]) ? false : true;
            //what is determining the layout ? if no thumbnail then full width + filter's conditions
            $post_list_content_class        = $layout['content'];

            if ( ! $this -> tc_post_list_design_title_in_caption() ){
                $hook_prefix = '__before';
                if ( $layout['show_thumb_first'] )
                    $hook_prefix = '__after';

                add_action( $hook_prefix.'_post_list_post',
                    array( $this, 'tc_post_list_design_display_title_metas' ) );       
            }

            ob_start();
            do_action( '__before_post_list_post');
            
                $post_content = $this -> tc_post_list_design_prepare_post_content($post_list_content_class);
                $post_content = ( $tc_show_post_list_thumb ) ?
                                    $post_content . $thumb_data[0] :
                                    $post_content;

                $figure_class = apply_filters('tc_post_list_design_figure_class', 'tc-design-figure span12' );
                $figure_class = ( $tc_show_post_list_thumb ) ?
                                    $figure_class :
                                    $figure_class . ' no-thumb';

                echo apply_filters('tc_post_list_design_container', $figure_class, $post_content);
                    
            do_action('__after_post_list_post');        
            //renders the hr separator after each article
            echo apply_filters( 'tc_post_list_separator', '<hr class="featurette-divider '.current_filter().'">' );

            $html = ob_get_contents();
            if ($html) ob_end_clean();

            echo apply_filters('tc_post_list_design_display', $html);
        }


        function tc_post_list_design_prepare_post_content($post_list_content_class){
            ob_start();
            ?>
            <figcaption class="<?php echo $post_list_content_class ?>">
                <?php do_action( '__before_post_list_design_figcaption_content' ) ?>
                    <?php do_action( 'tc_post_list_design_figcaption_content' ); ?>
                <?php do_action( '__after_post_list_design_figcaption_content' ) ?>
            </figcaption>
            <?php
            $html = ob_get_contents();
            if ($html) ob_end_clean();
            return apply_filters('tc_post_list_design_content', $html, $post_list_content_class);
        }

        function tc_post_list_design_post_link(){
            printf( '<a href="%1$s" title="%2s"></a>',
                get_permalink( get_the_ID() ),
                esc_attr( strip_tags( get_the_title( get_the_ID() ) ) ) );
        }

        function tc_post_list_design_expanded_post_title(){
            if ( ! $this -> tc_post_list_design_title_in_caption() )
                return;
            global $post;
            echo '<h2 class="title">'.$post->post_title.'</h2>';
        }
        
        function tc_post_list_design_display_figcaption_content(){
            ob_start();
            ?>
                <div class="entry-summary">
                    <?php the_excerpt(); ?>
                </div>
            <?php
            $html = ob_get_contents();
            if ($html) ob_end_clean();
            echo apply_filters('tc_post_list_design_display_figcaption_content', $html);
        }

        function tc_post_list_design_display_title_metas(){
            ob_start();
                do_action('__before_content');
            $html = ob_get_contents();
            if ($html) ob_end_clean();
            echo apply_filters('tc_post_list_design_display_title_metas', $html);
        }
        

        /* Callback pre_get_posts */
        // exclude the first sticky post
        function tc_post_list_design_sticky_post( $query ){
            if ( $this -> tc_post_list_is_design() &&
                     $this -> tc_consider_sticky_post( $query ) )
                $query->set('post__not_in', array(get_option('sticky_posts')[0]) );
        }

        /* inline css */
        function tc_post_list_design_write_inline_css( $_css){
            /* for testing only */
            $_css = sprintf("%s\n%s",
                $_css,
                "
                .tc-design-figure {
                    position: relative;
                  /*  float: left;
                    width: 100%;
                    height: 100%;*/
                    overflow: hidden;
                }
                .tc-design-figure img {
                    width: 100%;
                    height: auto;
                }
                .tc-design-post figcaption{
                    height: 100%;
                    background: rgba(0,0,0,.2);
                    filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#33000000', endColorstr='#33000000', GradientType=0);
                    position: absolute;
                    opacity: 0;
                    vertical-align: middle;
                    margin-left: 0;
                    width: 100%;
                    -webkit-box-sizing: border-box;
                    -moz-box-sizing: border-box;
                    box-sizing: border-box;
                }
                .expanded .tc-design-post figcaption{
                    opacity: 1;
                    width: auto;
                    max-width: 50%;
                    line-height: 10px;
                    height: auto;
                    top: 5%;
                    margin-left: 11%;
                    padding: 4%;
                }
                .expanded .tc-design-post figcaption .title{
                    padding: 0 10px;
                }
                .tc-post-list-design figure {
                    margin: 0;
                }
                figure.no-thumb figcaption{
                    opacity: 1;
                }
                .tc-post-list-design article.hover figcaption{
                    opacity: 1;
                }
                .tc-post-list-design .entry-summary{
                    display: block;
                    width: 85%;
                    height: 85%;
                    margin: auto;
                    overflow: hidden;
                    padding: 10px 10px 0 10px;
                }
                .tc-post-list-design article.sticky{
                    text-align: justify;
                }
                .tc-post-list-design .featurette-divider.__loop{
                    display: none;
                }
                .tc-post-list-design figcaption > a {
                    position: absolute;
                    z-index: 10;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%; 
                }
                .tc-is-mobile .tc-post-list-design article.tc-design figure figcaption > a {
                    -webkit-transition-property: width;
                    -webkit-transition-duration: 0.1s;
                    -webkit-transition-timing-function: linear;
                    -webkit-transition-delay: 1s;
                    transition-property: width;
                    transition-duration: 0.1s;
                    transition-timing-function: linear;
                    transition-delay: 0.5s;
                }
                .tc-is-mobile article:not([class*=expanded]) figure:not([class*=no-thumb]) figcaption > a {
                    width: 0;
                }

                .tc-is-mobile article:not([class*=expanded]).hover figure:not([class*=no-thumb]) figcaption > a {
                    width: 100%;
                }
                @media (max-width: 767px){
                    .featurette-divider.post-list-design{
                        display: none;
                    }
                    .tc-post-list-design .featurette-divider.__loop{
                        display: block;
                    }
                }
                \n"   
            );
/*                
            $cols = $this -> tc_get_post_list_cols();

            $first_article = 'article';
            for ( $i = 2; $i <= $cols + 1 ; $i++ )
                $first_article .= ' + article';
            
            $_css .= sprintf("
                .tc-post-list-design.row-fluid %s {
                    margin-left: 0;                
                }
                ", 
                $first_article
            );*/
            /*$_css = sprintf("%s\n%s",
                $_css,
                "
                .tc-post-list-design .tc-design-post,
                .tc-post-list-design.cols-2 .tc-design-post{
                    height: 350px;
                }
                .tc-post-list-design.cols-3 .tc-design-post{
                    height: 250px;   
                }
                .tc-post-list-design.cols-4 .tc-design-post{
                    height: 170px;   
                }
                @media (max-width: 979px){
                    [class*=cols].tc-post-list-design .tc-design-post{
                        height: 170px;   
                    }
                }
                \n
                "
            );*/

            /* retrieve the height/width ratios */
            $thumb_full_size = apply_filters( 'tc_design_full_size', 
                                    TC_init::$instance -> tc_design_full_size );

            $thumb_full_width = $thumb_full_size['width'];
            $thumb_size = apply_filters( 'tc_design_size', 
                                    TC_init::$instance -> tc_design_size );
            
            $thumb_width = $thumb_size['width'];
            $thumb_full_ratio = $thumb_full_size['height'] / $thumb_full_size['width'] * 100;
            $thumb_ratio =  $thumb_size['height'] / $thumb_size['width'] * 100;

            $_css = sprintf("%s\n%s",
                $_css,
                "
                .tc-post-list-design figure {
                    height: 0;
                    width: 100%;
                    padding-bottom: {$thumb_ratio}%;
                    max-width : {$thumb_width}px;
                }
                .tc-post-list-design.cols-1 figure {
                    padding-bottom: {$thumb_full_ratio}%;
                    max-width: ${thumb_full_width}px;   
                }
                @media (max-width: 767px){
                    .tc-post-list-design header,
                    .tc-post-list-design .tc-design-post{
                        margin: 0 auto !important;   
                        float: none;
                        width: auto;
                        max-width : {$thumb_width}px;
                    }
                    .tc-post-list-design.cols1 .tc-design-post {
                        max-width: ${thumb_full_width}px;   
                    }
                }
                \n
                "
            );

            return $_css;
        }
        /* Helpers */
        
        /* check if we have to expand the first sticky post */
        function tc_consider_sticky_post( $query = null ){
            global $wp_query;
            $query = ( $query ) ? $query : $wp_query;

            if ( !$query->is_main_query() )
                return false;
            if ( ! ( ( is_home() && 'posts' == get_option('show_on_front') ) ||
                    $wp_query->is_posts_page ) )
                return false;

            if ( ! ( $this -> tc_get_post_list_expand_featured() &&
                    isset( get_option ('sticky_posts')[0]) ) )
                return false;

            return true;
        }

        /* retrieves number of cols option, and wrap it into a filter */
        function tc_get_post_list_cols(){
            return apply_filters( 'tc_post_list_design_columns',
                esc_attr( tc__f('__get_option', 'tc_post_list_design_columns') ) );
        }
 
        /* retrieves the expand featured option, and wrap it into a filter */
        function tc_get_post_list_expand_featured(){
            return apply_filters( 'tc_post_list_design_expand_featured',
                esc_attr( tc__f('__get_option', 'tc_post_list_design_expand_featured') ) );
        }

        /* retrieves where to apply the post list design option, and wrap it into a filter 
         * dinamically
         * input param: post list type.
         */
        function tc_get_post_list_design_in( $type ){
            return apply_filters( 'tc_post_list_design_in_' . $type,
                esc_attr( tc__f('__get_option', 'tc_post_list_design_in_' . $type ) ) );
        }
 
        /* checks the option tc_post_list_design and wraps it into a filter */
        function tc_get_post_list_design(){
            return apply_filters( 'tc_post_list_design',
                'design' == esc_attr( tc__f('__get_option', 'tc_post_list_design') ) );
        }       

        function tc_post_list_is_design(){
            return apply_filters( 'tc_post_list_is_design',
                $this -> tc_get_post_list_design() && $this -> tc_post_list_design_match_type() );
        }

        /* returns if the current post is the expanded one */
        function is_expanded_featured(){
            global $wp_query;
            $current_post = $wp_query -> current_post;
            return ( $this -> has_expanded_featured && $current_post == 0 );
        }

        /* returns articles wrapper section columns */
        function tc_post_list_design_section_cols(){
            $cols = $this -> is_expanded_featured() ? '1' : $this -> tc_get_post_list_cols();
            return apply_filters( 'tc_post_list_design_section_cols', $cols );
        }

        /* returns the type of post list we're in if any, an empty string otherwise */
        function tc_post_list_type(){
            global $wp_query;
            if ( ( is_home() && 'posts' == get_option('show_on_front') ) ||
                    $wp_query->is_posts_page )
                return 'blog';
            else if ( is_search() )
                return 'search';
            else if ( is_archive() )
                return 'archive';
            return '';
        }

        /* performs the match between the option where to use post list design 
         * and the post list we're in */
        function tc_post_list_design_match_type(){
            $post_list_type = $this -> tc_post_list_type();
            return ( apply_filters('tc_post_list_design_do',
                $post_list_type && $this -> tc_get_post_list_design_in( $post_list_type ) ) );
        }

        /* checks whether we have to show the title in caption or not */
        function tc_post_list_design_title_in_caption(){
            return apply_filters( 'tc_post_list_design_title_in_caption', 
                $this -> is_expanded_featured() );
        }
    }
endif;

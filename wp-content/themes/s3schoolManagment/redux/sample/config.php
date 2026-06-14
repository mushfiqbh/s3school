<?php

if ( ! class_exists( 'Redux' ) ) { return; }

$opt_name = "opt_name";

// This line is only for altering the demo. Can be easily removed.
$opt_name = apply_filters( 'redux_demo/opt_name', $opt_name );

/*
 *
 * --> Used within different fields. Simply examples. Search for ACTUAL DECLARATION for field examples
 *
 */

$sampleHTML = '';
if ( file_exists( dirname( __FILE__ ) . '/info-html.html' ) ) {
  Redux_Functions::initWpFilesystem();

  global $wp_filesystem;

  $sampleHTML = $wp_filesystem->get_contents( dirname( __FILE__ ) . '/info-html.html' );
}

// Background Patterns Reader
$sample_patterns_path = ReduxFramework::$_dir . '../sample/patterns/';
$sample_patterns_url  = ReduxFramework::$_url . '../sample/patterns/';
$sample_patterns      = array();

if ( is_dir( $sample_patterns_path ) ) {

  if ( $sample_patterns_dir = opendir( $sample_patterns_path ) ) {
    $sample_patterns = array();

    while ( ( $sample_patterns_file = readdir( $sample_patterns_dir ) ) !== false ) {

      if ( stristr( $sample_patterns_file, '.png' ) !== false || stristr( $sample_patterns_file, '.jpg' ) !== false ) {
        $name              = explode( '.', $sample_patterns_file );
        $name              = str_replace( '.' . end( $name ), '', $sample_patterns_file );
        $sample_patterns[] = array(
          'alt' => $name,
          'img' => $sample_patterns_url . $sample_patterns_file
          );
      }
    }
  }
}

$theme = wp_get_theme();

$args = array(
  'opt_name'             => $opt_name,
  'display_name'         => $theme->get( 'Name' ),
  'display_version'      => $theme->get( 'Version' ),
  'menu_type'            => 'menu',
  'allow_sub_menu'       => false,
    // Show the sections below the admin menu item or not
  'menu_title'           => __( 'Theme Options', 'redux-framework-demo' ),
  'page_title'           => __( 'Theme Options', 'redux-framework-demo' ),
  'google_api_key'       => '',
  'google_update_weekly' => false,
  'async_typography'     => true,
  'admin_bar'            => true,
  'admin_bar_icon'       => 'dashicons-editor-paste-text',
  'admin_bar_priority'   => 6,
  'global_variable'      => 's3sRedux',
  'dev_mode'             => false,
  'update_notice'        => false,
  'customizer'           => true,
  'page_priority'        => 25,
  'page_parent'          => 'themes.php',
  'page_permissions'     => 'manage_options',
  'menu_icon'            => 'dashicons-editor-paste-text',
  'last_tab'             => '',
  'page_icon'            => 'dashicons-editor-paste-text',
  'page_slug'            => 's3school-menu',
  'save_defaults'        => true,
  'default_show'         => false,
  'default_mark'         => '',
  'show_import_export'   => true,
  'transient_time'       => 60 * MINUTE_IN_SECONDS,
  'output'               => true,
  'output_tag'           => true,
  'database'             => '',
  'use_cdn'              => true,
  'hints'                => array(
    'icon'          => 'el el-question-sign',
    'icon_position' => 'right',
    'icon_color'    => 'lightgray',
    'icon_size'     => 'normal',
    'tip_style'     => array(
      'color'   => 'red',
      'shadow'  => true,
      'rounded' => false,
      'style'   => '',
      ),
    'tip_position'  => array(
      'my' => 'top left',
      'at' => 'bottom right',
      ),
    'tip_effect'    => array(
      'show' => array(
        'effect'   => 'slide',
        'duration' => '500',
        'event'    => 'mouseover',
        ),
      'hide' => array(
        'effect'   => 'slide',
        'duration' => '500',
        'event'    => 'click mouseleave',
        ),
      ),
    )
  );

// ADMIN BAR LINKS -> Setup custom links in the admin bar menu as external items.
$args['admin_bar_links'][] = array(
  'id'    => 'redux-docs',
  'href'  => '',
  'title' => __( 'Documentation', 'redux-framework-demo' ),
  );

$args['admin_bar_links'][] = array(
  //'id'    => 'redux-support',
  'href'  => '',
  'title' => __( 'Support', 'redux-framework-demo' ),
  );

$args['admin_bar_links'][] = array(
  'id'    => 'redux-extensions',
  'href'  => '',
  'title' => __( 'Extensions', 'redux-framework-demo' ),
  );

// SOCIAL ICONS -> Setup custom links in the footer for quick links in your panel footer icons.
$args['share_icons'][] = array(
  'url'   => '',
  'title' => 'Visit us on GitHub',
  'icon'  => 'el el-github'
  //'img'   => '', // You can use icon OR img. IMG needs to be a full URL.
  );
$args['share_icons'][] = array(
  'url'   => '',
  'title' => 'Like us on Facebook',
  'icon'  => 'el el-facebook'
  );
$args['share_icons'][] = array(
  'url'   => '',
  'title' => 'Follow us on Twitter',
  'icon'  => 'el el-twitter'
  );
$args['share_icons'][] = array(
  'url'   => '',
  'title' => 'Find us on LinkedIn',
  'icon'  => 'el el-linkedin'
  );

// Panel Intro text -> before the form
if ( ! isset( $args['global_variable'] ) || $args['global_variable'] !== false ) {
  if ( ! empty( $args['global_variable'] ) ) {
   $v = $args['global_variable'];
 } else {
   $v = str_replace( '-', '_', $args['opt_name'] );
 }
 $args['intro_text'] = sprintf( __( '', 'redux-framework-demo' ), $v );
} else {
  $args['intro_text'] = __( '', 'redux-framework-demo' );
}

// Add content after the form.
$args['footer_text'] = __( '', 'redux-framework-demo' );

Redux::setArgs( $opt_name, $args );

/*
 * ---> END ARGUMENTS
 */


/*
 * ---> START HELP TABS
 */

$tabs = array(
  array(
    'id'      => 'redux-help-tab-1',
    'title'   => __( 'Theme Information 1', 'redux-framework-demo' ),
    'content' => __( '<p>This is the tab content, HTML is allowed.</p>', 'redux-framework-demo' )
    ),
  array(
    'id'      => 'redux-help-tab-2',
    'title'   => __( 'Theme Information 2', 'redux-framework-demo' ),
    'content' => __( '<p>This is the tab content, HTML is allowed.</p>', 'redux-framework-demo' )
    )
  );
Redux::setHelpTab( $opt_name, $tabs );

// Set the help sidebar
$content = __( '<p>This is the sidebar content, HTML is allowed.</p>', 'redux-framework-demo' );
Redux::setHelpSidebar( $opt_name, $content );


/*
 * <--- END HELP TABS
 */


/*
 *
 * ---> START SECTIONS
 *
 */

/*
    As of Redux 3.5+, there is an extensive API. This API can be used in a mix/match mode allowing for
 */

// -> START Basic Fields

  //>>>>>>>>> Header Section

    Redux::setSection($opt_name,array(
      'id'      => 'institute_information',
      'title'   => __('Other Information','s3school'),
      'icon'    => 'el el-tags',
      'fields'  =>array(
        array(
          'id'    => 'institute_name',
          'title' => __('Institute Name','s3school'),
          'type'  => 'text',
          'default'=> ''
        ),
        array(
          'id'    => 'inst_head_title',
          'title' => __('Head of Institute Title','s3school'),
          'type'  => 'text',
          'default'=> 'Principal'
        ),
        array(
          'id'    => 'institute_address',
          'title' => __('Institute Address','s3school'),
          'type'  => 'text',
          'default'=> ''
        ),
        array(
          'id'    => 'institute_phone',
          'title' => __('Institute Phone','s3school'),
          'type'  => 'text',
          'default'=> ''
        ),
        array(
          'id'    => 'mcqtitle',
          'title' => __('MCQ','s3school'),
          'type'  => 'text',
          'default'=> 'MCQ'
        ),
        array(
          'id'    => 'cqtitle',
          'title' => __('CQ','s3school'),
          'type'  => 'text',
          'default'=> 'CQ'
        ),
        array(
          'id'    => 'prctitle',
          'title' => __('Prectical','s3school'),
          'type'  => 'text',
          'default'=> 'PR'
        ),
        array(
          'id'    => 'catitle',
          'title' => __('CA','s3school'),
          'type'  => 'text',
          'default'=> 'PR'
        ),
        array(
          'id'       => 'principalSign',
          'type'     => 'media',
          'url'      => true,
          'title'    => __('Prinsipal Sign', 's3school'),
          'desc'     => __('', 's3school'),
          'subtitle' => __('', 's3school')
        ),
        array(
          'id'       => 'barcode',
          'type'     => 'media',
          'url'      => true,
          'title'    => __('Barcode Or Logo for ID card', 's3school'),
          'desc'     => __('', 's3school'),
          'subtitle' => __('', 's3school')
        ),
        array(
          'id'       => 'instLogo',
          'type'     => 'media',
          'url'      => true,
          'title'    => __('Institute logo for Admit/Seat card', 's3school'),
          'desc'     => __('', 's3school'),
          'subtitle' => __('', 's3school')
        ),
        array(
          'id'    => 'admitCareNote',
          'title' => __('Admit Card Note','s3school'),
          'desc'  => __('This note will show in footer of Admit Cards','s3school'),
          'type'  => 'editor',
          'default'=> '',
          'args' => array ( 'wpautop' => false )
        ),
        array(
          'id'    => 'stdid',
          'title' => __('Student ID Start After','s3school'),
          'type'  => 'text',
          'default'=> '0'
        ),
        array(
          'id'    => 'stdidpref',
          'title' => __('Student ID Prefix','s3school'),
          'desc'  => __('For adding the year just type year','s3school'),
          'type'  => 'text',
          'default'=> 'std'
        )
      )
    ));

    //>>>>>>>>> Main Header
    Redux::setSection($opt_name,array(
      'id'      => 'main_header',
      'title'   => __('Header','s3school'),
      'icon'    => 'el el-arrow-up',
      'fields'  =>array(
        array(
          'id'    => 'topbarOption',
          'title' => __('Topbar','s3school'),
          'type'  => 'button_set',
          'options'  => array(
            '0' => 'OFF',
            '1' => 'ON',
          ),
          'default' => '0',
          'desc' => '<img src="'.get_template_directory_uri().'/img/topbar.png">'
        ),
        array(
          'id'    => 'topbarBgColor',
          'title' => __('Topbar Background Color','s3school'),
          'type'  => 'color',
          'default' => '#fff'
        ),
        array(
          'id'    => 'topbarTextColor',
          'title' => __('Topbar Text Color','s3school'),
          'type'  => 'color',
          'default' => '#000'
        ),
        array(
          'id'    => 'headerBgColor',
          'title' => __('Header Background Color','s3school'),
          'type'  => 'color',
          'default' => '#d0fb3b'
        ),
        array(
          'id'    => 'headerTextColor',
          'title' => __('Header Text Color','s3school'),
          'type'  => 'color',
          'default' => '#0025f4'
        ),
        array(
          'id'    => 'menuBgColor',
          'title' => __('Menu Background Color','s3school'),
          'type'  => 'color',
          'default' => '#68bd98'
        ),
        array(
          'id'    => 'menuTextColor',
          'title' => __('Menu Text Color','s3school'),
          'type'  => 'color',
          'default' => '#fff'
        ),
        array(
          'id'    => 'headerStyle',
          'title' => __('Header Style','s3school'),
          'type'  => 'radio',
          'options'  => array(
            '1' => 'Header 1<img src="'.get_template_directory_uri().'/img/header.jpg">',
            '2' => 'Header 2<img src="'.get_template_directory_uri().'/img/header2.jpg">',
            '3' => 'Header 3<img src="'.get_template_directory_uri().'/img/header3.jpg">',
            '4' => 'Header 4<img src="'.get_template_directory_uri().'/img/header4.jpg">',
            '5' => 'Header 5<img src="'.get_template_directory_uri().'/img/header5.jpg">'
          ),
          'default' => '1',
        ),
        array(
          'id'    => 'logo_upload',
          'title' => __('Upload Logo','s3school'),
          'desc'  => __('You can upload/update site logo From here.','s3school'),
          'type'  => 'media',
          'url'   => true,
          'default'=>array(
            'url'   => get_template_directory_uri().'/img/logo.png'
          )
        ),
        array(
          'id'    => 'logo_width',
          'title' => __('Logo Width','s3school'),
          'type'  => 'text',
          'default'=> '500'
        ),
        array(
          'id'    => 'header_phone',
          'title' => __('Phone Number','s3school'),
          'desc'  => __('ex: +880 1723 000 000','s3school'),
          'type'  => 'text',
          'default'=> '+880 1723 000 000'
        ),
        array(
          'id'    => 'header_email',
          'title' => __('Email','s3school'),
          'desc'  => __('ex: admin@school.com','s3school'),
          'type'  => 'text',
          'default'=> 'admin@school.com'
        )
      )
    ));


    //>>> Index Page
    Redux::setSection($opt_name,array(
      'id'      => 'index_page',
      'title'   => __('Index Page','s3school'),
      'icon'    => 'el el-home',
      'fields'  =>array(
        array(
          'id'         => 'home_text_slides',
          'type'       => 'slides',
          'title'      => __('Home Slider','s3school'),
          'desc'       => __('', 's3school'),
          'placeholder' => array(
            'title'       => __('Slide Title', 's3school'),
            'description' => __('Description text', 's3school')
            ),
          'show' => array(
            'title' => true,
            'description' => true,
            'url' => false,
          )
        )
      )
    ));

    Redux::setSection($opt_name,array(
      'id'      => 'indexAbout',
      'title'   => __('About Us','s3school'),
      'subsection' => true,
      'fields'  =>array(
        array(
          'id'      => 'aboutTitelText',
          'title'   => __('About us Title','s3school'),
          'type'    => 'text',
          'default' => 'আমাদের সম্পর্কে'
        ),
        array(
          'id'       => 'home_about_img',
          'type'     => 'media',
          'url'      => true,
          'title'    => __('About Us Image', 's3school'),
          'desc'     => __('', 's3school'),
          'subtitle' => __('', 's3school'),
          'default'  => array(
            'url'    => get_template_directory_uri().'/img/school.jpg'
          )
        ),
        array(
          'id'    => 'aboutUsText',
          'title' => __('About Us Text','s3school'),
          'desc'  => __('','s3school'),
          'type'  => 'editor',
          'default'=> '',
          'args' => array ( 'wpautop' => false )
        ),
        array(
          'id'      => 'aboutUsTextLimit',
          'type'    => 'text',
          'title'   => 'Text Limit (character)',
          'desc'    => "Limit of the character of the about us. (For bangla it's like 1 character = 2-3 character)",
          'default' => 3000
        ),
        array(
          'id'      => 'aboutUsMoreBtn',
          'type'    => 'text',
          'title'   => 'Read More Button',
          'desc'    => "If you want to remove the button keep the text empty",
          'default' => 'More information'
        )

      )
    ));

    Redux::setSection($opt_name,array(
      'id'      => 'indexHeadmaster',
      'title'   => __('Head Master','s3school'),
      'subsection' => true,
      'fields'  =>array(
        array(
          'id'       => 'homeHeadmasterImg',
          'type'     => 'media',
          'url'      => true,
          'title'    => __('Head Master Image', 's3school'),
          'desc'     => __('', 's3school'),
          'subtitle' => __('', 's3school'),
          'default'  => array(
            'url'    => get_template_directory_uri().'/img/head.jpg'
          )
        ),
        array(
          'id'    => 'homeHeadmasterTitle',
          'title' => __('Email','s3school'),
          'type'  => 'text',
          'default'=> 'Head Master Speech'
        ),
        array(
          'id'    => 'homeHeadmaster',
          'title' => __('Head Master Speech','s3school'),
          'desc'  => __('','s3school'),
          'type'  => 'editor',
          'default'=> '',
          'args' => array ( 'wpautop' => false )
        ),
        array(
          'id'      => 'headmasterTextLimit',
          'type'    => 'text',
          'title'   => 'Text Limit (character)',
          'desc'    => "Limit of the character of the about us. (For bangla it's like 1 character = 2-3 character)",
          'default' => 1000
        ),
        array(
          'id'      => 'headmasterMoreBtn',
          'type'    => 'text',
          'title'   => 'Read More Button',
          'desc'    => "If you want to remove the button keep the text empty",
          'default' => 'Read More'
        )
      )
    ));
    Redux::setSection($opt_name,array(
      'id'      => 'indexChairman',
      'title'   => __('Chairman ','s3school'),
      'subsection' => true,
      'fields'  =>array(
        array(
          'id'       => 'homeChairmanImg',
          'type'     => 'media',
          'url'      => true,
          'title'    => __('Chairman Image', 's3school'),
          'desc'     => __('', 's3school'),
          'subtitle' => __('', 's3school'),
          'default'  => array(
            'url'    => get_template_directory_uri().'/img/head.jpg'
          )
        ),
        array(
          'id'    => 'homeChairmanTitle',
          'title' => __('Email','s3school'),
          'type'  => 'text',
          'default'=> 'Chairman Message '
        ),
        array(
          'id'    => 'homeChairman',
          'title' => __('Chairman Speech','s3school'),
          'desc'  => __('','s3school'),
          'type'  => 'editor',
          'default'=> '',
          'args' => array ( 'wpautop' => false )
        ),
        array(
          'id'      => 'chairmanTextLimit',
          'type'    => 'text',
          'title'   => 'Text Limit (character)',
          'desc'    => "Limit of the character of the about us. (For bangla it's like 1 character = 2-3 character)",
          'default' => 2000
        ),
        array(
          'id'      => 'chairmanMoreBtn',
          'type'    => 'text',
          'title'   => 'Read More Button',
          'desc'    => "If you want to remove the button keep the text empty",
          'default' => 'Read More'
        )
      )
    ));
    Redux::setSection($opt_name,array(
      'id'      => 'indexCountdown',
      'title'   => __('Countdown Section','s3school'),
      'subsection' => true,
      'fields'  =>array(
        array(
          'id'    => 'countLeftNumber',
          'title' => __('Number','s3school'),
          'desc'  => __('Left countdown Number','s3school'),
          'type'  => 'text',
          'default'=> ''
        ),
        array(
          'id'    => 'countLeftTitle',
          'title' => __('Title','s3school'),
          'desc'  => __('Left countdown Title','s3school'),
          'type'  => 'text',
          'default'=> ''
        ),
        array(
          'id'    => 'countLeftDesc',
          'title' => __('Description','s3school'),
          'desc'  => __('Left countdown Description','s3school'),
          'type'  => 'text',
          'default'=> ''
        ),
        array(
          'id'    => 'countMidNumber',
          'title' => __('Number','s3school'),
          'desc'  => __('Center countdown Number','s3school'),
          'type'  => 'text',
          'default'=> ''
        ),
        array(
          'id'    => 'countMidTitle',
          'title' => __('Title','s3school'),
          'desc'  => __('Center countdown Title','s3school'),
          'type'  => 'text',
          'default'=> ''
        ),
        array(
          'id'    => 'countMidDesc',
          'title' => __('Description','s3school'),
          'desc'  => __('Center countdown Description','s3school'),
          'type'  => 'text',
          'default'=> ''
        ),
        array(
          'id'    => 'countRightNumber',
          'title' => __('Number','s3school'),
          'desc'  => __('Right countdown Number','s3school'),
          'type'  => 'text',
          'default'=> ''
        ),
        array(
          'id'    => 'countRightTitle',
          'title' => __('Title','s3school'),
          'desc'  => __('Right countdown Title','s3school'),
          'type'  => 'text',
          'default'=> ''
        ),
        array(
          'id'    => 'countRightDesc',
          'title' => __('Description','s3school'),
          'desc'  => __('Right countdown Description','s3school'),
          'type'  => 'text',
          'default'=> ''
        )
      )
    ));
    Redux::setSection($opt_name,array(
      'id'      => 'indexTestimonialSlider',
      'title'   => __('Testimonial Slider','s3school'),
      'subsection' => true,
      'fields'  =>array(
        array(
          'id'         => 'testimonial_slides',
          'type'       => 'slides',
          'title'      => __('Testimonial Slider','s3school'),
          'placeholder' => array(
            'title'       => __('Slide Title', 's3school'),
            'description' => __('Description text', 's3school'),
            'url' => __('Rating, Numaric value 1-5', 's3school')
          ),
        ),
      )
    ));




  //>>>>>>>>> Footer
    Redux::setSection($opt_name,array(
      'id'      => 'footer',
      'title'   => __('Footer','s3school'),
      'icon'    => 'el el-arrow-down',
      'fields'  =>array(
        array(
          'id'    => 'footerTopBg',
          'title' => __('Footer top section Background color','s3school'),
          'type'  => 'color',
          'default' => '#ff9801'
        ),
        array(
          'id'    => 'footerTopText',
          'title' => __('Footer top section text color','s3school'),
          'type'  => 'color',
          'default' => '#fff'
        ),
        array(
          'id'    => 'footerBtmBg',
          'title' => __('Footer bottom section Background color','s3school'),
          'type'  => 'color',
          'default' => '#3ca2bf'
        ),
        array(
          'id'    => 'footerBtmText',
          'title' => __('Footer bottom section text color','s3school'),
          'type'  => 'color',
          'default' => '#fff'
        ),
        array(
          'id'    => 'footerAddress',
          'title' => __('Address','s3school'),
          'desc'  => __('','s3school'),
          'type'  => 'editor',
          'default'=> "Crafty Clicks<br>St Mary's Walk<br>United Kingdom",
          'args' => array ( 'wpautop' => false )
        ),
        array(
          'id'    => 'footerContact',
          'title' => __('Contact','s3school'),
          'desc'  => __('','s3school'),
          'type'  => 'editor',
          'default'=> "Abc@xyz.com<br>Abc@xyz.com<br>+880 1700 000 000",
          'args' => array ( 'wpautop' => false )
        ),
        array(
          'id'    => 'footerFbUrl',
          'title' => __('Facebook URL','s3school'),
          'desc'  => __('Use URL with https://<br>Ex: https://facebook.com/','s3school'),
          'type'  => 'text',
          'default'=> '#'
        ),
        array(
          'id'    => 'footerTwtUrl',
          'title' => __('Twitter URL','s3school'),
          'desc'  => __('Use URL with https://<br>Ex: https://twitter.com/','s3school'),
          'type'  => 'text',
          'default'=> '#'
        ),
        array(
          'id'    => 'footerGglUrl',
          'title' => __('Google+ URL','s3school'),
          'desc'  => __('Use URL with https://<br>Ex: https://google.com/','s3school'),
          'type'  => 'text',
          'default'=> '#'
        ),
        array(
          'id'    => 'copyrightText',
          'title' => __('Copyright Text','s3school'),
          'desc'  => __('','s3school'),
          'type'  => 'text',
          'default'=> 'Copyright © 2018'
        )
      )
    ));





/*
 * <--- END SECTIONS
 */


/*
 *
 * YOU MUST PREFIX THE FUNCTIONS BELOW AND ACTION FUNCTION CALLS OR ANY OTHER CONFIG MAY OVERRIDE YOUR CODE.
 *
 */

/*
*
* --> Action hook examples
*
*/

// If Redux is running as a plugin, this will remove the demo notice and links
//add_action( 'redux/loaded', 'remove_demo' );

// Function to test the compiler hook and demo CSS output.
// Above 10 is a priority, but 2 in necessary to include the dynamically generated CSS to be sent to the function.
//add_filter('redux/options/' . $opt_name . '/compiler', 'compiler_action', 10, 3);

// Change the arguments after they've been declared, but before the panel is created
//add_filter('redux/options/' . $opt_name . '/args', 'change_arguments' );

// Change the default value of a field after it's been set, but before it's been useds
//add_filter('redux/options/' . $opt_name . '/defaults', 'change_defaults' );

// Dynamically add a section. Can be also used to modify sections/fields
//add_filter('redux/options/' . $opt_name . '/sections', 'dynamic_section');

/**
 * This is a test function that will let you see when the compiler hook occurs.
 * It only runs if a field    set with compiler=>true is changed.
 * */
if ( ! function_exists( 'compiler_action' ) ) {
  function compiler_action( $options, $css, $changed_values ) {
    echo '<h1>The compiler hook has run!</h1>';
    echo "<pre>";
      print_r( $changed_values ); // Values that have changed since the last save
      echo "</pre>";
      //print_r($options); //Option values
      //print_r($css); // Compiler selector CSS values  compiler => array( CSS SELECTORS )
    }
  }

/**
 * Custom function for the callback validation referenced above
 * */
if ( ! function_exists( 'redux_validate_callback_function' ) ) {
  function redux_validate_callback_function( $field, $value, $existing_value ) {
    $error   = false;
    $warning = false;

        //do your validation
    if ( $value == 1 ) {
      $error = true;
      $value = $existing_value;
    } elseif ( $value == 2 ) {
      $warning = true;
      $value   = $existing_value;
    }

    $return['value'] = $value;

    if ( $error == true ) {
      $return['error'] = $field;
      $field['msg']    = 'your custom error message';
    }

    if ( $warning == true ) {
      $return['warning'] = $field;
      $field['msg']      = 'your custom warning message';
    }

    return $return;
  }
}

/**
 * Custom function for the callback referenced above
 */
if ( ! function_exists( 'redux_my_custom_field' ) ) {
  function redux_my_custom_field( $field, $value ) {
    print_r( $field );
    echo '<br/>';
    print_r( $value );
  }
}

/**
 * Custom function for filtering the sections array. Good for child themes to override or add to the sections.
 * Simply include this function in the child themes functions.php file.
 * NOTE: the defined constants for URLs, and directories will NOT be available at this point in a child theme,
 * so you must use get_template_directory_uri() if you want to use any of the built in icons
 * */
if ( ! function_exists( 'dynamic_section' ) ) {
  function dynamic_section( $sections ) {
        //$sections = array();
    $sections[] = array(
      'title'  => __( 'Section via hook', 'redux-framework-demo' ),
      'desc'   => __( '<p class="description">This is a section created by adding a filter to the sections array. Can be used by child themes to add/remove sections from the options.</p>', 'redux-framework-demo' ),
      'icon'   => 'el el-paper-clip',
            // Leave this as a blank section, no options just some intro text set above.
      'fields' => array()
      );

    return $sections;
  }
}

/**
 * Filter hook for filtering the args. Good for child themes to override or add to the args array. Can also be used in other functions.
 * */
if ( ! function_exists( 'change_arguments' ) ) {
  function change_arguments( $args ) {
    $args['dev_mode'] = false;

    return $args;
  }
}




/**
 * Filter hook for filtering the default value of any given field. Very useful in development mode.
 * */
if ( ! function_exists( 'change_defaults' ) ) {
  function change_defaults( $defaults ) {
    $defaults['str_replace'] = 'Testing filter hook!';

    return $defaults;
  }
}

/**
 * Removes the demo link and the notice of integrated demo from the redux-framework plugin
 */
if ( ! function_exists( 'remove_demo' ) ) {
  function remove_demo() {
    // Used to hide the demo mode link from the plugin page. Only used when Redux is a plugin.
    if ( class_exists( 'ReduxFrameworkPlugin' ) ) {
      remove_filter( 'plugin_row_meta', array(
        ReduxFrameworkPlugin::instance(),
        'plugin_metalinks'
        ), null, 2 );

      // Used to hide the activation notice informing users of the demo panel. Only used when Redux is a plugin.
      remove_action( 'admin_notices', array( ReduxFrameworkPlugin::instance(), 'admin_notices' ) );
    }
  }
}


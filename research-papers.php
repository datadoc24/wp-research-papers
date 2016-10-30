<?php
/*
Plugin Name: Research Papers
Description: Store details of research papers using URL or media file for each research paper; tag papers according to a variety of taxonomies; shortcode for display on a page filterable by taxonomy
Version: 1.0
Authors: A Sharp
License: GPLv3
*/

/*  Copyright 2016  Abraham Sharp  (email : abesharp@outlook.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
// Current version of this plugin
define( 'RESEARCH_PAPERS_VERSION', '1.0.0' );

include('includes/functions.php');

global $table_count;
$table_count = 1;
// Call function when plugin is activated
register_activation_hook( __FILE__, 'ddoc_research_papers_install' );

function ddoc_research_papers_install() {
	
    //nothing to really be done at install time
}

// Action hook to initialize the plugin
add_action( 'init', 'ddoc_research_papers_init' );

//Initialize the Research Papers Plugin
function ddoc_research_papers_init() {

	//register the questions custom post type
	$labels = array(
		'name' => __( 'Research Papers', 'ddoc-research-papers-plugin' ),
		'singular_name' => __( 'Research Paper', 'ddoc-research-papers-plugin' ),
		'add_new' => __( 'New Research Paper', 'ddoc-research-papers-plugin' ),
		'add_new_item' => __( 'Add New Research Paper', 'ddoc-research-papers-plugin' ),
		'edit_item' => __( 'Edit Research Paper', 'ddoc-research-papers-plugin' ),
		'new_item' => __( 'New Research Paper', 'ddoc-research-papers-plugin' ),
		'all_items' => __( 'Research Papers', 'ddoc-research-papers-plugin' ),
		'view_item' => __( 'View Research Paper', 'ddoc-research-papers-plugin' ),
		'search_items' => __( 'Search Research Papers', 'ddoc-research-papers-plugin' ),
		'not_found' =>  __( 'No papers found', 'ddoc-research-papers-plugin' ),
		'not_found_in_trash' => __( 'No research papers found in Trash', 'ddoc-research-papers-plugin' ),
		'menu_name' => __( 'Research Papers', 'ddoc-research-papers-plugin' )
	  );
	
	  $args = array(
		'labels' => $labels,
		'public' => false,
		'publicly_queryable' => false,
		'show_ui' => true, 
		'show_in_menu' => true, 
		'query_var' => true,
		'rewrite' => true,
		'capability_type' => 'post',
		'has_archive' => true, 
		'hierarchical' => false,
		'menu_position' => null,
		'supports' => array( 'title', 'thumbnail', 'comments')
	  ); 
	  
	  register_post_type( 'ddoc-research-papers', $args );
      ddoc_research_papers_taxonomies();
}

function ddoc_research_papers_taxonomies(){
    
    $taxs = array('author'=>__( 'Author', 'ddoc-research-papers-plugin' ),'year'=>__( 'Year', 'ddoc-research-papers-plugin' ),'journal'=>__( 'Journal', 'ddoc-research-papers-plugin' ));
    
    foreach ($taxs as $name => $label){
        
         $labels = array(
        'name'=>$label.'s',
        'singular_name'=>$label,
        'search_items'=>'Search '.$name,
        'all_items'=>'All '.$name.'s',
        'edit_item'=>'Edit '.$name,
        'update_item'=>'Update '.$name,
        'add_new_item'=>'Add new '.$name,
        'new_item_name'=>'New '.$name,
        'menu_name'=>$label.'s'
        );
        
        register_taxonomy ($name, 'ddoc-research-papers', array('hierarchical'=>false, 'query_var'=>true, 'rewrite'=>true, 'labels'=>$labels));
        
    }
}


//filters to manage what is shown in the table of risk assessment questions and user table
add_filter('manage_ddoc-research-papers_posts_columns', 'ddoc_columns_research_papers_head');
add_filter('manage_edit-ddoc-research-papers_sortable_columns', 'ddoc_sortable_category_columns');
add_action('manage_ddoc-research-papers_posts_custom_column', 'ddoc_columns_research_papers_content', 10, 2);

function ddoc_columns_research_papers_head($defaults){
    unset($defaults['date']);
    unset($defaults['author']);
    $defaults['author'] = __( 'Authors', 'ddoc-research-papers-plugin' );
    $defaults['journal'] = __( 'Journal', 'ddoc-research-papers-plugin' );
    $defaults['year'] = __( 'Year', 'ddoc-research-papers-plugin' );
    $defaults['url'] = __( 'URL / Web Link', 'ddoc-research-papers-plugin' );
    return $defaults;
}

function ddoc_sortable_category_columns($columns){
    $columns['author'] = __( 'Authors', 'ddoc-research-papers-plugin' );
    $columns['journal'] = __( 'Journal', 'ddoc-research-papers-plugin' );
    $columns['year'] = __( 'Year', 'ddoc-research-papers-plugin' );
    return $columns;
}

function ddoc_columns_research_papers_content($column_name, $post_ID) {
   
    if ($column_name == 'url') {
        $rp_url = get_post_meta( $post_ID, '_ddoc_research_paper_url', true );
        if ($rp_url) {
            echo $rp_url;
            return;
        }
    }
    
    else{
        $termlist = ddoc_get_terms($post_ID, $column_name);
        
        if ($termlist) {
            echo $termlist;
        }
        return;   
    }
}

#comma-separated list of tag names (all taxonomies)
function ddoc_get_terms( $post_ID , $tn ){
    
    $output = get_the_term_list( $post_ID, $tn,'', ', ','' );
    return $output;
}

// Action hook to create the papers shortcode
add_shortcode( $ddoc_rp_shortcode, 'ddoc_research_papers_shortcode' );


//meta box things
add_action( 'add_meta_boxes', 'ddoc_research_papers_register_meta_box' );

function ddoc_research_papers_register_meta_box() {
	
 	add_meta_box( 'ddoc-research-papers-question-meta', __( 'Research Paper Details','ddoc-research-papers-plugin' ), 'ddoc_research_papers__meta_box', 'ddoc-research-papers', 'normal', 'default' );
	
}

function ddoc_research_papers__meta_box( $post ) {

    wp_nonce_field( 'ddoc-rp-meta-box-save', 'ddoc-research-papers-plugin' );
    
    $rp_url = get_post_meta( $post->ID, '_ddoc_research_paper_url', true );
    $rp_desc = get_post_meta( $post->ID, '_ddoc_research_paper_desc', true );
  
    echo '<div><strong>' .__('Research Paper Web Link / URL', 'ddoc-research-papers-plugin').':</strong> <input type="text" name="ddoc_research_paper_url" value="'.esc_attr( $rp_url ).'" size="100"><br><strong>'.__('Research Description / Summary', 'ddoc-research-papers-plugin').'</strong><br /><textarea rows=10 style="width:100%" name="ddoc_research_paper_desc">'.esc_attr( $rp_desc ).'</textarea></div>';
	
}

add_action( 'save_post','ddoc_research_paper_save_meta_box' );

function ddoc_research_paper_save_meta_box( $post_id ) {

	//verify the post type is for Risk Assessment Question and metadata has been posted
	if (( get_post_type( $post_id ) == 'ddoc-research-papers'  ) && isset( $_POST['ddoc_research_paper_url'] )) {
		
		//if autosave skip saving data
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return;

		//check nonce for security
		check_admin_referer( 'ddoc-rp-meta-box-save', 'ddoc-research-papers-plugin' );

		// save the meta box data as post metadata
		update_post_meta( $post_id, '_ddoc_research_paper_url', sanitize_text_field( $_POST['ddoc_research_paper_url'] ) );
        update_post_meta( $post_id, '_ddoc_research_paper_desc', sanitize_text_field( $_POST['ddoc_research_paper_desc'] ) );
		
	}
	
}









// Register styles and scripts
add_action( 'wp_enqueue_scripts', 'ddoc_register_styles' );
add_action( 'wp_enqueue_scripts', 'ddoc_register_scripts');

function ddoc_register_styles() {
        wp_enqueue_style( 'jquery-data-tables', plugins_url( 'assets/css/datatables.min.css', __FILE__ ), array(), '1.10.12' );
		wp_enqueue_style( 'posts-data-table', plugins_url( 'assets/css/posts-data-table.min.css', __FILE__ ), array( 'jquery-data-tables' ));        
                
}
    
function ddoc_register_scripts() {
        wp_enqueue_script( 'jquery-data-tables', plugins_url( 'assets/js/datatables.min.js', __FILE__ ), array( 'jquery' ), '1.10.12', true );
        wp_enqueue_script( 'posts-data-table', plugins_url( 'assets/js/posts-data-table.min.js', __FILE__ ), array( 'jquery-data-tables' ));
}

    
?>
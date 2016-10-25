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
		'supports' => array( 'title', 'thumbnail')
	  ); 
	  
	  register_post_type( 'research-papers', $args );
      ddoc_research_papers_taxonomies();
}

function ddoc_research_papers_taxonomies(){
    
    $taxs = array('author'=>'Author','year'=>'Year','journal'=>'Journal');
    
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
        
        register_taxonomy ($name, 'research-papers', array('hierarchical'=>true, 'query_var'=>true, 'rewrite'=>true, 'labels'=>$labels));
        
    }
}


// Action hook to create the papers shortcode
add_shortcode( 'researchpapers', 'ddoc_research_papers_shortcode' );

//create shortcode
function ddoc_research_papers_shortcode( $atts, $content = null ) {
   
    $output='<h1>Research Papers</h1>';
    
    $taxlist = get_object_taxonomies( 'research-papers','objects');
    
    //drop-down for choosing ordering type
    $output.="Sort research papers by <select name='sortby'>";
    
    foreach ($taxlist as $taxobj){
        $taxlabel = $taxobj->label;
        
        $output.="<option>$taxlabel</option>";
    }
    
    $output.="</select></div>";
    
    return $output;
}
    
?>
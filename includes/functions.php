<?php
global $ddoc_rp_table_count;
$ddoc_rp_table_count = 1;

global $ddoc_rp_shortcode;
$ddoc_rp_shortcode = 'researchpapers';

global $ddoc_rp_shortcode_defaults;
$ddoc_rp_shortcode_defaults = array(
        'rows_per_page' => 20,
        'sort_by' => '',
        'sort_order' => '',
        'category' => '',
        'search_on_click' => false,
        'wrap' => true,
        'content_length' => 15,
        'scroll_offset' => 15
    );


function ddoc_research_papers_shortcode( $atts, $content = '' ) {
    global $ddoc_rp_shortcode_defaults;
    global $ddoc_rp_shortcode;
    
    $atts = shortcode_atts( $ddoc_rp_shortcode_defaults, $atts, $ddoc_rp_shortcode );
    return ddoc_research_papers_display( $atts );
}


function ddoc_research_papers_display( $args ) {
        global $ddoc_rp_table_count;
        
        
        
        $args['rows_per_page'] = filter_var( $args['rows_per_page'], FILTER_VALIDATE_INT );
        if ( ($args['rows_per_page'] < 1) || !$args['rows_per_page'] ) {
            $args['rows_per_page'] = false;
        }
        
        if ( !in_array( $args['sort_by'], array('id', 'title', 'category', 'date', 'authors', 'content') ) ) {
            $args['sort_by'] = 'title';
        }
        
        if ( !in_array( $args['sort_order'], array('asc', 'desc') ) ) {
            $args['sort_order'] = 'asc';
        }
        
        // Set default sort direction
        if ( !$args['sort_order'] ) {
            if ( $args['sort_by'] === 'date' ) {
                $args['sort_order'] = 'desc';
            } else {
                $args['sort_order'] = 'asc';
            }
        }
        
        $args['search_on_click'] = filter_var( $args['search_on_click'], FILTER_VALIDATE_BOOLEAN );
        $args['wrap'] = filter_var( $args['wrap'], FILTER_VALIDATE_BOOLEAN );
        $args['content_length'] = filter_var( $args['content_length'], FILTER_VALIDATE_INT );
        $args['scroll_offset'] = filter_var( $args['scroll_offset'], FILTER_VALIDATE_INT );
         
        $date_format = 'Y/m/d';
        $output = $table_head = $table_body = $body_row_fmt = '';
        
        // Start building the args needed for our posts query
        $post_args = array(
            'post_type' => 'ddoc-research-papers',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'suppress_filters' => false // Ensure WPML filters run on this query
        );
        
        if ( $args['category'] ) {
            $category = get_category_by_slug( $args['category'] );
            
            if ( $category ) {
                $post_args['category_name'] = $category->slug;
            }            
        }
        
        // Get all published posts in the current language
        $all_posts_curr_lang = get_posts( $post_args );

        if ( is_array( $all_posts_curr_lang ) && $all_posts_curr_lang ) {  // if we have posts
            
            
            $column_defaults = array( 
                 
                'title' => array(
                    'heading' => __('Title', 'ddoc-research-papers-plugin'),
                    'priority' => 1,
                    'width' => ''
                ), 
                'journal' => array(
                    'heading' => __('Journal', 'ddoc-research-papers-plugin'),
                    'priority' => 2,
                    'width' => ''
                ),
                'years' => array(
                    'heading' => __('Date', 'ddoc-research-papers-plugin'),
                    'priority' => 2,
                    'width' => ''
                ), 
                'authors' => array(
                    'heading' => __('Authors', 'ddoc-research-papers-plugin'),
                    'priority' => 1,
                    'width' => ''
                ), 
                'url' => array(
                    'heading' => __('URL / Web Link', 'ddoc-research-papers-plugin'),
                    'priority' => 3,
                    'width' => ''
                ), 
            );   
            
            $columns = array_keys( $column_defaults );
             
            
            // Build table header
            $heading_fmt = '<th data-name="%1$s" data-priority="%2$u" data-width="%3$s">%4$s</th>';
            $cell_fmt = '<td>%s</td>';
                        
            foreach( $columns as $column ) {
                
                if ( array_key_exists( $column, $column_defaults ) ) { // Double-check column name is valid
                                     
                    // Add heading to table
                    $table_head .= sprintf( $heading_fmt, $column, $column_defaults[$column]['priority'], $column_defaults[$column]['width'], $column_defaults[$column]['heading'] );
                    
                    // Add placeholder to table body format string so that content for this column is included in table output
                    $body_row_fmt .= sprintf($cell_fmt, '{' . $column . '}');
                }
            }
            
            $sort_column = $args['sort_by'];
            $sort_index = array_search( $sort_column, $columns );
            
            if ( $sort_index === false && array_key_exists( $sort_column, $column_defaults ) ) {
                // Sort column is not in list of displayed columns so we'll add it as a hidden column at end of table 
                $table_head .= sprintf( '<th data-name="%1$s" data-visible="false">%2$s</th>', $sort_column, $column_defaults[$sort_column]['heading'] );
                
                // Make sure data for this column is included in table content
                $body_row_fmt .= sprintf($cell_fmt, $sort_column); 
                
                // Set the sort column index to be this hidden column
                $sort_index = count($columns);
            }  
            
            $table_head = sprintf( '<thead><tr>%s</tr></thead>', $table_head );
            // end table header
                        
            // Build table body
            $body_row_fmt = '<tr>' . $body_row_fmt . '</tr>';
            
            // Loop through posts and add a row for each
            foreach ( (array) $all_posts_curr_lang as $_post ) {
                setup_postdata( $_post );
                
                // Format title
                $title = sprintf( '<a href="%1$s">%2$s</a>', get_permalink($_post), get_the_title( $_post ) );
                // Format authors
                $authors = strip_tags(get_the_term_list( $_post->ID, 'authors','', ', ','' ));
                // Format years
                $years = strip_tags(get_the_term_list( $_post->ID, 'years','', ', ','' ));
                // Format journal
                $journal = strip_tags(get_the_term_list( $_post->ID, 'journal','', ', ','' ));
                
                $url = get_post_meta( $_post->ID, '_ddoc_research_paper_url', true );
                
                $post_data_trans = array( 
                   
                    '{title}' => $title, 
                    '{url}' => sprintf( '<a href="%1$s" target=_blank>%2$s</a>', $url , $url ),
                    '{years}' => $years, 
                    '{authors}' => $authors, 
                    '{journal}' => $journal
                );
                
                $table_body .= strtr( $body_row_fmt, $post_data_trans );
                
            } // foreach post
            
            wp_reset_postdata();
            
            $table_body = sprintf( '<tbody>%s</tbody>', $table_body );
            // end table body
            
            $paging_attr = 'false';
            if ( ( $args['rows_per_page'] > 1 ) && ( $args['rows_per_page'] < count($all_posts_curr_lang) ) ) {
                $paging_attr = 'true';
            }
            
            $order_attr = ( $sort_index === false ) ? '' : sprintf( '[[%u, "%s"]]', $sort_index, $args['sort_order'] );            
            $offset_attr = ( $args['scroll_offset'] === false ) ? 'false' : $args['scroll_offset'];            
        
            $table_class = 'posts-data-table';
            if ( !$args['wrap'] ) {
                $table_class .= ' nowrap';
            }
            
            $output = sprintf( 
                '<table '
                    . 'id="posts-table-%1$u" '
                    . 'class="%2$s" '
                    . 'data-page-length="%3$u" '
                    . 'data-paging="%4$s" '
                    . 'data-order=\'%5$s\' '
                    . 'data-click-filter="%6$s" '
                    . 'data-scroll-offset="%7$s" '
                    . 'cellspacing="0" width="100%%">'
                    . '%8$s%9$s' .
                '</table>',
                $table_count,
                esc_attr( $table_class ),
                esc_attr( $args['rows_per_page'] ),
                esc_attr( $paging_attr ),
                esc_attr( $order_attr ),
                ( $args['search_on_click'] ? 'true' : 'false' ),
                esc_attr( $offset_attr ),
                $table_head,
                $table_body
            );
            
            $ddoc_rp_table_count++;
        } // if posts found

        return $output;
    } 
?>
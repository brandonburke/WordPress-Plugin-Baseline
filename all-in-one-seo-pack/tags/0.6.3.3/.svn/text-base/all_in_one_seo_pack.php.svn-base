<?php

/*
Plugin Name: All in One SEO Pack
Plugin URI: http://wp.uberdose.com/2007/03/24/all-in-one-seo-pack/
Description: Out-of-the-box SEO for your Wordpress blog.
Version: 0.6.3.3
Author: uberdose
Author URI: http://wp.uberdose.com/
*/

/* Copyright (C) 2007 Dirk Zimmermann (allinoneseopack AT uberdose DOT com)

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
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA */
 
class All_in_One_SEO_Pack {
	
 	var $version = "0.6.3.3";
 	
 	/**
 	 * Number of words to be used (max) for generating an excerpt.
 	 */
 	var $maximum_excerpt_length = 25;

 	/**
 	 * Minimum number of chars an excerpt should be so that it can be used
 	 * as description. Touch only if you know what you're doing.
 	 */
 	var $minimum_excerpt_length = 1;
 	
	function plugins_loaded() {
		if (get_option('aiosp_max_words_excerpt') && is_numeric(get_option('aiosp_max_words_excerpt'))) {
			$this->maximum_excerpt_length = get_option('aiosp_max_words_excerpt');
		}
		ob_start();
	}

	function init_textdomain() {
		if(function_exists('load_plugin_textdomain')) {
			load_plugin_textdomain('all_in_one_seo_pack', 'wp-content/plugins/all-in-one-seo-pack');
		}	
	}

	function wp_head() {
		if (is_feed()) {
			return;
		}
		global $post;
		$meta_string = null;
		
		$this->rewrite_title();
		
		echo "<!-- all in one seo pack $this->version -->\n";
		
		if (is_home() && get_option('aiosp_home_keywords')) {
			$keywords = trim(get_option('aiosp_home_keywords'));
		} else {
			$keywords = $this->get_all_keywords();
		}

		if (is_single() || is_page()) {
            $description = trim(stripslashes(get_post_meta($post->ID, "description", true)));
			if (!$description) {
				$description = $this->trim_excerpt_without_filters_full_length($post->post_excerpt);
				if (!$description && get_option("aiosp_generate_descriptions")) {
					$description = $this->trim_excerpt_without_filters($post->post_content);
				}				
			}
		} else if (is_home()) {
			$description = trim(stripslashes(get_option('aiosp_home_description')));
		}
		
		if (isset($description) && strlen($description) > $this->minimum_excerpt_length) {
			if (isset($meta_string)) {
				$meta_string .= "\n";
			}
			$meta_string .= sprintf("<meta name=\"description\" content=\"%s\"/>", $description);
		}

		if (isset ($keywords) && !empty($keywords)) {
			if (isset($meta_string)) {
				$meta_string .= "\n";
			}
			$meta_string .= sprintf("<meta name=\"keywords\" content=\"%s\"/>\n", $keywords);
		}

		if((is_category() && get_option('aiosp_category_noindex')) ||
			(!is_category() && is_archive() && get_option('aiosp_archive_noindex'))) {
			if (isset($meta_string)) {
				$meta_string .= "\n";
			}
			$meta_string = '<meta name="robots" content="noindex,follow" />';
		}
		
		if ($meta_string != null) {
			echo $meta_string;
		}
	}
	
	function rewrite_title() {
		global $post;

		if (is_home()) {
			$title = trim(stripslashes(get_option('aiosp_home_title')));
		} else if (is_single() || is_page()) {
        	$title = stripslashes(get_post_meta($post->ID, "title", true));
		} else if (is_category() && get_option('aiosp_use_category_description_as_title')) {
			$title = category_description();
		}
		$header = ob_get_contents();
		ob_end_clean();
		
		if ($title) {
			$title = addslashes($title);
			$header = preg_replace_callback("/<title>.*<\/title>/",
				create_function('$match',"return '<title>$title</title>';"), $header);
		} else if (get_option('aiosp_rewrite_titles')) {
			global $s;
			if (is_search() && isset($s) && !empty($s)) {
				if (function_exists('attribute_escape')) {
					$title = attribute_escape(stripslashes($s));
				} else {
					$title = wp_specialchars(stripslashes($s), true);
				}
			} else if (is_single() || is_page()) {
	            $title = stripslashes(get_post_meta($post->ID, "title", true));
			}
			if (!$title) {
				$title = wp_title('', false);
			}
			if ($title) {
				$title .= ' | ' . get_bloginfo('name');
				$title = trim($title);
				$header = preg_replace("/<title>.*<\/title>/", "<title>$title</title>", $header);
			}
		}

		if (get_template() != 'semiologic') {
			// invoke this on the default theme and nothing gets written
			// invoke on semiologic and an error occurrs:
			// "Warning: ob_start() [ref.outcontrol]: output handler 'ob_gzhandler' cannot be used twice in /Users/dirk/Documents/workspace/wp/wp-includes/functions.php on line 419"
			gzip_compression();
		}
		
		print($header);
	}
	
	function trim_excerpt_without_filters($text) {
		$text = str_replace(']]>', ']]&gt;', $text);
		$text = strip_tags($text);
		$excerpt_length = $this->maximum_excerpt_length;
		$words = explode(' ', $text, $excerpt_length + 1);
		if (count($words) > $excerpt_length) {
			array_pop($words);
			array_push($words, '...');
			$text = implode(' ', $words);
		}
		return trim(stripslashes($text));
	}
	
	function trim_excerpt_without_filters_full_length($text) {
		$text = str_replace(']]>', ']]&gt;', $text);
		$text = strip_tags($text);
		$excerpt_length = $this->maximum_excerpt_length;
		$words = explode(' ', $text, $excerpt_length + 1);
		return trim(stripslashes($text));
	}
	
	/**
	 * @return comma-separated list of unique keywords
	 */
	function get_all_keywords() {
		global $posts;

	    $keywords = array();
	    if (is_array($posts)) {
	        foreach ($posts as $post) {
	            if ($post) {
	            	if (get_option('aiosp_use_categories') && !is_page()) {
		                $categories = get_the_category($post->ID);
		                foreach ($categories as $category) {
		                	$keywords[] = $category->cat_name;
		                }
	            	}

	                // Ultimate Tag Warrior integration
	                global $utw;
	                if ($utw) {
	                	$tags = $utw->GetTagsForPost($post);
	                	foreach ($tags as $tag) {
							$tag = $tag->tag;
							$tag = str_replace('_',' ', $tag);
							$tag = str_replace('-',' ',$tag);
							$tag = stripslashes($tag);
	                		$keywords[] = $tag;
	                	}
	                }

	                $keywords_a = $keywords_i = null;
	                $description_a = $description_i = null;
	                $id = $post->ID;
		            $keywords_i = stripslashes(get_post_meta($post->ID, "keywords", true));
	                if (isset($keywords_i) && !empty($keywords_i)) {
	                    $keywords[] = $keywords_i;
	                }
	            }
	        }
	    }
	    
	    return $this->get_unique_keywords($keywords);
	}

	function get_unique_keywords($keywords) {
		$keywords_ar = array_unique($keywords);
		return implode(',', $keywords_ar);
	}
	
	function post_meta_tags($id) {
	    $awmp_edit = $_POST["aiosp_edit"];
	    if (isset($awmp_edit) && !empty($awmp_edit)) {
		    $keywords = $_POST["aiosp_keywords"];
		    $description = $_POST["aiosp_description"];
		    $title = $_POST["aiosp_title"];

		    delete_post_meta($id, 'keywords');
		    delete_post_meta($id, 'description');
		    delete_post_meta($id, 'title');

		    if (isset($keywords) && !empty($keywords)) {
			    add_post_meta($id, 'keywords', $keywords);
		    }
		    if (isset($description) && !empty($description)) {
			    add_post_meta($id, 'description', $description);
		    }
		    if (isset($title) && !empty($title)) {
			    add_post_meta($id, 'title', $title);
		    }
	    }
	}

	function add_meta_tags_textinput() {
	    global $post;
	    $keywords = stripslashes(get_post_meta($post->ID, 'keywords', true));
	    $title = stripslashes(get_post_meta($post->ID, 'title', true));
		?>
		<input value="aiosp_edit" type="hidden" name="aiosp_edit" />
		<table style="margin-bottom:40px; margin-top:30px;">
		<tr>
		<th style="text-align:left;" colspan="2">
		<a href="http://wp.uberdose.com/2007/03/24/all-in-one-seo-pack/">All in One SEO Pack</a>
		</th>
		</tr>
		<tr>
		<th scope="row" style="text-align:right;"><?php _e('Title:', 'all_in_one_seo_pack') ?></th>
		<td><input value="<?php echo $title ?>" type="text" name="aiosp_title" size="80"/></td>
		</tr>
		<tr>
		<th scope="row" style="text-align:right;"><?php _e('Keywords (comma separated):', 'all_in_one_seo_pack') ?></th>
		<td><input value="<?php echo $keywords ?>" type="text" name="aiosp_keywords" size="80"/></td>
		</tr>
		</table>
		<?php
	}

	function add_meta_tags_page_textinput() {
	    global $post;
	    $keywords = stripslashes(get_post_meta($post->ID, 'keywords', true));
	    $description = stripslashes(get_post_meta($post->ID, 'description', true));
	    $title = stripslashes(get_post_meta($post->ID, 'title', true));
		?>
		<input value="aiosp_edit" type="hidden" name="aiosp_edit" />
		<table style="margin-bottom:40px; margin-top:30px;">
		<tr>
		<th style="text-align:left;" colspan="2">
		<a href="http://wp.uberdose.com/2007/03/24/all-in-one-seo-pack/"><?php _e('All in One SEO Pack', 'all_in_one_seo_pack')?></a>
		</th>
		</tr>
		<tr>
		<th scope="row" style="text-align:right;"><?php _e('Title:', 'all_in_one_seo_pack') ?></th>
		<td><input value="<?php echo $title ?>" type="text" name="aiosp_title" size="80"/></td>
		</tr>
		<tr>
		<th scope="row" style="text-align:right;"><?php _e('Keywords (comma separated):', 'all_in_one_seo_pack') ?></th>
		<td><input value="<?php echo $keywords ?>" type="text" name="aiosp_keywords" size="80"/></td>
		</tr>
		<tr>
		<th scope="row" style="text-align:right;"><?php _e('Description:', 'all_in_one_seo_pack') ?></th>
		<td><input value="<?php echo $description ?>" type="text" name="aiosp_description" size="80"/></td>
		</tr>
		</table>
		<?php
	}

	function admin_menu() {
		add_submenu_page('options-general.php', __('All in One SEO', 'all_in_one_seo_pack'), __('All in One SEO', 'all_in_one_seo_pack'), 5, __FILE__, array($this, 'plugin_menu'));
	}
	
	function plugin_menu() {
		$message = null;
		$message_updated = __("All in One SEO Options Updated.");
		
		// update options
		if ($_POST['action'] && $_POST['action'] == 'aiosp_update') {
			$message = $message_updated;
			update_option('aiosp_home_title', $_POST['aiosp_home_title']);
			update_option('aiosp_home_description', $_POST['aiosp_home_description']);
			update_option('aiosp_home_keywords', $_POST['aiosp_home_keywords']);
			update_option('aiosp_max_words_excerpt', $_POST['aiosp_max_words_excerpt']);
			update_option('aiosp_rewrite_titles', $_POST['aiosp_rewrite_titles']);
			update_option('aiosp_use_categories', $_POST['aiosp_use_categories']);
			update_option('aiosp_use_category_description_as_title', $_POST['aiosp_use_category_description_as_title']);
			update_option('aiosp_category_noindex', $_POST['aiosp_category_noindex']);
			update_option('aiosp_archive_noindex', $_POST['aiosp_archive_noindex']);
			update_option('aiosp_generate_descriptions', $_POST['aiosp_generate_descriptions']);
			wp_cache_flush();
		}

?>
<?php if ($message) : ?>
<div id="message" class="updated fade"><p><?php echo $message; ?></p></div>
<?php endif; ?>
<div id="dropmessage" class="updated" style="display:none;"></div>
<div class="wrap">
<h2><?php _e('All in One SEO Plugin Options', 'all_in_one_seo_pack'); ?></h2>
<p>
<?php _e('<a target="_blank" title="All in One SEO Plugin Feedback" href="http://wp.uberdose.com/2007/03/24/all-in-one-seo-pack/#respond">Feedback</a>', 'all_in_one_seo_pack') ?>
| <?php _e('<a target="_blank" title="All in One SEO Plugin Help" href="http://wp.uberdose.com/2007/05/11/all-in-one-seo-pack-help/">Help</a>', 'all_in_one_seo_pack') ?>
</p>
<form name="dofollow" action="" method="post">
<table>
<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<a target="_blank" title="<?php _e('Help for Option Home Title', 'all_in_one_seo_pack')?>" href="http://wp.uberdose.com/2007/05/11/all-in-one-seo-pack-help/#hometitle">
<?php _e('Home Title:', 'all_in_one_seo_pack')?>
</a>
</td>
<td>
<textarea cols="60" rows="2" name="aiosp_home_title"><?php echo stripcslashes(get_option('aiosp_home_title')); ?></textarea>
</td>
</tr>
<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<a target="_blank" title="<?php _e('Help for Option Home Description', 'all_in_one_seo_pack')?>" href="http://wp.uberdose.com/2007/05/11/all-in-one-seo-pack-help/#homedescription">
<?php _e('Home Description:', 'all_in_one_seo_pack')?>
</a>
</td>
<td>
<textarea cols="60" rows="2" name="aiosp_home_description"><?php echo stripcslashes(get_option('aiosp_home_description')); ?></textarea>
</td>
</tr>
<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<a target="_blank" title="<?php _e('Help for Option Home Keywords', 'all_in_one_seo_pack')?>" href="http://wp.uberdose.com/2007/05/11/all-in-one-seo-pack-help/#homekeywords">
<?php _e('Home Keywords (comma separated):', 'all_in_one_seo_pack')?>
</a>
</td>
<td>
<textarea cols="60" rows="2" name="aiosp_home_keywords"><?php echo stripcslashes(get_option('aiosp_home_keywords')); ?></textarea>
</td>
</tr>
<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<a target="_blank" title="<?php _e('Help for Option Rewrite Titles', 'all_in_one_seo_pack')?>" href="http://wp.uberdose.com/2007/05/11/all-in-one-seo-pack-help/#rewritetitles">
<?php _e('Rewrite Titles:', 'all_in_one_seo_pack')?>
</a>
</td>
<td>
<input type="checkbox" name="aiosp_rewrite_titles" <?php if (get_option('aiosp_rewrite_titles')) echo "checked=\"1\""; ?>/>
</td>
</tr>
<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<a target="_blank" title="<?php _e('Help for Option Categories for META keywords', 'all_in_one_seo_pack')?>" href="http://wp.uberdose.com/2007/05/11/all-in-one-seo-pack-help/#categorymetakeywords">
<?php _e('Use Categories for META keywords:', 'all_in_one_seo_pack')?>
</td>
<td>
<input type="checkbox" name="aiosp_use_categories" <?php if (get_option('aiosp_use_categories')) echo "checked=\"1\""; ?>/>
</td>
</tr>
<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<a target="_blank" title="<?php _e('Help for Option Category Description as Title', 'all_in_one_seo_pack')?>" href="http://wp.uberdose.com/2007/05/11/all-in-one-seo-pack-help/#usecategorydescriptionastitle">
<?php _e('Use Category Description as Title:', 'all_in_one_seo_pack')?>
</a>
</td>
<td>
<input type="checkbox" name="aiosp_use_category_description_as_title" <?php if (get_option('aiosp_use_category_description_as_title')) echo "checked=\"1\""; ?>/>
</td>
</tr>
<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<a target="_blank" title="<?php _e('Help for Option noindex for Categories', 'all_in_one_seo_pack')?>" href="http://wp.uberdose.com/2007/05/11/all-in-one-seo-pack-help/#usenoindexforcategories">
<?php _e('Use noindex for Categories:', 'all_in_one_seo_pack')?>
</a>
</td>
<td>
<input type="checkbox" name="aiosp_category_noindex" <?php if (get_option('aiosp_category_noindex')) echo "checked=\"1\""; ?>/>
</td>
</tr>
<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<a target="_blank" title="<?php _e('Help for Option noindex for Archives', 'all_in_one_seo_pack')?>" href="http://wp.uberdose.com/2007/05/11/all-in-one-seo-pack-help/#usenoindexforarchives">
<?php _e('Use noindex for Archives:', 'all_in_one_seo_pack')?>
</a>
</td>
<td>
<input type="checkbox" name="aiosp_archive_noindex" <?php if (get_option('aiosp_archive_noindex')) echo "checked=\"1\""; ?>/>
</td>
</tr>
<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<a target="_blank" title="<?php _e('Help for Autogenerate Descriptions', 'all_in_one_seo_pack')?>" href="http://wp.uberdose.com/2007/05/11/all-in-one-seo-pack-help/#autogeneratedescriptions">
<?php _e('Autogenerate Descriptions:', 'all_in_one_seo_pack')?>
</a>
</td>
<td>
<input type="checkbox" name="aiosp_generate_descriptions" <?php if (get_option('aiosp_generate_descriptions')) echo "checked=\"1\""; ?>/>
</td>
</tr>
<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<a target="_blank" title="<?php _e('Help for Option Max Number of Words in Auto-Generated Descriptions', 'all_in_one_seo_pack')?>" href="http://wp.uberdose.com/2007/05/11/all-in-one-seo-pack-help/#maxwordsdescription">
<?php _e('Max Number of Words in Auto-Generated Descriptions:', 'all_in_one_seo_pack')?>
</a>
</td>
<td>
<input size="5" name="aiosp_max_words_excerpt" value="<?php echo stripcslashes(get_option('aiosp_max_words_excerpt')); ?>"/>
</td>
</tr>
</table>
<p class="submit">
<input type="hidden" name="action" value="aiosp_update" /> 
<input type="hidden" name="page_options" value="aiosp_home_description" /> 
<input type="submit" name="Submit" value="<?php _e('Update Options')?> &raquo;" /> 
</p>
</form>
</div>
<?php
	
	} // plugin_menu

}

add_option("aiosp_home_description", null, __('All in One SEO Plugin Home Description', 'all_in_one_seo_pack'), 'yes');
add_option("aiosp_home_title", null, __('All in One SEO Plugin Home Title', 'all_in_one_seo_pack'), 'yes');
add_option("aiosp_rewrite_titles", 1, __('All in One SEO Plugin Rewrite Titles', 'all_in_one_seo_pack'), 'yes');
add_option("aiosp_use_categories", 1, __('All in One SEO Plugin Use Categories', 'all_in_one_seo_pack'), 'yes');
add_option("aiosp_max_words_excerpt", 25, __('All in One SEO Plugin Maximum Number of Words in Auto-Generated Descriptions', 'all_in_one_seo_pack'), 'yes');
add_option("aiosp_use_category_description_as_title", 0, __('Use Category Description for Title', 'all_in_one_seo_pack'), 'yes');
add_option("aiosp_category_noindex", 1, __('All in One SEO Plugin Noindex for Categories', 'all_in_one_seo_pack'), 'yes');
add_option("aiosp_archive_noindex", 1, __('All in One SEO Plugin Noindex for Archives', 'all_in_one_seo_pack'), 'yes');
add_option("aiosp_generate_descriptions", 0, __('All in One SEO Plugin Autogenerate Descriptions', 'all_in_one_seo_pack'), 'yes');

$aiosp = new All_in_One_SEO_Pack();
add_action('wp_head', array($aiosp, 'wp_head'));
add_action('plugins_loaded', array($aiosp, 'plugins_loaded'));

add_action('init', array($aiosp, 'init_textdomain'));

add_action('simple_edit_form', array($aiosp, 'add_meta_tags_textinput'));
add_action('edit_form_advanced', array($aiosp, 'add_meta_tags_textinput'));
add_action('edit_page_form', array($aiosp, 'add_meta_tags_page_textinput'));

add_action('edit_post', array($aiosp, 'post_meta_tags'));
add_action('publish_post', array($aiosp, 'post_meta_tags'));
add_action('save_post', array($aiosp, 'post_meta_tags'));
add_action('edit_page_form', array($aiosp, 'post_meta_tags'));

add_action('admin_menu', array($aiosp, 'admin_menu'));

?>

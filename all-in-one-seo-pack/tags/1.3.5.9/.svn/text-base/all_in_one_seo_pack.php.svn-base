<?php

/*
Plugin Name: All in One SEO Pack
Plugin URI: http://wp.uberdose.com/2007/03/24/all-in-one-seo-pack/
Description: Out-of-the-box SEO for your Wordpress blog.
Version: 1.3.5.9
Author: uberdose
Author URI: http://wp.uberdose.com/
*/

/*
Copyright (C) 2007 uberdose (seopack AT uberdose DOT com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
 
class All_in_One_SEO_Pack {
	
 	var $version = "1.3.5.9";
 	
 	/**
 	 * Max numbers of chars in auto-generated description.
 	 */
 	var $maximum_description_length = 160;
 	
 	/**
 	 * Minimum number of chars an excerpt should be so that it can be used
 	 * as description. Touch only if you know what you're doing.
 	 */
 	var $minimum_description_length = 1;
 	
 	var $table_prefix = "aiosp_";
 	
 	var $table_categries;
 	
 	var $db_version = '0.1';
 	
 	var $ob_start_detected = false;
 	
 	var $title_start = -1;
 	
 	var $title_end = -1;
 	
 	var $orig_title = '';
 	
	function template_redirect() {
		if (is_feed()) {
			return;
		}

		if (get_option('aiosp_rewrite_titles')) {
			ob_start(array($this, 'output_callback_for_title'));
		}
	}
	
	function output_callback_for_title($content) {
		return $this->rewrite_title($content);
	}

	function init() {
		if(function_exists('load_plugin_textdomain')) {
			load_plugin_textdomain('all_in_one_seo_pack', 'wp-content/plugins/all-in-one-seo-pack');
		}
	}

	function is_static_front_page() {
		global $wp_query;
		$post = $wp_query->get_queried_object();
		return get_option('show_on_front') == 'page' && is_page() && $post->ID == get_option('page_on_front');
	}
	
	function is_static_posts_page() {
		global $wp_query;
		$post = $wp_query->get_queried_object();
		return get_option('show_on_front') == 'page' && is_home() && $post->ID == get_option('page_for_posts');
	}
	
	function wp_head() {
		if (is_feed()) {
			return;
		}
		if (get_option('aiosp_rewrite_titles')) {
			// make the title rewrite as short as possible
			$active_handlers = ob_list_handlers();
			if (sizeof($active_handlers) > 0 &&
				strtolower($active_handlers[sizeof($active_handlers) - 1]) ==
				strtolower('All_in_One_SEO_Pack::output_callback_for_title')) {
				ob_end_flush();
			} else {
				// if we get here there *could* be trouble with another plugin :(
				$this->ob_start_detected = true;
				echo "\n";
				foreach (ob_list_handlers() as $handler) {
					echo "<!-- all in one seo pack output handler $handler -->\n";
				}
			}
		}
		
		global $wp_query;
		$post = $wp_query->get_queried_object();
		$meta_string = null;
		
		echo "<!-- all in one seo pack $this->version ";
		if ($this->ob_start_detected) {
			echo "ob_start_detected ";
		}
		echo "[$this->title_start,$this->title_end,$this->orig_title] ";
		echo "-->\n";
		
		if ((is_home() && !$this->is_static_posts_page() && get_option('aiosp_home_keywords')) || $this->is_static_front_page()) {
			$keywords = trim(get_option('aiosp_home_keywords'));
		} else {
			$keywords = $this->get_all_keywords();
		}
		if (is_single() || is_page()) {
            if ($this->is_static_front_page()) {
				$description = trim(stripslashes(get_option('aiosp_home_description')));
            } else {
            	$description = $this->get_post_description($post);
            }
		} else if (is_home()) {
			if ($this->is_static_posts_page()) {
            	$description = $this->get_post_description(get_post(get_option('page_for_posts')));
			} else {
				$description = trim(stripslashes(get_option('aiosp_home_description')));
			}
		} else if (is_category()) {
			$description = category_description();
		}
		
		if (isset($description) && strlen($description) > $this->minimum_description_length) {
			$description = trim(strip_tags($description));
			$description = str_replace('"', '', $description);
			
			// replace newlines on mac / windows?
			$description = str_replace("\r\n", ' ', $description);
			
			// maybe linux uses this alone
			$description = str_replace("\n", ' ', $description);
			
			if (isset($meta_string)) {
				$meta_string .= "\n";
			} else {
				$meta_string = '';
			}
			
			// description format
            $description_format = get_option('aiosp_description_format');
            if (!isset($description_format) || empty($description_format)) {
            	$description_format = "%description%";
            }
            $description = str_replace('%description%', $description, $description_format);
            $description = str_replace('%blog_title%', get_bloginfo('name'), $description);
            $description = str_replace('%blog_description%', get_bloginfo('description'), $description);
			
			$meta_string .= sprintf("<meta name=\"description\" content=\"%s\"/>", $description);
		}

		if (isset ($keywords) && !empty($keywords)) {
			if (isset($meta_string)) {
				$meta_string .= "\n";
			}
			$meta_string .= sprintf("<meta name=\"keywords\" content=\"%s\"/>", $keywords);
		}

		if (function_exists('is_tag')) {
			$is_tag = is_tag();
		}
		
		if ((is_category() && get_option('aiosp_category_noindex')) ||
			(!is_category() && is_archive() &&!$is_tag && get_option('aiosp_archive_noindex')) ||
			(get_option('aiosp_tags_noindex') && $is_tag)) {
			if (isset($meta_string)) {
				$meta_string .= "\n";
			}
			$meta_string = '<meta name="robots" content="noindex,follow" />';
		}
		
		if ($meta_string != null) {
			echo "$meta_string\n";
		}
	}
	
	function get_post_description($post) {
	    $description = trim(stripslashes(get_post_meta($post->ID, "description", true)));
		if (!$description) {
			$description = $this->trim_excerpt_without_filters_full_length($post->post_excerpt);
			if (!$description && get_option("aiosp_generate_descriptions")) {
				$description = $this->trim_excerpt_without_filters($post->post_content);
			}				
		}
		return $description;
	}
	
	function replace_title($content, $title) {
		$title = trim(strip_tags($title));
		
		if (function_exists('langswitch_filter_langs_with_message')) {
			$title = langswitch_filter_langs_with_message($title);
		}
		
		$title_tag_start = "<title>";
		$title_tag_end = "</title>";
		$len_start = strlen($title_tag_start);
		$len_end = strlen($title_tag_end);
		$title = stripslashes(trim($title));
		$start = strpos($content, "<title>");
		$end = strpos($content, "</title>");
		
		$this->title_start = $start;
		$this->title_end = $end;
		$this->orig_title = $title;
		
		if ($start && $end) {
			$header = substr($content, 0, $start + $len_start) . $title .  substr($content, $end);
		} else {
			// this breaks some sitemap plugins (like wpg2)
			//$header = $content . "<title>$title</title>";
			
			$header = $content;
		}
		
		return $header;
	}
	
	function rewrite_title($header) {
		global $wp_query;
		if (!$wp_query) {
			$header .= "<!-- no wp_query found! -->\n";
			return $header;	
		}
		
		$post = $wp_query->get_queried_object();
		
		// the_search_query() is not suitable, it cannot just return
		global $s;

		if (is_home()) {
			if ($this->is_static_posts_page()) {
				$title = get_post_meta(get_option('page_for_posts'), "title", true);
				if (!$title) {
					$title = wp_title('', false);
				}
	            $title_format = get_option('aiosp_page_title_format');
	            $new_title = str_replace('%blog_title%', get_bloginfo('name'), $title_format);
	            $new_title = str_replace('%page_title%', $title, $new_title);
	            $new_title = str_replace('%blog_description%', get_bloginfo('description'), $new_title);
				$title = trim($new_title);
				$header = $this->replace_title($header, $title);
			} else {
				if (get_option('aiosp_home_title')) {
					$header = $this->replace_title($header, get_option('aiosp_home_title'));
				}
			}
		} else if (is_single()) {
			$categories = get_the_category();
			$category = '';
			if (count($categories) > 0) {
				$category = $categories[0]->cat_name;
			}
			$title = get_post_meta($post->ID, "title", true);
			if (!$title) {
				$title = get_post_meta($post->ID, "title_tag", true);
				if (!$title) {
					$title = wp_title('', false);
				}
			}
            $title_format = get_option('aiosp_post_title_format');
            $new_title = str_replace('%blog_title%', get_bloginfo('name'), $title_format);
            $new_title = str_replace('%blog_description%', get_bloginfo('description'), $new_title);
            $new_title = str_replace('%post_title%', $title, $new_title);
            $new_title = str_replace('%category%', $category, $new_title);
            $new_title = str_replace('%category_title%', $category, $new_title);
			$title = $new_title;
			$title = trim($title);
			$header = $this->replace_title($header, $title);
		} else if (is_search() && isset($s) && !empty($s)) {
			if (function_exists('attribute_escape')) {
				$search = attribute_escape(stripslashes($s));
			} else {
				$search = wp_specialchars(stripslashes($s), true);
			}
			$search = $this->capitalize($search);
            $title_format = get_option('aiosp_search_title_format');
            $title = str_replace('%blog_title%', get_bloginfo('name'), $title_format);
            $title = str_replace('%blog_description%', get_bloginfo('description'), $title);
            $title = str_replace('%search%', $search, $title);
			$header = $this->replace_title($header, $title);
		} else if (is_category() && !is_feed()) {
			$category_description = category_description();
			$category_name = ucwords(single_cat_title('', false));
            $title_format = get_option('aiosp_category_title_format');
            $title = str_replace('%category_title%', $category_name, $title_format);
            $title = str_replace('%category_description%', $category_description, $title);
            $title = str_replace('%blog_title%', get_bloginfo('name'), $title);
            $title = str_replace('%blog_description%', get_bloginfo('description'), $title);
			$header = $this->replace_title($header, $title);
		} else if (is_page()) {
			if ($this->is_static_front_page()) {
				if (get_option('aiosp_home_title')) {
					$header = $this->replace_title($header, get_option('aiosp_home_title'));
				}
			} else {
				$title = get_post_meta($post->ID, "title", true);
				if (!$title) {
					$title = wp_title('', false);
				}
	            $title_format = get_option('aiosp_page_title_format');
	            $new_title = str_replace('%blog_title%', get_bloginfo('name'), $title_format);
	            $new_title = str_replace('%blog_description%', get_bloginfo('description'), $new_title);
	            $new_title = str_replace('%page_title%', $title, $new_title);
				$title = trim($new_title);
				$header = $this->replace_title($header, $title);
			}
		} else if (function_exists('is_tag') && is_tag()) {
			global $utw;
			if ($utw) {
				$tags = $utw->GetCurrentTagSet();
				$tag = $tags[0]->tag;
	            $tag = str_replace('-', ' ', $tag);
			} else {
				// wordpress > 2.3
				$tag = wp_title('', false);
			}
			if ($tag) {
	            $tag = $this->capitalize($tag);
	            $title_format = get_option('aiosp_tag_title_format');
	            $title = str_replace('%blog_title%', get_bloginfo('name'), $title_format);
	            $title = str_replace('%blog_description%', get_bloginfo('description'), $title);
	            $title = str_replace('%tag%', $tag, $title);
				$header = $this->replace_title($header, $title);
			}
		} else if (is_archive()) {
			$date = wp_title('', false);
            $title_format = get_option('aiosp_archive_title_format');
            $new_title = str_replace('%blog_title%', get_bloginfo('name'), $title_format);
            $new_title = str_replace('%blog_description%', get_bloginfo('description'), $new_title);
            $new_title = str_replace('%date%', $date, $new_title);
			$title = trim($new_title);
			$header = $this->replace_title($header, $title);
		} else if (is_404()) {
            $title_format = get_option('aiosp_404_title_format');
            $new_title = str_replace('%blog_title%', get_bloginfo('name'), $title_format);
            $new_title = str_replace('%blog_description%', get_bloginfo('description'), $new_title);
            $new_title = str_replace('%request_url%', $_SERVER['REQUEST_URI'], $new_title);
            $new_title = str_replace('%request_words%', $this->request_as_words($_SERVER['REQUEST_URI']), $new_title);
			$header = $this->replace_title($header, $new_title);
		}
		
		return $header;

	}
	
	/**
	 * @return User-readable nice words for a given request.
	 */
	function request_as_words($request) {
		$request = str_replace('.html', ' ', $request);
		$request = str_replace('.htm', ' ', $request);
		$request = str_replace('.', ' ', $request);
		$request = str_replace('/', ' ', $request);
		$request_a = explode(' ', $request);
		$request_new = array();
		foreach ($request_a as $token) {
			$request_new[] = ucwords(trim($token));
		}
		$request = implode(' ', $request_new);
		return $request;
	}
	
	function capitalize($s) {
		$s = trim($s);
		$tokens = explode(' ', $s);
		while (list($key, $val) = each($tokens)) {
			$tokens[$key] = trim($tokens[$key]);
			$tokens[$key] = strtoupper(substr($tokens[$key], 0, 1)) . substr($tokens[$key], 1);
		}
		$s = implode(' ', $tokens);
		return $s;
	}
	
	function trim_excerpt_without_filters($text) {
		$text = str_replace(']]>', ']]&gt;', $text);
		$text = strip_tags($text);
		$max = $this->maximum_description_length;
		if ($max < strlen($text)) {
			while($text[$max] != ' ' && $max > $this->minimum_description_length) {
				$max--;
			}
		}
		$text = substr($text, 0, $max);
		return trim(stripslashes($text));
	}
	
	function trim_excerpt_without_filters_full_length($text) {
		$text = str_replace(']]>', ']]&gt;', $text);
		$text = strip_tags($text);
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

	                // custom field keywords
	                $keywords_a = $keywords_i = null;
	                $description_a = $description_i = null;
	                $id = $post->ID;
		            $keywords_i = stripslashes(get_post_meta($post->ID, "keywords", true));
	                $keywords_i = str_replace('"', '', $keywords_i);
	                if (isset($keywords_i) && !empty($keywords_i)) {
	                    $keywords[] = $keywords_i;
	                }
	                
	                // WP 2.3 tags
	                if (function_exists('get_the_tags')) {
	                	$tags = get_the_tags($post->ID);
	                	if ($tags && is_array($tags)) {
		                	foreach ($tags as $tag) {
		                		$keywords[] = $tag->name;
		                	}
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
	                
	                // autometa
	                $autometa = stripslashes(get_post_meta($post->ID, "autometa", true));
	                if (isset($autometa) && !empty($autometa)) {
	                	$autometa_array = explode(' ', $autometa);
	                	foreach ($autometa_array as $e) {
	                		$keywords[] = $e;
	                	}
	                }

	            	if (get_option('aiosp_use_categories') && !is_page()) {
		                $categories = get_the_category($post->ID);
		                foreach ($categories as $category) {
		                	$keywords[] = $category->cat_name;
		                }
	            	}

	            }
	        }
	    }
	    
	    return $this->get_unique_keywords($keywords);
	}
	
	function get_meta_keywords() {
		global $posts;

	    $keywords = array();
	    if (is_array($posts)) {
	        foreach ($posts as $post) {
	            if ($post) {
	                // custom field keywords
	                $keywords_a = $keywords_i = null;
	                $description_a = $description_i = null;
	                $id = $post->ID;
		            $keywords_i = stripslashes(get_post_meta($post->ID, "keywords", true));
	                $keywords_i = str_replace('"', '', $keywords_i);
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

	function edit_category($id) {
		global $wpdb;
		$id = $wpdb->escape($id);
	    $awmp_edit = $_POST["aiosp_edit"];
	    if (isset($awmp_edit) && !empty($awmp_edit)) {
		    $keywords = $wpdb->escape($_POST["aiosp_keywords"]);
		    $title = $wpdb->escape($_POST["aiosp_title"]);
		    $old_category = $wpdb->get_row("select * from $this->table_categories where category_id=$id", OBJECT);
		    if ($old_category) {
		    	$wpdb->query("update $this->table_categories
		    			set meta_title='$title', meta_keywords='$keywords'
		    			where category_id=$id");
		    } else {
		    	$wpdb->query("insert into $this->table_categories(meta_title, meta_keywords, category_id)
		    			values ('$title', '$keywords', $id");
		    }
		    //$wpdb->query("insert into $this->table_categories")
	    	/*
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
		    */
	    }
	}

	function edit_category_form() {
	    global $post;
	    $keywords = stripslashes(get_post_meta($post->ID, 'keywords', true));
	    $title = stripslashes(get_post_meta($post->ID, 'title', true));
	    $description = stripslashes(get_post_meta($post->ID, 'description', true));
		?>
		<input value="aiosp_edit" type="hidden" name="aiosp_edit" />
		<table class="editform" width="100%" cellspacing="2" cellpadding="5">
		<tr>
		<th width="33%" scope="row" valign="top">
		<a href="http://wp.uberdose.com/2007/03/24/all-in-one-seo-pack/"><?php _e('All in One SEO Pack', 'all_in_one_seo_pack') ?></a>
		</th>
		</tr>
		<tr>
		<th width="33%" scope="row" valign="top"><label for="aiosp_title"><?php _e('Title:', 'all_in_one_seo_pack') ?></label></th>
		<td><input value="<?php echo $title ?>" type="text" name="aiosp_title" size="70"/></td>
		</tr>
		<tr>
		<th width="33%" scope="row" valign="top"><label for="aiosp_keywords"><?php _e('Keywords (comma separated):', 'all_in_one_seo_pack') ?></label></th>
		<td><input value="<?php echo $keywords ?>" type="text" name="aiosp_keywords" size="70"/></td>
		</tr>
		</table>
		<?php
	}

	function add_meta_tags_textinput() {
	    global $post;
	    $post_id = $post;
	    if (is_object($post_id)) {
	    	$post_id = $post_id->ID;
	    }
	    $keywords = htmlspecialchars(stripslashes(get_post_meta($post_id, 'keywords', true)));
	    $title = htmlspecialchars(stripslashes(get_post_meta($post_id, 'title', true)));
	    $description = htmlspecialchars(stripslashes(get_post_meta($post_id, 'description', true)));
		?>
		<SCRIPT LANGUAGE="JavaScript">
		<!-- Begin
		function countChars(field,cntfield,maxlimit) {
		if (field.value.length > maxlimit)
		field.value = field.value.substring(0, maxlimit);
		else
		cntfield.value = maxlimit - field.value.length;
		}
		//  End -->
		</script>
		<input value="aiosp_edit" type="hidden" name="aiosp_edit" />
		<table style="margin-bottom:40px; margin-top:30px;">
		<tr>
		<th style="text-align:left;" colspan="2">
		<a href="http://wp.uberdose.com/2007/03/24/all-in-one-seo-pack/"><?php _e('All in One SEO Pack', 'all_in_one_seo_pack') ?></a>
		</th>
		</tr>
		<tr>
		<th scope="row" style="text-align:right;"><?php _e('Title:', 'all_in_one_seo_pack') ?></th>
		<td><input value="<?php echo $title ?>" type="text" name="aiosp_title" size="80"/></td>
		</tr>
		<tr>
		<th scope="row" style="text-align:right;"><?php _e('Description:', 'all_in_one_seo_pack') ?></th>
		<td><textarea name="aiosp_description" rows="1" cols="78"
		onKeyDown="countChars(document.post.aiosp_description,document.post.length1,160)"
		onKeyUp="countChars(document.post.aiosp_description,document.post.length1,160)"><?php echo $description ?></textarea><br/>
		<input readonly type="text" name="length1" size="3" maxlength="3" value="160" />
		<?php _e(' characters left (most search engines use a maximum of 160 chars for the description)', 'all_in_one_seo_pack') ?>
		</td>
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
	    $keywords = htmlspecialchars(stripslashes(get_post_meta($post->ID, 'keywords', true)));
	    $description = htmlspecialchars(stripslashes(get_post_meta($post->ID, 'description', true)));
	    $title = htmlspecialchars(stripslashes(get_post_meta($post->ID, 'title', true)));
		?>
		<SCRIPT LANGUAGE="JavaScript">
		<!-- Begin
		function countChars(field,cntfield,maxlimit) {
		if (field.value.length > maxlimit)
		field.value = field.value.substring(0, maxlimit);
		else
		cntfield.value = maxlimit - field.value.length;
		}
		//  End -->
		</script>
		<input value="aiosp_edit" type="hidden" name="aiosp_edit"/>
		<table style="margin-bottom:40px; margin-top:30px;">
		<tr>
		<th style="text-align:left;" colspan="2">
		<a href="http://wp.uberdose.com/2007/03/24/all-in-one-seo-pack/"><?php _e('All in One SEO Pack', 'all_in_one_seo_pack')?></a>
		</th>
		</tr>
		<tr>
		<th scope="row" style="text-align:right;"><?php _e('Title:', 'all_in_one_seo_pack') ?></th>
		<td><input value="<?php echo $title ?>" type="text" name="aiosp_title" size="80" tabindex="1000"/></td>
		</tr>
		<tr>
		<th scope="row" style="text-align:right;"><?php _e('Description:', 'all_in_one_seo_pack') ?></th>
		<td><textarea name="aiosp_description" rows="1" cols="78" tabindex="1001"
		onKeyDown="countChars(document.post.aiosp_description,document.post.length1,160)"
		onKeyUp="countChars(document.post.aiosp_description,document.post.length1,160)"><?php echo $description ?></textarea><br/>
		<input readonly type="text" name="length1" size="3" maxlength="3" value="160" />
		<?php _e(' characters left (most search engines use a maximum of 160 chars for the description)', 'all_in_one_seo_pack')?></td>
		</tr>
		<tr>
		<th scope="row" style="text-align:right;"><?php _e('Keywords (comma separated):', 'all_in_one_seo_pack') ?></th>
		<td><input value="<?php echo $keywords ?>" type="text" name="aiosp_keywords" size="80" tabindex="1002"/></td>
		</tr>
		</table>
		<?php
	}

	function admin_menu() {
		$file = __FILE__;
		
		// hack for 1.5
		global $wp_version;
		if (substr($wp_version, 0, 3) == '1.5') {
			$file = 'all-in-one-seo-pack/all_in_one_seo_pack.php';
		}
		add_submenu_page('options-general.php', __('All in One SEO', 'all_in_one_seo_pack'), __('All in One SEO', 'all_in_one_seo_pack'), 5, $file, array($this, 'plugin_menu'));
	}
	
	function plugin_menu() {
		$message = null;
		$message_updated = __("All in One SEO Options Updated.", 'all_in_one_seo_pack');
		
		// update options
		if ($_POST['action'] && $_POST['action'] == 'aiosp_update') {
			$message = $message_updated;
			update_option('aiosp_home_title', $_POST['aiosp_home_title']);
			update_option('aiosp_home_description', $_POST['aiosp_home_description']);
			update_option('aiosp_home_keywords', $_POST['aiosp_home_keywords']);
			update_option('aiosp_max_words_excerpt', $_POST['aiosp_max_words_excerpt']);
			update_option('aiosp_rewrite_titles', $_POST['aiosp_rewrite_titles']);
			update_option('aiosp_post_title_format', $_POST['aiosp_post_title_format']);
			update_option('aiosp_page_title_format', $_POST['aiosp_page_title_format']);
			update_option('aiosp_category_title_format', $_POST['aiosp_category_title_format']);
			update_option('aiosp_archive_title_format', $_POST['aiosp_archive_title_format']);
			update_option('aiosp_tag_title_format', $_POST['aiosp_tag_title_format']);
			update_option('aiosp_search_title_format', $_POST['aiosp_search_title_format']);
			update_option('aiosp_description_format', $_POST['aiosp_description_format']);
			update_option('aiosp_404_title_format', $_POST['aiosp_404_title_format']);
			update_option('aiosp_use_categories', $_POST['aiosp_use_categories']);
			update_option('aiosp_category_noindex', $_POST['aiosp_category_noindex']);
			update_option('aiosp_archive_noindex', $_POST['aiosp_archive_noindex']);
			update_option('aiosp_tags_noindex', $_POST['aiosp_tags_noindex']);
			update_option('aiosp_generate_descriptions', $_POST['aiosp_generate_descriptions']);
			update_option('aiosp_debug_info', $_POST['aiosp_debug_info']);
			if (function_exists('wp_cache_flush')) {
				wp_cache_flush();
			}
		}

?>
<?php if ($message) : ?>
<div id="message" class="updated fade"><p><?php echo $message; ?></p></div>
<?php endif; ?>
<div id="dropmessage" class="updated" style="display:none;"></div>
<div class="wrap">
<h2><?php _e('All in One SEO Plugin Options', 'all_in_one_seo_pack'); ?></h2>
<p>
<?php _e("This is version ", 'all_in_one_seo_pack') ?><?php _e("$this->version. ", 'all_in_one_seo_pack') ?>
<a target="_blank" title="<?php _e('All in One SEO Plugin Release History', 'all_in_one_seo_pack')?>"
href="http://wp.uberdose.com/2007/07/27/all-in-one-seo-pack-release-history/"><php _e("Should I upgrade?", 'all_in_one_seo_pack')?>
</a>
</p>
<script type="text/javascript">
<!--
    function toggleVisibility(id) {
       var e = document.getElementById(id);
       if(e.style.display == 'block')
          e.style.display = 'none';
       else
          e.style.display = 'block';
    }
//-->
</script>
<p>
<a target="_blank" title="<?php _e('All in One SEO Plugin Help', 'all_in_one_seo_pack') ?>" href="http://wp.uberdose.com/2007/05/11/all-in-one-seo-pack-help/">
<?php _e('Help', 'all_in_one_seo_pack') ?></a>
| <a target="_blank" title="<?php _e('FAQ', 'all_in_one_seo_pack') ?>"
href="http://wp.uberdose.com/2007/07/11/all-in-one-seo-pack-faq/"><?php _e('FAQ', 'all_in_one_seo_pack') ?></a>
| <a target="_blank" title="<?php _e('All in One SEO Plugin Feedback', 'all_in_one_seo_pack') ?>"
href="http://wp.uberdose.com/2007/03/24/all-in-one-seo-pack/#respond"><?php _e('Feedback', 'all_in_one_seo_pack') ?></a>
| <a target="_blank" title="<?php _e('All in One SEO Plugin Translations', 'all_in_one_seo_pack') ?>"
href="http://wp.uberdose.com/2007/10/02/translations-for-all-in-one-seo-pack/"><?php _e('Translations', 'all_in_one_seo_pack') ?></a>
</p>
<form name="dofollow" action="" method="post">
<table>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<a style="cursor:pointer;" title="<?php _e('Click for Help!', 'all_in_one_seo_pack')?>" onclick="toggleVisibility('aiosp_home_title_tip');">
<?php _e('Home Title:', 'all_in_one_seo_pack')?>
</a>
</td>
<td>
<textarea cols="60" rows="2" name="aiosp_home_title"><?php echo stripcslashes(get_option('aiosp_home_title')); ?></textarea>
<div style="max-width:500px; text-align:left; display:none" id="aiosp_home_title_tip">
<?php
_e('As the name implies, this will be the title of your homepage. This is independent of any other option. If not set, the default blog title will get used.', 'all_in_one_seo_pack');
 ?>
</div>
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<a style="cursor:pointer;" title="<?php _e('Click for Help!', 'all_in_one_seo_pack')?>" onclick="toggleVisibility('aiosp_home_description_tip');">
<?php _e('Home Description:', 'all_in_one_seo_pack')?>
</a>
</td>
<td>
<textarea cols="60" rows="2" name="aiosp_home_description"><?php echo stripcslashes(get_option('aiosp_home_description')); ?></textarea>
<div style="max-width:500px; text-align:left; display:none" id="aiosp_home_description_tip">
<?php
_e('The META description for your homepage. Independent of any other options, the default is no META description at all if this is not set.', 'all_in_one_seo_pack');
 ?>
</div>
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<a style="cursor:pointer;" title="<?php _e('Click for Help!', 'all_in_one_seo_pack')?>" onclick="toggleVisibility('aiosp_home_keywords_tip');">
<?php _e('Home Keywords (comma separated):', 'all_in_one_seo_pack')?>
</a>
</td>
<td>
<textarea cols="60" rows="2" name="aiosp_home_keywords"><?php echo stripcslashes(get_option('aiosp_home_keywords')); ?></textarea>
<div style="max-width:500px; text-align:left; display:none" id="aiosp_home_keywords_tip">
<?php
_e('A comma separated list of your most important keywords for your site that will be written as META keywords on your homepage. Don’t stuff everything in here.', 'all_in_one_seo_pack');
 ?>
</div>
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<a style="cursor:pointer;" title="<?php _e('Click for Help!', 'all_in_one_seo_pack')?>" onclick="toggleVisibility('aiosp_rewrite_titles_tip');">
<?php _e('Rewrite Titles:', 'all_in_one_seo_pack')?>
</a>
</td>
<td>
<input type="checkbox" name="aiosp_rewrite_titles" <?php if (get_option('aiosp_rewrite_titles')) echo "checked=\"1\""; ?>/>
<div style="max-width:500px; text-align:left; display:none" id="aiosp_rewrite_titles_tip">
<?php
_e('Note that this is all about the title tag. This is what you see in your browser’s window title bar. This is NOT visible on a page, only in the window title bar and of course in the source. If set, all page, post, category, search and archive page titles get rewritten. You can specify the format for most of them. For example: The default templates puts the title tag of posts like this: “Blog Archive >> Blog Name >> Post Title” (maybe I’ve overdone slightly). This is far from optimal. With the default post title format, Rewrite Title rewrites this to “Post Title | Blog Name”. If you have manually defined a title (in one of the text fields for All in One SEO Plugin input) this will become the title of your post in the format string.', 'all_in_one_seo_pack');
 ?>
</div>
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<a style="cursor:pointer;" title="<?php _e('Click for Help!', 'all_in_one_seo_pack')?>" onclick="toggleVisibility('aiosp_post_title_format_tip');">
<?php _e('Post Title Format:', 'all_in_one_seo_pack')?>
</a>
</td>
<td>
<input size="59" name="aiosp_post_title_format" value="<?php echo stripcslashes(get_option('aiosp_post_title_format')); ?>"/>
<div style="max-width:500px; text-align:left; display:none" id="aiosp_post_title_format_tip">
<?php
_e('The following macros are supported:', 'all_in_one_seo_pack');
echo('<ul>');
echo('<li>'); _e('%blog_title% - Your blog title', 'all_in_one_seo_pack'); echo('</li>');
echo('<li>'); _e('%blog_description% - Your blog description', 'all_in_one_seo_pack'); echo('</li>');
echo('<li>'); _e('%post_title% - The original title of the post', 'all_in_one_seo_pack'); echo('</li>');
echo('<li>'); _e('%category_title% - The (main) category of the post', 'all_in_one_seo_pack'); echo('</li>');
echo('<li>'); _e('%category% - Alias for %category_title%', 'all_in_one_seo_pack'); echo('</li>');
echo('</ul>');
 ?>
</div>
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<a style="cursor:pointer;" title="<?php _e('Click for Help!', 'all_in_one_seo_pack')?>" onclick="toggleVisibility('aiosp_page_title_format_tip');">
<?php _e('Page Title Format:', 'all_in_one_seo_pack')?>
</a>
</td>
<td>
<input size="59" name="aiosp_page_title_format" value="<?php echo stripcslashes(get_option('aiosp_page_title_format')); ?>"/>
<div style="max-width:500px; text-align:left; display:none" id="aiosp_page_title_format_tip">
<?php
_e('The following macros are supported:', 'all_in_one_seo_pack');
echo('<ul>');
echo('<li>'); _e('%blog_title% - Your blog title', 'all_in_one_seo_pack'); echo('</li>');
echo('<li>'); _e('%blog_description% - Your blog description', 'all_in_one_seo_pack'); echo('</li>');
echo('<li>'); _e('%page_title% - The original title of the page', 'all_in_one_seo_pack'); echo('</li>');
echo('</ul>');
 ?>
</div>
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<a style="cursor:pointer;" title="<?php _e('Click for Help!', 'all_in_one_seo_pack')?>" onclick="toggleVisibility('aiosp_category_title_format_tip');">
<?php _e('Category Title Format:', 'all_in_one_seo_pack')?>
</a>
</td>
<td>
<input size="59" name="aiosp_category_title_format" value="<?php echo stripcslashes(get_option('aiosp_category_title_format')); ?>"/>
<div style="max-width:500px; text-align:left; display:none" id="aiosp_category_title_format_tip">
<?php
_e('The following macros are supported:', 'all_in_one_seo_pack');
echo('<ul>');
echo('<li>'); _e('%blog_title% - Your blog title', 'all_in_one_seo_pack'); echo('</li>');
echo('<li>'); _e('%blog_description% - Your blog description', 'all_in_one_seo_pack'); echo('</li>');
echo('<li>'); _e('%category_title% - The original title of the category', 'all_in_one_seo_pack'); echo('</li>');
echo('<li>'); _e('%category_description% - The description of the category', 'all_in_one_seo_pack'); echo('</li>');
echo('</ul>');
 ?>
</div>
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<a style="cursor:pointer;" title="<?php _e('Click for Help!', 'all_in_one_seo_pack')?>" onclick="toggleVisibility('aiosp_archive_title_format_tip');">
<?php _e('Archive Title Format:', 'all_in_one_seo_pack')?>
</a>
</td>
<td>
<input size="59" name="aiosp_archive_title_format" value="<?php echo stripcslashes(get_option('aiosp_archive_title_format')); ?>"/>
<div style="max-width:500px; text-align:left; display:none" id="aiosp_archive_title_format_tip">
<?php
_e('The following macros are supported:', 'all_in_one_seo_pack');
echo('<ul>');
echo('<li>'); _e('%blog_title% - Your blog title', 'all_in_one_seo_pack'); echo('</li>');
echo('<li>'); _e('%blog_description% - Your blog description', 'all_in_one_seo_pack'); echo('</li>');
echo('<li>'); _e('%date% - The original archive title given by wordpress, e.g. “2007″ or “2007 August”', 'all_in_one_seo_pack'); echo('</li>');
echo('</ul>');
 ?>
</div>
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<a style="cursor:pointer;" title="<?php _e('Click for Help!', 'all_in_one_seo_pack')?>" onclick="toggleVisibility('aiosp_tag_title_format_tip');">
<?php _e('Tag Title Format:', 'all_in_one_seo_pack')?>
</a>
</td>
<td>
<input size="59" name="aiosp_tag_title_format" value="<?php echo stripcslashes(get_option('aiosp_tag_title_format')); ?>"/>
<div style="max-width:500px; text-align:left; display:none" id="aiosp_tag_title_format_tip">
<?php
_e('The following macros are supported:', 'all_in_one_seo_pack');
echo('<ul>');
echo('<li>'); _e('%blog_title% - Your blog title', 'all_in_one_seo_pack'); echo('</li>');
echo('<li>'); _e('%blog_description% - Your blog description', 'all_in_one_seo_pack'); echo('</li>');
echo('<li>'); _e('%tag% - The name of the tag', 'all_in_one_seo_pack'); echo('</li>');
echo('</ul>');
 ?>
</div>
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<a style="cursor:pointer;" title="<?php _e('Click for Help!', 'all_in_one_seo_pack')?>" onclick="toggleVisibility('aiosp_search_title_format_tip');">
<?php _e('Search Title Format:', 'all_in_one_seo_pack')?>
</a>
</td>
<td>
<input size="59" name="aiosp_search_title_format" value="<?php echo stripcslashes(get_option('aiosp_search_title_format')); ?>"/>
<div style="max-width:500px; text-align:left; display:none" id="aiosp_search_title_format_tip">
<?php
_e('The following macros are supported:', 'all_in_one_seo_pack');
echo('<ul>');
echo('<li>'); _e('%blog_title% - Your blog title', 'all_in_one_seo_pack'); echo('</li>');
echo('<li>'); _e('%blog_description% - Your blog description', 'all_in_one_seo_pack'); echo('</li>');
echo('<li>'); _e('%search% - What was searched for', 'all_in_one_seo_pack'); echo('</li>');
echo('</ul>');
 ?>
</div>
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<a style="cursor:pointer;" title="<?php _e('Click for Help!', 'all_in_one_seo_pack')?>" onclick="toggleVisibility('aiosp_description_format_tip');">
<?php _e('Description Format:', 'all_in_one_seo_pack')?>
</a>
</td>
<td>
<input size="59" name="aiosp_description_format" value="<?php echo stripcslashes(get_option('aiosp_description_format')); ?>"/>
<div style="max-width:500px; text-align:left; display:none" id="aiosp_description_format_tip">
<?php
_e('The following macros are supported:', 'all_in_one_seo_pack');
echo('<ul>');
echo('<li>'); _e('%blog_title% - Your blog title', 'all_in_one_seo_pack'); echo('</li>');
echo('<li>'); _e('%blog_description% - Your blog description', 'all_in_one_seo_pack'); echo('</li>');
echo('<li>'); _e('%description% - The original description as determined by the plugin, e.g. the excerpt if one is set or an auto-generated one if that option is set', 'all_in_one_seo_pack'); echo('</li>');
echo('</ul>');
 ?>
</div>
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<a style="cursor:pointer;" title="<?php _e('Click for Help!', 'all_in_one_seo_pack')?>" onclick="toggleVisibility('aiosp_404_title_format_tip');">
<?php _e('404 Title Format:', 'all_in_one_seo_pack')?>
</a>
</td>
<td>
<input size="59" name="aiosp_404_title_format" value="<?php echo stripcslashes(get_option('aiosp_404_title_format')); ?>"/>
<div style="max-width:500px; text-align:left; display:none" id="aiosp_404_title_format_tip">
<?php
_e('The following macros are supported:', 'all_in_one_seo_pack');
echo('<ul>');
echo('<li>'); _e('%blog_title% - Your blog title', 'all_in_one_seo_pack'); echo('</li>');
echo('<li>'); _e('%blog_description% - Your blog description', 'all_in_one_seo_pack'); echo('</li>');
echo('<li>'); _e('%request_url% - The original URL path, like "/url-that-does-not-exist/"', 'all_in_one_seo_pack'); echo('</li>');
echo('<li>'); _e('%request_words% - The URL path in human readable form, like "Url That Does Not Exist"', 'all_in_one_seo_pack'); echo('</li>');
echo('</ul>');
 ?>
</div>
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<a style="cursor:pointer;" title="<?php _e('Click for Help!', 'all_in_one_seo_pack')?>" onclick="toggleVisibility('aiosp_use_categories_tip');">
<?php _e('Use Categories for META keywords:', 'all_in_one_seo_pack')?>
</td>
<td>
<input type="checkbox" name="aiosp_use_categories" <?php if (get_option('aiosp_use_categories')) echo "checked=\"1\""; ?>/>
<div style="max-width:500px; text-align:left; display:none" id="aiosp_use_categories_tip">
<?php
_e('Check this if you want your categories for a given post used as the META keywords for this post (in addition to any keywords and tags you specify on the post edit page).', 'all_in_one_seo_pack');
 ?>
</div>
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<a style="cursor:pointer;" title="<?php _e('Click for Help!', 'all_in_one_seo_pack')?>" onclick="toggleVisibility('aiosp_category_noindex_tip');">
<?php _e('Use noindex for Categories:', 'all_in_one_seo_pack')?>
</a>
</td>
<td>
<input type="checkbox" name="aiosp_category_noindex" <?php if (get_option('aiosp_category_noindex')) echo "checked=\"1\""; ?>/>
<div style="max-width:500px; text-align:left; display:none" id="aiosp_category_noindex_tip">
<?php
_e('Check this for excluding category pages from being crawled. Useful for avoiding duplicate content.', 'all_in_one_seo_pack');
 ?>
</div>
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<a style="cursor:pointer;" title="<?php _e('Click for Help!', 'all_in_one_seo_pack')?>" onclick="toggleVisibility('aiosp_archive_noindex_tip');">
<?php _e('Use noindex for Archives:', 'all_in_one_seo_pack')?>
</a>
</td>
<td>
<input type="checkbox" name="aiosp_archive_noindex" <?php if (get_option('aiosp_archive_noindex')) echo "checked=\"1\""; ?>/>
<div style="max-width:500px; text-align:left; display:none" id="aiosp_archive_noindex_tip">
<?php
_e('Check this for excluding archive pages from being crawled. Useful for avoiding duplicate content.', 'all_in_one_seo_pack');
 ?>
</div>
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<a style="cursor:pointer;" title="<?php _e('Click for Help!', 'all_in_one_seo_pack')?>" onclick="toggleVisibility('aiosp_tags_noindex_tip');">
<?php _e('Use noindex for Tag Archives:', 'all_in_one_seo_pack')?>
</a>
</td>
<td>
<input type="checkbox" name="aiosp_tags_noindex" <?php if (get_option('aiosp_tags_noindex')) echo "checked=\"1\""; ?>/>
<div style="max-width:500px; text-align:left; display:none" id="aiosp_tags_noindex_tip">
<?php
_e('Check this for excluding tag pages from being crawled. Useful for avoiding duplicate content.', 'all_in_one_seo_pack');
 ?>
</div>
</td>
</tr>

<tr>
<th scope="row" style="text-align:right; vertical-align:top;">
<a style="cursor:pointer;" title="<?php _e('Click for Help!', 'all_in_one_seo_pack')?>" onclick="toggleVisibility('aiosp_generate_descriptions_tip');">
<?php _e('Autogenerate Descriptions:', 'all_in_one_seo_pack')?>
</a>
</td>
<td>
<input type="checkbox" name="aiosp_generate_descriptions" <?php if (get_option('aiosp_generate_descriptions')) echo "checked=\"1\""; ?>/>
<div style="max-width:500px; text-align:left; display:none" id="aiosp_generate_descriptions_tip">
<?php
_e('Check this and your META descriptions will get autogenerated if there’s no excerpt.', 'all_in_one_seo_pack');
 ?>
</div>
</td>
</tr>

</table>
<p class="submit">
<input type="hidden" name="action" value="aiosp_update" /> 
<input type="hidden" name="page_options" value="aiosp_home_description" /> 
<input type="submit" name="Submit" value="<?php _e('Update Options', 'all_in_one_seo_pack')?> &raquo;" /> 
</p>
</form>
</div>
<?php
	
	} // plugin_menu

}

add_option("aiosp_home_description", null, 'All in One SEO Plugin Home Description', 'yes');
add_option("aiosp_home_title", null, 'All in One SEO Plugin Home Title', 'yes');
add_option("aiosp_rewrite_titles", 1, 'All in One SEO Plugin Rewrite Titles', 'yes');
add_option("aiosp_use_categories", 0, 'All in One SEO Plugin Use Categories', 'yes');
add_option("aiosp_category_noindex", 1, 'All in One SEO Plugin Noindex for Categories', 'yes');
add_option("aiosp_archive_noindex", 1, 'All in One SEO Plugin Noindex for Archives', 'yes');
add_option("aiosp_tags_noindex", 0, 'All in One SEO Plugin Noindex for Tag Archives', 'yes');
add_option("aiosp_generate_descriptions", 1, 'All in One SEO Plugin Autogenerate Descriptions', 'yes');
add_option("aiosp_post_title_format", '%post_title% | %blog_title%', 'All in One SEO Plugin Post Title Format', 'yes');
add_option("aiosp_page_title_format", '%page_title% | %blog_title%', 'All in One SEO Plugin Page Title Format', 'yes');
add_option("aiosp_category_title_format", '%category_title% | %blog_title%', 'All in One SEO Plugin Category Title Format', 'yes');
add_option("aiosp_archive_title_format", '%date% | %blog_title%', 'All in One SEO Plugin Archive Title Format', 'yes');
add_option("aiosp_tag_title_format", '%tag% | %blog_title%', 'All in One SEO Plugin Tag Title Format', 'yes');
add_option("aiosp_search_title_format", '%search% | %blog_title%', 'All in One SEO Plugin Search Title Format', 'yes');
add_option("aiosp_description_format", '%description%', 'All in One SEO Plugin Description Format', 'yes');
add_option("aiosp_404_title_format", 'Nothing found for %request_words%', 'All in One SEO Plugin 404 Title Format', 'yes');

$aiosp = new All_in_One_SEO_Pack();
add_action('wp_head', array($aiosp, 'wp_head'));
add_action('template_redirect', array($aiosp, 'template_redirect'));

add_action('init', array($aiosp, 'init'));

add_action('simple_edit_form', array($aiosp, 'add_meta_tags_textinput'));
add_action('edit_form_advanced', array($aiosp, 'add_meta_tags_textinput'));
add_action('edit_page_form', array($aiosp, 'add_meta_tags_page_textinput'));
//add_action('edit_category_form', array($aiosp, 'edit_category_form'));

add_action('edit_post', array($aiosp, 'post_meta_tags'));
add_action('publish_post', array($aiosp, 'post_meta_tags'));
add_action('save_post', array($aiosp, 'post_meta_tags'));
add_action('edit_page_form', array($aiosp, 'post_meta_tags'));
//add_action('edit_category', array($aiosp, 'edit_category'));

add_action('admin_menu', array($aiosp, 'admin_menu'));

?>

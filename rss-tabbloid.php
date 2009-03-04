<?php
/**
 * RSS2 Feed Template for displaying RSS2 Posts feed.
 *
 * @package WordPress
 */

header('Content-Type: text/xml; charset=' . get_option('blog_charset'), true);
$more = 1;

function TinyURL($u){
 $ch = curl_init(); 
 $timeout = 5; 
 curl_setopt($ch,CURLOPT_URL,"http://tinyurl.com/api-create.php?url=".$u); 
 curl_setopt($ch,CURLOPT_RETURNTRANSFER,1); 
 curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout); 
 $data = curl_exec($ch); 
 curl_close($ch); 
 return $data; 
}
// Code taken from click tracker plugin
function extract_urls($content){
	$regex_pattern = "/<a[\s]+[^>]*href\s*=\s*[\"\']?([^\'\" >]+)[\'\" >]/i";
	preg_match_all ("/a[\s]+[^>]*?href[\s]?=[\s\"\']+".
                    "(.*?)[\"\']+.*?>"."([^<]+|.*?)?<\/a>/", 
                    $content, &$matches);

	$total = count($matches[0]);
	$links = array();
	for($i=0;$i<$total;$i++){
		$links[$i]['html'] = $matches[0][$i];
		$links[$i]['href'] = $matches[1][$i];
		$links[$i]['link'] = $matches[2][$i];
	}
	return $links;
}

function replace_urls($content) {

	$links = extract_urls($content);

	$_link_count = count($links);
	$_links = $links;
	$footerHTML="";
	$counter=1;
	if ($_link_count>0){
		$footerHTML = "<p><strong>Links</strong>";
		for($i=0;$i<$_link_count;$i++){
			if (!preg_match('#\.(jpg|png|gif)$#', $_links[$i]['href'])){
				$_new_links = $_links[$i]['html']."<sup>[".$counter."]</sup>";
				$content = str_replace($_links[$i]['html'],$_new_links,$content);
				if (strlen($_links[$i]['href'])>64){
					$ahref = TinyURL($_links[$i]['href']);
				} else {
					$ahref  = $_links[$i]['href'];
				}
				$footerHTML = $footerHTML."<br/>[".$counter."] <a href='".$ahref."'>".$ahref."</a>";
				$counter = $counter + 1;  
			}
		}
		$footerHTML = $footerHTML."</p>";
	}
	$content = $content.$footerHTML;
	echo $content;
}
?>
<?php echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'; ?>

<rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:atom="http://www.w3.org/2005/Atom"
	<?php do_action('rss2_ns'); ?>
>

<channel>
	<title><?php bloginfo_rss('name'); wp_title_rss(); ?></title>
	<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
	<link><?php bloginfo_rss('url') ?></link>
	<description><?php bloginfo_rss("description") ?></description>
	<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false); ?></pubDate>
	<?php the_generator( 'rss2' ); ?>
	<language><?php echo get_option('rss_language'); ?></language>
	<?php do_action('rss2_head'); ?>
	<?php while( have_posts()) : the_post(); 
	 
	?>
	<item>
		<title><?php the_title_rss() ?></title>
		<link><?php the_permalink_rss() ?></link>
		<comments><?php comments_link(); ?></comments>
		<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), false); ?></pubDate>
		<dc:creator><?php the_author() ?></dc:creator>
		<?php the_category_rss() ?>

		<guid isPermaLink="false"><?php the_guid(); ?></guid>
<?php if (get_option('rss_use_excerpt')) : ?>
		<description><![CDATA[<?php the_excerpt_rss() ?>]]></description>
<?php else : ?>
		<description><![CDATA[<?php the_excerpt_rss() ?>]]></description>
	<?php if ( strlen( $post->post_content ) > 0 ) { 
	$content = get_the_content();
	$content = apply_filters('the_content', $content);
	$content = str_replace(']]>', ']]&gt;', $content);?>
		<content:encoded><![CDATA[<?php replace_urls($content); ?>]]></content:encoded>
	<?php } else { ?>
		<content:encoded><![CDATA[<?php the_excerpt_rss() ?>]]></content:encoded>
	<?php } ?>
<?php endif; ?>
		<wfw:commentRss><?php echo get_post_comments_feed_link(); ?></wfw:commentRss>
<?php rss_enclosure(); ?>
	<?php do_action('rss2_item'); ?>
	</item>
	<?php 
	endwhile; ?>
</channel>
</rss>

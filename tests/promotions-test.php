<?php
/** Isolated shortcode and redirect tests; not WordPress integration tests. */
define('ABSPATH',__DIR__); define('DAY_IN_SECONDS',86400); define('AGP_FILE',__FILE__); define('AGP_VERSION','0.3.0');
class AGP_Leads {
 public static function public_book($id){return $id===123 && $GLOBALS['public'];}
 public static function input($key){return isset($_POST[$key])&&is_string($_POST[$key])?$_POST[$key]:'';}
 public static function rate($key,$limit){$GLOBALS['rate_key']=$key;return $GLOBALS['rate_ok'];}
}
class AGP_Books {public static function sanitize($f,$v){return preg_match('~^https?://~',$v)?$v:'';}}
function get_post_meta($id,$k,$s){return $GLOBALS['url'];}
function wp_parse_url($u,$part=-1){return $part===-1?parse_url($u):parse_url($u,$part);}
function wp_verify_nonce($n,$action){return $n==='valid'&&$action==='agp_click_123';}
function absint($n){return abs((int)$n);}
function nocache_headers(){}
class Denied extends Exception {public $status;public function __construct($s){$this->status=$s;}}
class Redirected extends Exception {public $url,$status;public function __construct($u,$s){$this->url=$u;$this->status=$s;}}
function wp_die($m,$t='',$a=array()){throw new Denied($a['response']??500);}
function wp_redirect($url,$status,$by){throw new Redirected($url,$status);}
function shortcode_atts($defaults,$a){return array_merge($defaults,array_intersect_key((array)$a,$defaults));}
function sanitize_hex_color($v){return preg_match('/^#(?:[a-f0-9]{3}|[a-f0-9]{6})$/i',$v)?$v:null;}
function sanitize_text_field($v){return strip_tags($v);}
function esc_attr($v){return htmlspecialchars((string)$v,ENT_QUOTES);}
function esc_html($v){return htmlspecialchars((string)$v,ENT_QUOTES);}
function esc_url($v){return esc_attr($v);}
function esc_attr__($s,$domain){return esc_attr($s);}
function get_the_title($id){return '<script>Book</script>';}
function get_post_field($k,$id){return '<b>Chapter description</b>';}
function get_the_post_thumbnail($id,$size,$a){return '<img alt="Cover">';}
function get_permalink($id){return 'https://example.org/book';}
function wp_trim_words($v,$limit){return $v;}
function wp_strip_all_tags($v){return strip_tags($v);}
function strip_shortcodes($v){return $v;}
function admin_url($p){return 'https://example.org/wp-admin/'.$p;}
function wp_nonce_field($a,$b,$c,$d){return '<input name="agp_nonce" value="valid">';}
function get_posts($query){$GLOBALS['query']=$query;return array((object)array('ID'=>123));}
class ClickDB {
 public $prefix='wp_', $params=array(), $writes=0;
 public function prepare($sql,...$args){$this->params=$args;return $sql;}
 public function query($q){$this->writes++;return 1;}
 public function get_results($q){return array((object)array('day'=>gmdate('Y-m-d'),'book_id'=>123,'retailer'=>'www.amazon.com','clicks'=>5));}
}
$wpdb=new ClickDB();
require __DIR__.'/../authorgrowth-pro/includes/class-clicks.php';
require __DIR__.'/../authorgrowth-pro/includes/class-promotions.php';
$n=0;
function check($v,$label){global $n;$n++;if(!$v){throw new Exception('FAIL: '.$label);}}
function click(){try{AGP_Clicks::redirect();}catch(Denied|Redirected $e){return $e;}throw new Exception('No response');}
$GLOBALS['public']=true;$GLOBALS['url']='https://www.amazon.com/dp/EXAMPLE';$GLOBALS['rate_ok']=true;
check(AGP_Clicks::destination(123)===$GLOBALS['url'],'saved retailer accepted');
foreach(array('javascript:alert(1)','https://user:pass@amazon.com/book','https://example.org:444/book') as $url){$GLOBALS['url']=$url;check(AGP_Clicks::destination(123)==='','unsafe destination rejected');}
$GLOBALS['url']='https://www.amazon.com/dp/EXAMPLE';$GLOBALS['public']=false;check(AGP_Clicks::destination(123)==='','private book rejected');check(AGP_Promotions::single(array('id'=>123))==='','private card omitted');$GLOBALS['public']=true;
check(AGP_Clicks::period('2025-12-29','weekly')==='2026-W01','ISO week boundary');
check(AGP_Clicks::period('2026-11-06','monthly')==='2026-11','monthly group');check(AGP_Clicks::period('2026-11-06','daily')==='2026-11-06','daily group');
foreach(array(array(),array('HTTP_USER_AGENT'=>'Googlebot'),array('HTTP_USER_AGENT'=>'Mozilla','HTTP_SEC_PURPOSE'=>'prefetch'),array('HTTP_USER_AGENT'=>'Mozilla','HTTP_DNT'=>'1'),array('HTTP_USER_AGENT'=>'Mozilla','HTTP_SEC_GPC'=>'1')) as $s){check(!AGP_Clicks::eligible($s),'automated or opted-out count excluded');}
check(AGP_Clicks::eligible(array('HTTP_USER_AGENT'=>'Mozilla/5.0')),'browser eligible');
$_SERVER=array('REQUEST_METHOD'=>'GET');check(click()->status===405,'GET cannot count or redirect');
$_SERVER=array('REQUEST_METHOD'=>'POST','HTTP_USER_AGENT'=>'Mozilla/5.0','REMOTE_ADDR'=>'192.0.2.1');$_POST=array('book'=>'123','agp_nonce'=>'bad');check(click()->status===403,'invalid nonce denied');
$_POST['agp_nonce']='valid';$_POST['url']='https://evil.example';$r=click();check($r instanceof Redirected && $r->url===$GLOBALS['url'] && $r->status===303,'ignore visitor destination');
check($wpdb->writes===1 && $wpdb->params===array(gmdate('Y-m-d'),123,'www.amazon.com'),'aggregate book retailer day only');
$GLOBALS['rate_ok']=false;$r=click();check($r instanceof Redirected && $wpdb->writes===1,'rate limit suppresses count without blocking purchase');$GLOBALS['rate_ok']=true;
$_SERVER['HTTP_DNT']='1';$r=click();check($r instanceof Redirected && $wpdb->writes===1,'DNT suppresses count but preserves redirect');unset($_SERVER['HTTP_DNT']);
$a=AGP_Promotions::options(array('color'=>'red;position:fixed','background'=>'#fff','columns'=>99,'limit'=>999,'layout'=>'bad','button'=>'<b>Shop</b>'));
check($a['color']==='#F97316' && $a['background']==='#fff','strict color validation');check($a['columns']===4 && $a['limit']===50 && $a['layout']==='grid','bound queries and layouts');check($a['button']==='Shop','button text sanitized');
$html=AGP_Promotions::single(array('id'=>123));check(str_contains($html,'&lt;script&gt;Book&lt;/script&gt;')&&!str_contains($html,'<script>'),'title escaped');check(str_contains($html,'method="post"')&&str_contains($html,'agp_click'),'card uses tracked button');
$html=AGP_Promotions::collection(array('ids'=>'123,5','layout'=>'list','columns'=>2));check(str_contains($html,'agp-promo-list')&&$GLOBALS['query']['post__in']===array(123,5)&&$GLOBALS['query']['has_password']===false,'selected books list query');
check(AGP_Promotions::collection(array('ids'=>'bad'))==='','empty ID selection never shows all');
check(str_contains(AGP_Promotions::floating(array('book'=>123)),'agp-floating'),'sticky promotion rendered');check(AGP_Promotions::floating(array('book'=>123))==='','only one sticky bar per page');
function current_user_can($cap){return $GLOBALS['admin_allowed'];}
function selected($a,$b,$echo=false){return $a===$b?'selected="selected"':'';}
function submit_button($a,$b,$c,$d){echo '<button>Apply</button>';}
function number_format_i18n($n){return number_format($n);}
$GLOBALS['admin_allowed']=false;try{AGP_Clicks::admin();}catch(Denied $e){check($e->status===403,'analytics rejects unauthorized access');}
$GLOBALS['admin_allowed']=true;$_GET=array('days'=>'90','group'=>'weekly','book'=>'123');ob_start();AGP_Clicks::admin();$report=ob_get_clean();
check(str_contains($report,'www.amazon.com') && str_contains($report,AGP_Clicks::period(gmdate('Y-m-d'),'weekly')) && str_contains($report,'Recorded clicks'),'report contains grouped data');
check(str_contains($report,'&lt;script&gt;Book&lt;/script&gt;'),'report escapes book title');
$_GET=array('days'=>array('7'),'group'=>array('weekly'),'book'=>array('123'));ob_start();AGP_Clicks::admin();$report=ob_get_clean();
check(str_contains($report,'value="30" selected="selected"'),'malformed filter defaults safely');
echo "PASS: $n promotion/analytics assertions\n";

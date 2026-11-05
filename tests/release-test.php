<?php
/** Release-candidate contract tests with WordPress doubles. No live site implied. */
define('ABSPATH',__DIR__); define('AGP_VERSION','0.4.0-rc.1'); define('DAY_IN_SECONDS',86400);
function get_option($key,$default=false){return $GLOBALS['options'][$key]??$default;}
function sanitize_text_field($s){return trim(strip_tags($s));}
function sanitize_textarea_field($s){return trim(strip_tags($s));}
function sanitize_hex_color($v){return preg_match('/^#(?:[a-f0-9]{3}|[a-f0-9]{6})$/i',$v)?$v:null;}
function is_email($v){return filter_var($v,FILTER_VALIDATE_EMAIL);}
function wp_mail($to,$subject,$message,$headers=array()){$GLOBALS['mail']=compact('to','subject','message','headers');return $GLOBALS['mail_ok']??true;}
function esc_attr($s){return htmlspecialchars((string)$s,ENT_QUOTES);}
function esc_html($s){return htmlspecialchars((string)$s,ENT_QUOTES);}
function esc_url($s){return esc_attr($s);}
function absint($v){return abs((int)$v);}
function wp_unslash($s){return is_array($s)?array_map('wp_unslash',$s):stripslashes($s);}
function shortcode_atts($a,$b){return array_merge($a,array_intersect_key((array)$b,$a));}
function wp_unique_id($p){static $i=0;return $p.++$i;}
function get_post_meta($id,$key,$single){return $GLOBALS['meta'][$key]??'';}
function update_post_meta($id,$key,$value){$GLOBALS['meta'][$key]=$value;return true;}
function get_post_type($id){return $id===123?'agp_book':'post';}
function get_post_status($id){return $GLOBALS['post_status']??'publish';}
function get_post_field($key,$id){return $key==='post_password'?($GLOBALS['password']??''):'';}
function wp_is_post_revision($id){return $GLOBALS['revision']??false;}
function current_user_can($cap,...$args){return $GLOBALS['allowed']??true;}
function wp_verify_nonce($value,$action){return $value==='valid';}
function wp_nonce_field($a,$b='_wpnonce',$referer=true,$echo=true){$GLOBALS['nonce_actions'][]=$a;$s='<input name="'.esc_attr($b).'" value="valid">';if($echo){echo $s;}return $s;}
function check_admin_referer($action){if(($GLOBALS['admin_nonce']??'valid')!=='valid'){wp_die('Invalid nonce','',array('response'=>403));}}
class EndRequest extends Exception {public $status;public function __construct($m,$s){parent::__construct($m);$this->status=$s;}}
function wp_die($m,$t='',$a=array()){throw new EndRequest($m,$a['response']??500);}
function admin_url($p){return 'https://example.org/wp-admin/'.$p;}
function wp_safe_redirect($url){throw new EndRequest($url,302);}
function wp_redirect($url,$status,$by){throw new EndRequest($url,$status);}
function add_query_arg($args,$url=null){return ($url??'https://example.org/wp-admin/admin.php').'?'.(is_array($args)?http_build_query($args):$args);}
function get_the_ID(){return 123;}
function esc_html__($s,$domain){return esc_html($s);}
function esc_html_e($s,$domain){echo esc_html($s);}
function __($s,$domain){return $s;}
function wp_count_posts($type,$permission){return (object)array('publish'=>1,'draft'=>2,'future'=>0);}
function the_privacy_policy_link(){echo get_the_privacy_policy_link();}
function get_the_title($id){return 'Test & <book>';}
function get_the_post_thumbnail($id,$size,$a){return '<img alt="Test cover">';}
function get_the_privacy_policy_link(){return '<a href="/privacy">Privacy</a>';}
function current_time($format,$utc){return gmdate('Y-m-d H:i:s');}
function nocache_headers(){}
function wp_salt($kind){return 'test-salt';}
function wp_parse_url($u,$part=-1){return $part===-1?parse_url($u):parse_url($u,$part);}
function esc_url_raw($v,$schemes){return preg_match('~^https?://~',$v)?$v:'';}
function wp_http_validate_url($v){return filter_var($v,FILTER_VALIDATE_URL);}
function wp_insert_comment($data){$GLOBALS['inserted']=$data;return $GLOBALS['insert_ok']??77;}
function get_comments($query){$GLOBALS['comment_query']=$query;return $GLOBALS['comments']??array();}
function get_comment_meta($id,$key,$single){return $GLOBALS['rating']??4;}
function get_comment($id){return $GLOBALS['comment']??null;}
function wp_set_comment_status($id,$status,$error){$GLOBALS['moderated']=array($id,$status);return true;}
function wp_delete_comment($id,$force){$GLOBALS['deleted'][]=$id;return true;}
function is_wp_error($v){return false;}
function checked($a,$b,$echo=false){return $a==$b?'checked':'';}
function selected($a,$b,$echo=false){return $a==$b?'selected':'';}
function submit_button($label,...$args){echo '<button>'.esc_html($label).'</button>';}
function get_bloginfo($v){return '6.5-test-double';}
function is_ssl(){return true;}
function get_privacy_policy_url(){return 'https://example.org/privacy';}
function wp_next_scheduled($key){return time();}
function update_option($key,$value,$autoload=false){$GLOBALS['options'][$key]=$value;return true;}
function number_format_i18n($n){return number_format($n);}
class ReleaseDB {
 public $prefix='wp_', $options='wp_options', $queries=array(),$data=array(),$params=array(),$success=true;
 public function prepare($query,...$values){$this->params=$values;$this->queries[]=array($query,$values);return $query;}
 public function esc_like($v){return $v;}
 public function get_var($q){if(str_contains($q,'option_value')){return $GLOBALS['rate_count']??1;}if(str_contains($q,'SHOW TABLES')){return $this->params[0];}return null;}
 public function query($q){return $this->success?1:false;}
 public function insert($table,$data){$this->data=$data;return $this->success;}
 public function update($table,$data,$where){$this->data=array_merge($this->data,$data);return $this->success;}
 public function get_results($q){return array();}
 public function get_row($q){return (object)array('total'=>0,'verified'=>0,'subscribers'=>0);}
 public function delete($table,$where){return $this->success?1:false;}
}
$wpdb=new ReleaseDB();$GLOBALS['options']=array();
foreach(array('settings','books','leads','clicks','launches','reviews','dashboard','promotions') as $file){require __DIR__.'/../authorgrowth-pro/includes/class-'.$file.'.php';}
$n=0;
function check($ok,$msg){global $n;++$n;if(!$ok){throw new Exception('FAIL: '.$msg);}}
function response($callback){try{$callback();}catch(EndRequest $e){return $e;}throw new Exception('No terminal response');}
function screen($callback){ob_start();$callback();return ob_get_clean();}
$s=AGP_Settings::get();check($s['leads']===1&&$s['reviews']===1,'prior versions enable modules by default');
$s=AGP_Settings::sanitize(array('accent'=>'red;position:fixed','brand'=>'<b>Author</b>','sender_email'=>"a@example.org\r\nBcc: bad@example.org",'pending_days'=>-4,'lead_days'=>99999,'reviews'=>array('1')));
check($s['accent']==='#F97316'&&$s['brand']==='Author','sanitize branding');check($s['sender_email']==='','reject header injection');check($s['pending_days']===1&&$s['lead_days']===3650&&$s['reviews']===0,'bound retention and reject malformed module');
$GLOBALS['options']['agp_settings']=array('sender_email'=>'author@example.org','sender_name'=>"Author\r\nBcc: nobody");
AGP_Settings::mail('reader@example.org','Test','Body');check(!preg_match('/[\r\n]/',end($GLOBALS['mail']['headers'])),'sender headers have no line breaks');check($GLOBALS['mail']['to']==='reader@example.org','mail recipient preserved');
check(AGP_Launches::valid_date('2026-02-30T12:00')==='','reject impossible launch date');check(AGP_Launches::valid_date('2028-02-29T12:30')==='2028-02-29T12:30','leap day launch');check(AGP_Launches::valid_date(array())==='','malformed date rejected');
$GLOBALS['meta']=array('_agp_launch_at'=>gmdate('Y-m-d\TH:i',time()+2*DAY_IN_SECONDS),'_agp_preorder_url'=>'https://shop.example/preorder','_agp_buy_url'=>'https://shop.example/buy');
$html=AGP_Launches::shortcode(array('book'=>123));check(str_contains($html,'data-agp-launch')&&str_contains($html,'agp_waitlist')&&in_array('agp_preorder_123',$GLOBALS['nonce_actions'],true),'launch countdown and waitlist generated');check(str_contains($html,'value="preorder"'),'launch preorder uses typed button');check(str_contains($html,'Test &amp; &lt;book&gt;'),'launch title escaped');
check(AGP_Clicks::destination(123,'preorder')==='https://shop.example/preorder','preorder uses saved destination');check(AGP_Clicks::destination(123,'arbitrary')==='','unknown destination kind rejected');
$GLOBALS['meta']['_agp_launch_at']='2020-01-01T00:00';$html=AGP_Launches::shortcode(array('book'=>123));check(!str_contains($html,'data-agp-launch')&&!str_contains($html,'agp_waitlist')&&str_contains($html,'AVAILABLE NOW'),'past launch switches to Buy Now');check(AGP_Clicks::destination(123,'preorder')==='','past preorder fails closed');
$GLOBALS['meta']['_agp_launch_at']=gmdate('Y-m-d\TH:i',time()+DAY_IN_SECONDS);
$_POST=array('book'=>'123','agp_nonce'=>'valid','email'=>'Reader@example.org','consent'=>'');check(response(array('AGP_Launches','capture'))->status===400,'waitlist requires purpose consent');
$_POST['consent']='1';$_POST['website']='spam';check(response(array('AGP_Launches','capture'))->status===400,'waitlist honeypot');unset($_POST['website']);
check(response(array('AGP_Launches','capture'))->status===200,'waitlist capture');check($wpdb->data['verified_at']===null&&$wpdb->data['email']==='reader@example.org','waitlist pending and normalized');
preg_match('/token=([a-f0-9]{64})/',$GLOBALS['mail']['message'],$m);check(isset($m[1])&&hash('sha256',$m[1])===$wpdb->data['token_hash'],'waitlist token hash at rest');
$GLOBALS['mail_ok']=false;check(response(array('AGP_Launches','capture'))->status===503&&$wpdb->data['token_hash']===null,'waitlist mail failure invalidates pending token');$GLOBALS['mail_ok']=true;
$_GET=array('token'=>'bad');check(response(array('AGP_Launches','confirm'))->status===403,'malformed waitlist confirmation rejected');
$_GET['token']=str_repeat('a',64);check(response(array('AGP_Launches','confirm'))->status===200,'valid confirmation conditional update');$wpdb->success=false;check(response(array('AGP_Launches','confirm'))->status===403,'failed confirmation rejected');$wpdb->success=true;
$data=array('name'=>'Real reader','email'=>'reader@example.org','review'=>'<b>A reader submission.</b>','rating'=>'','consent'=>'1','website'=>'');
check(AGP_Reviews::validate($data)['rating']===0,'optional rating');$data['rating']='6';check(!AGP_Reviews::validate($data),'invalid rating');$data['rating']='4';$data['consent']='';check(!AGP_Reviews::validate($data),'publication consent required');$data['consent']='1';$data['review']=array('bad');check(!AGP_Reviews::validate($data),'malformed review rejected');$data['review']='A real submission from the test fixture.';
$_POST=$data+array('book'=>'123','agp_nonce'=>'valid');check(response(array('AGP_Reviews','submit'))->status===200,'review submitted');check($GLOBALS['inserted']['comment_approved']===0&&$GLOBALS['inserted']['comment_type']==='agp_review','review always pending');check($GLOBALS['inserted']['comment_author_IP']===''&&$GLOBALS['inserted']['comment_agent']==='','reviews store no raw IP or agent');check($GLOBALS['inserted']['comment_meta']['agp_rating']===4,'review rating recorded');
$GLOBALS['rate_count']=6;check(response(array('AGP_Reviews','submit'))->status===429,'review rate rejection');unset($GLOBALS['rate_count']);
$GLOBALS['comments']=array();check(AGP_Reviews::display(array('book'=>123))==='','no synthetic reviews on empty site');
$GLOBALS['comments']=array((object)array('comment_ID'=>77,'comment_author'=>'<Reader>','comment_content'=>'<script>bad</script>','comment_post_ID'=>123));
$html=AGP_Reviews::display(array('book'=>123,'layout'=>'slider'));check(str_contains($html,'agp-review-slider')&&str_contains($html,'data-agp-controls'),'manual slider');check(!str_contains($html,'<script>')&&str_contains($html,'&lt;Reader&gt;'),'review output escaped');check($GLOBALS['comment_query']['status']==='approve','display only approved comments');check(str_contains($html,'out of 5 stars'),'accessible rating');check(!str_contains(AGP_Reviews::display(array('book'=>123,'stars'=>'no')),'out of 5 stars'),'optional stars hidden');
$GLOBALS['comment']=(object)array('comment_type'=>'comment','comment_post_ID'=>123);$_POST=array('id'=>'77','decision'=>'approve');check(response(array('AGP_Reviews','moderate'))->status===403,'cannot moderate unrelated comments');
$GLOBALS['comment']->comment_type='agp_review';check(response(array('AGP_Reviews','moderate'))->status===302&&$GLOBALS['moderated']===array(77,'approve'),'admin approval');
$GLOBALS['admin_nonce']='bad';check(response(array('AGP_Reviews','moderate'))->status===403,'moderation nonce enforced');unset($GLOBALS['admin_nonce']);
$GLOBALS['options']['agp_settings']=array('launches'=>0,'reviews'=>0,'promotions'=>0,'leads'=>0,'analytics'=>0);
check(AGP_Launches::shortcode(array('book'=>123))===''&&AGP_Reviews::form(array('book'=>123))===''&&AGP_Reviews::display(array('book'=>123))===''&&AGP_Promotions::single(array('id'=>123))===''&&AGP_Leads::form(array('book'=>123))==='','disabled public modules hidden');
$_POST=array('book'=>'123','email'=>'reader@example.org','consent'=>'1','agp_nonce'=>'valid');check(response(array('AGP_Launches','capture'))->status===400&&response(array('AGP_Leads','capture'))->status===400&&response(array('AGP_Reviews','submit'))->status===400,'disabled handlers reject requests');
$GLOBALS['options']['agp_settings']=array();
$GLOBALS['comments']=array();$_GET=array();
foreach(array(array('AGP_Settings','admin'),array('AGP_Settings','wizard'),array('AGP_Launches','admin'),array('AGP_Reviews','admin'),array('AGP_Leads','admin'),array('AGP_Clicks','admin'),array('AGP_Dashboard','render')) as $callback){
 check(strlen(screen($callback))>100,'admin screen renders: '.implode('::',$callback));
}
foreach(array(1,2,3) as $step){$_GET=array('step'=>$step);check(str_contains(screen(array('AGP_Settings','wizard')),'setup'),'wizard step renders');}
$GLOBALS['allowed']=false;foreach(array(array('AGP_Settings','admin'),array('AGP_Settings','wizard'),array('AGP_Launches','admin'),array('AGP_Reviews','admin')) as $callback){check(response($callback)->status===403,'admin access denied');}$GLOBALS['allowed']=true;
$_POST=array('agp_launch_nonce'=>'bad','agp_launch_at'=>'2030-01-01T00:00');$old=$GLOBALS['meta']['_agp_launch_at'];AGP_Launches::save(123);check($GLOBALS['meta']['_agp_launch_at']===$old,'launch save nonce rejected');$_POST['agp_launch_nonce']='valid';AGP_Launches::save(123);check($GLOBALS['meta']['_agp_launch_at']==='2030-01-01T00:00','authorized launch save');
$GLOBALS['options']['agp_settings']=array();
check(str_contains(AGP_Reviews::form(array('book'=>123)),'Submit for approval'),'review form renders');
check(strlen(screen(function(){AGP_Launches::box((object)array('ID'=>123));}))>100,'launch metabox renders');
check(strlen(screen(function(){AGP_Books::box((object)array('ID'=>123));}))>100,'book metabox renders');
$dir=sys_get_temp_dir().'/agp-rc-private-'.getmypid();mkdir($dir);define('AGP_PRIVATE_DIR',$dir);$name=str_repeat('c',32).'.pdf';file_put_contents($dir.'/'.$name,'%PDF-test');$GLOBALS['meta']['_agp_private_pdf']=$name;
try{check(str_contains(AGP_Leads::form(array('book'=>123)),'Read a free chapter'),'chapter shortcode renders');check(AGP_Leads::pdf(123)===$dir.'/'.$name,'private PDF path survives release');}finally{unlink($dir.'/'.$name);rmdir($dir);}
check(AGP_Launches::privacy_export('reader@example.org')['done']&&AGP_Launches::privacy_erase('reader@example.org')['items_removed'],'waitlist privacy operations');
$GLOBALS['options']['agp_settings']=array('lead_days'=>30,'click_days'=>90,'pending_days'=>2);$wpdb->queries=array();AGP_Leads::cleanup();AGP_Launches::cleanup();check(count($wpdb->queries)>=5,'configured retention issues bounded date deletions');
$GLOBALS['comments']=array(77);AGP_Reviews::cleanup();check(in_array(77,$GLOBALS['deleted'],true)&&$GLOBALS['comment_query']['type']==='agp_review','cleanup scoped to review type');
$GLOBALS['comments']=array((object)array('comment_ID'=>77,'comment_author'=>'Reader','comment_author_email'=>'reader@example.org','comment_content'=>'Review text','comment_post_ID'=>123));
check(AGP_Reviews::privacy_export('reader@example.org')['data'][0]['data'][1]['value']==='reader@example.org','review privacy export includes email');
$GLOBALS['comments']=array(77);check(AGP_Reviews::privacy_erase('reader@example.org')['items_removed']&&$GLOBALS['comment_query']['author_email']==='reader@example.org','review privacy erasure scoped to email');
echo "PASS: $n Phase 4 assertions\n";

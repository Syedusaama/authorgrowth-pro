<?php
function get_option($key,$default=false){return $default;}
/** Isolated request-handler tests. No WordPress/database/mail integration implied. */
$root = sys_get_temp_dir() . '/agp-public-' . getmypid(); mkdir($root);
$private = sys_get_temp_dir() . '/agp-private-' . getmypid(); mkdir($private);
define('ABSPATH', $root . '/'); define('AGP_PRIVATE_DIR', $private);
$_SERVER['DOCUMENT_ROOT'] = $root; $_SERVER['REMOTE_ADDR'] = '192.0.2.1';
require __DIR__ . '/../authorgrowth-pro/includes/class-settings.php';
require __DIR__ . '/../authorgrowth-pro/includes/class-leads.php';
function sanitize_hex_color($v){return preg_match('/^#(?:[a-f0-9]{3}|[a-f0-9]{6})$/i',$v)?$v:null;}
function sanitize_text_field($v){return strip_tags($v);}
class StopRequest extends Exception { public $status; public function __construct($msg, $status) { parent::__construct($msg); $this->status=$status; } }
function wp_die($msg, $title='', $args=array()) { throw new StopRequest($msg, $args['response'] ?? 500); }
function wp_unslash($s) { return stripslashes($s); }
function absint($v) { return abs((int)$v); }
function wp_verify_nonce($v,$action) { return $v==='valid'; }
function get_post_type($id) { return $id===1 ? 'agp_book' : 'post'; }
function get_post_status($id) { return $GLOBALS['status'] ?? 'publish'; }
function get_post_field($key,$id) { return $GLOBALS['password'] ?? ''; }
function get_post_meta($id,$key,$single) { return $GLOBALS['pdf_name']; }
function is_email($s) { return filter_var($s, FILTER_VALIDATE_EMAIL); }
function wp_salt($v) { return 'test-salt'; }
function current_time($v,$utc) { return '2026-11-06 00:00:00'; }
function admin_url($p) { return 'https://example.org/wp-admin/'.$p; }
function add_query_arg($args,$url) { return $url.'?'.http_build_query($args); }
function wp_mail($email,$subject,$body) { $GLOBALS['mail']=$body; return $GLOBALS['mail_ok']; }
class FakeDB {
 public $prefix='wp_', $options='wp_options', $data=array(), $count=1, $write_ok=true, $existing=null;
 public function prepare($query,...$values) { return $query; }
 public function query($q) { return $this->write_ok ? 1 : false; }
 public function get_var($q) { return str_contains($q,'option_value') ? $this->count : $this->existing; }
 public function esc_like($v) { return $v; }
 public function insert($table,$data) { $this->data=$data; return $this->write_ok; }
 public function update($table,$data,$where) { $this->data=array_merge($this->data,$data); return $this->write_ok; }
}
$wpdb=new FakeDB(); $GLOBALS['mail_ok']=true;
$n=0;
function check($ok,$msg) { global $n; ++$n; if (!$ok) { throw new Exception('FAIL: '.$msg); } }
function capture_status() { try { AGP_Leads::capture(); } catch(StopRequest $e) { return $e->status; } throw new Exception('Handler did not finish'); }
try {
 $GLOBALS['pdf_name']=str_repeat('a',32).'.pdf'; file_put_contents($private.'/'.$GLOBALS['pdf_name'], '%PDF-test');
 check(AGP_Leads::private_dir()===$private, 'accept private directory');
 check(AGP_Leads::pdf(1)===$private.'/'.$GLOBALS['pdf_name'], 'resolve private PDF');
 $GLOBALS['pdf_name']='../../secret.pdf'; check(!AGP_Leads::pdf(1),'reject traversal');
 $GLOBALS['pdf_name']=str_repeat('b',32).'.pdf'; symlink($root.'/public.pdf', $private.'/'.$GLOBALS['pdf_name']); file_put_contents($root.'/public.pdf','%PDF-test');
 check(!AGP_Leads::pdf(1),'reject symlink escaping directory');
 $GLOBALS['pdf_name']=str_repeat('a',32).'.pdf';
 check(AGP_Leads::public_book(1),'published book allowed');
 $GLOBALS['password']='secret'; check(!AGP_Leads::public_book(1),'password protected book denied'); unset($GLOBALS['password']);
 $GLOBALS['status']='draft'; check(!AGP_Leads::public_book(1),'draft denied'); unset($GLOBALS['status']);
 $_POST=array('book'=>'1','agp_nonce'=>'bad','email'=>'reader@example.org'); check(capture_status()===400,'bad nonce denied');
 $_POST['agp_nonce']='valid'; $_POST['email']='not-email'; check(capture_status()===400,'invalid email denied');
 $_POST['email']=array('bad'); check(capture_status()===400,'array email denied');
 $_POST['email']='Reader@example.org'; $_POST['website']='spam'; check(capture_status()===400,'honeypot denied'); unset($_POST['website']);
 $wpdb->count=11; check(capture_status()===429,'rate limit enforced'); $wpdb->count=1;
 $wpdb->write_ok=false; check(!AGP_Leads::rate('test',10),'database failure fails closed'); $wpdb->write_ok=true;
 check(capture_status()===200,'valid capture succeeds');
 check($wpdb->data['email']==='reader@example.org','email normalized');
 check($wpdb->data['consent']===0 && $wpdb->data['consent_text']==='', 'optional consent remains off');
 check($wpdb->data['verified_at']===null,'new email remains unverified');
 preg_match('/token=([a-f0-9]{64})/',$GLOBALS['mail'],$matches);
 check(isset($matches[1]) && hash('sha256',$matches[1])===$wpdb->data['token_hash'],'email bearer token hashed at rest');
 check($wpdb->data['expires']>=time()+1795 && $wpdb->data['expires']<=time()+1800,'30 minute expiry');
 $_POST['consent']='1'; $wpdb->existing=2; check(capture_status()===200,'repeat request accepted');
 check($wpdb->data['consent']===1 && $wpdb->data['consent_text']===AGP_Leads::CONSENT, 'consent wording recorded');
 $GLOBALS['mail_ok']=false; check(capture_status()===503,'mail failure reported'); check($wpdb->data['token_hash']===null,'mail failure invalidates token');
 foreach(array('=SUM(A1)','+123','-1','@cmd',"\t=evil") as $cell) { check(str_starts_with(AGP_Leads::csv_cell($cell),"'"),'CSV formula escaped'); }
 check(AGP_Leads::csv_cell('reader@example.org')==='reader@example.org','normal CSV unchanged');
 $_GET['token']='bad'; try { AGP_Leads::download(); } catch(StopRequest $e) { check($e->status===403,'malformed download token denied'); }
 echo "PASS: $n lead assertions\n";
} finally { foreach(glob($private.'/*') as $f) { unlink($f); } foreach(glob($root.'/*') as $f) { unlink($f); } rmdir($private); rmdir($root); }

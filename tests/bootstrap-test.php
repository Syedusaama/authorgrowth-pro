<?php
/** Simulated bootstrap/upgrade contracts; dbDelta is a double, not MySQL. */
$root=sys_get_temp_dir().'/agp-bootstrap-'.getmypid();mkdir($root.'/wp-admin/includes',0700,true);file_put_contents($root.'/wp-admin/includes/upgrade.php','<?php');
define('ABSPATH',$root.'/');define('DAY_IN_SECONDS',86400);
function add_action($hook,$callback,$priority=10,$args=1){$GLOBALS['actions'][$hook][]=$callback;}
function add_filter(...$args){}
function add_shortcode($tag,$cb){$GLOBALS['shortcodes'][$tag]=$cb;}
function register_activation_hook($file,$cb){$GLOBALS['activation']=$cb;}
function register_deactivation_hook($file,$cb){$GLOBALS['deactivation']=$cb;}
function get_option($key,$default=false){return $GLOBALS['options'][$key]??$default;}
function update_option($key,$value,$autoload=false){$GLOBALS['options'][$key]=$value;}
function dbDelta($sql){if(!preg_match('/CREATE TABLE (\w+)/',$sql,$m)){throw new Exception('Invalid schema');}$GLOBALS['ddl'][]=$sql;if(empty($GLOBALS['fail_install'])){$GLOBALS['tables'][$m[1]]=true;}}
function register_post_type($type,$args){$GLOBALS['post_types'][$type]=$args;}
function register_post_meta(...$args){}
function wp_next_scheduled($hook){return $GLOBALS['scheduled']??false;}
function wp_schedule_event($time,$schedule,$hook){$GLOBALS['scheduled']=$time;}
function wp_clear_scheduled_hook($hook){$GLOBALS['scheduled']=false;}
function flush_rewrite_rules(){}
function sanitize_text_field($s){return strip_tags($s);}
function sanitize_hex_color($s){return preg_match('/^#[a-f0-9]{3,6}$/i',$s)?$s:null;}
function is_email($s){return filter_var($s,FILTER_VALIDATE_EMAIL);}
function __($s,$domain){return $s;}
class SchemaDB {
 public $prefix='wp_', $last=array();
 public function prepare($q,...$args){$this->last=$args;return $q;}
 public function esc_like($s){return $s;}
 public function get_var($q){$name=$this->last[0]??'';return !empty($GLOBALS['tables'][$name])?$name:null;}
 public function get_charset_collate(){return 'DEFAULT CHARACTER SET utf8mb4';}
}
$wpdb=new SchemaDB();$GLOBALS['options']=array();
require __DIR__.'/../authorgrowth-pro/authorgrowth-pro.php';
$n=0;function check($v,$label){global $n;++$n;if(!$v){throw new Exception('FAIL: '.$label);}}
try{
 foreach(array('fresh','phase-1','phase-2','phase-3') as $version){
  $GLOBALS['options']=array();$GLOBALS['tables']=array();$GLOBALS['ddl']=array();$GLOBALS['scheduled']=false;
  if(in_array($version,array('phase-2','phase-3'),true)){$GLOBALS['options']['agp_leads_schema']='1';$GLOBALS['tables']['wp_agp_leads']=true;}
  if($version==='phase-3'){$GLOBALS['options']['agp_clicks_schema']='1';$GLOBALS['tables']['wp_agp_clicks']=true;}
  foreach($GLOBALS['actions']['init'] as $callback){$callback();}
  check(isset($GLOBALS['tables']['wp_agp_waitlist'],$GLOBALS['tables']['wp_agp_leads'],$GLOBALS['tables']['wp_agp_clicks']),'schema upgrade paths: '.$version);
  check($GLOBALS['options']['agp_waitlist_schema']==='1'&&$GLOBALS['scheduled']>0,'schema marker and cron: '.$version);
  check(AGP_Settings::enabled('leads')&&AGP_Settings::enabled('reviews'),'defaults preserve enabled modules: '.$version);
  foreach($GLOBALS['ddl'] as $sql){check(!preg_match('/DROP |TRUNCATE |DELETE |INSERT INTO /i',$sql),'upgrade does not deliberately discard data');}
 }
 $expected=array('agp_launch','agp_review_form','agp_reviews','agp_book','agp_books','agp_floating_cta','agp_chapter');
 foreach($expected as $tag){check(is_callable($GLOBALS['shortcodes'][$tag]),'registered shortcode: '.$tag);}
 check($GLOBALS['post_types']['agp_book']['show_in_rest']===true,'native book editor remains registered');
 $GLOBALS['options']=array();$GLOBALS['tables']=array();$GLOBALS['fail_install']=true;AGP_Launches::install();AGP_Leads::install();AGP_Clicks::install();check(empty($GLOBALS['options']),'failed table installation is not marked complete');
 $GLOBALS['deactivation']();check(!$GLOBALS['scheduled'],'deactivation clears cleanup cron');
 echo "PASS: $n bootstrap/upgrade contract assertions\n";
}finally{unlink($root.'/wp-admin/includes/upgrade.php');rmdir($root.'/wp-admin/includes');rmdir($root.'/wp-admin');rmdir($root);}

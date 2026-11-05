<?php
/** Invoked as a subprocess by download-test.py so successful streaming may exit. */
$mode=$argv[1]; $root=sys_get_temp_dir().'/agp-dl-public-'.getmypid(); $dir=sys_get_temp_dir().'/agp-dl-private-'.getmypid(); mkdir($root); mkdir($dir);
define('ABSPATH',$root.'/'); define('AGP_PRIVATE_DIR',$dir);
$name=str_repeat('a',32).'.pdf'; file_put_contents($dir.'/'.$name,'%PDF-1.4 test chapter');
register_shutdown_function(function()use($dir,$root,$name){unlink($dir.'/'.$name);rmdir($dir);rmdir($root);});
function get_post_type($id){return 'agp_book';}
function get_post_status($id){return $GLOBALS['mode']==='draft'?'draft':'publish';}
function get_post_field($key,$id){return '';}
function get_post_meta($id,$key,$single){return $GLOBALS['mode']==='missing'?'':$GLOBALS['name'];}
function wp_unslash($s){return $s;}
function current_time($a,$b){return '2026-11-06 00:00:00';}
function nocache_headers(){}
function wp_die($m,$t='',$a=array()){echo 'DENIED:'.($a['response']??500);exit;}
class DownloadDB {
 public $prefix='wp_';
 public function prepare($sql,...$args){
  if(str_starts_with($sql,'UPDATE') && (!str_contains($sql,'AND token_hash=%s AND expires>=%d') || $args[2]!==hash('sha256',str_repeat('a',64)))){throw new Exception('Missing atomic token guard');}
  return $sql;
 }
 public function get_row($q){return in_array($GLOBALS['mode'],array('expired','replay'),true)?null:(object)array('book_id'=>1,'id'=>1);}
 public function query($q){return $GLOBALS['mode']==='race'?0:1;}
}
$wpdb=new DownloadDB(); $_GET['token']=str_repeat('a',64);
require __DIR__.'/../authorgrowth-pro/includes/class-leads.php'; AGP_Leads::download();

<?php
/** Isolated private-directory boundary fixture. */
$base=sys_get_temp_dir().'/agp-storage-'.getmypid();mkdir($base.'/public/wordpress',0700,true);mkdir($base.'/private');
$mode=$argv[1];$paths=array('private'=>$base.'/private','public'=>$base.'/public','wordpress'=>$base.'/public/wordpress','missing'=>$base.'/missing','symlink'=>$base.'/alias');
if($mode==='symlink'){symlink($base.'/public',$base.'/alias');}
define('ABSPATH',$base.'/public/wordpress/');define('AGP_PRIVATE_DIR',$paths[$mode]);$_SERVER['DOCUMENT_ROOT']=$base.'/public';
require __DIR__.'/../authorgrowth-pro/includes/class-leads.php';
try{echo AGP_Leads::private_dir()?'ALLOWED':'DENIED';}finally{if(is_link($base.'/alias')){unlink($base.'/alias');}rmdir($base.'/private');rmdir($base.'/public/wordpress');rmdir($base.'/public');rmdir($base);}

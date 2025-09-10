<?php
declare(strict_types=1); if (!defined('ABSPATH')) exit;
/** Minimal HLS preview API (<=100 LOC) */
function _pc_prev_dir($s){ $u=wp_upload_dir(); $b=trailingslashit($u['basedir']).'perceptor_previews'; if(!is_dir($b)) wp_mkdir_p($b); $d=$b.'/'.sanitize_file_name($s); if(!is_dir($d)) wp_mkdir_p($d); return $d; }
function _pc_prev_url($s){ $u=wp_upload_dir(); return trailingslashit($u['baseurl']).'perceptor_previews/'.rawurlencode($s); }
function _pc_hmac_ok(array $pay){ $sec=get_option('perceptor_secret',''); if(!$sec) return new WP_Error('auth','No secret',['status'=>401]); $ts=intval($pay['ts']??0); if(abs(time()-$ts)>300) return new WP_Error('auth','TS window',['status'=>401]); $calc=hash_hmac('sha256',json_encode($pay,JSON_UNESCAPED_SLASHES),$sec); $sig=strtolower($_SERVER['HTTP_X_PERCEPTOR_SIGNATURE']??''); if(!hash_equals($calc,$sig)) return new WP_Error('auth','Bad sig',['status'=>401,'calc'=>$calc]); return true; }
add_action('rest_api_init',function(){
  register_rest_route('perceptor/v1','/preview_start',[
    'methods'=>'POST','permission_callback'=>fn()=>current_user_can('manage_options'),
    'callback'=>function(WP_REST_Request $r){ $cam=sanitize_text_field($r->get_param('camera')); if(!$cam) return new WP_Error('bad','camera',['status'=>400]);
      $sess='pv_'.uniqid('',true); file_put_contents(_pc_prev_dir($sess).'/index.m3u8',"# Waiting\n");
      update_option('perceptor_preview_active_'.$cam,['session'=>$sess,'camera'=>$cam,'t'=>time()],false);
      return ['ok'=>true,'session'=>$sess,'playlist_url'=>_pc_prev_url($sess).'/index.m3u8'];
  }]);
  register_rest_route('perceptor/v1','/preview_stop',[
    'methods'=>'POST','permission_callback'=>fn()=>current_user_can('manage_options'),
    'callback'=>function(WP_REST_Request $r){ $cam=sanitize_text_field($r->get_param('camera')); delete_option('perceptor_preview_active_'.$cam); return ['ok'=>true]; }
  ]);
  register_rest_route('perceptor/v1','/preview_url',[
    'methods'=>'GET','permission_callback'=>fn()=>current_user_can('manage_options'),
    'callback'=>function(WP_REST_Request $r){ $cam=sanitize_text_field($r->get_param('camera')); $m=get_option('perceptor_preview_active_'.$cam); if(!$m) return ['ok'=>false]; return ['ok'=>true,'session'=>$m['session'],'playlist_url'=>_pc_prev_url($m['session']).'/index.m3u8']; }
  ]);
  register_rest_route('perceptor/v1','/preview_chunk',[
    'methods'=>'POST','permission_callback'=>'__return_true',
    'callback'=>function(WP_REST_Request $r){ $sess=sanitize_text_field($r->get_param('session')); $name=sanitize_file_name($r->get_param('name')); $f=$r->get_file_params()['file']??null; if(!$sess||!$name||!$f) return new WP_Error('bad','missing',['status'=>400]);
      $ts=intval($r->get_header('x-perceptor-date')); $ok=_pc_hmac_ok(['session'=>$sess,'name'=>$name,'ts'=>$ts]); if(is_wp_error($ok)) return $ok;
      $dst=_pc_prev_dir($sess).'/'.$name; if(!@move_uploaded_file($f['tmp_name'],$dst)) @copy($f['tmp_name'],$dst); if(!file_exists($dst)) return new WP_Error('upload','save fail',['status'=>500]);
      return ['ok'=>true,'url'=>_pc_prev_url($sess).'/'.$name];
  }]);
});

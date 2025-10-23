<?php
/**
 * Plugin Name:  WP Console Mute (MU)
 * Plugin URI:   https://github.com/codecorntech/wp-console-mute
 * Description:  Disattiva in modo selettivo o totale i messaggi della console JavaScript in WordPress (anche negli iframe),
 *               e previene errori noti di librerie in editori visuali (es. Elementor Checklist bug).
 * Version:      1.1.2
 * Author:       CodeCorn™ Technology
 * Author URI:   https://www.codecorn.it
 * License:      MIT
 * License URI:  https://opensource.org/licenses/MIT
 * Text Domain:  wp-console-mute
 * Domain Path:  /languages
 *
 * ============================================================
 *  MU-PLUGIN NOTICE:
 *  Questo file può essere copiato in:
 *    → /wp-content/mu-plugins/mu-cc-console-mute.php
 *  Verrà caricato automaticamente da WordPress, senza attivazione manuale.
 * ============================================================
 */

/**
 * ============================================================
 * 1  FIX SPECIFICO PER EDITOR ELEMENTOR
 *    Evita crash "Cannot read properties of null (reading 'parentElement')"
 *    causato da checklist.min.js → ToggleIcon.apply()
 * ============================================================
 */
// add_action('elementor/editor/after_enqueue_scripts', function () {

//   // Silenzia solo in editor (non nel frontend)
//   $js = <<<'JS'
// (function(){
//   try {
//     // Intercetta l'errore specifico in runtime
//     window.addEventListener('error', function(ev){
//       if(
//         ev?.message?.includes("Cannot read properties of null (reading 'parentElement')") &&
//         String(ev?.filename || '').includes('checklist.min.js')
//       ){
//         ev.preventDefault();
//         ev.stopImmediatePropagation();
//         return false;
//       }
//     }, true);

//     // Patcha ToggleIcon.apply se presente
//     function patchToggleIcon(){
//       try{
//         var ToggleIcon =
//           window.ToggleIcon ||
//           window.elementor?.modules?.checklist?.commands?.ToggleIcon ||
//           window.elementor?.common?.commands?.ToggleIcon;

//         if(!ToggleIcon || ToggleIcon.__ccPatched) return;

//         var proto = ToggleIcon.prototype || ToggleIcon;
//         var orig  = proto.apply;
//         if(typeof orig !== 'function') return;

//         proto.apply = function(){
//           try{
//             var t = this?.target || this?.$target || this?.el || null;
//             if(t && !t.parentElement) return;
//             return orig.apply(this, arguments);
//           }catch(e){
//             if(String(e).includes("parentElement")) return;
//             throw e;
//           }
//         };
//         ToggleIcon.__ccPatched = true;
//       }catch(e){}
//     }

//     document.addEventListener('DOMContentLoaded', patchToggleIcon);
//     (window.elementor ? elementor.on('preview:loaded', patchToggleIcon) : null);
//     setTimeout(patchToggleIcon, 1500);
//   }catch(e){}
// })();
// JS;
//   echo "<script>{$js}</script>";
// }, PHP_INT_MAX);

/**
 * ============================================================
 * 2  SOLO GMaps warnings → Silenziamento selettivo (default)
 * ============================================================
 */
function cc_console_mute_gmaps_warnings()
{
  $disable_in_dev = false; // setta true per non silenziare in WP_DEBUG

  if (defined('WP_DEBUG') && WP_DEBUG && $disable_in_dev) {
    error_log('[WP Console Mute] Debug attivo, skip del silenziamento.');
    return;
  }

  $patterns = apply_filters(
    'cc_console_mute_patterns',
    [
      'Google Maps JavaScript API',
      'Google Maps JavaScript API has been loaded',

    ]
  );
  $json_patterns = wp_json_encode($patterns ?: []);
  $js = <<<JS
(function(){
  try {
    var patterns = {$json_patterns};
    var originalWarn = console.warn ? console.warn.bind(console) : function(){};
    console.warn = function(){
      try {
        var first = arguments[0];
        if(typeof first==='string' && patterns.some(p => first.toLowerCase().includes(String(p).toLowerCase()))){
          return;
        }
      }catch(e){}
      return originalWarn.apply(console, arguments);
    };
  } catch(e) {}
})();
JS;
  echo "<script>{$js}</script>";
}

/**
 * ============================================================
 * 3  MUTO TOTALE → tutti i metodi console (opzionale)
 * ============================================================
 */
function cc_console_mute_all()
{
  if (defined('WP_DEBUG') && WP_DEBUG)
    return;

  $methods = ['log', 'warn', 'error', 'debug', 'info', 'trace', 'table', 'group', 'groupCollapsed', 'groupEnd', 'dir'];
  $json_methods = wp_json_encode($methods ?: []);
  $js = <<<JS
(function(){
  try{
    var noop=function(){};
    var methods={$json_methods};
    function patch(win){
      if(!win?.console) return;
      methods.forEach(m=>{try{win.console[m]=noop;}catch(e){}});
    }
    patch(window);
    document.querySelectorAll('iframe').forEach(f=>{
      try{patch(f.contentWindow);}catch(e){}
      f.addEventListener('load',ev=>{try{patch(ev.target.contentWindow);}catch(e){}});
    });
    new MutationObserver(m=>m.forEach(rec=>rec.addedNodes.forEach(n=>{
      if(n.tagName?.toLowerCase()==='iframe'){try{patch(n.contentWindow);}catch(e){}}
      n.querySelectorAll?.('iframe').forEach(f=>{try{patch(f.contentWindow);}catch(e){}});
    }))).observe(document.documentElement,{childList:true,subtree:true});
  }catch(e){}
})();
JS;
  echo "<script>{$js}</script>";
}

/**
 * ============================================================
 * 4  HOOKS ATTIVI
 * ============================================================
 */

// Default: silenzia solo Google Maps
add_action('wp_footer', 'cc_console_mute_gmaps_warnings', PHP_INT_MAX);
add_action('admin_footer', 'cc_console_mute_gmaps_warnings', PHP_INT_MAX);

// Per silenziare TUTTO, decommenta:
// add_action('wp_footer', 'cc_console_mute_all', PHP_INT_MAX);
// add_action('admin_footer', 'cc_console_mute_all', PHP_INT_MAX);

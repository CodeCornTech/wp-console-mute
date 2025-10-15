<?php
/**
 * Plugin Name:  WP Console Mute (MU)
 * Plugin URI:   https://github.com/codecorntech/wp-console-mute
 * Description:  Disattiva in modo selettivo o totale i messaggi della console JavaScript in WordPress (anche negli iframe). Ideale per ambienti di produzione.
 * Version:      1.0.0
 * Author:       CodeCorn™ Technology
 * Author URI:   https://www.codecorn.it
 * License:      MIT
 * License URI:  https://opensource.org/licenses/MIT
 * Text Domain:  wp-console-mute
 * Domain Path:  /languages
 *
 * @package CodeCorn\WPConsoleMute
 * @since   1.0.0
 *
 * ============================================================
 *  MU-PLUGIN NOTICE:
 *  Questo file può essere copiato in:
 *    → /wp-content/mu-plugins/mu-cc-console-mute.php
 *  Verrà caricato automaticamente da WordPress, senza attivazione manuale.
 * ============================================================
 */

/**
 * Stampa JS che silenzia SOLO i warning della Google Maps JavaScript API.
 *
 * Usa console.warn "wrapping": inoltra tutto tranne i messaggi che combaciano
 * uno dei pattern configurabili via filtro `cc_console_mute_patterns`.
 *
 * Hook consigliati: wp_footer, admin_footer
 *
 * @since 1.0.0
 * @return void
 */
function cc_console_mute_gmaps_warnings()
{
    $disable_in_dev = false; // se vuoi sempre silenziare, metti false

    // In sviluppo lascia passare i log (facoltativo: commenta se vuoi sempre silenziare).
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("[WP Console Mute] MU-plugin caricato correttamente. \$disable_in_dev: $disable_in_dev");
        if ($disable_in_dev)
            return;
    }

    /**
     * Permette di estendere i pattern da silenziare.
     *
     * @since 1.0.0
     * @param string[] $patterns Elenco di pattern (case-insensitive) cercati in args[0] se stringa.
     */
    $patterns = apply_filters(
        'cc_console_mute_patterns',
        [
            'Google Maps JavaScript API',         // warning classici della Maps JS API
            'Google Maps JavaScript API has been loaded',
        ]
    );

    // Prepara array JS in modo sicuro.
    $json_patterns = wp_json_encode(array_values(array_filter(array_map('strval', $patterns))));
    if (!$json_patterns) {
        $json_patterns = '[]';
    }

    $js = <<<JS
(function(){
  try {
    var patterns = {$json_patterns};
    var originalWarn = console.warn ? console.warn.bind(console) : function(){};

    console.warn = function() {
      try {
        var first = arguments[0];
        if (typeof first === 'string' && patterns.some(function(p){ return first.toLowerCase().indexOf(String(p).toLowerCase()) !== -1; })) {
          return; // ignora i warning che combaciano
        }
      } catch(e) {}
      return originalWarn.apply(console, arguments);
    };
  } catch(e) {}
})();
JS;

    echo "<script>{$js}</script>";
}

/**
 * Stampa JS che disabilita TUTTI i metodi console (anche negli iframe, presenti e futuri).
 *
 * Metodi toccati: log, warn, error, debug, info, trace, table, group, groupCollapsed, groupEnd, dir
 *
 * Hook consigliati: wp_footer, admin_footer
 *
 * @since 1.0.0
 * @return void
 */
function cc_console_mute_all()
{
    // Frena in sviluppo (facoltativo): evita di perdere log utili in dev.
    if (defined('WP_DEBUG') && WP_DEBUG) {
        return;
    }

    $methods = ['log', 'warn', 'error', 'debug', 'info', 'trace', 'table', 'group', 'groupCollapsed', 'groupEnd', 'dir'];
    $json_methods = wp_json_encode($methods);
    if (!$json_methods) {
        $json_methods = '[]';
    }

    $js = <<<JS
(function(){
  try {
    var noop = function(){};
    var methods = {$json_methods};

    function patchConsole(win){
      if(!win) return;
      try {
        if (win.console) {
          methods.forEach(function(m){
            try { win.console[m] = noop; } catch(e){}
          });
        }
      } catch(e){}
    }

    // Patch finestra corrente
    patchConsole(window);

    // Patch iframe ESISTENTI (già in pagina)
    try {
      var iframes = document.getElementsByTagName('iframe');
      for (var i=0; i<iframes.length; i++){
        var f = iframes[i];
        try { patchConsole(f.contentWindow); } catch(e){}
        // in caso l'iframe carichi dopo
        f.addEventListener('load', function(ev){
          try { patchConsole(ev.target && ev.target.contentWindow); } catch(e){}
        });
      }
    } catch(e){}

    // Patch iframe FUTURI via MutationObserver
    try {
      var obs = new MutationObserver(function(mutations){
        mutations.forEach(function(m){
          if (m.type === 'childList' && m.addedNodes && m.addedNodes.length){
            m.addedNodes.forEach(function(node){
              if (node && node.tagName && node.tagName.toLowerCase() === 'iframe'){
                try { patchConsole(node.contentWindow); } catch(e){}
                node.addEventListener('load', function(ev){
                  try { patchConsole(ev.target && ev.target.contentWindow); } catch(e){}
                });
              }
              // se vengono aggiunti container con iframe annidati
              if (node && node.querySelectorAll) {
                node.querySelectorAll('iframe').forEach(function(f){
                  try { patchConsole(f.contentWindow); } catch(e){}
                  f.addEventListener('load', function(ev){
                    try { patchConsole(ev.target && ev.target.contentWindow); } catch(e){}
                  });
                });
              }
            });
          }
        });
      });
      obs.observe(document.documentElement || document.body, {childList:true, subtree:true});
    } catch(e){}
  } catch(e){}
})();
JS;

    echo "<script>{$js}</script>";
}

/**
 * HOOKS
 * Abilita UNO dei due blocchi seguenti:
 */

// 1) Silenzia SOLO i warning di Google Maps (ATTIVO di default)
add_action('wp_footer', 'cc_console_mute_gmaps_warnings', PHP_INT_MAX);
add_action('admin_footer', 'cc_console_mute_gmaps_warnings', PHP_INT_MAX);

// 2) Zittisce TUTTO (sconsigliato in dev) — abilita se/quando necessario
// add_action( 'wp_footer', 'cc_console_mute_all', PHP_INT_MAX );
// add_action( 'admin_footer', 'cc_console_mute_all', PHP_INT_MAX );

// 3) ### Note rapide
// /** Se vuoi aggiungere altri messaggi da ignorare nel filtro “gmaps”, puoi usare: */
// add_filter('cc_console_mute_patterns', function($p){
//     $p[] = 'Some noisy library';
//     return $p;
// });
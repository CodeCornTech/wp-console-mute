# 🧩 WP Console Mute (MU Plugin Edition)

> **MU-plugin minimale per WordPress** che disattiva in modo selettivo o totale i messaggi della console JavaScript, mantenendo il frontend e la dashboard puliti in produzione.

---

## 🚀 Funzionalità principali

✅ **Modalità selettiva (default)**  
Silenzia solo i warning della **Google Maps JavaScript API**, senza toccare gli altri log utili per il debug.

✅ **Modalità totale (full mute)**  
Disabilita completamente tutti i metodi `console.*` (`log`, `warn`, `error`, `info`, ecc.) anche negli iframe presenti o creati dinamicamente.

✅ **Sicuro e moderno**

-   Nessuna modifica a `document.createElement`
-   Usa `MutationObserver` per intercettare iframe futuri
-   Compatibile con `WP_DEBUG`
-   100% inline-JS senza dipendenze esterne

✅ **Progettato per ambienti CodeCorn™**  
Perfettamente integrabile in stack moderni e child-theme professionali.

---

## ⚙️ Installazione

### 🔹 Metodo 1 — Come MU-plugin (consigliato)

1. Clona o scarica la repo:
    ```bash
    git clone https://github.com/codecorntech/wp-console-mute.git
    ```


2. Copia il file principale direttamente nella directory MU-plugins di WordPress:

   ```
   wp-content/mu-plugins/mu-cc-console-mute.php
   ```

3. WordPress caricherà automaticamente lo script su **tutti i siti** senza necessità di attivazione da Bacheca.

---

### 🔹 Metodo 2 — Da `functions.php`

Puoi anche copiare le funzioni direttamente nel tuo tema child:

```php
require_once get_stylesheet_directory() . '/inc/mu-cc-console-mute.php';
```

---

## 🧩 Utilizzo

### 🔸 Modalità predefinita — solo Google Maps warnings

Il comportamento di default silenzia i warning della Google Maps API:

```php
add_action( 'wp_footer', 'cc_console_mute_gmaps_warnings', PHP_INT_MAX );
add_action( 'admin_footer', 'cc_console_mute_gmaps_warnings', PHP_INT_MAX );
```

### 🔸 Modalità avanzata — zittisci tutta la console

Per disabilitare completamente tutti i log:

```php
// add_action( 'wp_footer', 'cc_console_mute_all', PHP_INT_MAX );
// add_action( 'admin_footer', 'cc_console_mute_all', PHP_INT_MAX );
```

> 💡 Decommenta solo in ambienti di produzione, non in sviluppo.

---

## 🎛️ Personalizzazione

Aggiungi pattern personalizzati per silenziare altri warning:

```php
add_filter( 'cc_console_mute_patterns', function( $patterns ) {
    $patterns[] = 'Some noisy library';
    $patterns[] = 'Deprecated feature';
    return $patterns;
});
```

---

## 🧠 Hooks disponibili

| Hook                       | Descrizione                                                                        |
| -------------------------- | ---------------------------------------------------------------------------------- |
| `cc_console_mute_patterns` | Array di stringhe da confrontare con i messaggi `console.warn` (case-insensitive). |

---

## 🔒 Sicurezza

* Non altera in modo invasivo l’ambiente JS globale
* Non disattiva log lato PHP o server
* Opera solo dopo il caricamento di tutti gli script in footer

---

## 📦 Struttura del progetto

```bash
wp-console-mute/
│
├── mu-cc-console-mute.php   # MU-plugin principale
├── README.md                # Questo file
├── LICENSE                  # Licenza MIT
└── .gitignore               # File Git standard
```

---

## 🧰 Compatibilità

* WordPress 5.8+
* PHP 7.4 – 8.3
* Gutenberg & Dashboard moderna
* Testato su Hello Elementor, Astra, GeneratePress

---

## 🧑‍💻 Autore

**CodeCorn™ Technology**
Soluzioni DevOps, Plugin WordPress e Infrastructure Tools.
📍 Roma, Italia — [www.codecorn.it](https://www.codecorn.it)

---

## ⚖️ Licenza

Rilasciato sotto **MIT License** — libero uso e modifica con attribuzione.

---

> 💬 *“Silenziare il rumore, per vedere meglio ciò che conta.”*
> — CodeCorn™ Dev Team

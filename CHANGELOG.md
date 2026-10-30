## Tema Meteo Webcam — functions.php v1.5 (30 ottobre 2026) — featured images JPEG → WebP

- Snippet una-tantum `mwt_fi_webp`: converte in WebP (qualità 82) le immagini in evidenza dei post/pagine pubblicati — il file pieno (solo se larghezza ≤ 2560px: oltre si rischia la memoria GD sull'hosting condiviso), la taglia `large` (featured del post singolo, ricodificando il file già ridimensionato) e `mwt-card` 640×360 (se manca viene generata dal pieno: riginenero delle thumbnail rinviato).
- Aggiorna `_wp_attached_file`, `_wp_attachment_metadata` e `post_mime_type`; i vecchi .jpg restano su disco come backup (rollback via FTP, pulibili a fine conversione).
- Pagina strumento in wp-admin: `?mwt_fi_webp=1` dry-run, `&go=1` converte 10 allegati per richiesta, `&auto=1` auto-riguarda la pagina fino a fine coda, `&reset=1` riscansiona. Progresso nell'opzione `mwt_fi_webp_todo`; a fine coda flush W3TC automatico.
- SNIPPET TEMPORANEO: rimuovere a conversione completata e verificata (la versione pulita successiva = v1.6).

## v3.39 (30 ottobre 2026) — autenticazione HTTP Basic/Digest per URL con credenziali
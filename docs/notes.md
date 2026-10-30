# Note operative e casi limite


## 30 ottobre 2026 — tema v1.5: conversione featured images JPEG → WebP

- Obiettivo: alleggerire il caricamento delle pagine convertendo le immagini in evidenza da JPEG a WebP. Le immagini webcam del plugin sono già tutte WebP; questa conversione riguarda solo la Media Library (featured images).
- Il tema le serve in due taglie: `large` nel post singolo (index.php) e `mwt-card` 640×360 nelle card di home/archivi.
- Snippet una-tantum in functions.php v1.5 (repo GitHub, commit su main): pagina admin `?mwt_fi_webp=1` (dry-run) / `&go=1` (10 allegati per richiesta) / `&auto=1` (auto-refresh fino a fine coda) / `&reset=1`.
- Full convertito solo se larghezza ≤ 2560px (limite memoria GD su Tophost condiviso): gli originali più grandi restano JPEG come candidato alto del srcset.
- I vecchi .jpg NON vengono cancellati (backup su disco); a conversione verificata si può pulire uploads/ dai jpg residui e rimuovere lo snippet (versione pulita v1.6).
- Verifica post-conversione: una pagina post singolo + la home con DevTools (Content-Type image/webp su featured e card), W3TC flush, LCP in calo su PageSpeed.


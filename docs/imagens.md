# Guia de Imagens de Produtos

Para adicionar fotos reais aos produtos, salve os arquivos WebP em `public/images/products/` seguindo a convenção:

- **Miniatura (card/listagem):** `public/images/products/{slug}-400.webp` (400×500 px, proporção 4:5)
- **Detalhe (página de produto):** `public/images/products/{slug}.webp` (800×1000 px, proporção 4:5)

Enquanto o arquivo não existir em `public/images/products/`, o componente `<x-product-image>` exibe automaticamente um placeholder neutro com o ícone da categoria.

| Slug | Produto | Categoria | Arquivos Esperados |
| :--- | :--- | :--- | :--- |
| `smartphone-galaxy-s24-ultra` | Smartphone Galaxy S24 Ultra 256GB Titânio | Smartphones | `smartphone-galaxy-s24-ultra-400.webp`, `smartphone-galaxy-s24-ultra.webp` |
| `iphone-16-pro-max-256gb` | Apple iPhone 16 Pro Max 256GB Titânio Natural | Smartphones | `iphone-16-pro-max-256gb-400.webp`, `iphone-16-pro-max-256gb.webp` |
| `xiaomi-14-ultra-512gb` | Xiaomi 14 Ultra 512GB Leica Optical Quad Camera | Smartphones | `xiaomi-14-ultra-512gb-400.webp`, `xiaomi-14-ultra-512gb.webp` |
| `google-pixel-9-pro-128gb` | Google Pixel 9 Pro 128GB Porcelain com Gemini AI | Smartphones | `google-pixel-9-pro-128gb-400.webp`, `google-pixel-9-pro-128gb.webp` |
| `headphone-sony-wh-1000xm5-bluetooth` | Headphone Sony WH-1000XM5 com Cancelamento de Ruído | Áudio & Fones | `headphone-sony-wh-1000xm5-bluetooth-400.webp`, `headphone-sony-wh-1000xm5-bluetooth.webp` |
| `apple-airpods-pro-2-usbc` | Apple AirPods Pro (2ª Geração) com Estojo MagSafe USB-C | Áudio & Fones | `apple-airpods-pro-2-usbc-400.webp`, `apple-airpods-pro-2-usbc.webp` |
| `jbl-charge-5-bluetooth` | Caixa de Som Portátil JBL Charge 5 Bluetooth IP67 | Áudio & Fones | `jbl-charge-5-bluetooth-400.webp`, `jbl-charge-5-bluetooth.webp` |
| `headphone-bose-quietcomfort-ultra` | Headphone Bose QuietComfort Ultra com Áudio Imersivo | Áudio & Fones | `headphone-bose-quietcomfort-ultra-400.webp`, `headphone-bose-quietcomfort-ultra.webp` |
| `apple-watch-series-10-gps-46mm` | Apple Watch Series 10 GPS 46mm Alumínio Meia-Noite | Smartwatches & Wearables | `apple-watch-series-10-gps-46mm-400.webp`, `apple-watch-series-10-gps-46mm.webp` |
| `samsung-galaxy-watch-ultra-47mm` | Samsung Galaxy Watch Ultra 47mm LTE Titânio Cinza | Smartwatches & Wearables | `samsung-galaxy-watch-ultra-47mm-400.webp`, `samsung-galaxy-watch-ultra-47mm.webp` |
| `garmin-fenix-7-pro-solar` | Garmin Fenix 7 Pro Solar Multisport GPS com Lanterna LED | Smartwatches & Wearables | `garmin-fenix-7-pro-solar-400.webp`, `garmin-fenix-7-pro-solar.webp` |
| `ipad-air-11-m2-128gb` | Apple iPad Air 11" Chip M2 Wi-Fi 128GB Azul | Tablets & E-readers | `ipad-air-11-m2-128gb-400.webp`, `ipad-air-11-m2-128gb.webp` |
| `kindle-paperwhite-16gb-luz-ajustavel` | Kindle Paperwhite 16GB Tela 6.8" com Luz Quente Ajustável | Tablets & E-readers | `kindle-paperwhite-16gb-luz-ajustavel-400.webp`, `kindle-paperwhite-16gb-luz-ajustavel.webp` |
| `samsung-galaxy-tab-s9-fe-128gb` | Samsung Galaxy Tab S9 FE 10.9" Wi-Fi 128GB com Caneta S Pen | Tablets & E-readers | `samsung-galaxy-tab-s9-fe-128gb-400.webp`, `samsung-galaxy-tab-s9-fe-128gb.webp` |
| `notebook-dell-inspiron-15` | Notebook Dell Inspiron 15 Core i7 16GB SSD 512GB | Notebooks | `notebook-dell-inspiron-15-400.webp`, `notebook-dell-inspiron-15.webp` |
| `macbook-air-13-m3-16gb-512gb` | Apple MacBook Air 13" Chip M3 16GB RAM SSD 512GB Meia-Noite | Notebooks | `macbook-air-13-m3-16gb-512gb-400.webp`, `macbook-air-13-m3-16gb-512gb.webp` |
| `notebook-gamer-asus-rog-zephyrus-g16` | Notebook Gamer ASUS ROG Zephyrus G16 Core Ultra 9 RTX 4070 | Notebooks | `notebook-gamer-asus-rog-zephyrus-g16-400.webp`, `notebook-gamer-asus-rog-zephyrus-g16.webp` |
| `notebook-lenovo-thinkpad-e14-i5` | Notebook Lenovo ThinkPad E14 Intel Core i5 16GB SSD 512GB | Notebooks | `notebook-lenovo-thinkpad-e14-i5-400.webp`, `notebook-lenovo-thinkpad-e14-i5.webp` |
| `teclado-mecanico-logitech-mx-mechanical` | Teclado Mecânico Sem Fio Logitech MX Mechanical | Periféricos | `teclado-mecanico-logitech-mx-mechanical-400.webp`, `teclado-mecanico-logitech-mx-mechanical.webp` |
| `mouse-ergonomico-logitech-mx-master-3s` | Mouse Sem Fio Logitech MX Master 3S Ergonômico | Periféricos | `mouse-ergonomico-logitech-mx-master-3s-400.webp`, `mouse-ergonomico-logitech-mx-master-3s.webp` |
| `mouse-gamer-razer-deathadder-v3-pro` | Mouse Gamer Sem Fio Razer DeathAdder V3 Pro Ultralight 63g | Periféricos | `mouse-gamer-razer-deathadder-v3-pro-400.webp`, `mouse-gamer-razer-deathadder-v3-pro.webp` |
| `teclado-mecanico-corsair-k70-rgb-pro` | Teclado Mecânico Gamer Corsair K70 RGB PRO Cherry MX Red | Periféricos | `teclado-mecanico-corsair-k70-rgb-pro-400.webp`, `teclado-mecanico-corsair-k70-rgb-pro.webp` |
| `webcam-logitech-brio-4k-hdr` | Webcam Ultra HD 4K Logitech Brio com HDR e RightLight 3 | Periféricos | `webcam-logitech-brio-4k-hdr-400.webp`, `webcam-logitech-brio-4k-hdr.webp` |
| `monitor-gamer-lg-ultragear-27-144hz` | Monitor Gamer LG UltraGear 27" Full HD IPS 144Hz 1ms | Monitores & Telas | `monitor-gamer-lg-ultragear-27-144hz-400.webp`, `monitor-gamer-lg-ultragear-27-144hz.webp` |
| `monitor-dell-ultrasharp-32-4k-usb-c` | Monitor Profissional Dell UltraSharp 32" 4K Hub USB-C IPS Black | Monitores & Telas | `monitor-dell-ultrasharp-32-4k-usb-c-400.webp`, `monitor-dell-ultrasharp-32-4k-usb-c.webp` |
| `monitor-curvo-samsung-odyssey-oled-g9-49` | Monitor Curvo Gamer Samsung Odyssey OLED G9 49" 240Hz 0.03ms | Monitores & Telas | `monitor-curvo-samsung-odyssey-oled-g9-49-400.webp`, `monitor-curvo-samsung-odyssey-oled-g9-49.webp` |
| `ssd-kingston-kc3000-2tb-nvme` | SSD Kingston KC3000 2TB NVMe PCIe 4.0 M.2 7000MB/s | Armazenamento & Memória | `ssd-kingston-kc3000-2tb-nvme-400.webp`, `ssd-kingston-kc3000-2tb-nvme.webp` |
| `ssd-portatil-sandisk-extreme-1tb` | SSD Portátil SanDisk Extreme 1TB USB 3.2 Gen 2 Resistente IP55 | Armazenamento & Memória | `ssd-portatil-sandisk-extreme-1tb-400.webp`, `ssd-portatil-sandisk-extreme-1tb.webp` |
| `playstation-5-slim-1tb` | Console PlayStation 5 Slim Edição Digital 1TB SSD | Consoles | `playstation-5-slim-1tb-400.webp`, `playstation-5-slim-1tb.webp` |
| `xbox-series-x-1tb` | Console Xbox Series X 1TB Preto Fosco 12 Teraflops | Consoles | `xbox-series-x-1tb-400.webp`, `xbox-series-x-1tb.webp` |
| `nintendo-switch-oled-branco` | Console Nintendo Switch Modelo OLED Branco 64GB | Consoles | `nintendo-switch-oled-branco-400.webp`, `nintendo-switch-oled-branco.webp` |
| `controle-sony-dualsense-edge-ps5` | Controle Sem Fio Sony DualSense Edge PS5 Pro Personalizável | Acessórios Gamer | `controle-sony-dualsense-edge-ps5-400.webp`, `controle-sony-dualsense-edge-ps5.webp` |
| `headset-hyperx-cloud-iii-wireless` | Headset Sem Fio Gamer HyperX Cloud III Wireless 120h | Acessórios Gamer | `headset-hyperx-cloud-iii-wireless-400.webp`, `headset-hyperx-cloud-iii-wireless.webp` |
| `volante-logitech-g29-driving-force` | Volante e Pedais Logitech G29 Driving Force Force Feedback | Acessórios Gamer | `volante-logitech-g29-driving-force-400.webp`, `volante-logitech-g29-driving-force.webp` |
| `air-fryer-philips-walita-essential-xl` | Air Fryer Philips Walita Essential XL 6,2L Digital | Eletrodomésticos | `air-fryer-philips-walita-essential-xl-400.webp`, `air-fryer-philips-walita-essential-xl.webp` |
| `cafeteira-nespresso-vertuo-pop` | Cafeteira Nespresso Vertuo Pop Preto Espresso e Café | Eletrodomésticos | `cafeteira-nespresso-vertuo-pop-400.webp`, `cafeteira-nespresso-vertuo-pop.webp` |
| `amazon-echo-dot-5-alexa` | Smart Speaker Amazon Echo Dot 5ª Geração com Alexa | Assistentes & Hubs | `amazon-echo-dot-5-alexa-400.webp`, `amazon-echo-dot-5-alexa.webp` |
| `amazon-echo-show-8-3-geracao` | Smart Display Amazon Echo Show 8 (3ª Geração) Áudio Espacial | Assistentes & Hubs | `amazon-echo-show-8-3-geracao-400.webp`, `amazon-echo-show-8-3-geracao.webp` |
| `camera-intelbras-im3-c-wifi` | Câmera de Segurança Inteligente Intelbras Wi-Fi Full HD iM3 C | Segurança & Iluminação | `camera-intelbras-im3-c-wifi-400.webp`, `camera-intelbras-im3-c-wifi.webp` |
| `fechadura-digital-intelbras-fr101` | Fechadura Digital de Sobrepor Intelbras FR 101 com Teclado Touch | Segurança & Iluminação | `fechadura-digital-intelbras-fr101-400.webp`, `fechadura-digital-intelbras-fr101.webp` |
| `lampada-inteligente-philips-smart-led` | Lâmpada Inteligente Wi-Fi Philips Smart LED Colors E27 9W | Segurança & Iluminação | `lampada-inteligente-philips-smart-led-400.webp`, `lampada-inteligente-philips-smart-led.webp` |
| `camera-sony-alpha-a7-iv` | Câmera Mirrorless Full-Frame Sony Alpha A7 IV 4K 33MP (Corpo) | Câmeras & Ação | `camera-sony-alpha-a7-iv-400.webp`, `camera-sony-alpha-a7-iv.webp` |
| `camera-gopro-hero-12-black` | Câmera de Ação GoPro HERO12 Black À Prova D'Água 5.3K | Câmeras & Ação | `camera-gopro-hero-12-black-400.webp`, `camera-gopro-hero-12-black.webp` |
| `drone-dji-mini-4-pro-rc2` | Drone DJI Mini 4 Pro com Controle DJI RC 2 Tela Integrada | Drones & Estabilizadores | `drone-dji-mini-4-pro-rc2-400.webp`, `drone-dji-mini-4-pro-rc2.webp` |
| `estabilizador-dji-osmo-mobile-6` | Estabilizador Gimbal de Smartphone DJI Osmo Mobile 6 | Drones & Estabilizadores | `estabilizador-dji-osmo-mobile-6-400.webp`, `estabilizador-dji-osmo-mobile-6.webp` |

<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // -------------------------------------------------------------
        // 1. ELETRÔNICOS
        // -------------------------------------------------------------
        $eletronicos = Category::updateOrCreate(
            ['slug' => 'eletronicos'],
            ['name' => 'Eletrônicos', 'parent_id' => null, 'icon' => 'smartphone']
        );

        $smartphones = Category::updateOrCreate(
            ['slug' => 'smartphones'],
            ['name' => 'Smartphones', 'parent_id' => $eletronicos->id, 'icon' => 'smartphone']
        );

        $audio = Category::updateOrCreate(
            ['slug' => 'audio-fones'],
            ['name' => 'Áudio & Fones', 'parent_id' => $eletronicos->id, 'icon' => 'headphones']
        );

        $smartwatches = Category::updateOrCreate(
            ['slug' => 'smartwatches'],
            ['name' => 'Smartwatches & Wearables', 'parent_id' => $eletronicos->id, 'icon' => 'watch']
        );

        $tablets = Category::updateOrCreate(
            ['slug' => 'tablets-ereaders'],
            ['name' => 'Tablets & E-readers', 'parent_id' => $eletronicos->id, 'icon' => 'tablet']
        );

        // -------------------------------------------------------------
        // 2. INFORMÁTICA
        // -------------------------------------------------------------
        $informatica = Category::updateOrCreate(
            ['slug' => 'informatica'],
            ['name' => 'Informática', 'parent_id' => null, 'icon' => 'laptop']
        );

        $notebooks = Category::updateOrCreate(
            ['slug' => 'notebooks'],
            ['name' => 'Notebooks', 'parent_id' => $informatica->id, 'icon' => 'laptop']
        );

        $perifericos = Category::updateOrCreate(
            ['slug' => 'perifericos'],
            ['name' => 'Periféricos', 'parent_id' => $informatica->id, 'icon' => 'mouse']
        );

        $monitores = Category::updateOrCreate(
            ['slug' => 'monitores'],
            ['name' => 'Monitores & Telas', 'parent_id' => $informatica->id, 'icon' => 'monitor']
        );

        $armazenamento = Category::updateOrCreate(
            ['slug' => 'armazenamento'],
            ['name' => 'Armazenamento & Memória', 'parent_id' => $informatica->id, 'icon' => 'hard-drive']
        );

        // -------------------------------------------------------------
        // 3. GAMES
        // -------------------------------------------------------------
        $games = Category::updateOrCreate(
            ['slug' => 'games'],
            ['name' => 'Games', 'parent_id' => null, 'icon' => 'gamepad-2']
        );

        $consoles = Category::updateOrCreate(
            ['slug' => 'consoles'],
            ['name' => 'Consoles', 'parent_id' => $games->id, 'icon' => 'gamepad-2']
        );

        $acessoriosGamer = Category::updateOrCreate(
            ['slug' => 'acessorios-gamer'],
            ['name' => 'Acessórios Gamer', 'parent_id' => $games->id, 'icon' => 'gamepad']
        );

        // -------------------------------------------------------------
        // 4. CASA & COZINHA
        // -------------------------------------------------------------
        $casaCozinha = Category::updateOrCreate(
            ['slug' => 'casa-cozinha'],
            ['name' => 'Casa & Cozinha', 'parent_id' => null, 'icon' => 'cooking-pot']
        );

        $eletrodomesticos = Category::updateOrCreate(
            ['slug' => 'eletrodomesticos'],
            ['name' => 'Eletrodomésticos', 'parent_id' => $casaCozinha->id, 'icon' => 'cooking-pot']
        );

        $assistentesHubs = Category::updateOrCreate(
            ['slug' => 'assistentes-hubs'],
            ['name' => 'Assistentes & Hubs', 'parent_id' => $casaCozinha->id, 'icon' => 'speaker']
        );

        $seguranca = Category::updateOrCreate(
            ['slug' => 'seguranca-conectividade'],
            ['name' => 'Segurança & Iluminação', 'parent_id' => $casaCozinha->id, 'icon' => 'lightbulb']
        );

        // -------------------------------------------------------------
        // 5. CÂMERAS & DRONES
        // -------------------------------------------------------------
        $camerasDrones = Category::updateOrCreate(
            ['slug' => 'cameras-drones'],
            ['name' => 'Câmeras & Drones', 'parent_id' => null, 'icon' => 'camera']
        );

        $camerasAcao = Category::updateOrCreate(
            ['slug' => 'cameras-acao'],
            ['name' => 'Câmeras & Ação', 'parent_id' => $camerasDrones->id, 'icon' => 'camera']
        );

        $drones = Category::updateOrCreate(
            ['slug' => 'drones-estabilizadores'],
            ['name' => 'Drones & Estabilizadores', 'parent_id' => $camerasDrones->id, 'icon' => 'plane']
        );

        // =============================================================
        // PRODUTOS
        // =============================================================
        $products = [
            // --- SMARTPHONES ---
            [
                'category_id' => $smartphones->id,
                'slug' => 'smartphone-galaxy-s24-ultra',
                'name' => 'Smartphone Galaxy S24 Ultra 256GB Titânio',
                'description' => 'Tela Dynamic AMOLED 2X de 6.8", processador Snapdragon 8 Gen 3, câmera quádrupla de até 200MP com zoom óptico de 5x e caneta S Pen inclusa.',
                'price' => 5899.00,
                'compare_at_price' => 6499.00,
                'cashback_percent' => 3.00,
                'stock' => 15,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $smartphones->id,
                'slug' => 'iphone-16-pro-max-256gb',
                'name' => 'Apple iPhone 16 Pro Max 256GB Titânio Natural',
                'description' => 'Design em titânio grau 5 aeroespacial, tela Super Retina XDR de 6.9", potente chip A18 Pro, botão de Controle da Câmera e gravação em 4K Dolby Vision a 120 qps.',
                'price' => 8999.00,
                'compare_at_price' => 9999.00,
                'cashback_percent' => 3.00,
                'stock' => 12,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $smartphones->id,
                'slug' => 'xiaomi-14-ultra-512gb',
                'name' => 'Xiaomi 14 Ultra 512GB Leica Optical Quad Camera',
                'description' => 'Engenharia óptica Leica com sensor Sony LYT-900 de 1 polegada, abertura variável contínua, Snapdragon 8 Gen 3 e carregador HyperCharge de 90W.',
                'price' => 5499.00,
                'compare_at_price' => 6199.00,
                'cashback_percent' => 4.00,
                'stock' => 10,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $smartphones->id,
                'slug' => 'google-pixel-9-pro-128gb',
                'name' => 'Google Pixel 9 Pro 128GB Porcelain com Gemini AI',
                'description' => 'O ápice da inteligência artificial móvel com processador Google Tensor G4, recursos proeminentes do Gemini integrados e sistema fotográfico triplo de nível profissional.',
                'price' => 6299.00,
                'compare_at_price' => 6999.00,
                'cashback_percent' => 3.00,
                'stock' => 8,
                'image_path' => '/images/products/placeholder.svg',
            ],

            // --- ÁUDIO & FONES ---
            [
                'category_id' => $audio->id,
                'slug' => 'headphone-sony-wh-1000xm5-bluetooth',
                'name' => 'Headphone Sony WH-1000XM5 com Cancelamento de Ruído',
                'description' => 'Líder em cancelamento de ruído ativo com dois processadores e 8 microfones, áudio Hi-Res sem fio, chamadas ultra nítidas e bateria com duração de até 30 horas.',
                'price' => 1899.00,
                'compare_at_price' => 2199.00,
                'cashback_percent' => 5.00,
                'stock' => 25,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $audio->id,
                'slug' => 'apple-airpods-pro-2-usbc',
                'name' => 'Apple AirPods Pro (2ª Geração) com Estojo MagSafe USB-C',
                'description' => 'Cancelamento Ativo de Ruído até 2x mais eficiente, Áudio Espacial Personalizado com rastreamento dinâmico da cabeça e estojo com alto-falante integrado para Busca Precisa.',
                'price' => 1649.00,
                'compare_at_price' => 1999.00,
                'cashback_percent' => 4.00,
                'stock' => 30,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $audio->id,
                'slug' => 'jbl-charge-5-bluetooth',
                'name' => 'Caixa de Som Portátil JBL Charge 5 Bluetooth IP67',
                'description' => 'Potente JBL Original Pro Sound com driver de longa excursão e tweeter separado, bateria para até 20 horas de reprodução contínua e recurso powerbank integrado.',
                'price' => 899.00,
                'compare_at_price' => 1099.00,
                'cashback_percent' => 4.00,
                'stock' => 35,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $audio->id,
                'slug' => 'headphone-bose-quietcomfort-ultra',
                'name' => 'Headphone Bose QuietComfort Ultra com Áudio Imersivo',
                'description' => 'Tecnologia inovadora Bose Immersive Audio, cancelamento de ruído estelar com calibração CustomTune que adapta o som ao formato de cada ouvido.',
                'price' => 2499.00,
                'compare_at_price' => 2899.00,
                'cashback_percent' => 5.00,
                'stock' => 14,
                'image_path' => '/images/products/placeholder.svg',
            ],

            // --- SMARTWATCHES & WEARABLES ---
            [
                'category_id' => $smartwatches->id,
                'slug' => 'apple-watch-series-10-gps-46mm',
                'name' => 'Apple Watch Series 10 GPS 46mm Alumínio Meia-Noite',
                'description' => 'Tela inovadora OLED grande de ângulo ampliado mais brilhante, perfil 10% mais fino, notificações de apneia do sono, ECG e monitoramento avançado de saúde e treinos.',
                'price' => 4199.00,
                'compare_at_price' => 4699.00,
                'cashback_percent' => 3.00,
                'stock' => 18,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $smartwatches->id,
                'slug' => 'samsung-galaxy-watch-ultra-47mm',
                'name' => 'Samsung Galaxy Watch Ultra 47mm LTE Titânio Cinza',
                'description' => 'Caixa de titânio de grau aeroespacial, resistência à água 10ATM / IP68, botão físico de ação rápida e bateria com duração de até 100 horas para esportes extremos.',
                'price' => 3899.00,
                'compare_at_price' => 4399.00,
                'cashback_percent' => 3.00,
                'stock' => 15,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $smartwatches->id,
                'slug' => 'garmin-fenix-7-pro-solar',
                'name' => 'Garmin Fenix 7 Pro Solar Multisport GPS com Lanterna LED',
                'description' => 'Lente de carregamento solar Power Glass para semanas de autonomia, lanterna LED multi-modo integrada, métricas avançadas de treino e mapas TopoActive integrados.',
                'price' => 5499.00,
                'compare_at_price' => 6199.00,
                'cashback_percent' => 4.00,
                'stock' => 10,
                'image_path' => '/images/products/placeholder.svg',
            ],

            // --- TABLETS & E-READERS ---
            [
                'category_id' => $tablets->id,
                'slug' => 'ipad-air-11-m2-128gb',
                'name' => 'Apple iPad Air 11" Chip M2 Wi-Fi 128GB Azul',
                'description' => 'Desempenho impressionante com o processador Apple M2, tela Liquid Retina brilhante antirreflexo com True Tone, suporte a Apple Pencil Pro e Magic Keyboard.',
                'price' => 5299.00,
                'compare_at_price' => 5899.00,
                'cashback_percent' => 3.00,
                'stock' => 16,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $tablets->id,
                'slug' => 'kindle-paperwhite-16gb-luz-ajustavel',
                'name' => 'Kindle Paperwhite 16GB Tela 6.8" com Luz Quente Ajustável',
                'description' => 'Tela antirreflexo de 300 ppi que reproduz texto impresso com perfeição, temperatura de luz ajustável do branco ao âmbar, bateria para até 10 semanas e proteção IPX8 à prova d\'água.',
                'price' => 699.00,
                'compare_at_price' => 799.00,
                'cashback_percent' => 5.00,
                'stock' => 40,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $tablets->id,
                'slug' => 'samsung-galaxy-tab-s9-fe-128gb',
                'name' => 'Samsung Galaxy Tab S9 FE 10.9" Wi-Fi 128GB com Caneta S Pen',
                'description' => 'Tela imersiva de 90Hz, certificação IP68 de resistência contra água e poeira no tablet e na caneta S Pen inclusa, perfeito para estudos, ilustrações e anotações.',
                'price' => 2499.00,
                'compare_at_price' => 2899.00,
                'cashback_percent' => 4.00,
                'stock' => 20,
                'image_path' => '/images/products/placeholder.svg',
            ],

            // --- NOTEBOOKS & MACBOOKS ---
            [
                'category_id' => $notebooks->id,
                'slug' => 'notebook-dell-inspiron-15',
                'name' => 'Notebook Dell Inspiron 15 Core i7 16GB SSD 512GB',
                'description' => 'Alta performance para produtividade e multitarefa com processador Intel Core i7 de 13ª geração, tela Full HD antirreflexo e teclado numérico integrado.',
                'price' => 4199.90,
                'compare_at_price' => 4799.00,
                'cashback_percent' => 2.00,
                'stock' => 10,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $notebooks->id,
                'slug' => 'macbook-air-13-m3-16gb-512gb',
                'name' => 'Apple MacBook Air 13" Chip M3 16GB RAM SSD 512GB Meia-Noite',
                'description' => 'Incrivelmente fino com apenas 11,3mm de espessura, chip Apple M3 com CPU de 8 núcleos e GPU de 10 núcleos, até 18 horas de bateria e tela Liquid Retina de alta precisão.',
                'price' => 9499.00,
                'compare_at_price' => 10499.00,
                'cashback_percent' => 3.00,
                'stock' => 12,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $notebooks->id,
                'slug' => 'notebook-gamer-asus-rog-zephyrus-g16',
                'name' => 'Notebook Gamer ASUS ROG Zephyrus G16 Core Ultra 9 RTX 4070',
                'description' => 'Chassi ultrafino em peça única de alumínio CNC, tela ROG Nebula OLED 2.5K de 240Hz, processador Intel Core Ultra 9 com IA dedicada e placa gráfica NVIDIA RTX 4070.',
                'price' => 13999.00,
                'compare_at_price' => 15499.00,
                'cashback_percent' => 2.00,
                'stock' => 6,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $notebooks->id,
                'slug' => 'notebook-lenovo-thinkpad-e14-i5',
                'name' => 'Notebook Lenovo ThinkPad E14 Intel Core i5 16GB SSD 512GB',
                'description' => 'Confiabilidade e durabilidade de nível militar MIL-SPEC comprovada, teclado ergonômico premiado com TrackPoint, leitor biométrico e tampa mecânica ThinkShutter.',
                'price' => 3899.00,
                'compare_at_price' => 4399.00,
                'cashback_percent' => 3.00,
                'stock' => 15,
                'image_path' => '/images/products/placeholder.svg',
            ],

            // --- PERIFÉRICOS & ACESSÓRIOS ---
            [
                'category_id' => $perifericos->id,
                'slug' => 'teclado-mecanico-logitech-mx-mechanical',
                'name' => 'Teclado Mecânico Sem Fio Logitech MX Mechanical',
                'description' => 'Teclas mecânicas de perfil baixo silenciosas (Tactile Quiet), retroiluminação inteligente com sensor de aproximação das mãos e conexão sem fio Logi Bolt ou Bluetooth.',
                'price' => 749.50,
                'compare_at_price' => 849.00,
                'cashback_percent' => 5.00,
                'stock' => 30,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $perifericos->id,
                'slug' => 'mouse-ergonomico-logitech-mx-master-3s',
                'name' => 'Mouse Sem Fio Logitech MX Master 3S Ergonômico',
                'description' => 'Sensor de alta precisão 8.000 DPI com rastreamento em qualquer superfície (inclusive vidro), rolagem eletromagnética MagSpeed e cliques silenciosos Quiet Clicks.',
                'price' => 589.00,
                'compare_at_price' => 699.00,
                'cashback_percent' => 4.00,
                'stock' => 40,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $perifericos->id,
                'slug' => 'mouse-gamer-razer-deathadder-v3-pro',
                'name' => 'Mouse Gamer Sem Fio Razer DeathAdder V3 Pro Ultralight 63g',
                'description' => 'Formato ergonômico refinado em colaboração com atletas mundiais de eSports, sensor óptico Focus Pro 30K de máxima precisão e switches ópticos de 3ª geração.',
                'price' => 899.00,
                'compare_at_price' => 1049.00,
                'cashback_percent' => 4.00,
                'stock' => 22,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $perifericos->id,
                'slug' => 'teclado-mecanico-corsair-k70-rgb-pro',
                'name' => 'Teclado Mecânico Gamer Corsair K70 RGB PRO Cherry MX Red',
                'description' => 'Chassi de alumínio escovado com switches lineares Cherry MX Red, tecnologia Corsair AXON com frequência de sondagem de 8.000Hz e iluminação RGB dinâmica por tecla.',
                'price' => 999.00,
                'compare_at_price' => 1199.00,
                'cashback_percent' => 4.00,
                'stock' => 18,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $perifericos->id,
                'slug' => 'webcam-logitech-brio-4k-hdr',
                'name' => 'Webcam Ultra HD 4K Logitech Brio com HDR e RightLight 3',
                'description' => 'Transmissão em vídeo cristalino 4K a 30fps ou 1080p a 60fps com HDR, suporte a reconhecimento facial seguro Windows Hello e microfones estéreo integrados antirruído.',
                'price' => 1199.00,
                'compare_at_price' => 1399.00,
                'cashback_percent' => 3.00,
                'stock' => 25,
                'image_path' => '/images/products/placeholder.svg',
            ],

            // --- MONITORES & TELAS ---
            [
                'category_id' => $monitores->id,
                'slug' => 'monitor-gamer-lg-ultragear-27-144hz',
                'name' => 'Monitor Gamer LG UltraGear 27" Full HD IPS 144Hz 1ms',
                'description' => 'Cores precisas com painel IPS e 99% da gama sRGB, taxa de atualização veloz de 144Hz com tempo de resposta real de 1ms MBR, compatível com NVIDIA G-Sync e HDR10.',
                'price' => 1399.00,
                'compare_at_price' => 1599.00,
                'cashback_percent' => 4.00,
                'stock' => 20,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $monitores->id,
                'slug' => 'monitor-dell-ultrasharp-32-4k-usb-c',
                'name' => 'Monitor Profissional Dell UltraSharp 32" 4K Hub USB-C IPS Black',
                'description' => 'Tecnologia IPS Black pioneira com taxa de contraste de 2.000:1, resolução 4K UHD 3840x2160, hub USB-C com fornecimento de até 90W e porta RJ45 Gigabit integrada.',
                'price' => 4799.00,
                'compare_at_price' => 5299.00,
                'cashback_percent' => 3.00,
                'stock' => 8,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $monitores->id,
                'slug' => 'monitor-curvo-samsung-odyssey-oled-g9-49',
                'name' => 'Monitor Curvo Gamer Samsung Odyssey OLED G9 49" 240Hz 0.03ms',
                'description' => 'Tela curva ultrawide 32:9 de 49 polegadas Dual QHD (5120x1440), painel OLED com pretos infinitos, tempo de resposta relâmpago de 0.03ms e processador Neo Quantum Pro.',
                'price' => 9899.00,
                'compare_at_price' => 10999.00,
                'cashback_percent' => 2.00,
                'stock' => 5,
                'image_path' => '/images/products/placeholder.svg',
            ],

            // --- ARMAZENAMENTO & MEMÓRIA ---
            [
                'category_id' => $armazenamento->id,
                'slug' => 'ssd-kingston-kc3000-2tb-nvme',
                'name' => 'SSD Kingston KC3000 2TB NVMe PCIe 4.0 M.2 7000MB/s',
                'description' => 'Velocidades de leitura e gravação fulminantes de até 7.000 MB/s, controlador Phison E18 de alto rendimento e dissipador térmico em alumínio e grafeno de perfil baixo.',
                'price' => 1149.00,
                'compare_at_price' => 1299.00,
                'cashback_percent' => 5.00,
                'stock' => 30,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $armazenamento->id,
                'slug' => 'ssd-portatil-sandisk-extreme-1tb',
                'name' => 'SSD Portátil SanDisk Extreme 1TB USB 3.2 Gen 2 Resistente IP55',
                'description' => 'Velocidades de transferência de até 1.050 MB/s em design compacto e robusto com alça mosquetão, resistência contra quedas de até 2 metros e proteção IP55 contra chuva e poeira.',
                'price' => 689.00,
                'compare_at_price' => 799.00,
                'cashback_percent' => 5.00,
                'stock' => 35,
                'image_path' => '/images/products/placeholder.svg',
            ],

            // --- CONSOLES DE VIDEOGAME ---
            [
                'category_id' => $consoles->id,
                'slug' => 'playstation-5-slim-1tb',
                'name' => 'Console PlayStation 5 Slim Edição Digital 1TB SSD',
                'description' => 'Chassi refinado e mais compacto, armazenamento ultrarrápido SSD de 1TB, feedback tátil imersivo, gatilhos adaptáveis no DualSense e suporte a Ray Tracing em 4K nativo.',
                'price' => 3599.00,
                'compare_at_price' => 3999.00,
                'cashback_percent' => 3.00,
                'stock' => 20,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $consoles->id,
                'slug' => 'xbox-series-x-1tb',
                'name' => 'Console Xbox Series X 1TB Preto Fosco 12 Teraflops',
                'description' => 'O console mais veloz e potente da história do Xbox com 12 teraflops de potência gráfica RDNA 2, Quick Resume para alternar jogos instantaneamente e suporte a 120 FPS.',
                'price' => 4299.00,
                'compare_at_price' => 4799.00,
                'cashback_percent' => 3.00,
                'stock' => 15,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $consoles->id,
                'slug' => 'nintendo-switch-oled-branco',
                'name' => 'Console Nintendo Switch Modelo OLED Branco 64GB',
                'description' => 'Brilhante tela OLED de 7 polegadas com cores intensas, suporte traseiro ajustável largo de mesa, base com entrada LAN integrada e versatilidade única portátil e na TV.',
                'price' => 2099.00,
                'compare_at_price' => 2399.00,
                'cashback_percent' => 4.00,
                'stock' => 25,
                'image_path' => '/images/products/placeholder.svg',
            ],

            // --- ACESSÓRIOS GAMER ---
            [
                'category_id' => $acessoriosGamer->id,
                'slug' => 'controle-sony-dualsense-edge-ps5',
                'name' => 'Controle Sem Fio Sony DualSense Edge PS5 Pro Personalizável',
                'description' => 'Controle de alto desempenho para eSports com módulos analógicos substituíveis, botões traseiros mapeáveis, travas de gatilho ajustáveis e estojo rígido de transporte.',
                'price' => 1349.00,
                'compare_at_price' => 1599.00,
                'cashback_percent' => 4.00,
                'stock' => 18,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $acessoriosGamer->id,
                'slug' => 'headset-hyperx-cloud-iii-wireless',
                'name' => 'Headset Sem Fio Gamer HyperX Cloud III Wireless 120h',
                'description' => 'Drivers angulados de 53mm com calibração acústica profissional, áudio espacial DTS Headphone:X, microfone cristalino de 10mm com LED e bateria absurda para até 120 horas de gameplay.',
                'price' => 899.00,
                'compare_at_price' => 1099.00,
                'cashback_percent' => 5.00,
                'stock' => 24,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $acessoriosGamer->id,
                'slug' => 'volante-logitech-g29-driving-force',
                'name' => 'Volante e Pedais Logitech G29 Driving Force Force Feedback',
                'description' => 'Force Feedback realista de motor duplo com engrenagens helicoidais anti-retrocesso, 900 graus de rotação volante-a-volante e pedais responsivos não lineares em aço escovado.',
                'price' => 1799.00,
                'compare_at_price' => 2099.00,
                'cashback_percent' => 4.00,
                'stock' => 12,
                'image_path' => '/images/products/placeholder.svg',
            ],

            // --- CASA & COZINHA (ELETRODOMÉSTICOS & CASA INTELIGENTE) ---
            [
                'category_id' => $eletrodomesticos->id,
                'slug' => 'air-fryer-philips-walita-essential-xl',
                'name' => 'Air Fryer Philips Walita Essential XL 6,2L Digital',
                'description' => 'Fritadeira sem óleo com capacidade de 6,2 litros, tecnologia Rapid Air para refeições crocantes por fora e suculentas por dentro, com 7 receitas pré-programadas.',
                'price' => 699.00,
                'compare_at_price' => 899.00,
                'cashback_percent' => 6.00,
                'stock' => 30,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $eletrodomesticos->id,
                'slug' => 'cafeteira-nespresso-vertuo-pop',
                'name' => 'Cafeteira Nespresso Vertuo Pop Preto Espresso e Café',
                'description' => 'Sistema de extração por centrifugação que prepara cafés de 40ml a 230ml com crema perfeita, aquecimento em apenas 30 segundos e design compacto para qualquer cozinha.',
                'price' => 899.00,
                'compare_at_price' => 1099.00,
                'cashback_percent' => 5.00,
                'stock' => 22,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $assistentesHubs->id,
                'slug' => 'amazon-echo-dot-5-alexa',
                'name' => 'Smart Speaker Amazon Echo Dot 5ª Geração com Alexa',
                'description' => 'Experiência de áudio aprimorada com vocais nítidos e graves profundos, sensores integrados de movimento e temperatura ambiente e controle completo de rotinas por voz.',
                'price' => 389.00,
                'compare_at_price' => 449.00,
                'cashback_percent' => 5.00,
                'stock' => 50,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $assistentesHubs->id,
                'slug' => 'amazon-echo-show-8-3-geracao',
                'name' => 'Smart Display Amazon Echo Show 8 (3ª Geração) Áudio Espacial',
                'description' => 'Tela HD de 8 polegadas sensível ao toque com som espacial envolvente, câmera central de 13 MP com enquadramento automático para chamadas em vídeo e hub Zigbee/Matter integrado.',
                'price' => 1199.00,
                'compare_at_price' => 1399.00,
                'cashback_percent' => 4.00,
                'stock' => 20,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $seguranca->id,
                'slug' => 'camera-intelbras-im3-c-wifi',
                'name' => 'Câmera de Segurança Inteligente Intelbras Wi-Fi Full HD iM3 C',
                'description' => 'Resolução Full HD 1080p com campo de visão superamplo de 131°, visão noturna de até 10 metros, detecção inteligente de ruídos e interação com áudio bidirecional.',
                'price' => 249.00,
                'compare_at_price' => 299.00,
                'cashback_percent' => 5.00,
                'stock' => 40,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $seguranca->id,
                'slug' => 'fechadura-digital-intelbras-fr101',
                'name' => 'Fechadura Digital de Sobrepor Intelbras FR 101 com Teclado Touch',
                'description' => 'Abertura rápida e segura via senha de 4 a 12 dígitos, sensor de travamento automático ao bater a porta, alarme sonoro contra arrombamento e função não perturbe.',
                'price' => 749.00,
                'compare_at_price' => 849.00,
                'cashback_percent' => 4.00,
                'stock' => 15,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $seguranca->id,
                'slug' => 'lampada-inteligente-philips-smart-led',
                'name' => 'Lâmpada Inteligente Wi-Fi Philips Smart LED Colors E27 9W',
                'description' => 'Escolha entre 16 milhões de cores vibrantes e regule a temperatura do branco quente ao frio, programação de horários automáticos e integração com Alexa e Google Assistente.',
                'price' => 99.00,
                'compare_at_price' => 129.00,
                'cashback_percent' => 5.00,
                'stock' => 60,
                'image_path' => '/images/products/placeholder.svg',
            ],

            // --- CÂMERAS & DRONES ---
            [
                'category_id' => $camerasAcao->id,
                'slug' => 'camera-sony-alpha-a7-iv',
                'name' => 'Câmera Mirrorless Full-Frame Sony Alpha A7 IV 4K 33MP (Corpo)',
                'description' => 'Sensor Full-Frame Exmor R de 33MP, processador de última geração BIONZ XR, gravação 4K 60p em 10-bit 4:2:2 e autofoco híbrido em tempo real para pessoas, aves e animais.',
                'price' => 14899.00,
                'compare_at_price' => 16499.00,
                'cashback_percent' => 2.00,
                'stock' => 5,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $camerasAcao->id,
                'slug' => 'camera-gopro-hero-12-black',
                'name' => 'Câmera de Ação GoPro HERO12 Black À Prova D\'Água 5.3K',
                'description' => 'Vídeos impressionantes em 5.3K60 e 4K120, estabilização de vídeo premiada HyperSmooth 6.0 com trava de horizonte 360°, fotos de 27MP e resistente à água até 10 metros sem case.',
                'price' => 2499.00,
                'compare_at_price' => 2899.00,
                'cashback_percent' => 4.00,
                'stock' => 18,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $drones->id,
                'slug' => 'drone-dji-mini-4-pro-rc2',
                'name' => 'Drone DJI Mini 4 Pro com Controle DJI RC 2 Tela Integrada',
                'description' => 'Design ultraleve dobrável com menos de 249g, gravação 4K/60fps HDR, detecção omnidirecional de obstáculos em tempo real e tempo máximo de voo de até 34 minutos.',
                'price' => 7999.00,
                'compare_at_price' => 8899.00,
                'cashback_percent' => 3.00,
                'stock' => 7,
                'image_path' => '/images/products/placeholder.svg',
            ],
            [
                'category_id' => $drones->id,
                'slug' => 'estabilizador-dji-osmo-mobile-6',
                'name' => 'Estabilizador Gimbal de Smartphone DJI Osmo Mobile 6',
                'description' => 'Estabilização motorizada de 3 eixos, desdobramento magnético ultrarrápido, haste extensora embutida para selfies e ângulos dinâmicos, com rastreamento inteligente ActiveTrack 6.0.',
                'price' => 999.00,
                'compare_at_price' => 1199.00,
                'cashback_percent' => 4.00,
                'stock' => 20,
                'image_path' => '/images/products/placeholder.svg',
            ],
        ];

        foreach ($products as $productData) {
            $productData['image_path'] = '/images/products/'.$productData['slug'].'.webp';
            Product::updateOrCreate(
                ['slug' => $productData['slug']],
                array_merge($productData, ['is_active' => true])
            );
        }
    }
}

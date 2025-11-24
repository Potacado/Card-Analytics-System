<?php
/*
Plugin Name: Card Analytics
Description: Full-featured card system with interactive dashboard, filtering, and dynamic ROI.
Version: 2.0
*/

function load_chart_scripts() {
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.0', true);
}
add_action('wp_enqueue_scripts', 'load_chart_scripts');

// 1. Dashboard
function display_interactive_dashboard() {
    $current_user = wp_get_current_user();
    $args = array('post_type' => 'cards', 'author' => $current_user->ID, 'posts_per_page' => -1);
    $cards = get_posts($args);

    $total_value = 0;
    $total_cost = 0;
    $rarities = array();

    foreach ($cards as $card) {
        $price = (float)get_field('market_price', $card->ID);
        $cost = (float)get_field('purchase_cost', $card->ID);
        $rarity = get_field('rarity', $card->ID);
        
        $total_value += $price;
        $total_cost += $cost;
        
        if ($rarity && !in_array($rarity, $rarities)) {
            $rarities[] = $rarity;
        }
    }

    $total_profit = $total_value - $total_cost;
    $total_roi_percent = ($total_cost > 0) ? round(($total_profit / $total_cost) * 100, 1) : 0;
    $roi_color = ($total_profit >= 0) ? '#15803d' : '#b91c1c';
    $roi_bg = ($total_profit >= 0) ? '#dcfce7' : '#fee2e2';

    $output = '<style>
        /* 統計儀表板 */
        .stats-container { display: flex; gap: 20px; margin-bottom: 25px; }
        .stat-box { 
            flex: 1; 
            background: #fff; 
            padding: 20px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
            border: 1px solid #eee;
            text-align: center;
        }
        .stat-title { font-size: 0.9em; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .stat-value { font-size: 1.8em; font-weight: bold; color: #2c3e50; }
        .stat-roi { font-size: 1.2em; padding: 4px 12px; border-radius: 20px; display: inline-block; margin-top: 5px; }

        /* 過濾器 */
        .filter-container { margin-bottom: 15px; text-align: right; }
        .rarity-select { padding: 8px 15px; border-radius: 6px; border: 1px solid #ddd; font-size: 1em; color: #444; background: #fff; cursor: pointer; }

        /* 表格優化 */
        .card-table { width: 100%; border-collapse: separate; border-spacing: 0; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.08); border: 1px solid #f0f0f0; }
        .card-table th { background: #f8f9fa; color: #2c3e50; padding: 15px; font-weight: 700; border-bottom: 2px solid #e9ecef; text-align: left; }
        .card-table td { padding: 15px; border-bottom: 1px solid #f1f1f1; vertical-align: middle; }
        
        /* 可點擊行 */
        .clickable-row { cursor: pointer; transition: background 0.2s; }
        .clickable-row:hover { background-color: #f0f8ff !important; transform: scale(1.005); box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        
        .card-img { width: 50px; height: auto; border-radius: 6px; border: 1px solid #eee; }
        .profit-plus { color: #15803d; font-weight: bold; background: #dcfce7; padding: 4px 8px; border-radius: 20px; font-size: 0.85em; }
        .profit-minus { color: #b91c1c; font-weight: bold; background: #fee2e2; padding: 4px 8px; border-radius: 20px; font-size: 0.85em; }
    </style>';

    $output .= '<div class="stats-container">
        <div class="stat-box">
            <div class="stat-title">Total Collection Value</div>
            <div class="stat-value" id="disp-total-val">$' . number_format($total_value, 2) . '</div>
        </div>
        <div class="stat-box">
            <div class="stat-title">Total ROI</div>
            <div class="stat-value">
                <span id="disp-total-roi" class="stat-roi" style="background:'.$roi_bg.'; color:'.$roi_color.';">
                    ' . ($total_profit >= 0 ? '+' : '') . $total_roi_percent . '%
                </span>
            </div>
            <div style="font-size:0.8em; color:#aaa; margin-top:5px;" id="disp-profit-abs">
                (' . ($total_profit >= 0 ? '+' : '') . '$' . number_format($total_profit, 2) . ')
            </div>
        </div>
    </div>';

    $output .= '<div class="filter-container">
        <label style="font-weight:bold; margin-right:10px;">Filter by Rarity:</label>
        <select id="rarity-filter" class="rarity-select" onchange="filterCards()">
            <option value="all">Show All</option>';
            foreach ($rarities as $r) {
                $output .= '<option value="' . esc_attr($r) . '">' . esc_html($r) . '</option>';
            }
    $output .= '</select></div>';

    $output .= '<table class="card-table">
                <tr>
                    <th>Image</th>
                    <th>Card Name</th>
                    <th>Rarity</th>
                    <th>Cost</th>
                    <th>Market Price</th>
                    <th>ROI</th>
                </tr>';
    
    foreach ($cards as $card) {
        $market_price = (float)get_field('market_price', $card->ID);
        $purchase_cost = (float)get_field('purchase_cost', $card->ID);
        $rarity = get_field('rarity', $card->ID);
        $img_url = get_field('card_image_url', $card->ID);
        $link = get_permalink($card->ID);

        $profit = $market_price - $purchase_cost;
        $profit_percent = ($purchase_cost > 0) ? round(($profit / $purchase_cost) * 100, 1) : 0;
        $profit_class = ($profit >= 0) ? 'profit-plus' : 'profit-minus';
        $sign = ($profit >= 0) ? '+' : '';
        $img_tag = $img_url ? '<img src="' . esc_url($img_url) . '" class="card-img">' : '-';

        $output .= '<tr class="clickable-row" 
                        onclick="window.location.href=\'' . $link . '\'" 
                        data-rarity="' . esc_attr($rarity) . '" 
                        data-price="' . $market_price . '" 
                        data-cost="' . $purchase_cost . '">
            <td>' . $img_tag . '</td>
            <td style="font-weight:bold;">' . esc_html($card->post_title) . '</td>
            <td><span style="background:#f1f1f1; padding:2px 8px; border-radius:4px; font-size:0.9em;">' . esc_html($rarity) . '</span></td>
            <td style="color:#888;">$' . number_format($purchase_cost, 2) . '</td>
            <td style="font-weight:bold;">$' . number_format($market_price, 2) . '</td>
            <td><span class="' . $profit_class . '">' . $sign . $profit_percent . '%</span></td>
        </tr>';
    }
    $output .= '</table>';

    $output .= '<script>
    function filterCards() {
        var filter = document.getElementById("rarity-filter").value;
        var rows = document.querySelectorAll(".clickable-row");
        
        var totalVal = 0;
        var totalCost = 0;

        rows.forEach(function(row) {
            var rowRarity = row.getAttribute("data-rarity");
            var price = parseFloat(row.getAttribute("data-price"));
            var cost = parseFloat(row.getAttribute("data-cost"));

            if (filter === "all" || rowRarity === filter) {
                row.style.display = "";
                totalVal += price;
                totalCost += cost;
            } else {
                row.style.display = "none";
            }
        });

        var totalProfit = totalVal - totalCost;
        var roi = (totalCost > 0) ? ((totalProfit / totalCost) * 100).toFixed(1) : 0;
        var sign = (totalProfit >= 0) ? "+" : "";

        document.getElementById("disp-total-val").innerText = "$" + totalVal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        
        var roiEl = document.getElementById("disp-total-roi");
        roiEl.innerText = sign + roi + "%";
        roiEl.style.color = (totalProfit >= 0) ? "#15803d" : "#b91c1c";
        roiEl.style.background = (totalProfit >= 0) ? "#dcfce7" : "#fee2e2";

        document.getElementById("disp-profit-abs").innerText = "(" + sign + "$" + totalProfit.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ")";
    }
    </script>';

    return $output;
}
add_shortcode('interactive_dashboard', 'display_interactive_dashboard'); 


// 2. Single Card View
function custom_single_card_template($content) {
    if (is_singular('cards') && in_the_loop() && is_main_query()) {
        
        $post_id = get_the_ID();
        $set = get_field('set', $post_id);
        $rarity = get_field('rarity', $post_id);
        $condition = get_field('condition', $post_id);
        $market_price = get_field('market_price', $post_id);
        $img_url = get_field('card_image_url', $post_id);

        $chart_html = do_shortcode('[market_trend card_id="' . $post_id . '"]');

        $new_content = '
        <div style="display: flex; gap: 40px; margin-bottom: 40px; flex-wrap: wrap;">
            <!-- 左邊：大圖 -->
            <div style="flex: 1; min-width: 300px;">
                <img src="' . esc_url($img_url) . '" style="width: 100%; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            </div>

            <!-- 右邊：詳細資料 -->
            <div style="flex: 1.5; min-width: 300px;">
                <h1 style="margin-top:0;">' . get_the_title() . '</h1>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 12px; border: 1px solid #eee;">
                    <p><strong>Set:</strong> ' . esc_html($set) . '</p>
                    <p><strong>Rarity:</strong> ' . esc_html($rarity) . '</p>
                    <p><strong>Condition:</strong> ' . esc_html($condition) . '</p>
                    <hr style="margin: 15px 0; border-color: #eee;">
                    <p style="font-size: 1.5em; color: #2c3e50;"><strong>Current Price:</strong> $' . number_format($market_price, 2) . '</p>
                </div>
                
                <h3 style="margin-top: 30px;">Price History (Trend)</h3>
                ' . $chart_html . '
            </div>
        </div>';

        return $new_content;
    }
    return $content;
}
add_filter('the_content', 'custom_single_card_template');

// 3. Market Trend Chart
function market_trend($atts) {
    $atts = shortcode_atts(array('card_id' => 0), $atts);
    $history_json = get_field('historical_prices', $atts['card_id']); 
    $history = json_decode($history_json, true); 

    $labels = ''; $data = '';
    if ($history && is_array($history)) {
        foreach ($history as $entry) {
            $labels .= '"' . $entry['date'] . '",';
            $data .= $entry['price'] . ',';
        }
    } else {
        return '<p>No data available.</p>';
    }

    $chart_id = 'chart_' . rand(1000,9999);

    return '<div style="width: 100%; height: 300px;"><canvas id="' . $chart_id . '"></canvas></div>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        new Chart(document.getElementById("' . $chart_id . '"), {
            type: "line",
            data: {
                labels: [' . rtrim($labels, ',') . '],
                datasets: [{
                    label: "Price History",
                    data: [' . rtrim($data, ',') . '],
                    borderColor: "#4db8ff",
                    backgroundColor: "rgba(77, 184, 255, 0.1)",
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: { maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });
    });
    </script>';
}
add_shortcode('market_trend', 'market_trend');


// 4. Price Alert System
function price_alert_shortcode() {
    if (!is_user_logged_in()) return '<p>Please login.</p>';
    $current_user_id = get_current_user_id();
    $message = '';
    if (isset($_POST['submit_price_alert'])) {
        $card_name = sanitize_text_field($_POST['alert_card_name']);
        $target_price = floatval($_POST['alert_target_price']);
        add_user_meta($current_user_id, 'user_card_alerts', array('card_name'=>$card_name, 'target_price'=>$target_price));
        $message = '<div style="background:#dcfce7;color:#15803d;padding:10px;border-radius:5px;margin-bottom:10px;">Alert Set!</div>';
    }
    if (isset($_POST['reset_alerts'])) {
        delete_user_meta($current_user_id, 'user_card_alerts');
        $message = '<div style="background:#fee2e2;color:#b91c1c;padding:10px;border-radius:5px;margin-bottom:10px;">Cleared.</div>';
    }

    $args = array('post_type' => 'cards', 'posts_per_page' => -1);
    $cards = get_posts($args);
    
    $output = $message . '<div style="border:1px solid #eee; padding:20px; background:#fff; border-radius:12px; box-shadow:0 5px 20px rgba(0,0,0,0.05);">';
    $output .= '<h3 style="margin-top:0;">Set Price Alert</h3><form method="post" style="display:flex; gap:10px; align-items:center;">';
    $output .= '<select name="alert_card_name" style="padding:8px;">';
    foreach ($cards as $card) $output .= '<option>' . esc_html($card->post_title) . '</option>';
    $output .= '</select> <input type="number" step="0.01" name="alert_target_price" placeholder="Target $" required style="padding:8px; width:100px;"> ';
    $output .= '<input type="submit" name="submit_price_alert" value="Set" style="background:#2c3e50; color:white; border:none; padding:8px 15px; border-radius:5px; cursor:pointer;"></form></div>';

    $saved_alerts = get_user_meta($current_user_id, 'user_card_alerts');
    if (!empty($saved_alerts)) {
        $output .= '<table style="width:100%; margin-top:20px; border-collapse:collapse;"><tr><th style="text-align:left; padding:10px; border-bottom:2px solid #eee;">Card</th><th style="text-align:left; border-bottom:2px solid #eee;">Target</th><th style="text-align:left; border-bottom:2px solid #eee;">Current</th><th style="text-align:left; border-bottom:2px solid #eee;">Status</th></tr>';
        foreach ($saved_alerts as $alert) {
            $card_obj = get_page_by_title($alert['card_name'], OBJECT, 'cards');
            $curr = $card_obj ? get_field('market_price', $card_obj->ID) : 0;
            $status = ($curr <= $alert['target_price']) ? '<span style="color:red; font-weight:bold;">⚠️ ALERT!</span>' : '<span style="color:green;">Waiting</span>';
            $output .= '<tr><td style="padding:10px; border-bottom:1px solid #eee;">'.$alert['card_name'].'</td><td style="border-bottom:1px solid #eee;">$'.$alert['target_price'].'</td><td style="border-bottom:1px solid #eee;">$'.$curr.'</td><td style="border-bottom:1px solid #eee;">'.$status.'</td></tr>';
        }
        $output .= '</table><form method="post" style="margin-top:10px;"><input type="submit" name="reset_alerts" value="Clear Lists" style="background:#eee; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;"></form>';
    }
    return $output;
}
add_shortcode('price_alert_system', 'price_alert_shortcode');

// 5. Market Overview (Highlights + All Charts)
function display_market_overview() {
    $args = array('post_type' => 'cards', 'posts_per_page' => -1);
    $cards = get_posts($args);

    $chart_data_collection = array();
    $movers = array();

    foreach ($cards as $card) {
        $history_json = get_field('historical_prices', $card->ID);
        $history = json_decode($history_json, true);

        if ($history && count($history) >= 2) {

            $first_entry = reset($history);
            $last_entry = end($history);
            
            $start_price = (float)$first_entry['price'];
            $end_price = (float)$last_entry['price'];

            $change_amt = $end_price - $start_price;
            $change_pct = ($start_price > 0) ? ($change_amt / $start_price) * 100 : 0;

            $movers[] = array(
                'name' => $card->post_title,
                'pct' => $change_pct,
                'price' => $end_price,
                'img' => get_field('card_image_url', $card->ID)
            );

            $labels = array();
            $prices = array();
            foreach ($history as $h) {
                $labels[] = $h['date'];
                $prices[] = $h['price'];
            }
            
            $chart_data_collection[] = array(
                'id' => 'overview_chart_' . $card->ID,
                'title' => $card->post_title,
                'labels' => $labels,
                'data' => $prices,
                'color' => ($change_amt >= 0) ? '#15803d' : '#b91c1c',
                'bg_color' => ($change_amt >= 0) ? 'rgba(21, 128, 61, 0.1)' : 'rgba(185, 28, 28, 0.1)'
            );
        }
    }

    usort($movers, function($a, $b) {
        return $b['pct'] <=> $a['pct'];
    });

    $top_gainer = !empty($movers) ? $movers[0] : null;
    $top_loser = !empty($movers) ? end($movers) : null;

    $output = '<style>
        .market-highlights { display: flex; gap: 20px; margin-bottom: 40px; }
        .highlight-card { flex: 1; background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #eee; display: flex; align-items: center; gap: 15px; }
        .highlight-label { font-size: 0.85em; text-transform: uppercase; letter-spacing: 1px; color: #888; margin-bottom: 5px; }
        .highlight-val { font-size: 1.5em; font-weight: bold; color: #333; }
        .trend-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .trend-card { background: #fff; padding: 15px; border-radius: 12px; border: 1px solid #f0f0f0; box-shadow: 0 2px 10px rgba(0,0,0,0.03); }
        .trend-header { display: flex; justify-content: space-between; margin-bottom: 10px; font-weight: bold; color: #2c3e50; }
        .badge-green { background: #dcfce7; color: #15803d; padding: 2px 8px; border-radius: 10px; font-size: 0.8em; }
        .badge-red { background: #fee2e2; color: #b91c1c; padding: 2px 8px; border-radius: 10px; font-size: 0.8em; }
        .hl-img { width: 60px; height: auto; border-radius: 4px; border: 1px solid #eee; }
    </style>';

    $output .= '<h2 style="margin-bottom:20px; color:#2c3e50;">Market Highlights (30 Days)</h2>';
    $output .= '<div class="market-highlights">';

    if ($top_gainer) {
        $output .= '<div class="highlight-card" style="border-left: 5px solid #15803d;">
            <img src="' . esc_url($top_gainer['img']) . '" class="hl-img">
            <div>
                <div class="highlight-label">🚀 Top Gainer</div>
                <div class="highlight-val">' . esc_html($top_gainer['name']) . '</div>
                <div class="badge-green" style="display:inline-block; margin-top:5px;">+' . round($top_gainer['pct'], 2) . '%</div>
                <span style="font-size:0.9em; color:#666;">Current: $' . $top_gainer['price'] . '</span>
            </div>
        </div>';
    }

    if ($top_loser && $top_loser['name'] !== $top_gainer['name']) {
        $output .= '<div class="highlight-card" style="border-left: 5px solid #b91c1c;">
            <img src="' . esc_url($top_loser['img']) . '" class="hl-img">
            <div>
                <div class="highlight-label">📉 Biggest Drop</div>
                <div class="highlight-val">' . esc_html($top_loser['name']) . '</div>
                <div class="badge-red" style="display:inline-block; margin-top:5px;">' . round($top_loser['pct'], 2) . '%</div>
                <span style="font-size:0.9em; color:#666;">Current: $' . $top_loser['price'] . '</span>
            </div>
        </div>';
    }
    $output .= '</div>';

    $output .= '<h2 style="margin-bottom:20px; color:#2c3e50;">Market Trends Overview</h2>';
    $output .= '<div class="trend-grid">';
    
    foreach ($chart_data_collection as $c_data) {
        $output .= '<div class="trend-card">
            <div class="trend-header">
                <span>' . $c_data['title'] . '</span>
            </div>
            <div style="height: 150px; width: 100%;">
                <canvas id="' . $c_data['id'] . '"></canvas>
            </div>
        </div>';
    }
    $output .= '</div>';

    $output .= '<script>
    document.addEventListener("DOMContentLoaded", function() {
        var charts = ' . json_encode($chart_data_collection) . ';
        
        charts.forEach(function(c) {
            new Chart(document.getElementById(c.id), {
                type: "line",
                data: {
                    labels: c.labels,
                    datasets: [{
                        data: c.data,
                        borderColor: c.color,
                        backgroundColor: c.bg_color,
                        borderWidth: 2,
                        pointRadius: 0,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { enabled: true } },
                    scales: {
                        x: { display: false },
                        y: { display: true, ticks: { font: { size: 10 } } }
                    }
                }
            });
        });
    });
    </script>';

    return $output;
}
add_shortcode('market_overview', 'display_market_overview');

// Login Redirect
function my_login_redirect( $redirect_to, $request, $user ) {
    if ( isset( $user->wp_error ) ) return $redirect_to;
    return home_url( '/my-card-collection/' ); 
}
add_filter( 'login_redirect', 'my_login_redirect', 10, 3 );
?>
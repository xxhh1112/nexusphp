<?php

return [
    'name' => '名稱',
    'email' => '郵箱',
    'image' => '圖片',
    'expire_at' => '過期時間',
    'username' => '用戶',
    'status' => '狀態',
    'enabled' => '啟用',
    'disabled' => '禁用',
    'created_at' => '創建時間',
    'updated_at' => '更新時間',
    'begin' => '開始時間',
    'end' => '結束時間',
    'uploaded' => '上傳量',
    'downloaded' => '下載量',
    'ratio' => '分享率',
    'seed_time_required' => '還需做種時間',
    'inspect_time_left' => '考察剩余時間',
    'added' => '添加時間',
    'last_access' => '最後訪問時間',
    'priority' => '優先級',
    'priority_help' => '值越大，越靠前',
    'comment' => '備註',
    'duration' => '時長',
    'description' => '描述',
    'price' => '價格',
    'deadline' => '截止時間',
    'operator' => '操作者',
    'setting' => [
        'nav_text' => '設置',
        'backup' => [
            'tab_header' => '備份',
            'enabled' => '是否啟用',
            'enabled_help' => '是否啟用備份功能',
            'frequency' => '頻率',
            'frequency_help' => '備份頻率',
            'hour' => '小時',
            'hour_help' => '在這個點鐘數進行備份',
            'minute' => '分鐘',
            'minute_help' => "在前面點鐘數的這一分鐘進行備份。如果頻率是按 'hourly'，此值會被忽略",
            'google_drive_client_id' => 'Google Drive client ID',
            'google_drive_client_secret' => 'Google Drive client secret',
            'google_drive_refresh_token' => 'Google Drive refresh token',
            'google_drive_folder_id' => 'Google Drive folder ID',
            'via_ftp' => '通過 FTP 保存',
            'via_ftp_help' => '是否通過 FTP 保存。如果通過，把配置信息添加到 .env 文件，參考 <a href="https://laravel.com/docs/master/filesystem#ftp-driver-configuration">Laravel 文檔</a>',
            'via_sftp' => '通過 SFTP 保存',
            'via_sftp_help' => '是否通過 SFTP 保存。如果通過，把配置信息添加到 .env 文件，參考 <a href="https://laravel.com/docs/master/filesystem#sftp-driver-configuration">Laravel 文檔</a>',
        ],
        'hr' => [
            'tab_header' => 'H&R',
            'mode' => '模式',
            'inspect_time' => '考察時長',
            'inspect_time_help' => '考察時長自下載完成後開始計算，單位：小時',
            'seed_time_minimum' => '達標做種時長',
            'seed_time_minimum_help' => '達標的最短做種時長，單位：小時，必須小於考察時長',
            'ignore_when_ratio_reach' => '達標分享率',
            'ignore_when_ratio_reach_help' => '達標的最小分享率',
            'ban_user_when_counts_reach' => 'H&R 數量上限',
            'ban_user_when_counts_reach_help' => 'H&R 數量達到此值，賬號會被禁用',
        ],
        'seed_box' => [
            'tab_header' => 'SeedBox',
            'enabled_help' => '是否啟用 SeedBox 規則',
            'no_promotion' => '無優惠',
            'no_promotion_help' => '不享受任何優惠，上傳量/下載量按實際值計算',
            'max_uploaded' => '最大上傳量倍數',
            'max_uploaded_help' => '總上傳量最多為其體積的多少倍',
            'not_seed_box_max_speed' => '非 SeedBox 最高限速',
            'not_seed_box_max_speed_help' => '單位：Mbps。若超過此值又不能匹配 SeedBox 記錄，禁用下載權限',
        ],
    ],
    'user' => [
        'label' => '用戶',
        'uploaded' => '上傳量',
        'downloaded' => '下載量',
        'invites' => '邀請',
        'seedbonus' => '魔力',
        'attendance_card' => '補簽卡',
        'class' => '等級',
        'status' => '狀態',
        'enabled' => '啟用',
        'username' => '用戶名',
        'invite_by' => '邀請人',
        'two_step_authentication' => '兩步驗證',
        'seed_points' => '做種積分',
        'downloadpos' => '下載權限',
    ],
    'medal' => [
        'label' => '勛章',
        'image_large' => '大圖',
        'image_small' => '小圖',
        'get_type' => '獲取方式',
        'duration' => '有效時長',
        'duration_help' => '單位：天。如果留空，用戶永久擁有',
    ],
    'user_medal' => [
        'label' => '用戶勛章',
    ],
    'exam' => [
        'label' => '考核',
        'is_done' => '是否完成',
        'is_discovered' => '自動發現',
        'register_time_range' => [
            'begin' => '註冊時間開始',
            'end' => '註冊時間結束',
        ],
        'donated' => '是否捐贈',
        'index_formatted' => '考核指標',
        'filter_formatted' => '目標用戶',
        'section_base_info' => '基本信息',
        'priority_help' => '值越大，優先級越高。當用戶匹配多個考核時，優先級高的優先分配。',
        'section_time' => '時間',
        'duration_help' => '單位：天。分配給用戶時，如果指定了開始/結束時間，用戶考核的時間範圍就是這個範圍。否則，用戶考核的開始時間是分配時間，結束時間是開始時間加上時長。',
        'section_target_user' => '目標用戶',
        'index_required_value' => '要求量',
        'index_required_label' => '指標',
        'index_placeholder' => '上傳增量/下載增量單位為：GB，平均做種時間單位為：小時',
        'index_current_value' => '當前量',
        'index_result' => '結果',
    ],
    'exam_user' => [
        'label' => '用戶考核',
        'is_done' => '是否完成',
    ],
    'torrent' => [
        'label' => '種子',
        'owner' => '發布者',
        'size' => '大小',
        'ttl' => '存活時間',
        'seeders' => '做种',
        'leechers' => '下载',
        'times_completed' => '完成次數',
        'category' => '類型',
        'approval_status' => '審核狀態',
        'pos_state' => '置頂',
        'sp_state' => '優惠',
        'visible' => '活種',
    ],
    'hit_and_run' => [
        'label' => '用戶 H&R',
    ],
    'tag' => [
        'label' => '標簽',
        'color' => '背景顏色',
        'font_color' => '字體顏色',
        'font_size' => '字體大小',
        'margin' => '外邊距',
        'padding' => '內邊距',
        'border_radius' => '邊框圓角',
    ],
    'agent_allow' => [
        'label' => '允許客戶端',
        'family' => '系列',
        'start_name' => '起始名稱',
        'peer_id_start' => 'Peer ID 超始',
        'peer_id_pattern' => 'Peer ID 正則',
        'peer_id_matchtype' => 'Peer ID 匹配類型',
        'peer_id_match_num' => 'Peer ID 匹配次數',
        'agent_start' => 'Agent 起始',
        'agent_pattern' => 'Agent 正則',
        'agent_matchtype' => 'Agent 匹配類型',
        'agent_match_num' => 'Agent 匹配次數',
        'exception' => '排除',
        'allowhttps' => '允許 https',
    ],
    'agent_deny' => [
        'label' => '拒絕客戶端',
        'peer_id' => 'Peer ID',
        'agent' => 'Agent',
    ],
    'claim' => [
        'label' => '用戶認領',
        'last_settle_at' => '上次結算時間',
        'seed_time_this_month' => '本月做種時間',
        'uploaded_this_month' => '本月上傳量',
        'is_reached_this_month' => '本月是否達標',
    ],
    'torrent_state' => [
        'label' => '全站優惠',
        'global_sp_state' => '全站優惠',
    ],
    'role' => [
        'class' => '關聯用户等級',
    ],
    'seed_box_record' => [
        'label' => 'SeedBox 記錄',
        'type' => '添加類型',
        'operator' => '運營商',
        'bandwidth' => '帶寬(Mbps)',
        'ip' => 'IP(段)',
        'ip_begin' => '起始 IP',
        'ip_end' => '結束 IP',
        'ip_help' => '填寫起始 IP + 結束 IP，或 IP(段)，不要同時填寫',
        'status' => '狀態',
    ],
    'menu' => [
        'label' => '自定義菜單',
        'enable_help' => '是否啟用自定義菜單',
    ],
    'menu_item' => [
        'label' => '菜單項',
        'url' => '鏈接',
        'text' => '顯示文本',
        'target' => '打開方式',
        'style' => '樣式',
        'parent_id' => '父菜單',
        'min_class' => '最低可見等級',
    ],
    'user_meta' => [
        'meta_keys' => [
            \App\Models\UserMeta::META_KEY_CHANGE_USERNAME => '改名卡',
            \App\Models\UserMeta::META_KEY_PERSONALIZED_USERNAME => '彩虹 ID',
        ],
    ],
];

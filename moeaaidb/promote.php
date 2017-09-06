<?php

// 從 https://data.gov.tw/dataset/18366 抓出 102 - 104 補助資料，並調整資料
//
// wget http://www.moeaidb.gov.tw/opendata/01/af_opendata.zip
// env LC_ALL=C unzip af_opendata.zip
// convmv -f big5 -t utf-8 *.csv --notest


foreach ( array(
    '102年open data輔導廠商表.csv',
    '103年open data輔導廠商表.csv',
    '104年open data輔導廠商表1050415.csv',
) as $file) {
    $output = fopen('output-' . $file, 'w');
    $fp = fopen($file, 'r');
    $columns = fgetcsv($fp);

    $change_unit_rate = array(); // 記錄是否有單位從千元改回元的

    foreach ($columns as $seq => $n) {
        // 先把 (千元) 的都改成 (元)
        if (strpos($n, '(千元)')) {
            $columns[$seq] = str_replace('(千元)', '(元)', $n);
            $change_unit_rate[$columns[$seq]] = 1000;
        }

        // remove BOM
        $columns[$seq] = str_replace("\xEF\xBB\xBF", '', $columns[$seq]);

    }

    fputcsv($output, array_merge(array('vat'), array_filter($columns, function($n){ return $n != '統一編號'; })));

    while ($rows = fgetcsv($fp)) {
        $values = array_combine($columns, array_map('trim', $rows));

        // 把統一編號改名為 vat 並搬到最前面
        $vat = $values['統一編號'];
        unset($values['統一編號']);
        $values = array_merge(array('vat' => $vat), $values);

        // 如果是 (元) 的把逗點去掉，並且看需不需要乘一千
        foreach ($columns as $c) {
            if (strpos($c, '(元)')) {
                $values[$c] = str_replace(',', '', $values[$c]);
            }

            if (array_key_exists($c, $change_unit_rate)) {
                $values[$c] *= $change_unit_rate[$c];
            }
        }

        fputcsv($output, array_values($values));
    }
    fclose($output);
}

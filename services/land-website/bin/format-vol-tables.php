#!/usr/bin/env php
<?php

/**
 * Форматирование таблиц с данными по волонтёрам.
 *
 * Читает данные из файла guests.csv, формирует файлы 2015.md, 2016.md и так далее.
 **/

declare(strict_types=1);

if (count($argv) != 2) {
    fail("Usage: %s guests.csv\n", $argv[0]);
}

$data = load_file($argv[1]);
write_reports($data);

function count_days(string $came, string $left): int
{
    $dur = (strtotime($left) - strtotime($came)) / 86400;
    return $dur + 1;
}

function dd(): void
{
    $args = func_get_args();
    var_dump(...$args);
    exit(1);
}

function fail(): void
{
    $args = func_get_args();
    printf(...$args);
    exit(1);
}

function format_link(string $link = null): string
{
    if (null === $link) {
        return '';
    }

    $links = preg_split('@\s*,\s*@', $link, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($links as $link) {
        if (false !== strpos($link, '@')) {
            continue;
        }

        if (false === strpos($link, '://')) {
            $link = 'https://land.umonkey.net' . $link;
        }

        return "[ссылка]({$link})";
    }

    return '';
}

function format_markdown_table(array $head, array $align, array $table): string
{
    $widths = get_column_widths(array_merge([$head], $table));

    $text = format_table_row($head, $widths, $align) . "\n";
    $text .= format_table_aligners($widths, $align) . "\n";

    foreach ($table as $row) {
        $text .= format_table_row($row, $widths, $align) . "\n";
    }

    return $text;
}

function format_table_aligners(array $widths, array $align): string
{
    $cells = [];

    foreach ($align as $idx => $dir) {
        $width = $widths[$idx];

        if ($dir == 'r') {
            $cells[] = str_repeat('-', $width + 1) . ':';
        } elseif ($dir == 'c') {
            $cells[] = ':' . str_repeat('-', $width) . ':';
        } else {
            $cells[] = ':' . str_repeat('-', $width + 1);
        }
    }

    return '|' . implode('|', $cells) . '|';
}

function format_table_row(array $row, array $widths, array $align): string
{
    $cells = [];

    foreach ($row as $idx => $cell) {
        $width = $widths[$idx];
        $add = $width - mb_strlen($cell);

        if ($add > 0) {
            $pad = str_repeat(' ', $add);

            switch ($align[$idx]) {
                case 'l':
                    $cell = $cell . $pad;
                    break;
                case 'r':
                    $cell = $pad . $cell;
                    break;
            }
        }

        $cells[] = $cell;
    }

    return '| ' . implode(' | ', $cells) . ' |';
}

function format_year_report(int $year, array $data): string
{
    $totalDays = get_total_days($data);
    $totalPeople = get_unique_visitors($data);

    $report = format_year_table($year, $data) . "\n";

    return $report;
}

function format_year_table(int $year, array $data): string
{
    $head = ['№', 'Имя', 'Чел.', 'Откуда', 'Заезд', 'Выезд', 'Дней', 'Комментарии'];
    $align = ['r', 'l', 'r', 'l', 'l', 'l', 'r', 'l'];

    $index = 1;
    $nameHistory = [];
    $totalDays = 0;

    $table = [];
    foreach ($data as $item) {
        $row = [];

        $name = $item['name'];
        $days = count_days($item['came'], $item['left']);
        $qty = (int)$item['qty'];

        if (isset($nameHistory[$name])) {
            $row[] = '';
        } else {
            $row[] = (string)$index++;
            $nameHistory[$name] = $qty;
        }

        $row[] = '[[' . $name . ']]';
        $row[] = $qty === 1 ? '' : (string)$item['qty'];
        $row[] = $item['city'];
        $row[] = strftime('%d.%m', strtotime($item['came']));
        $row[] = strftime('%d.%m', strtotime($item['left']));
        $row[] = (string)$days;
        $row[] = $item['comments'];

        $table[] = $row;

        $totalDays += $qty * $days;
    }

    $totalPeople = array_sum($nameHistory);

    $table[] = [
        '-',
        'Всего',
        sprintf('**%u**', $totalPeople),
        '',
        '',
        '',
        sprintf('**%u**', $totalDays),
        sprintf('в среднем %u дней', floor($totalDays / $totalPeople)),
    ];

    return format_markdown_table($head, $align, $table);
}

function get_column_widths(array $table): array
{
    $widths = [];

    foreach ($table as $row) {
        foreach ($row as $idx => $cell) {
            $width = $widths[$idx] ?? 0;
            $widths[$idx] = max($width, mb_strlen($cell));
        }
    }

    return $widths;
}

function get_sources(array $data): string
{
    $sources = [];
    $nameHistory = [];

    foreach ($data as $row) {
        $name = $row['name'];
        $qty = (int)$row['qty'];

        if (!isset($nameHistory[$name])) {
            $nameHistory[$name] = true;

            $source = $row['city'];

            $count = $sources[$source] ?? 0;
            $count += $qty;
            $sources[$source] = $count;
        }
    }

    ksort($sources);

    $text = "";
    foreach ($sources as $city => $count) {
        $text .= sprintf("- %s: %u\n", $city, $count);
    }

    return $text;
}

function get_total_days(array $table): int
{
    $days = 0;

    foreach ($table as $row) {
        $qty = (int)$row['qty'];
        $days += count_days($row['came'], $row['left']) * $qty;
    }

    return $days;
}

function get_total_report(array $data): string
{
    $years = get_year_range($data);

    $tableHead = ['№', 'Город'];
    $tableAlign = ['r', 'l'];
    foreach ($years as $year) {
        $tableHead[] = (string)$year;
        $tableAlign[] = 'r';
    }
    $tableHead[] = 'Всего';
    $tableAlign[] = 'r';

    $tableBody = [];

    $cities = [];
    $allCities = [];

    $totals = [];  // Сводка по годам.

    foreach ($data as $row) {
        $year = (int)substr($row['came'], 0, 4);
        $name = $row['name'];
        $city = $row['city'];
        $qty = (int)$row['qty'];

        $cities[$city][$year][$name] = ($cities[$city][$year][$name] ?? 0) + $qty;
        $cities[$city]['total'][$name] = ($cities[$city]['total'][$name] ?? 0) + $qty;

        $totals[$year][$name] = $qty;
        $totals['total'][$name] = $qty;

        $allCities[$city] = true;
    }

    ksort($allCities);
    foreach (array_keys($allCities) as $city) {
        $row = [];
        $row[] = (string)(count($tableBody) + 1);
        $row[] = $city;

        foreach ($years as $year) {
            $count = array_sum($cities[$city][$year] ?? []);
            $row[] = $count ? (string)$count : '';
        }

        $count = array_sum($cities[$city]['total'] ?? []);
        $row[] = $count ? (string)$count : '';

        $tableBody[] = $row;
    }

    $row = ['', 'Всего'];
    foreach ($years as $year) {
        $count = array_sum($totals[$year] ?? []);
        $row[] = $count ? (string)$count : '';
    }
    $count = array_sum($totals['total'] ?? []);
    $row[] = $count ? (string)$count : '';
    $tableBody[] = $row;

    $text = format_markdown_table($tableHead, $tableAlign, $tableBody);

    return $text;
}

function get_unique_visitors(array $table): int
{
    $count = 0;
    $history = [];

    foreach ($table as $row) {
        if (!isset($history[$row['name']])) {
            $count += (int)$row['qty'];
            $history[$row['name']] = true;
        }
    }

    return $count;
}

function get_year_guests(int $year, array $data): array
{
    return array_filter($data, function (array $item) use ($year) {
        $itemYear = (int)substr($item['came'], 0, 4);
        return $itemYear == $year;
    });
}

function get_year_range(array $data): array
{
    $years = [];

    foreach ($data as $row) {
        $year = (int)substr($row['came'], 0, 4);
        $years[$year] = true;
    }

    ksort($years);

    return array_keys($years);
}

function load_file(string $filename): array
{
    if (!file_exists($filename)) {
        fail("File %s not found.\n", $filename);
    }

    $head = null;
    $body = [];

    $lines = file($filename);
    foreach ($lines as $line) {
        if (trim($line) === '') {
            continue;
        }

        $cells = explode(";", trim($line));

        if (null === $head) {
            $head = $cells;
        } else {
            $row = [];

            foreach ($head as $cell) {
                $row[$cell] = '';
            }

            foreach ($cells as $idx => $cell) {
                $row[$head[$idx]] = $cell;
            }
            $body[] = $row;
        }
    }

    usort($body, function (array $a, array $b): int {
        return strcmp($a['came'], $b['came']);
    });

    return $body;
}

function write_reports(array $data): void
{
    $text = "# Статистика посещения лагеря\n";
    $text .= "\n";
    $text .= "Здесь собрана вся информация о посещаемости нашего [[Волонтёрский лагерь|волонтёрского лагеря]].\n";
    $text .= "\n";
    $text .= "<div id=\"toc\"></div>\n";
    $text .= "\n";

    $text .= "## Источники\n";
    $text .= "\n";
    $text .= get_total_report($data);
    $text .= "\n";

    $years = get_year_range($data);
    foreach ($years as $year) {
        $guests = get_year_guests($year, $data);
        $report = format_year_report($year, $guests);

        $text .= "\n";
        $text .= sprintf("## %u год\n", $year);
        $text .= "\n";
        $text .= $report;
    }

    $report = get_total_report($data);
    file_put_contents('report.md', $text);
}

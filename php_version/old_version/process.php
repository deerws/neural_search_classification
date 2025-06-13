<?php
function filterData($data, $filters) {
    return array_filter($data, function($row) use ($filters) {
        $match = true;
        if ($filters['programa'] && $row['Programa'] !== $filters['programa']) {
            $match = false;
        }
        if ($filters['status']) {
            $approvals = explode(';', $row['Approved Classification']);
            $statusFound = false;
            foreach ($approvals as $approval) {
                if (strpos($approval, $filters['status']) !== false) {
                    $statusFound = true;
                    break;
                }
            }
            if (!$statusFound) $match = false;
        }
        if ($filters['user'] && strpos($row['User'], $filters['user']) === false) {
            $match = false;
        }
        return $match;
    });
}

function shortenText($text) {
    if (preg_match('/^(.*?)\s*\(/', $text, $match)) {
        return $match[1];
    }
    return $text;
}

function detectConflict($linha, $data) {
    $statuses = [];
    foreach ($data as $row) {
        if ($row['Linha de Pesquisa'] === $linha) {
            $approvals = explode(';', $row['Approved Classification']);
            foreach ($approvals as $approval) {
                if (preg_match('/(Approved|Rejected)/', $approval, $match)) {
                    $statuses[] = $match[0];
                }
            }
        }
    }
    return count(array_unique($statuses)) > 1;
}
?>
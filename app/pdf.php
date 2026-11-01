<?php

function pdf_latin(string $text): string
{
    $converted = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
    $text = $converted === false ? $text : $converted;
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

function pdf_bytes(string $titre, array $lignes): string
{
    $ops = "BT /F1 16 Tf 48 800 Td (" . pdf_latin($titre) . ") Tj\n/F1 11 Tf\n";
    foreach ($lignes as $ligne) {
        $ops .= "0 -16 Td (" . pdf_latin($ligne) . ") Tj\n";
    }
    $ops .= "ET";
    $objects = [];
    $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
    $objects[] = "<< /Type /Pages /Count 1 /Kids [3 0 R] >>";
    $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>";
    $objects[] = "<< /Length " . strlen($ops) . " >>\nstream\n" . $ops . "\nendstream";
    $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $i => $object) {
        $offsets[] = strlen($pdf);
        $pdf .= ($i + 1) . " 0 obj\n" . $object . "\nendobj\n";
    }
    $xref = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer << /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
    return $pdf;
}

function ecrire_pdf(string $nom, string $titre, array $lignes): string
{
    file_put_contents(upload_dir() . '/' . $nom, pdf_bytes($titre, $lignes));
    return $nom;
}

function envoyer_pdf(string $nom, string $titre, array $lignes): void
{
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $nom . '"');
    echo pdf_bytes($titre, $lignes);
    exit;
}

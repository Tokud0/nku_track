<?php

namespace app\helpers;

/**
 * Извлечение текста из файлов для базы знаний чат-бота (Word, PDF, блокнот, Excel).
 */
class ChatbotFileExtractor
{
    /**
     * @param string $path путь к сохранённому файлу
     * @param string $ext расширение (нижний регистр): txt, docx, xlsx
     * @return string извлечённый текст в UTF-8
     */
    public static function extract($path, $ext)
    {
        $ext = strtolower($ext);
        $content = '';

        switch ($ext) {
            case 'txt':
                $content = self::extractTxt($path);
                break;
            case 'docx':
                $content = self::extractDocx($path);
                break;
            case 'xlsx':
                $content = self::extractExcel($path);
                break;
            default:
                return '';
        }

        return $content !== '' ? mb_convert_encoding($content, 'UTF-8', mb_detect_encoding($content, ['UTF-8', 'Windows-1251', 'ISO-8859-1'], true) ?: 'UTF-8') : '';
    }

    private static function extractTxt($path)
    {
        $raw = @file_get_contents($path);
        return $raw !== false ? $raw : '';
    }

    private static function extractPdf($path)
    {
        if (!class_exists(\Smalot\PdfParser\Parser::class)) {
            return '[Для извлечения текста из PDF установите: composer require smalot/pdfparser]';
        }
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($path);
            return $pdf->getText() ?: '';
        } catch (\Exception $e) {
            return '';
        }
    }

    private static function extractDocx($path)
    {
        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::RDONLY) !== true) {
            return '';
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml === false || $xml === '') {
            return '';
        }
        $xml = preg_replace('/<w:p\b[^>]*>/', "\n", $xml);
        $text = strip_tags($xml);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    private static function extractDoc($path)
    {
        return '[Формат .doc не поддерживается. Используйте .docx.]';
    }

    private static function extractExcel($path)
    {
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            return '[Для извлечения текста из Excel установите: composer require phpoffice/phpspreadsheet]';
        }
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $texts = [];
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    foreach ($row->getCellIterator() as $cell) {
                        $v = $cell->getValue();
                        if ($v !== null && trim((string)$v) !== '') {
                            $texts[] = trim((string)$v);
                        }
                    }
                }
            }
            return implode("\n", $texts);
        } catch (\Exception $e) {
            return '';
        }
    }
}

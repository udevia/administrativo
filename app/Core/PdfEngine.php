<?php
declare(strict_types=1);

namespace App\Core;

class PdfEngine {
    /**
     * Emite el documento en formato HTML preparado para renderizado e impresión directa / PDF del navegador
     */
    public static function streamPdf(string $htmlContent, string $filename = 'documento.pdf', bool $autoPrint = false): void {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
            header('X-Document-Name: ' . $filename);
        }

        // Si se solicita auto-impresión o el documento no contiene script de impresión
        if ($autoPrint && !str_contains($htmlContent, 'window.print()')) {
            $printScript = "<script>window.addEventListener('DOMContentLoaded', () => { window.print(); });</script>";
            if (str_contains($htmlContent, '</body>')) {
                $htmlContent = str_replace('</body>', $printScript . '</body>', $htmlContent);
            } else {
                $htmlContent .= $printScript;
            }
        }

        echo $htmlContent;
        exit;
    }

    /**
     * Genera un contenedor HTML imprimible con estilos estándar mi ERP
     */
    public static function wrapDocument(string $title, string $bodyContent, string $paperSize = 'letter'): string {
        $styles = ($paperSize === 'ticket80') 
            ? 'body { width: 80mm; font-family: monospace; font-size: 11px; margin: 0; padding: 4mm; }'
            : 'body { font-family: "Helvetica Neue", Arial, sans-serif; font-size: 12px; margin: 20mm; color: #1e293b; }';

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{$title}</title>
    <style>
        {$styles}
        .page-break { page-break-after: always; }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    {$bodyContent}
</body>
</html>
HTML;
    }
}

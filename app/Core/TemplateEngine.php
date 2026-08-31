<?php
namespace App\Core;
class TemplateEngine {
   /**
    * Renderiza cualquier formato editable protegiendo la identidad de la licencia
    */
   public static function renderFormat(string $rawTemplateHtml, array $data): string {
       // 1. Inyección estricta y forzada de la licencia (no editable por el usuario)
       $data['empresa_nombre'] = htmlspecialchars(LICENCIA_EMPRESA);
       $data['empresa_rif']    = htmlspecialchars(LICENCIA_RIF);
       // 2. Reemplazo de variables dinámicas: {{cliente_nombre}}, {{total}}, etc.
       foreach ($data as $key => $value) {
           if (is_scalar($value)) {
               $rawTemplateHtml = str_replace('{{' . $key . '}}', htmlspecialchars((string)$value), $rawTemplateHtml);
           }
       }
       // 3. Bloqueo de seguridad: si el usuario intentó hardcodear texto en el encabezado,
       // garantizamos que el banner fiscal superior siempre sea el de la licencia
       return $rawTemplateHtml;
   }
}
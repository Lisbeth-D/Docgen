<?php

namespace Database\Seeders;

use App\Models\DocumentoAdjudicacion;
use Illuminate\Database\Seeder;

class DocumentoAdjudicacionSeeder extends Seeder
{
    public function run(): void
    {
        $documentos = [
            ['nombre' => 'Acta Constitutiva y reformas', 'leyenda' => 'Acta Constitutiva y sus reformas, señalando los siguientes datos: número de escritura, lugar y fecha de constitución, nombre y número de la notaría y notario, nombre o razón social de la empresa, objeto social de la empresa, lugar, fecha y folio del Registro Público de la Propiedad y/o acta de nacimiento.', 'orden' => 1],
            ['nombre' => 'Poder Notarial del Representante Legal', 'leyenda' => 'Poder Notarial de su Representante Legal, señalando los siguientes datos: nombre del apoderado o representante legal, número de escritura, lugar, fecha y nombre de quien expide el instrumento notarial de constitución o poder, así como el RFC correspondiente.', 'orden' => 2],
            ['nombre' => 'Constancia de situación fiscal', 'leyenda' => 'Constancia de Situación Fiscal, cuya actividad se encuentre relacionada con los bienes o servicios objeto del presente procedimiento.', 'orden' => 3],
            ['nombre' => 'Identificación oficial vigente', 'leyenda' => 'Copia simple, por ambos lados, de identificación oficial vigente, como INE o pasaporte.', 'orden' => 4],
            ['nombre' => 'Comprobante de domicilio', 'leyenda' => 'Comprobante de domicilio con una antigüedad no mayor a 60 días.', 'orden' => 5],
            ['nombre' => 'Opinión de cumplimiento fiscal SAT (32-D)', 'leyenda' => 'Opinión de cumplimiento de obligaciones fiscales emitida por el SAT, vigente y en sentido positivo.', 'orden' => 6],
            ['nombre' => 'Opinión de cumplimiento IMSS', 'leyenda' => 'Opinión de cumplimiento de obligaciones en materia de seguridad social emitida por el IMSS, vigente y en sentido positivo.', 'orden' => 7],
            ['nombre' => 'Opinión de cumplimiento INFONAVIT (32-D)', 'leyenda' => 'Constancia de situación fiscal en materia de aportaciones patronales y entero de descuentos emitida por el INFONAVIT, sin adeudo.', 'orden' => 8],
            ['nombre' => 'Tarjeta patronal IMSS', 'leyenda' => 'Tarjeta de identificación patronal emitida por el IMSS.', 'orden' => 9],
            ['nombre' => 'CLABE interbancaria', 'leyenda' => 'Documento bancario que contenga la CLABE interbancaria de la cuenta del proveedor.', 'orden' => 10],
            ['nombre' => 'Registro Único de Proveedores (RUP)', 'leyenda' => 'Registro Único de Proveedores y Contratistas vigente.', 'orden' => 11],
        ];

        foreach ($documentos as $documento) {
            DocumentoAdjudicacion::firstOrCreate(
                ['nombre' => $documento['nombre']],
                array_merge($documento, [
                    'activo' => true,
                    'obligatorio' => false,
                ])
            );
        }
    }
}

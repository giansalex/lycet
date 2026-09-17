<?php

namespace App\OpenApi\Schemas;

/**
 * Esquemas de respuestas y utilidades (QR, configuración de archivos).
 *
 * @OA\Schema(
 *     schema="Error",
 *     @OA\Property(property="code", type="string"),
 *     @OA\Property(property="message", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="CdrResponse",
 *     @OA\Property(property="accepted", type="boolean", readOnly=true),
 *     @OA\Property(property="id", type="string"),
 *     @OA\Property(property="code", type="string"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="notes", type="array", @OA\Items(type="string"))
 * )
 *
 * @OA\Schema(
 *     schema="BillResult",
 *     @OA\Property(property="success", type="boolean"),
 *     @OA\Property(property="error", ref="#/components/schemas/Error"),
 *     @OA\Property(property="cdrZip", type="string", format="byte"),
 *     @OA\Property(property="cdrResponse", ref="#/components/schemas/CdrResponse")
 * )
 *
 * @OA\Schema(
 *     schema="SummaryResult",
 *     @OA\Property(property="success", type="boolean"),
 *     @OA\Property(property="error", ref="#/components/schemas/Error"),
 *     @OA\Property(property="ticket", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="StatusResult",
 *     @OA\Property(property="cdrZip", type="string", format="byte"),
 *     @OA\Property(property="cdrResponse", ref="#/components/schemas/CdrResponse"),
 *     @OA\Property(property="success", type="boolean"),
 *     @OA\Property(property="error", ref="#/components/schemas/Error"),
 *     @OA\Property(property="code", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="DocumentResponse",
 *     @OA\Property(property="xml", type="string"),
 *     @OA\Property(property="hash", type="string", description="Resumen de la Firma Digital"),
 *     @OA\Property(property="sunatResponse", ref="#/components/schemas/BillResult")
 * )
 *
 * @OA\Schema(
 *     schema="SummaryResponse",
 *     @OA\Property(property="xml", type="string"),
 *     @OA\Property(property="hash", type="string", description="Resumen de la Firma Digital"),
 *     @OA\Property(property="sunatResponse", ref="#/components/schemas/SummaryResult")
 * )
 *
 * @OA\Schema(
 *     schema="ValidationResponse",
 *     @OA\Property(property="message", type="string"),
 *     @OA\Property(property="field", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="FilesData",
 *     @OA\Property(property="certificate", type="string", description="certificado PEM en formato base64"),
 *     @OA\Property(property="logo", type="string", description="logo png en formato base64")
 * )
 *
 * @OA\Schema(
 *     schema="QrParameter",
 *     @OA\Property(property="ruc", type="string", description="ruc del emisor"),
 *     @OA\Property(property="tipo", type="string", description="tipo de comprobante"),
 *     @OA\Property(property="serie", type="string"),
 *     @OA\Property(property="numero", type="string"),
 *     @OA\Property(property="emision", type="string", format="date", description="fecha de emisión"),
 *     @OA\Property(property="igv", type="number", format="float"),
 *     @OA\Property(property="total", type="number", format="float", description="total importe de venta"),
 *     @OA\Property(property="clienteTipo", type="string", description="Tipo de documento del cliente"),
 *     @OA\Property(property="clienteNumero", type="string", description="Numero de documento del cliente")
 * )
 */
final class ResponseSchemas
{
}

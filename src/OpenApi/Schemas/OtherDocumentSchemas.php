<?php

namespace App\OpenApi\Schemas;

/**
 * Esquemas de resumen, baja, reversión, percepción, retención y guía de remisión.
 *
 * @OA\Schema(
 *     schema="SummaryPerception",
 *     @OA\Property(property="codReg", type="string"),
 *     @OA\Property(property="tasa", type="number", format="float"),
 *     @OA\Property(property="mtoBase", type="number", format="float"),
 *     @OA\Property(property="mto", type="number", format="float"),
 *     @OA\Property(property="mtoTotal", type="number", format="float")
 * )
 *
 * @OA\Schema(
 *     schema="SummaryDetail",
 *     @OA\Property(property="tipoDoc", type="string"),
 *     @OA\Property(property="serieNro", type="string"),
 *     @OA\Property(property="clienteTipo", type="string"),
 *     @OA\Property(property="clienteNro", type="string"),
 *     @OA\Property(property="docReferencia", ref="#/components/schemas/Document"),
 *     @OA\Property(property="percepcion", ref="#/components/schemas/SummaryPerception"),
 *     @OA\Property(property="estado", type="string"),
 *     @OA\Property(property="total", type="number", format="float"),
 *     @OA\Property(property="porcentajeIgv", type="number", format="float"),
 *     @OA\Property(property="mtoOperGravadas", type="number", format="float"),
 *     @OA\Property(property="mtoOperInafectas", type="number", format="float"),
 *     @OA\Property(property="mtoOperExoneradas", type="number", format="float"),
 *     @OA\Property(property="mtoOperExportacion", type="number", format="float"),
 *     @OA\Property(property="mtoOperGratuitas", type="number", format="float"),
 *     @OA\Property(property="mtoOtrosCargos", type="number", format="float"),
 *     @OA\Property(property="mtoIGV", type="number", format="float"),
 *     @OA\Property(property="mtoIvap", type="number", format="float"),
 *     @OA\Property(property="mtoIcbper", type="number", format="float"),
 *     @OA\Property(property="mtoISC", type="number", format="float"),
 *     @OA\Property(property="mtoOtrosTributos", type="number", format="float")
 * )
 *
 * @OA\Schema(
 *     schema="Summary",
 *     @OA\Property(property="correlativo", type="string"),
 *     @OA\Property(property="fecGeneracion", type="string", format="date-time"),
 *     @OA\Property(property="fecResumen", type="string", format="date-time"),
 *     @OA\Property(property="moneda", type="string"),
 *     @OA\Property(property="company", ref="#/components/schemas/Company"),
 *     @OA\Property(property="details", type="array", @OA\Items(ref="#/components/schemas/SummaryDetail")),
 *     @OA\Property(property="xmlId", type="string"),
 *     @OA\Property(property="name", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="VoidedDetail",
 *     @OA\Property(property="tipoDoc", type="string"),
 *     @OA\Property(property="serie", type="string"),
 *     @OA\Property(property="correlativo", type="string"),
 *     @OA\Property(property="desMotivoBaja", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="Voided",
 *     @OA\Property(property="correlativo", type="string"),
 *     @OA\Property(property="fecGeneracion", type="string", format="date-time"),
 *     @OA\Property(property="fecComunicacion", type="string", format="date-time"),
 *     @OA\Property(property="company", ref="#/components/schemas/Company"),
 *     @OA\Property(property="details", type="array", @OA\Items(ref="#/components/schemas/VoidedDetail")),
 *     @OA\Property(property="xmlId", type="string"),
 *     @OA\Property(property="name", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="Reversion",
 *     @OA\Property(property="correlativo", type="string"),
 *     @OA\Property(property="fecGeneracion", type="string", format="date-time"),
 *     @OA\Property(property="fecComunicacion", type="string", format="date-time"),
 *     @OA\Property(property="company", ref="#/components/schemas/Company"),
 *     @OA\Property(property="details", type="array", @OA\Items(ref="#/components/schemas/VoidedDetail")),
 *     @OA\Property(property="xmlId", type="string"),
 *     @OA\Property(property="name", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="Payment",
 *     @OA\Property(property="moneda", type="string"),
 *     @OA\Property(property="importe", type="number", format="float"),
 *     @OA\Property(property="fecha", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Exchange",
 *     @OA\Property(property="monedaRef", type="string"),
 *     @OA\Property(property="monedaObj", type="string"),
 *     @OA\Property(property="factor", type="number", format="float"),
 *     @OA\Property(property="fecha", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="PerceptionDetail",
 *     @OA\Property(property="tipoDoc", type="string"),
 *     @OA\Property(property="numDoc", type="string"),
 *     @OA\Property(property="fechaEmision", type="string", format="date-time"),
 *     @OA\Property(property="impTotal", type="number", format="float"),
 *     @OA\Property(property="moneda", type="string"),
 *     @OA\Property(property="cobros", type="array", @OA\Items(ref="#/components/schemas/Payment")),
 *     @OA\Property(property="fechaPercepcion", type="string", format="date-time"),
 *     @OA\Property(property="impPercibido", type="number", format="float"),
 *     @OA\Property(property="impCobrar", type="number", format="float"),
 *     @OA\Property(property="tipoCambio", ref="#/components/schemas/Exchange")
 * )
 *
 * @OA\Schema(
 *     schema="Perception",
 *     @OA\Property(property="serie", type="string"),
 *     @OA\Property(property="correlativo", type="string"),
 *     @OA\Property(property="fechaEmision", type="string", format="date-time"),
 *     @OA\Property(property="company", ref="#/components/schemas/Company"),
 *     @OA\Property(property="proveedor", ref="#/components/schemas/Client"),
 *     @OA\Property(property="regimen", type="string"),
 *     @OA\Property(property="tasa", type="number", format="float"),
 *     @OA\Property(property="impPercibido", type="number", format="float"),
 *     @OA\Property(property="impCobrado", type="number", format="float"),
 *     @OA\Property(property="observacion", type="string"),
 *     @OA\Property(property="details", type="array", @OA\Items(ref="#/components/schemas/PerceptionDetail")),
 *     @OA\Property(property="name", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="RetentionDetail",
 *     @OA\Property(property="tipoDoc", type="string"),
 *     @OA\Property(property="numDoc", type="string"),
 *     @OA\Property(property="fechaEmision", type="string", format="date-time"),
 *     @OA\Property(property="impTotal", type="number", format="float"),
 *     @OA\Property(property="moneda", type="string"),
 *     @OA\Property(property="pagos", type="array", @OA\Items(ref="#/components/schemas/Payment")),
 *     @OA\Property(property="fechaRetencion", type="string", format="date-time"),
 *     @OA\Property(property="impRetenido", type="number", format="float"),
 *     @OA\Property(property="impPagar", type="number", format="float"),
 *     @OA\Property(property="tipoCambio", ref="#/components/schemas/Exchange")
 * )
 *
 * @OA\Schema(
 *     schema="Retention",
 *     @OA\Property(property="serie", type="string"),
 *     @OA\Property(property="correlativo", type="string"),
 *     @OA\Property(property="fechaEmision", type="string", format="date-time"),
 *     @OA\Property(property="proveedor", ref="#/components/schemas/Client"),
 *     @OA\Property(property="company", ref="#/components/schemas/Company"),
 *     @OA\Property(property="regimen", type="string"),
 *     @OA\Property(property="tasa", type="number", format="float"),
 *     @OA\Property(property="impRetenido", type="number", format="float"),
 *     @OA\Property(property="impPagado", type="number", format="float"),
 *     @OA\Property(property="observacion", type="string"),
 *     @OA\Property(property="details", type="array", @OA\Items(ref="#/components/schemas/RetentionDetail")),
 *     @OA\Property(property="name", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="Puerto",
 *     @OA\Property(property="codigo", type="string"),
 *     @OA\Property(property="nombre", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="Transportist",
 *     @OA\Property(property="tipoDoc", type="string"),
 *     @OA\Property(property="numDoc", type="string"),
 *     @OA\Property(property="rznSocial", type="string"),
 *     @OA\Property(property="nroMtc", type="string"),
 *     @OA\Property(property="placa", type="string"),
 *     @OA\Property(property="choferTipoDoc", type="string"),
 *     @OA\Property(property="choferDoc", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="Vehicle",
 *     @OA\Property(property="placa", type="string"),
 *     @OA\Property(property="nroCirculacion", type="string"),
 *     @OA\Property(property="codEmisor", type="string"),
 *     @OA\Property(property="nroAutorizacion", type="string"),
 *     @OA\Property(property="secundarios", type="array", @OA\Items(ref="#/components/schemas/Vehicle"))
 * )
 *
 * @OA\Schema(
 *     schema="Driver",
 *     @OA\Property(property="tipo", type="string"),
 *     @OA\Property(property="tipoDoc", type="string"),
 *     @OA\Property(property="nroDoc", type="string"),
 *     @OA\Property(property="nombres", type="string"),
 *     @OA\Property(property="apellidos", type="string"),
 *     @OA\Property(property="licencia", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="Shipment",
 *     @OA\Property(property="codTraslado", type="string"),
 *     @OA\Property(property="desTraslado", type="string"),
 *     @OA\Property(property="sustentoPeso", type="string"),
 *     @OA\Property(property="indTransbordo", type="boolean"),
 *     @OA\Property(property="indicadores", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="pesoItems", type="number", format="float"),
 *     @OA\Property(property="pesoTotal", type="number", format="float"),
 *     @OA\Property(property="undPesoTotal", type="string"),
 *     @OA\Property(property="numBultos", type="integer"),
 *     @OA\Property(property="modTraslado", type="string"),
 *     @OA\Property(property="fecTraslado", type="string", format="date-time"),
 *     @OA\Property(
 *         property="fecEntregaBienes",
 *         type="string",
 *         format="date-time",
 *         description="Fecha de entrega de bienes al transportista. Requerido cuando el modo de traslado es transporte público (modTraslado = '02'). Corresponde al nodo UBL cac:ShipmentStage/cac:TransitPeriod/cbc:StartDate."
 *     ),
 *     @OA\Property(property="numContenedor", type="string"),
 *     @OA\Property(property="contenedores", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="codPuerto", type="string"),
 *     @OA\Property(property="puerto", ref="#/components/schemas/Puerto"),
 *     @OA\Property(property="aeropuerto", ref="#/components/schemas/Puerto"),
 *     @OA\Property(property="transportista", ref="#/components/schemas/Transportist"),
 *     @OA\Property(property="vehiculo", ref="#/components/schemas/Vehicle"),
 *     @OA\Property(property="choferes", type="array", @OA\Items(ref="#/components/schemas/Driver")),
 *     @OA\Property(property="llegada", ref="#/components/schemas/Direction"),
 *     @OA\Property(property="partida", ref="#/components/schemas/Direction")
 * )
 *
 * @OA\Schema(
 *     schema="AdditionalDoc",
 *     @OA\Property(property="tipoDesc", type="string"),
 *     @OA\Property(property="tipo", type="string"),
 *     @OA\Property(property="nro", type="string"),
 *     @OA\Property(property="emisor", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="DespatchDetail",
 *     @OA\Property(property="codigo", type="string"),
 *     @OA\Property(property="descripcion", type="string"),
 *     @OA\Property(property="unidad", type="string"),
 *     @OA\Property(property="cantidad", type="number", format="float"),
 *     @OA\Property(property="codProdSunat", type="string"),
 *     @OA\Property(property="atributos", type="array", @OA\Items(ref="#/components/schemas/DetailAttribute"))
 * )
 *
 * @OA\Schema(
 *     schema="Despatch",
 *     @OA\Property(property="version", type="string"),
 *     @OA\Property(property="tipoDoc", type="string"),
 *     @OA\Property(property="serie", type="string"),
 *     @OA\Property(property="correlativo", type="string"),
 *     @OA\Property(property="observacion", type="string"),
 *     @OA\Property(property="fechaEmision", type="string", format="date-time"),
 *     @OA\Property(property="company", ref="#/components/schemas/Company"),
 *     @OA\Property(property="destinatario", ref="#/components/schemas/Client"),
 *     @OA\Property(property="tercero", ref="#/components/schemas/Client"),
 *     @OA\Property(property="comprador", ref="#/components/schemas/Client"),
 *     @OA\Property(property="envio", ref="#/components/schemas/Shipment"),
 *     @OA\Property(property="docBaja", ref="#/components/schemas/Document"),
 *     @OA\Property(property="relDoc", ref="#/components/schemas/Document"),
 *     @OA\Property(property="addDocs", type="array", @OA\Items(ref="#/components/schemas/AdditionalDoc")),
 *     @OA\Property(property="details", type="array", @OA\Items(ref="#/components/schemas/DespatchDetail")),
 *     @OA\Property(property="name", type="string")
 * )
 */
final class OtherDocumentSchemas
{
}
